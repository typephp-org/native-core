<?php

namespace TypePHP\NativeCore\Host\Windows;

use TypePHP\NativeCore\Application\Application;
use TypePHP\NativeCore\Application\Host;

final class WindowsDesktopHost implements Host
{
    private WindowsDesktopProgram $program;
    private bool $running;
    private bool $stopRequested;
    private bool $stopForwarded;

    public function __construct(WindowsDesktopProgram $program)
    {
        $this->program = $program;
        $this->running = false;
        $this->stopRequested = false;
        $this->stopForwarded = false;
    }

    public function run(Application $application): int
    {
        if ($this->stopRequested) {
            $application->requestStop();
            return 0;
        }

        $this->running = true;
        try {
            return $this->program->run($application->context());
        } finally {
            // A native close is also cooperative application cancellation.
            // Keeping running=true here lets Application forward one final
            // stop request so the native adapter can wake or close safely.
            $application->requestStop();
            $this->running = false;
        }
    }

    public function requestStop(): void
    {
        $this->stopRequested = true;
        if (!$this->running || $this->stopForwarded) {
            return;
        }

        $this->stopForwarded = true;
        $this->program->requestStop();
    }
}
