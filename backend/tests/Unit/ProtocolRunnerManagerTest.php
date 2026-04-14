<?php

use App\Contracts\ProtocolRunner;
use App\Models\Run;
use App\Services\Runners\ProtocolRunnerManager;

it('returns the runner for a supported protocol', function (): void {
    $httpRunner = new class implements ProtocolRunner
    {
        public function protocol(): string
        {
            return 'http';
        }

        public function run(Run $run): void
        {
        }
    };

    $mqttRunner = new class implements ProtocolRunner
    {
        public function protocol(): string
        {
            return 'mqtt';
        }

        public function run(Run $run): void
        {
        }
    };

    $manager = new ProtocolRunnerManager([$httpRunner, $mqttRunner]);

    expect($manager->for('mqtt'))->toBe($mqttRunner);
});

it('throws for an unsupported protocol', function (): void {
    $httpRunner = new class implements ProtocolRunner
    {
        public function protocol(): string
        {
            return 'http';
        }

        public function run(Run $run): void
        {
        }
    };

    $manager = new ProtocolRunnerManager([$httpRunner]);

    $manager->for('coap');
})->throws(InvalidArgumentException::class, 'Unsupported protocol runner [coap].');
