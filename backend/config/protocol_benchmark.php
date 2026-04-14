<?php

$appUrl = rtrim((string) env('APP_URL', 'http://localhost:8000'), '/');

return [
    'http' => [
        'base_url' => rtrim((string) env('BENCHMARK_HTTP_BASE_URL', $appUrl), '/'),
        'probe_path' => (string) env('BENCHMARK_HTTP_PROBE_PATH', '/api/v1/probe/http-echo'),
        'method' => strtoupper((string) env('BENCHMARK_HTTP_METHOD', 'POST')),
        'timeout_ms' => (int) env('BENCHMARK_HTTP_TIMEOUT_MS', 5000),
        'message_count' => (int) env('BENCHMARK_HTTP_MESSAGE_COUNT', 10),
        'payload_bytes' => (int) env('BENCHMARK_HTTP_PAYLOAD_BYTES', 256),
        'delay_ms' => (int) env('BENCHMARK_HTTP_DELAY_MS', 0),
    ],
    'mqtt' => [
        'host' => (string) env('BENCHMARK_MQTT_HOST', '127.0.0.1'),
        'port' => (int) env('BENCHMARK_MQTT_PORT', 1883),
        'topic_prefix' => trim((string) env('BENCHMARK_MQTT_TOPIC_PREFIX', 'protocol-benchmark'), '/'),
        'timeout_ms' => (int) env('BENCHMARK_MQTT_TIMEOUT_MS', 5000),
        'keep_alive' => (int) env('BENCHMARK_MQTT_KEEP_ALIVE', 30),
        'message_count' => (int) env('BENCHMARK_MQTT_MESSAGE_COUNT', 10),
        'payload_bytes' => (int) env('BENCHMARK_MQTT_PAYLOAD_BYTES', 256),
        'qos' => (int) env('BENCHMARK_MQTT_QOS', 0),
        'delay_ms' => (int) env('BENCHMARK_MQTT_DELAY_MS', 0),
    ],
    'scenarios' => [
        'http' => [
            'baseline_latency' => [
                'method' => 'POST',
                'message_count' => 10,
                'payload_bytes' => 256,
                'timeout_ms' => 5000,
                'delay_ms' => 0,
            ],
            'large_payload' => [
                'method' => 'POST',
                'message_count' => 10,
                'payload_bytes' => 4096,
                'timeout_ms' => 8000,
                'delay_ms' => 0,
            ],
            'slow_polling' => [
                'method' => 'GET',
                'message_count' => 20,
                'payload_bytes' => 256,
                'timeout_ms' => 5000,
                'delay_ms' => 1000,
            ],
        ],
        'mqtt' => [
            'baseline_latency' => [
                'message_count' => 10,
                'payload_bytes' => 256,
                'timeout_ms' => 5000,
                'delay_ms' => 0,
                'qos' => 0,
            ],
            'reliable_delivery' => [
                'message_count' => 10,
                'payload_bytes' => 256,
                'timeout_ms' => 5000,
                'delay_ms' => 0,
                'qos' => 1,
            ],
            'large_payload' => [
                'message_count' => 10,
                'payload_bytes' => 4096,
                'timeout_ms' => 8000,
                'delay_ms' => 0,
                'qos' => 1,
            ],
        ],
    ],
];
