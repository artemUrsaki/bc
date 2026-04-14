<?php

namespace App\Contracts;

use App\Models\Run;

interface ProtocolRunner
{
    public function protocol(): string;

    public function run(Run $run): void;
}
