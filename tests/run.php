<?php

require_once __DIR__ . '/../src/bootstrap.php';
require_once __DIR__ . '/../hosts/bootstrap.php';
require_once __DIR__ . '/../tools/NativeCli.php';

use TypePHP\NativeCore\Application\Application;
use TypePHP\NativeCore\Application\ApplicationContext;
use TypePHP\NativeCore\Application\Host;
use TypePHP\NativeCore\Application\NativeApplication;
use TypePHP\NativeCore\Cancellation\CancellationTokenSource;
use TypePHP\NativeCore\Config\ArrayConfig;
use TypePHP\NativeCore\Error\CircularDependencyException;
use TypePHP\NativeCore\Error\DuplicateServiceException;
use TypePHP\NativeCore\Error\MissingServiceException;
use TypePHP\NativeCore\Error\OperationResult;
use TypePHP\NativeCore\Events\Event;
use TypePHP\NativeCore\Events\EventListener;
use TypePHP\NativeCore\Events\SynchronousEventBus;
use TypePHP\NativeCore\Filesystem\LocalFilesystem;
use TypePHP\NativeCore\Filesystem\Path;
use TypePHP\NativeCore\Ipc\InMemoryChannel;
use TypePHP\NativeCore\Logging\Logger;
use TypePHP\NativeCore\Module\Module;
use TypePHP\NativeCore\Module\ModuleApi;
use TypePHP\NativeCore\Host\Windows\WindowsDesktopHost;
use TypePHP\NativeCore\Host\Windows\WindowsDesktopProgram;
use TypePHP\NativeCore\Host\Worker\Worker;
use TypePHP\NativeCore\Host\Worker\WorkerHealth;
use TypePHP\NativeCore\Host\Worker\WorkerHost;
use TypePHP\NativeCore\Host\Worker\WorkerPolicy;
use TypePHP\NativeCore\Host\Worker\WorkResult;
use TypePHP\NativeCore\Process\FileLock;
use TypePHP\NativeCore\Services\ServiceFactory;
use TypePHP\NativeCore\Services\ServiceRegistry;
use TypePHP\NativeCore\Signals\NoopSignalSource;
use TypePHP\NativeCore\Time\BlockingScheduler;
use TypePHP\NativeCore\Time\MonotonicClock;
use TypePHP\NativeCore\Time\ScheduledTask;
use TypePHP\NativeCore\Time\Sleeper;
use TypePHP\NativeCore\Time\SystemMonotonicClock;

final class TestSuite
{
    private int $passed = 0;

    public function same(mixed $expected, mixed $actual, string $message): void
    {
        if ($expected !== $actual) {
            throw new RuntimeException($message . ': expected ' . var_export($expected, true)
                . ', got ' . var_export($actual, true));
        }
        $this->passed++;
    }

    public function truth(bool $actual, string $message): void
    {
        $this->same(true, $actual, $message);
    }

    public function count(): int
    {
        return $this->passed;
    }
}

final class PlainService
{
    public string $name;

    public function __construct(string $name) { $this->name = $name; }
}

final class PlainFactory implements ServiceFactory
{
    public function create(ServiceRegistry $services): object { return new PlainService('made'); }
}

final class FactoryA implements ServiceFactory
{
    public function create(ServiceRegistry $services): object { return $services->get('b'); }
}

final class FactoryB implements ServiceFactory
{
    public function create(ServiceRegistry $services): object { return $services->get('a'); }
}

final class Recorder
{
    /** @var array<int, string> */
    public array $events = [];
}

final class RecordingModule implements Module
{
    private string $name;
    private Recorder $recorder;

    public function __construct(string $name, Recorder $recorder)
    {
        $this->name = $name;
        $this->recorder = $recorder;
    }

    public function apiVersion(): int { return ModuleApi::VERSION; }
    public function register(ServiceRegistry $services): void { $this->recorder->events[] = 'register:' . $this->name; }
    public function start(ApplicationContext $context): void { $this->recorder->events[] = 'start:' . $this->name; }
    public function stop(ApplicationContext $context): void { $this->recorder->events[] = 'stop:' . $this->name; }
}

final class ThrowingHost implements Host
{
    public function run(Application $application): int { throw new RuntimeException('host failed'); }
    public function requestStop(): void {}
}

final class BasicEvent implements Event
{
    public function name(): string { return 'basic'; }
}

final class RecordingListener implements EventListener
{
    public int $count = 0;
    public function supports(string $eventName): bool { return $eventName === 'basic'; }
    public function handle(Event $event): void { $this->count++; }
}

