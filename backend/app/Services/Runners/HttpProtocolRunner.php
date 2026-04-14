<?php

namespace App\Services\Runners;

use App\Contracts\ProtocolRunner;
use App\Models\Run;
use App\Services\Runners\Concerns\BuildsBenchmarkPayload;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
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
        $url = $this->resolveUrl($config);
        $method = strtoupper((string) $config['method']);
        $messageCount = max((int) $config['message_count'], 1);
        $timeoutSeconds = max(((int) $config['timeout_ms']) / 1000, 1);
        $delayMs = max((int) $config['delay_ms'], 0);

        for ($sequenceNo = 1; $sequenceNo <= $messageCount; $sequenceNo++) {
            $body = [
                'run_id' => $run->id,
                'sequence_no' => $sequenceNo,
                'payload' => $this->benchmarkPayload((int) $config['payload_bytes'], $sequenceNo),
            ];

            $sentAt = now();
            $startedAt = hrtime(true);

            try {
                $response = Http::acceptJson()
                    ->timeout($timeoutSeconds)
                    ->send($method, $url, $this->requestOptions($method, $body));

                $receivedAt = now();
                $latencyMs = round((hrtime(true) - $startedAt) / 1_000_000, 3);

                $run->samples()->create([
                    'sequence_no' => $sequenceNo,
                    'sent_at' => $sentAt,
                    'received_at' => $receivedAt,
                    'latency_ms' => $latencyMs,
                    'success' => $response->successful(),
                    'status_code' => $response->status(),
                    'error_code' => $response->successful() ? null : 'http_status_error',
                    'error_message' => $response->successful() ? null : $response->reason(),
                    'metadata' => [
                        'url' => $url,
                        'method' => $method,
                        'response_size_bytes' => strlen((string) $response->body()),
                    ],
                ]);
            } catch (ConnectionException $exception) {
                $this->recordFailureSample($run, $sequenceNo, $sentAt, 'connection_error', $exception->getMessage(), $method, $url);
            } catch (Throwable $exception) {
                $this->recordFailureSample($run, $sequenceNo, $sentAt, 'request_error', $exception->getMessage(), $method, $url);
            }

            if ($delayMs > 0) {
                usleep($delayMs * 1000);
            }
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
        string $url
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
            ],
        ]);
    }
}
