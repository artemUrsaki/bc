<?php

namespace App\Services\Runners;

use App\Contracts\ProtocolRunner;
use App\Models\Run;
use App\Services\RunEventService;
use App\Services\Runners\Concerns\BuildsBenchmarkPayload;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

class HttpProtocolRunner implements ProtocolRunner
{
    use BuildsBenchmarkPayload;

    public function protocol(): string
    {
        return 'http';
    }

    public function run(Run $run): void
    {
        $config = array_replace(config('protocol_benchmark.http'), $run->config ?? []);
        $eventService = app(RunEventService::class);
        $url = $this->resolveUrl($config);
        $method = strtoupper((string) $config['method']);
        $messageCount = max((int) $config['message_count'], 1);
        $timeoutSeconds = max(((int) $config['timeout_ms']) / 1000, 1);
        $delayMs = max((int) $config['delay_ms'], 0);
        $retryAttempts = max((int) ($config['retry_attempts'] ?? 0), 0);

        $eventService->record(
            $run,
            'http.runner.prepared',
            'HTTP reliability scenario prepared.',
            context: [
                'url' => $url,
                'method' => $method,
                'message_count' => $messageCount,
                'retry_attempts' => $retryAttempts,
            ]
        );

        for ($sequenceNo = 1; $sequenceNo <= $messageCount; $sequenceNo++) {
            $body = [
                'run_id' => $run->id,
                'sequence_no' => $sequenceNo,
                'payload' => $this->benchmarkPayload((int) $config['payload_bytes'], $sequenceNo),
            ];

            $sentAt = now();
            $startedAt = hrtime(true);
            $attempt = 0;

            while ($attempt <= $retryAttempts) {
                $attempt++;

                $eventService->record(
                    $run,
                    'http.request.attempted',
                    "HTTP request attempt {$attempt} for sequence {$sequenceNo}.",
                    context: [
                        'sequence_no' => $sequenceNo,
                        'attempt' => $attempt,
                    ]
                );

                try {
                    $this->assertNoInjectedFailure($config, $sequenceNo);

                    $response = Http::acceptJson()
                        ->timeout($timeoutSeconds)
                        ->send($method, $url, $this->requestOptions($method, $body));

                    $receivedAt = now();
                    $latencyMs = round((hrtime(true) - $startedAt) / 1_000_000, 3);
                    $successful = $response->successful();

                    $run->samples()->create([
                        'sequence_no' => $sequenceNo,
                        'sent_at' => $sentAt,
                        'received_at' => $receivedAt,
                        'latency_ms' => $latencyMs,
                        'success' => $successful,
                        'status_code' => $response->status(),
                        'error_code' => $successful ? null : 'http_status_error',
                        'error_message' => $successful ? null : $response->reason(),
                        'metadata' => [
                            'url' => $url,
                            'method' => $method,
                            'response_size_bytes' => strlen((string) $response->body()),
                            'attempt' => $attempt,
                        ],
                    ]);

                    $eventService->record(
                        $run,
                        $successful ? 'http.request.succeeded' : 'http.request.failed',
                        $successful
                            ? "HTTP request {$sequenceNo} succeeded."
                            : "HTTP request {$sequenceNo} returned a failing status.",
                        $successful ? 'info' : 'warning',
                        [
                            'sequence_no' => $sequenceNo,
                            'attempt' => $attempt,
                            'status_code' => $response->status(),
                            'latency_ms' => $latencyMs,
                        ]
                    );

                    if (! $successful && $attempt <= $retryAttempts) {
                        $eventService->record(
                            $run,
                            'http.request.retrying',
                            "Retrying HTTP request {$sequenceNo}.",
                            'warning',
                            [
                                'sequence_no' => $sequenceNo,
                                'next_attempt' => $attempt + 1,
                            ]
                        );

                        $this->applyRetryDelay($config);
                        continue;
                    }

                    break;
                } catch (ConnectionException $exception) {
                    $errorCode = 'connection_error';

                    if ($attempt > $retryAttempts) {
                        $this->recordFailureSample($run, $sequenceNo, $sentAt, $errorCode, $exception->getMessage(), $method, $url, $attempt);
                    }

                    $eventService->record(
                        $run,
                        'http.request.connection_failed',
                        "HTTP request {$sequenceNo} connection failed.",
                        'error',
                        [
                            'sequence_no' => $sequenceNo,
                            'attempt' => $attempt,
                            'message' => $exception->getMessage(),
                        ]
                    );

                    if ($attempt <= $retryAttempts) {
                        $eventService->record(
                            $run,
                            'http.request.retrying',
                            "Retrying HTTP request {$sequenceNo} after connection failure.",
                            'warning',
                            [
                                'sequence_no' => $sequenceNo,
                                'next_attempt' => $attempt + 1,
                            ]
                        );
                        $this->applyRetryDelay($config);
                        continue;
                    }

                    break;
                } catch (RuntimeException $exception) {
                    $errorCode = $exception->getMessage() === 'Injected timeout fault.' ? 'timeout' : 'request_error';

                    if ($attempt > $retryAttempts) {
                        $this->recordFailureSample($run, $sequenceNo, $sentAt, $errorCode, $exception->getMessage(), $method, $url, $attempt);
                    }

                    $eventService->record(
                        $run,
                        $errorCode === 'timeout' ? 'http.request.timeout' : 'http.request.exception',
                        "HTTP request {$sequenceNo} failed.",
                        'error',
                        [
                            'sequence_no' => $sequenceNo,
                            'attempt' => $attempt,
                            'message' => $exception->getMessage(),
                        ]
                    );

                    if ($attempt <= $retryAttempts) {
                        $eventService->record(
                            $run,
                            'http.request.retrying',
                            "Retrying HTTP request {$sequenceNo} after failure.",
                            'warning',
                            [
                                'sequence_no' => $sequenceNo,
                                'next_attempt' => $attempt + 1,
                            ]
                        );
                        $this->applyRetryDelay($config);
                        continue;
                    }

                    break;
                } catch (Throwable $exception) {
                    if ($attempt > $retryAttempts) {
                        $this->recordFailureSample($run, $sequenceNo, $sentAt, 'request_error', $exception->getMessage(), $method, $url, $attempt);
                    }

                    $eventService->record(
                        $run,
                        'http.request.exception',
                        "HTTP request {$sequenceNo} failed with an exception.",
                        'error',
                        [
                            'sequence_no' => $sequenceNo,
                            'attempt' => $attempt,
                            'message' => $exception->getMessage(),
                        ]
                    );

                    if ($attempt <= $retryAttempts) {
                        $this->applyRetryDelay($config);
                        continue;
                    }

                    break;
                }
            }

            if ($delayMs > 0) {
                usleep($delayMs * 1000);
            }
        }
    }