final class ThrowingLifecycleListener implements EventListener
{
    public function supports(string $eventName): bool { return $eventName === 'application.stopping'; }
    public function handle(Event $event): void { throw new RuntimeException('listener failed'); }
}

final class CaptureLogger implements Logger
{
    /** @var array<int, string> */
    public array $messages = [];
    public function log(string $level, string $message, array $context): void { $this->messages[] = $level . ':' . $message; }
}

final class CancellingHost implements Host
{
    public int $stopRequests = 0;
    public function run(Application $application): int
    {
        $application->requestStop();
        $application->requestStop();
        return 0;
    }
    public function requestStop(): void { $this->stopRequests++; }
}

final class ThrowingStopHost implements Host
{
    public function run(Application $application): int
    {
        $application->requestStop();
        return 7;
    }
    public function requestStop(): void { throw new RuntimeException('stop failed'); }
}

final class PassiveCountingHost implements Host
{
    public int $stopRequests = 0;
    public function run(Application $application): int { return 0; }
    public function requestStop(): void { $this->stopRequests++; }
}

final class FixedMonotonicClock implements MonotonicClock
{
    public function elapsedMilliseconds(): int { return 1234; }
}

final class NoWaitSleeper implements Sleeper
{
    public int $calls = 0;
    /** @var array<int, int> */
    public array $delays = [];
    public function sleepMilliseconds(int $milliseconds): void
    {
        $this->calls++;
        $this->delays[] = $milliseconds;
    }
}

final class ScriptedWorker implements Worker
{
    private int $run = 0;

    public function handle(ApplicationContext $context): WorkResult
    {
        $this->run++;
        if ($this->run === 1) {
            return WorkResult::retry('queue unavailable');
        }
        if ($this->run === 2) {
            return WorkResult::retry('queue still unavailable');
        }
        if ($this->run === 3) {
            return WorkResult::next();
        }
        return WorkResult::stop();
    }
}

final class ThrowingWorker implements Worker
{
    public function handle(ApplicationContext $context): WorkResult
    {
        throw new RuntimeException('worker crashed');
    }
}

final class CancellingTask implements ScheduledTask
{
    public int $runs = 0;
    public function run(ApplicationContext $context): void
    {
        $this->runs++;
        if ($this->runs === 3) {
            $context->cancellation()->requestCancellation();
        }
    }
}

final class RecordingWindowsDesktopProgram implements WindowsDesktopProgram
{
    public int $runs = 0;
    public int $stopRequests = 0;
    private int $exitCode;

    public function __construct(int $exitCode)
    {
        $this->exitCode = $exitCode;
    }

    public function run(ApplicationContext $context): int
    {
        $this->runs++;
        return $this->exitCode;
    }

    public function requestStop(): void
    {
        $this->stopRequests++;
    }
}

$suite = new TestSuite();

$cli = new NativeCli();
ob_start();
$doctorExit = $cli->run(['native', 'doctor', __DIR__ . '/../examples/daemon']);
$doctorOutput = (string) ob_get_clean();
$suite->same(0, $doctorExit, 'native doctor accepts the worker example');
$suite->truth(strpos($doctorOutput, 'READY') !== false, 'native doctor reports readiness');

$generatedDirectory = __DIR__ . '/../build/test-generated-worker-' . getmypid();
ob_start();
$generateExit = $cli->run(['native', 'new:worker', $generatedDirectory, 'MailWorker']);
ob_end_clean();
$suite->same(0, $generateExit, 'native CLI generates a worker project');
$suite->truth(is_file($generatedDirectory . '/src/MailWorker.php'), 'worker project contains business worker');
$suite->truth(is_file($generatedDirectory . '/project.yml'), 'worker project contains explicit AOT manifest');
unlink($generatedDirectory . '/src/MailWorker.php');
unlink($generatedDirectory . '/composer.json');
unlink($generatedDirectory . '/main.php');
unlink($generatedDirectory . '/run-zend.php');
unlink($generatedDirectory . '/project.yml');
rmdir($generatedDirectory . '/src');
rmdir($generatedDirectory);

