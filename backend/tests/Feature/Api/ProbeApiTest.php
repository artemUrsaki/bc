<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('echoes benchmark probe payload metadata', function (): void {
    $response = $this->postJson('/api/v1/probe/http-echo', [
        'run_id' => 11,
        'sequence_no' => 4,
        'payload' => 'test-payload',
    ]);

    $response
        ->assertOk()
        ->assertJsonPath('run_id', 11)
        ->assertJsonPath('sequence_no', 4)
        ->assertJsonPath('payload_size_bytes', 12);
});
