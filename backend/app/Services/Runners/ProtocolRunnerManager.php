<?php

namespace App\Services\Runners;

use App\Contracts\ProtocolRunner;
use InvalidArgumentException;

class ProtocolRunnerManager
{
    /**
     * @param iterable<ProtocolRunner> $runners
     */
    public function __construct(private iterable $runners)
    {
    }

    public function for(string $protocol): ProtocolRunner
    {
        foreach ($this->runners as $runner) {
            if ($runner->protocol() === $protocol) {
                return $runner;
            }
        }

        throw new InvalidArgumentException("Unsupported protocol runner [{$protocol}].");
    }
}