    /**
     * @param array<string,mixed> $config
     */
    private function assertNoInjectedFailure(array $config, int $sequenceNo): void
    {
        if (in_array($sequenceNo, $config['simulate_connection_failure_on_sequences'] ?? [], true)) {
            throw new ConnectionException('Injected connection failure.');
        }

        if (in_array($sequenceNo, $config['simulate_timeout_on_sequences'] ?? [], true)) {
            throw new RuntimeException('Injected timeout fault.');
        }
    }

    /**
     * @param array<string,mixed> $config
     */
    private function applyRetryDelay(array $config): void
    {
        $retryDelayMs = max((int) ($config['retry_delay_ms'] ?? 0), 0);

        if ($retryDelayMs > 0) {
            usleep($retryDelayMs * 1000);
        }
    }

    /**
     * @param array<string,mixed> $config
     */
    private function resolveUrl(array $config): string
    {
        if (! empty($config['url'])) {
            return (string) $config['url'];
        }

        $baseUrl = rtrim((string) $config['base_url'], '/');
        $probePath = '/'.ltrim((string) $config['probe_path'], '/');

        return $baseUrl.$probePath;
    }

    /**
     * @param array<string,mixed> $body
     * @return array<string,mixed>
     */
    private function requestOptions(string $method, array $body): array
    {
        return match ($method) {
            'GET' => ['query' => $body],
            default => ['json' => $body],
        };
    }

    private function recordFailureSample(
        Run $run,
        int $sequenceNo,
        mixed $sentAt,
        string $errorCode,
        string $message,
        string $method,
        string $url,
        int $attempt = 1
    ): void {
        $run->samples()->create([
            'sequence_no' => $sequenceNo,
            'sent_at' => $sentAt,
            'received_at' => now(),
            'latency_ms' => null,
            'success' => false,
            'status_code' => null,
            'error_code' => $errorCode,
            'error_message' => $message,
            'metadata' => [
                'url' => $url,
                'method' => $method,
                'attempt' => $attempt,
            ],
        ]);
    }
}
