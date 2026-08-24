<?php

require_once __DIR__ . '/../vendor/autoload.php';

use TypePHP\NativeCore\Application\ApplicationContext;
use TypePHP\NativeCore\Application\NativeApplication;
use TypePHP\NativeCore\Host\Windows\WindowsDesktopHost;
use TypePHP\NativeCore\Host\Windows\WindowsDesktopProgram;

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

echo "PASS composer-autoload\n";
