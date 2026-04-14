<?php

namespace App\Services\Mqtt;

use RuntimeException;

class MqttSocketClient
{
    /**
     * @var resource|null
     */
    private $socket = null;

    /**
     * @var array<int,array<string,mixed>>
     */
    private array $pendingPublishes = [];

    /**
     * @var array<int,true>
     */
    private array $pendingPubAcks = [];

    /**
     * @var array<int,true>
     */
    private array $pendingSubAcks = [];

    private int $packetIdentifier = 1;

    public function __construct(
        private readonly string $host,
        private readonly int $port,
        private readonly int $timeoutMs,
    ) {
    }

    public function connect(string $clientId, int $keepAlive = 30, bool $cleanSession = true): void
    {
        $this->socket = @stream_socket_client(
            "tcp://{$this->host}:{$this->port}",
            $errorNumber,
            $errorMessage,
            max($this->timeoutMs / 1000, 1)
        );

        if (! is_resource($this->socket)) {
            throw new RuntimeException("Failed to connect to MQTT broker: {$errorMessage}", $errorNumber);
        }

        $this->applyStreamTimeout($this->timeoutMs);

        $flags = $cleanSession ? 0x02 : 0x00;
        $payload = $this->encodeString($clientId);
        $variableHeader = $this->encodeString('MQTT').chr(0x04).chr($flags).pack('n', $keepAlive);

        $this->writePacket(0x10, $variableHeader.$payload);

        $packet = $this->readPacket($this->timeoutMs);

        if ($packet['type'] !== 2 || strlen($packet['body']) < 2) {
            throw new RuntimeException('Invalid CONNACK packet from MQTT broker.');
        }

        $returnCode = ord($packet['body'][1]);

        if ($returnCode !== 0) {
            throw new RuntimeException("MQTT broker rejected connection with code [{$returnCode}].");
        }
    }

    public function subscribe(string $topic, int $qos = 0): void
    {
        $packetId = $this->nextPacketIdentifier();
        $payload = $this->encodeString($topic).chr($qos);

        $this->writePacket(0x82, pack('n', $packetId).$payload);
        $this->waitForSubAck($packetId, $this->timeoutMs);
    }

    public function publish(string $topic, string $payload, int $qos = 0, bool $retain = false): void
    {
        $packetId = null;
        $header = 0x30 | ($retain ? 0x01 : 0x00);
        $variableHeader = $this->encodeString($topic);

        if ($qos === 1) {
            $packetId = $this->nextPacketIdentifier();
            $header |= 0x02;
            $variableHeader .= pack('n', $packetId);
        }

        $this->writePacket($header, $variableHeader.$payload);

        if ($packetId !== null) {
            $this->waitForPubAck($packetId, $this->timeoutMs);
        }
    }

    /**
     * @return array<string,mixed>
     */
    public function waitForMessage(string $topic, int $timeoutMs, ?string $expectedPayload = null): array
    {
        $deadline = microtime(true) + ($timeoutMs / 1000);

        while (microtime(true) < $deadline) {
            foreach ($this->pendingPublishes as $index => $publish) {
                if ($publish['topic'] !== $topic) {
                    continue;
                }

                if ($expectedPayload !== null && $publish['payload'] !== $expectedPayload) {
                    continue;
                }

                unset($this->pendingPublishes[$index]);

                return $publish;
            }

            $remainingMs = max((int) (($deadline - microtime(true)) * 1000), 1);
            $this->dispatchIncomingPacket($remainingMs);
        }

        throw new RuntimeException("Timed out waiting for MQTT message on topic [{$topic}].");
    }

    public function disconnect(): void
    {
        if (! is_resource($this->socket)) {
            return;
        }

        $this->writePacket(0xE0, '');
        fclose($this->socket);
        $this->socket = null;
    }

    private function waitForPubAck(int $packetId, int $timeoutMs): void
    {
        $deadline = microtime(true) + ($timeoutMs / 1000);

        while (microtime(true) < $deadline) {
            if (isset($this->pendingPubAcks[$packetId])) {
                unset($this->pendingPubAcks[$packetId]);

                return;
            }

            $remainingMs = max((int) (($deadline - microtime(true)) * 1000), 1);
            $this->dispatchIncomingPacket($remainingMs);
        }

        throw new RuntimeException("Timed out waiting for PUBACK [{$packetId}].");
    }

    private function waitForSubAck(int $packetId, int $timeoutMs): void
    {
        $deadline = microtime(true) + ($timeoutMs / 1000);

        while (microtime(true) < $deadline) {
            if (isset($this->pendingSubAcks[$packetId])) {
                unset($this->pendingSubAcks[$packetId]);

                return;
            }

            $remainingMs = max((int) (($deadline - microtime(true)) * 1000), 1);
            $this->dispatchIncomingPacket($remainingMs);
        }

        throw new RuntimeException("Timed out waiting for SUBACK [{$packetId}].");
    }

