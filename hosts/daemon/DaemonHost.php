<?php

namespace TypePHP\NativeCore\Host\Daemon;

use TypePHP\NativeCore\Application\Application;
use TypePHP\NativeCore\Application\Host;
use TypePHP\NativeCore\Signals\SignalSource;
use TypePHP\NativeCore\Time\Sleeper;

final class DaemonHost implements Host
{
    private DaemonWorker $worker;
    private SignalSource $signals;
    private Sleeper $sleeper;
    private int $intervalMs;
    private bool $stopRequested;

    public function __construct(DaemonWorker $worker, SignalSource $signals, Sleeper $sleeper, int $intervalMs)
    {
        $this->worker = $worker;
        $this->signals = $signals;
        $this->sleeper = $sleeper;
        $this->intervalMs = $intervalMs;
        $this->stopRequested = false;
    }

    public function run(Application $application): int
    {
        $this->signals->install();
        while (!$this->stopRequested && !$this->signals->stopRequested()
            && !$application->context()->cancellation()->isCancellationRequested()) {
            $this->worker->tick($application->context());
            $this->sleeper->sleepMilliseconds($this->intervalMs);
        }
        $application->requestStop();
        return 0;
    }

    public function requestStop(): void
    {
        $this->stopRequested = true;
    }
}
