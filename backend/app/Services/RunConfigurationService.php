<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class RunConfigurationService
{
    /**
     * @param array<string,mixed> $experimentDefaults
     * @param array<string,mixed> $requestConfig
     * @return array{config: array<string,mixed>, environment_snapshot: array<string,mixed>}
     */
    public function resolve(string $protocol, ?string $scenario, array $experimentDefaults, array $requestConfig, Request $request): array
    {
        $config = array_replace_recursive(
            $this->defaultConfig($protocol),
            $this->scenarioTemplate($protocol, $scenario),
            $experimentDefaults,
            $requestConfig,
        );

        $this->validateAllowedKeys($protocol, array_keys($config));
        $this->validateResolvedConfig($protocol, $config);

        if ($scenario !== null) {
            $config['scenario'] = $scenario;
        }

        return [
            'config' => $config,
            'environment_snapshot' => $this->environmentSnapshot($protocol, $scenario, $request),
        ];
    }

    /**
     * @return array<int,string>
     */
    public function availableScenarios(string $protocol): array
    {
        return array_keys(config("protocol_benchmark.scenarios.{$protocol}", []));
    }

    /**
     * @param array<string,mixed> $defaultConfig
     */
    public function validateExperimentDefaults(string $protocol, array $defaultConfig): void
    {
        $config = array_replace_recursive(
            $this->defaultConfig($protocol),
            $defaultConfig,
        );

        $this->validateAllowedKeys($protocol, array_keys($config));
        $this->validateResolvedConfig($protocol, $config);
    }

    /**
     * @return array<string,mixed>
     */
    private function defaultConfig(string $protocol): array
    {
        return config("protocol_benchmark.{$protocol}", []);
    }

    /**
     * @return array<string,mixed>
     */
    private function scenarioTemplate(string $protocol, ?string $scenario): array
    {
        if ($scenario === null) {
            return [];
        }

        $template = config("protocol_benchmark.scenarios.{$protocol}.{$scenario}");

        if (! is_array($template)) {
            throw ValidationException::withMessages([
                'scenario' => ["Scenario [{$scenario}] is not available for protocol [{$protocol}]."],
            ]);
        }

        return $template;
    }

    /**
     * @param array<int,string> $keys
     */
    private function validateAllowedKeys(string $protocol, array $keys): void
    {
        $allowed = $this->allowedKeys($protocol);
        $unknown = array_values(array_diff($keys, $allowed));

        if ($unknown === []) {
            return;
        }

        throw ValidationException::withMessages([
            'config' => [
                'Unsupported config keys for protocol ['.$protocol.']: '.implode(', ', $unknown).'.',
            ],
        ]);
    }

    /**
     * @param array<string,mixed> $config
     */
    private function validateResolvedConfig(string $protocol, array $config): void
    {
        $validator = Validator::make($config, $this->rules($protocol));

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }

    /**
     * @return array<int,string>
     */
    private function allowedKeys(string $protocol): array
    {
        return match ($protocol) {
            'http' => [
                'base_url',
                'probe_path',
                'url',
                'method',
                'timeout_ms',
                'message_count',
                'payload_bytes',
                'delay_ms',
                'scenario',
            ],
            'mqtt' => [
                'host',
                'port',
                'topic_prefix',
                'timeout_ms',
                'keep_alive',
                'message_count',
                'payload_bytes',
                'qos',
                'delay_ms',
                'scenario',
            ],
            default => [],
        };
    }

    /**
     * @return array<string,mixed>
     */
    private function rules(string $protocol): array
    {
        return match ($protocol) {
            'http' => [
                'base_url' => ['required_without:url', 'nullable', 'url'],
                'probe_path' => ['nullable', 'string', 'max:255'],
                'url' => ['nullable', 'url'],
                'method' => ['required', 'string', Rule::in(['GET', 'POST'])],
                'timeout_ms' => ['required', 'integer', 'min:100', 'max:60000'],
                'message_count' => ['required', 'integer', 'min:1', 'max:10000'],
                'payload_bytes' => ['required', 'integer', 'min:1', 'max:1048576'],
                'delay_ms' => ['required', 'integer', 'min:0', 'max:60000'],
                'scenario' => ['nullable', 'string'],
            ],
            'mqtt' => [
                'host' => ['required', 'string', 'max:255'],
                'port' => ['required', 'integer', 'min:1', 'max:65535'],
                'topic_prefix' => ['required', 'string', 'max:255'],
                'timeout_ms' => ['required', 'integer', 'min:100', 'max:60000'],
                'keep_alive' => ['required', 'integer', 'min:5', 'max:3600'],
                'message_count' => ['required', 'integer', 'min:1', 'max:10000'],
                'payload_bytes' => ['required', 'integer', 'min:1', 'max:1048576'],
                'qos' => ['required', 'integer', Rule::in([0, 1])],
                'delay_ms' => ['required', 'integer', 'min:0', 'max:60000'],
                'scenario' => ['nullable', 'string'],
            ],
            default => [],
        };
    }

    /**
     * @return array<string,mixed>
     */
    private function environmentSnapshot(string $protocol, ?string $scenario, Request $request): array
    {
        return [
            'protocol' => $protocol,
            'scenario' => $scenario,
            'app_env' => config('app.env'),
            'app_url' => config('app.url'),
            'laravel_version' => app()->version(),
            'php_version' => PHP_VERSION,
            'os_family' => PHP_OS_FAMILY,
            'queue_connection' => config('queue.default'),
            'database_connection' => config('database.default'),
            'request_ip' => $request->ip(),
            'captured_at' => now()->toIso8601String(),
        ];
    }
}
