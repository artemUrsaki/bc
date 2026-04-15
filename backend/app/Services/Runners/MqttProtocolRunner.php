<?php

namespace App\Services\Runners;

use App\Contracts\ProtocolRunner;
use App\Models\Run;
use App\Services\Mqtt\MqttSocketClient;
use App\Services\RunEventService;
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
        $eventService = app(RunEventService::class);
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

        $eventService->record(
            $run,
            'mqtt.runner.prepared',
            'MQTT reliability scenario prepared.',
            context: [
                'host' => (string) $config['host'],
                'port' => (int) $config['port'],
                'topic' => $topic,
                'qos' => $qos,
                'message_count' => $messageCount,
            ]
        );

        if (in_array(1, $config['simulate_connection_failure_on_sequences'] ?? [], true)) {
            throw new RuntimeException('Injected MQTT connection failure.');
        }

        $client->connect($clientId, (int) $config['keep_alive']);
        $eventService->record(
            $run,
            'mqtt.connection.established',
            'MQTT connection established.',
            context: ['topic' => $topic]
        );
        $client->subscribe($topic, $qos);
        $eventService->record(
            $run,
            'mqtt.subscription.created',
            'MQTT subscription created.',
            context: ['topic' => $topic, 'qos' => $qos]
        );

        try {
            for ($sequenceNo = 1; $sequenceNo <= $messageCount; $sequenceNo++) {
                $payload = json_encode([
                    'run_id' => $run->id,
                    'sequence_no' => $sequenceNo,
                    'payload' => $this->benchmarkPayload((int) $config['payload_bytes'], $sequenceNo),
                ], JSON_THROW_ON_ERROR);

                $sentAt = now();
                $startedAt = hrtime(true);

                $eventService->record(
                    $run,
                    'mqtt.message.attempted',
                    "MQTT message {$sequenceNo} attempted.",
                    context: [
                        'sequence_no' => $sequenceNo,
                        'topic' => $topic,
                        'qos' => $qos,
                    ]
                );

                try {
                    if (in_array($sequenceNo, $config['simulate_connection_failure_on_sequences'] ?? [], true)) {
                        throw new RuntimeException('Injected MQTT connection failure.');
                    }

                    if (in_array($sequenceNo, $config['simulate_timeout_on_sequences'] ?? [], true)) {
                        throw new RuntimeException('Injected MQTT timeout.');
                    }

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

                    $eventService->record(
                        $run,
                        'mqtt.message.received',
                        "MQTT message {$sequenceNo} received.",
                        context: [
                            'sequence_no' => $sequenceNo,
                            'latency_ms' => $latencyMs,
                            'dup' => (bool) $message['dup'],
                        ]
                    );
                } catch (Throwable $exception) {
                    $errorCode = match ($exception->getMessage()) {
                        'Injected MQTT timeout.' => 'timeout',
                        'Injected MQTT connection failure.' => 'connection_error',
                        default => 'mqtt_error',
                    };

                    $run->samples()->create([
                        'sequence_no' => $sequenceNo,
                        'sent_at' => $sentAt,
                        'received_at' => now(),
                        'latency_ms' => null,
                        'success' => false,
                        'status_code' => null,
                        'error_code' => $errorCode,
                        'error_message' => $exception->getMessage(),
                        'metadata' => [
                            'topic' => $topic,
                            'qos' => $qos,
                        ],
                    ]);

                    $eventService->record(
                        $run,
                        $errorCode === 'timeout' ? 'mqtt.message.timeout' : 'mqtt.message.failed',
                        "MQTT message {$sequenceNo} failed.",
                        'error',
                        [
                            'sequence_no' => $sequenceNo,
                            'error_code' => $errorCode,
                            'message' => $exception->getMessage(),
                        ]
                    );
                }

                if ($delayMs > 0) {
                    usleep($delayMs * 1000);
                }
            }
        } finally {
            $client->disconnect();
            $eventService->record(
                $run,
                'mqtt.connection.closed',
                'MQTT connection closed.',
                context: ['topic' => $topic]
            );
        }
    }
}