$registry = new ServiceRegistry();
$registry->register('plain', new PlainFactory());
$suite->same('made', $registry->get('plain')->name, 'factory resolves a singleton');
$suite->truth($registry->get('plain') === $registry->get('plain'), 'factory result is cached');
try {
    $registry->register('plain', new PlainFactory());
    throw new RuntimeException('duplicate registration did not fail');
} catch (DuplicateServiceException $expected) {
    $suite->truth(true, 'duplicate registration fails');
}
try {
    $registry->get('missing');
    throw new RuntimeException('missing dependency did not fail');
} catch (MissingServiceException $expected) {
    $suite->truth(true, 'missing dependency fails');
}
$cyclic = new ServiceRegistry();
$cyclic->register('a', new FactoryA());
$cyclic->register('b', new FactoryB());
try {
    $cyclic->get('a');
    throw new RuntimeException('cycle did not fail');
} catch (CircularDependencyException $expected) {
    $suite->truth(true, 'circular dependency fails');
}

$recorder = new Recorder();
$application = NativeApplication::configure()
    ->withModule(new RecordingModule('one', $recorder))
    ->withModule(new RecordingModule('two', $recorder))
    ->build();
try {
    $application->run(new ThrowingHost());
} catch (RuntimeException $expected) {
    $suite->same('host failed', $expected->getMessage(), 'host exception crosses boundary');
}
$suite->same(
    ['register:one', 'register:two', 'start:one', 'start:two', 'stop:two', 'stop:one'],
    $recorder->events,
    'module lifecycle is ordered and stop runs after failure'
);
$suite->same(Application::STOPPED, $application->state(), 'application reaches stopped state');

$cancelRecorder = new Recorder();
$lifecycleBus = new SynchronousEventBus();
$lifecycleBus->addListener(new ThrowingLifecycleListener());
$capture = new CaptureLogger();
$cancelApplication = NativeApplication::configure()
    ->withModule(new RecordingModule('cancel', $cancelRecorder))
    ->withEventBus($lifecycleBus)
    ->withLogger($capture)
    ->build();
$cancellingHost = new CancellingHost();
$suite->same(0, $cancelApplication->run($cancellingHost), 'cancel host exits normally');
$suite->same(1, $cancellingHost->stopRequests, 'application forwards stop to active host once');
$suite->same(
    ['register:cancel', 'start:cancel', 'stop:cancel'],
    $cancelRecorder->events,
    'stop runs after cancellation and listener failure'
);
$suite->same(
    ['error:lifecycle event listener failed'],
    $capture->messages,
    'lifecycle listener failure is logged'
);

$stopFailureLogger = new CaptureLogger();
$stopFailureApplication = NativeApplication::configure()->withLogger($stopFailureLogger)->build();
$suite->same(7, $stopFailureApplication->run(new ThrowingStopHost()), 'host stop failure does not replace exit code');
$suite->truth(
    $stopFailureApplication->context()->cancellation()->isCancellationRequested(),
    'host stop failure does not undo cancellation'
);
$suite->same(
    ['error:host stop request failed'],
    $stopFailureLogger->messages,
    'host stop failure is logged'
);

$preCancelled = new CancellationTokenSource();
$preCancelled->requestCancellation();
$preCancelledApplication = NativeApplication::configure()->withCancellation($preCancelled)->build();
$passiveHost = new PassiveCountingHost();
$suite->same(0, $preCancelledApplication->run($passiveHost), 'pre-cancelled application can enter host cleanup path');
$suite->same(1, $passiveHost->stopRequests, 'pre-existing cancellation is forwarded before host run');

$desktopProgram = new RecordingWindowsDesktopProgram(9);
$desktopApplication = NativeApplication::configure()->build();
$suite->same(
    9,
    $desktopApplication->run(new WindowsDesktopHost($desktopProgram)),
    'windows desktop host preserves program exit code'
);
$suite->same(1, $desktopProgram->runs, 'windows desktop host runs its program once');
$suite->same(1, $desktopProgram->stopRequests, 'windows desktop close forwards one stop request');
$suite->truth(
    $desktopApplication->context()->cancellation()->isCancellationRequested(),
    'windows desktop close requests cooperative cancellation'
);

$preCancelledDesktopToken = new CancellationTokenSource();
$preCancelledDesktopToken->requestCancellation();
$preCancelledDesktopProgram = new RecordingWindowsDesktopProgram(9);
$preCancelledDesktopApplication = NativeApplication::configure()
    ->withCancellation($preCancelledDesktopToken)
    ->build();
$suite->same(
    0,
    $preCancelledDesktopApplication->run(new WindowsDesktopHost($preCancelledDesktopProgram)),
    'pre-cancelled windows desktop host exits without opening a native loop'
);
$suite->same(0, $preCancelledDesktopProgram->runs, 'pre-cancelled windows desktop program does not run');

