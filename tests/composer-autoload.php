<?php

require_once __DIR__ . '/../vendor/autoload.php';

use TypePHP\NativeCore\Application\ApplicationContext;
use TypePHP\NativeCore\Application\NativeApplication;
use TypePHP\NativeCore\Host\Windows\WindowsDesktopHost;
use TypePHP\NativeCore\Host\Windows\WindowsDesktopProgram;
use TypePHP\NativeCore\Host\Worker\Worker;
use TypePHP\NativeCore\Host\Worker\WorkerHost;
use TypePHP\NativeCore\Host\Worker\WorkResult;

final class ComposerAutoloadWorker implements Worker
{
    public function handle(ApplicationContext $context): WorkResult
    {
        return WorkResult::stop();
    }
}

final class ComposerAutoloadDesktopProgram implements WindowsDesktopProgram
{
    private int $runs;
    private int $stopRequests;

    public function __construct()
    {
        $this->runs = 0;
        $this->stopRequests = 0;
    }

    public function run(ApplicationContext $context): int
    {
        $this->runs++;
        return 0;
    }

    public function requestStop(): void
    {
        $this->stopRequests++;
    }

    public function passed(): bool
    {
        return $this->runs === 1 && $this->stopRequests === 1;
    }
}

$program = new ComposerAutoloadDesktopProgram();
$exitCode = NativeApplication::configure()->build()->run(new WindowsDesktopHost($program));
if ($exitCode !== 0 || !$program->passed()) {
    throw new RuntimeException('Composer autoload smoke failed');
}

$workerHost = WorkerHost::defaults(new ComposerAutoloadWorker());
$workerExitCode = NativeApplication::configure()->build()->run($workerHost);
if ($workerExitCode !== 0 || $workerHost->health()->state() !== 'stopped') {
    throw new RuntimeException('Composer Worker autoload smoke failed');
}

echo "PASS composer-autoload\n";