    private function dispatchIncomingPacket(int $timeoutMs): void
    {
        $packet = $this->readPacket($timeoutMs);

        switch ($packet['type']) {
            case 3:
                $publish = $this->decodePublish($packet['flags'], $packet['body']);
                $this->pendingPublishes[] = $publish;

                if ($publish['qos'] === 1 && $publish['packet_id'] !== null) {
                    $this->writePacket(0x40, pack('n', $publish['packet_id']));
                }

                break;
            case 4:
                $packetId = $this->decodePacketIdentifier($packet['body']);
                $this->pendingPubAcks[$packetId] = true;
                break;
            case 9:
                $packetId = $this->decodePacketIdentifier($packet['body']);
                $this->pendingSubAcks[$packetId] = true;
                break;
            default:
                break;
        }
    }

    /**
     * @return array{type:int,flags:int,body:string}
     */
    private function readPacket(int $timeoutMs): array
    {
        $this->assertConnected();
        $this->applyStreamTimeout($timeoutMs);

        $firstByte = $this->readExact(1);
        $type = ord($firstByte) >> 4;
        $flags = ord($firstByte) & 0x0F;
        $remainingLength = $this->decodeRemainingLength();
        $body = $remainingLength > 0 ? $this->readExact($remainingLength) : '';

        return [
            'type' => $type,
            'flags' => $flags,
            'body' => $body,
        ];
    }

    /**
     * @return array<string,mixed>
     */
    private function decodePublish(int $flags, string $body): array
    {
        $offset = 0;
        $topic = $this->decodeString($body, $offset);
        $qos = ($flags >> 1) & 0x03;
        $packetId = null;

        if ($qos > 0) {
            $packetId = unpack('npacket_id', substr($body, $offset, 2))['packet_id'];
            $offset += 2;
        }

        return [
            'topic' => $topic,
            'payload' => substr($body, $offset),
            'qos' => $qos,
            'dup' => (bool) ($flags & 0x08),
            'retain' => (bool) ($flags & 0x01),
            'packet_id' => $packetId,
        ];
    }

    private function decodePacketIdentifier(string $body): int
    {
        if (strlen($body) < 2) {
            throw new RuntimeException('MQTT ack packet is missing packet identifier.');
        }

        return unpack('npacket_id', substr($body, 0, 2))['packet_id'];
    }

    private function nextPacketIdentifier(): int
    {
        $current = $this->packetIdentifier;
        $this->packetIdentifier = $this->packetIdentifier >= 65535 ? 1 : $this->packetIdentifier + 1;

        return $current;
    }

    private function writePacket(int $header, string $body): void
    {
        $this->assertConnected();
        $packet = chr($header).$this->encodeRemainingLength(strlen($body)).$body;
        $written = fwrite($this->socket, $packet);

        if ($written === false || $written !== strlen($packet)) {
            throw new RuntimeException('Failed to write MQTT packet to socket.');
        }
    }

    private function readExact(int $length): string
    {
        $buffer = '';

        while (strlen($buffer) < $length) {
            $chunk = fread($this->socket, $length - strlen($buffer));

            if ($chunk === false || $chunk === '') {
                $meta = stream_get_meta_data($this->socket);

                if (($meta['timed_out'] ?? false) === true) {
                    throw new RuntimeException('MQTT socket read timed out.');
                }

                throw new RuntimeException('MQTT socket closed unexpectedly.');
            }

            $buffer .= $chunk;
        }

        return $buffer;
    }

    private function decodeRemainingLength(): int
    {
        $multiplier = 1;
        $value = 0;

        do {
            $encodedByte = ord($this->readExact(1));
            $value += ($encodedByte & 127) * $multiplier;
            $multiplier *= 128;
        } while (($encodedByte & 128) !== 0);

        return $value;
    }

    private function encodeRemainingLength(int $length): string
    {
        $encoded = '';

        do {
            $digit = $length % 128;
            $length = intdiv($length, 128);

            if ($length > 0) {
                $digit |= 0x80;
            }

            $encoded .= chr($digit);
        } while ($length > 0);

        return $encoded;
    }

    private function encodeString(string $value): string
    {
        return pack('n', strlen($value)).$value;
    }

    private function decodeString(string $value, int &$offset): string
    {
        $length = unpack('nlength', substr($value, $offset, 2))['length'];
        $offset += 2;
        $decoded = substr($value, $offset, $length);
        $offset += $length;

        return $decoded;
    }

    private function applyStreamTimeout(int $timeoutMs): void
    {
        $this->assertConnected();
        $seconds = intdiv($timeoutMs, 1000);
        $microseconds = ($timeoutMs % 1000) * 1000;
        stream_set_timeout($this->socket, $seconds, $microseconds);
    }

    private function assertConnected(): void
    {
        if (! is_resource($this->socket)) {
            throw new RuntimeException('MQTT socket is not connected.');
        }
    }
}
