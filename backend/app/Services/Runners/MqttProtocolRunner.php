<?php

namespace App\Services\Runners;

use App\Contracts\ProtocolRunner;
use App\Models\Run;
use App\Services\Mqtt\MqttSocketClient;
use App\Services\Runners\Concerns\BuildsBenchmarkPayload;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class MqttProtocolRunner implements ProtocolRunner
{
    use BuildsBenchmarkPayload;

    public function protocol(): string
    {
        return 'mqtt';
    }

    public function run(Run $run): void
    {
        $config = array_replace(config('protocol_benchmark.mqtt'), $run->config ?? []);
        $qos = (int) $config['qos'];

        if (! in_array($qos, [0, 1], true)) {
            throw new RuntimeException("MQTT runner supports QoS 0 and 1. Received [{$qos}].");
        }

        $client = new MqttSocketClient(
            (string) $config['host'],
            (int) $config['port'],
            (int) $config['timeout_ms'],
        );

        $topic = trim((string) $config['topic_prefix'], '/')."/run/{$run->id}";
        $clientId = Str::limit("benchmark-run-{$run->id}-".Str::random(10), 23, '');
        $messageCount = max((int) $config['message_count'], 1);
        $delayMs = max((int) $config['delay_ms'], 0);

        $client->connect($clientId, (int) $config['keep_alive']);
        $client->subscribe($topic, $qos);

        try {
            for ($sequenceNo = 1; $sequenceNo <= $messageCount; $sequenceNo++) {
                $payload = json_encode([
                    'run_id' => $run->id,
                    'sequence_no' => $sequenceNo,
                    'payload' => $this->benchmarkPayload((int) $config['payload_bytes'], $sequenceNo),
                ], JSON_THROW_ON_ERROR);

                $sentAt = now();
                $startedAt = hrtime(true);

                try {
                    $client->publish($topic, $payload, $qos);
                    $message = $client->waitForMessage($topic, (int) $config['timeout_ms'], $payload);
                    $receivedAt = now();
                    $latencyMs = round((hrtime(true) - $startedAt) / 1_000_000, 3);
                    $decodedPayload = json_decode((string) $message['payload'], true);

                    $run->samples()->create([
                        'sequence_no' => $sequenceNo,
                        'sent_at' => $sentAt,
                        'received_at' => $receivedAt,
                        'latency_ms' => $latencyMs,
                        'success' => true,
                        'status_code' => null,
                        'error_code' => null,
                        'error_message' => null,
                        'metadata' => [
                            'topic' => $topic,
                            'qos' => $message['qos'],
                            'dup' => $message['dup'],
                            'retain' => $message['retain'],
                            'payload_sequence_no' => $decodedPayload['sequence_no'] ?? null,
                        ],
                    ]);
                } catch (Throwable $exception) {
                    $run->samples()->create([
                        'sequence_no' => $sequenceNo,
                        'sent_at' => $sentAt,
                        'received_at' => now(),
                        'latency_ms' => null,
                        'success' => false,
                        'status_code' => null,
                        'error_code' => 'mqtt_error',
                        'error_message' => $exception->getMessage(),
                        'metadata' => [
                            'topic' => $topic,
                            'qos' => $qos,
                        ],
                    ]);
                }

                if ($delayMs > 0) {
                    usleep($delayMs * 1000);
                }
            }
        } finally {
            $client->disconnect();
        }
    }
}
