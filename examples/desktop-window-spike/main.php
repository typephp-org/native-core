<?php

use TypePHP\NativeCore\Application\ApplicationContext;
use TypePHP\NativeCore\Application\NativeApplication;
use TypePHP\NativeCore\Host\Windows\WindowsDesktopHost;
use TypePHP\NativeCore\Host\Windows\WindowsDesktopProgram;
use TypePHP\NativeCore\Logging\JsonLineLogger;
use TypePHP\NativeCore\Time\SystemSleeper;

final class DesktopSpikeProgram implements WindowsDesktopProgram
{
    private int $window;
    private int $smokeFrames;
    private bool $stopRequested = false;

    public function __construct(int $smokeFrames)
    {
        $this->window = 0;
        $this->smokeFrames = $smokeFrames;
        $this->stopRequested = false;
    }

    public function run(ApplicationContext $context): int
    {
        $this->window = win_create_window('TypePHP Native Core - Desktop Spike', 640, 360);
        if ($this->window === 0) {
            return 2;
        }
        win_show_window($this->window);

        $frames = 0;
        $sleeper = new SystemSleeper();
        while (!$this->stopRequested && win_pump_events()) {
            $frames++;
            if ($this->smokeFrames > 0 && $frames >= $this->smokeFrames) {
                win_close_window($this->window);
            }
            $sleeper->sleepMilliseconds(16);
        }

        $context->logger()->log('info', 'desktop host stopped', ['frames' => $frames]);
        return 0;
    }

    public function requestStop(): void
    {
        $this->stopRequested = true;
        if ($this->window !== 0) {
            win_close_window($this->window);
        }
    }
}

function main(): void
{
    global $argv;
    $smokeFrames = 0;
    if (count($argv) > 1 && $argv[1] === '--smoke') {
        $smokeFrames = 3;
    }
    $application = NativeApplication::configure()->withLogger(new JsonLineLogger())->build();
    $application->run(new WindowsDesktopHost(new DesktopSpikeProgram($smokeFrames)));
}
