<?php

namespace TypePHP\NativeCore\Host\Console;

use TypePHP\NativeCore\Application\Application;
use TypePHP\NativeCore\Application\Host;

final class ConsoleHost implements Host
{
    private ConsoleProgram $program;
    private bool $stopRequested;

    public function __construct(ConsoleProgram $program)
    {
        $this->program = $program;
        $this->stopRequested = false;
    }

    public function run(Application $application): int
    {
        if ($this->stopRequested) {
            $application->requestStop();
            return 0;
        }
        return $this->program->execute($application->context());
    }

    public function requestStop(): void
    {
        $this->stopRequested = true;
    }
}
