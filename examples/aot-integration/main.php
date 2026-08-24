<?php

use TypePHP\NativeCore\Application\Application;
use TypePHP\NativeCore\Application\ApplicationContext;
use TypePHP\NativeCore\Application\Host;
use TypePHP\NativeCore\Application\NativeApplication;
use TypePHP\NativeCore\Error\CircularDependencyException;
use TypePHP\NativeCore\Error\DuplicateServiceException;
use TypePHP\NativeCore\Error\MissingServiceException;
use TypePHP\NativeCore\Host\Windows\WindowsDesktopHost;
use TypePHP\NativeCore\Host\Windows\WindowsDesktopProgram;
use TypePHP\NativeCore\Module\Module;
use TypePHP\NativeCore\Module\ModuleApi;
use TypePHP\NativeCore\Services\ServiceFactory;
use TypePHP\NativeCore\Services\ServiceRegistry;

final class AotRecorder
{
    /** @var array<int, string> */
    public array $events = [];
}

final class AotModule implements Module
{
    private AotRecorder $recorder;

    public function __construct(AotRecorder $recorder) { $this->recorder = $recorder; }
    public function apiVersion(): int { return ModuleApi::VERSION; }
    public function register(ServiceRegistry $services): void { $this->recorder->events[] = 'register'; }
    public function start(ApplicationContext $context): void { $this->recorder->events[] = 'start'; }
    public function stop(ApplicationContext $context): void { $this->recorder->events[] = 'stop'; }
}

final class AotHost implements Host
{
    private AotRecorder $recorder;
    public int $stopRequests = 0;
    public function __construct(AotRecorder $recorder) { $this->recorder = $recorder; }
    public function run(Application $application): int
    {
        $this->recorder->events[] = 'run';
        $application->requestStop();
        return 0;
    }
    public function requestStop(): void { $this->stopRequests++; }
}

final class AotService
{
}

final class AotFactory implements ServiceFactory
{
    public function create(ServiceRegistry $services): object { return new AotService(); }
}

final class AotFactoryA implements ServiceFactory
{
    public function create(ServiceRegistry $services): object { return $services->get('b'); }
}

final class AotFactoryB implements ServiceFactory
{
    public function create(ServiceRegistry $services): object { return $services->get('a'); }
}

final class AotWindowsDesktopProgram implements WindowsDesktopProgram
{
    public int $runs = 0;
    public int $stopRequests = 0;

    public function run(ApplicationContext $context): int
    {
        $this->runs++;
        return 6;
    }

    public function requestStop(): void
    {
        $this->stopRequests++;
    }
}

function checkAot(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function main(): void
{
    $recorder = new AotRecorder();
    $application = NativeApplication::configure()->withModule(new AotModule($recorder))->build();
    $host = new AotHost($recorder);
    checkAot($application->run($host) === 0, 'host exit code');
    checkAot($host->stopRequests === 1, 'host stop forwarding');
    checkAot(implode(',', $recorder->events) === 'register,start,run,stop', 'lifecycle order');
    $monotonicBefore = $application->context()->monotonicClock()->elapsedMilliseconds();
    $monotonicAfter = $application->context()->monotonicClock()->elapsedMilliseconds();
    checkAot($monotonicAfter >= $monotonicBefore, 'monotonic clock');

    $desktopProgram = new AotWindowsDesktopProgram();
    $desktopApplication = NativeApplication::configure()->build();
    checkAot(
        $desktopApplication->run(new WindowsDesktopHost($desktopProgram)) === 6,
        'windows desktop exit code'
    );
    checkAot($desktopProgram->runs === 1, 'windows desktop program run');
    checkAot($desktopProgram->stopRequests === 1, 'windows desktop stop forwarding');
    checkAot(
        $desktopApplication->context()->cancellation()->isCancellationRequested(),
        'windows desktop cancellation'
    );

    $services = new ServiceRegistry();
    $services->register('service', new AotFactory());
    checkAot($services->get('service') === $services->get('service'), 'service singleton');

    $duplicate = false;
    try {
        $services->register('service', new AotFactory());
    } catch (DuplicateServiceException $expected) {
        $duplicate = true;
    }
    checkAot($duplicate, 'duplicate error');

    $missing = false;
    try {
        $services->get('missing');
    } catch (MissingServiceException $expected) {
        $missing = true;
    }
    checkAot($missing, 'missing error');

    $cyclic = new ServiceRegistry();
    $cyclic->register('a', new AotFactoryA());
    $cyclic->register('b', new AotFactoryB());
    $cycle = false;
    try {
        $cyclic->get('a');
    } catch (CircularDependencyException $expected) {
        $cycle = true;
    }
    checkAot($cycle, 'cycle error');

    echo "PASS aot-integration\n";
}