$config = new ArrayConfig(['name' => 'native', 'workers' => 4, 'debug' => true]);
$suite->same('native', $config->string('name', ''), 'string config');
$suite->same(4, $config->integer('workers', 0), 'integer config');
$suite->truth($config->boolean('debug', false), 'boolean config');

$bus = new SynchronousEventBus();
$listener = new RecordingListener();
$bus->addListener($listener);
$bus->dispatch(new BasicEvent());
$suite->same(1, $listener->count, 'object event listener');

$logger = new CaptureLogger();
$logger->log('info', 'replaceable', []);
$suite->same(['info:replaceable'], $logger->messages, 'logger replacement');

$clockContext = NativeApplication::configure()
    ->withMonotonicClock(new FixedMonotonicClock())
    ->build()
    ->context();
$suite->same(1234, $clockContext->monotonicClock()->elapsedMilliseconds(), 'monotonic clock replacement');
$systemMonotonic = new SystemMonotonicClock();
$before = $systemMonotonic->elapsedMilliseconds();
$after = $systemMonotonic->elapsedMilliseconds();
$suite->truth($after >= $before, 'system monotonic clock does not move backwards');

$context = NativeApplication::configure()->build()->context();
$sleeper = new NoWaitSleeper();
$task = new CancellingTask();
$iterations = (new BlockingScheduler($sleeper))->run($task, $context, 1, 0);
$suite->same(3, $iterations, 'scheduler observes cancellation');
$suite->same(2, $sleeper->calls, 'scheduler waits between ticks');

$workerSleeper = new NoWaitSleeper();
$workerHealth = new WorkerHealth();
$workerLogger = new CaptureLogger();
$workerHost = new WorkerHost(
    new ScriptedWorker(),
    new WorkerPolicy(5, 10, 100, 5),
    new NoopSignalSource(),
    $workerSleeper,
    $workerHealth
);
$workerApplication = NativeApplication::configure()->withLogger($workerLogger)->build();
$suite->same(0, $workerApplication->run($workerHost), 'worker exits cleanly on stop result');
$suite->same([10, 20, 5], $workerSleeper->delays, 'worker applies exponential retry and idle delays');
$suite->same(1, $workerHealth->successfulRuns(), 'worker records successful work');
$suite->same(2, $workerHealth->totalFailures(), 'worker records retry attempts');
$suite->same(0, $workerHealth->consecutiveFailures(), 'successful work resets consecutive failures');
$suite->same(WorkerHealth::STOPPED, $workerHealth->state(), 'worker health reaches stopped state');

$failedWorkerSleeper = new NoWaitSleeper();
$failedWorkerHealth = new WorkerHealth();
$failedWorkerLogger = new CaptureLogger();
$failedWorkerHost = new WorkerHost(
    new ThrowingWorker(),
    new WorkerPolicy(0, 1, 2, 2),
    new NoopSignalSource(),
    $failedWorkerSleeper,
    $failedWorkerHealth
);
$failedWorkerApplication = NativeApplication::configure()->withLogger($failedWorkerLogger)->build();
$suite->same(1, $failedWorkerApplication->run($failedWorkerHost), 'worker failure limit returns non-zero');
$suite->same([1], $failedWorkerSleeper->delays, 'worker stops retrying at the configured failure limit');
$suite->same(WorkerHealth::FAILED, $failedWorkerHealth->state(), 'worker failure limit marks health failed');
$suite->same('worker crashed', $failedWorkerHealth->lastError(), 'worker health keeps the last failure');
$suite->same(
    ['warning:worker retry scheduled', 'error:worker failure limit reached'],
    $failedWorkerLogger->messages,
    'worker logs retry and terminal failure'
);

$channel = new InMemoryChannel();
$channel->send('ping');
$suite->same('ping', $channel->receive(), 'IPC channel round trip');
$suite->same('ok', OperationResult::success('ok')->value(), 'successful result');
$suite->same('failed', OperationResult::failure('failed')->error(), 'failed result');

$filesystem = new LocalFilesystem();
$testFile = Path::join(__DIR__, '../build/test-filesystem.txt');
$filesystem->write($testFile, 'data');
$suite->same('data', $filesystem->read($testFile), 'filesystem round trip');
$filesystem->delete($testFile);
$suite->same(false, $filesystem->exists($testFile), 'filesystem delete');

$lockPath = Path::join(__DIR__, '../build/test-single-instance.lock');
$lock = new FileLock($lockPath);
$suite->truth($lock->acquire(), 'process lock acquisition');
$lock->release();
if (is_file($lockPath)) {
    unlink($lockPath);
}

echo 'PASS assertions=' . $suite->count() . "\n";
