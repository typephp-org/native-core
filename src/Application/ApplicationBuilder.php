<?php

namespace TypePHP\NativeCore\Application;

use TypePHP\NativeCore\Cancellation\CancellationTokenSource;
use TypePHP\NativeCore\Config\ArrayConfig;
use TypePHP\NativeCore\Config\Config;
use TypePHP\NativeCore\Config\Environment;
use TypePHP\NativeCore\Error\LifecycleException;
use TypePHP\NativeCore\Events\EventBus;
use TypePHP\NativeCore\Events\SynchronousEventBus;
use TypePHP\NativeCore\Logging\Logger;
use TypePHP\NativeCore\Logging\NullLogger;
use TypePHP\NativeCore\Module\Module;
use TypePHP\NativeCore\Module\ModuleApi;
use TypePHP\NativeCore\Services\ServiceRegistry;
use TypePHP\NativeCore\Time\Clock;
use TypePHP\NativeCore\Time\MonotonicClock;
use TypePHP\NativeCore\Time\SystemClock;
use TypePHP\NativeCore\Time\SystemMonotonicClock;

final class ApplicationBuilder
{
    /** @var array<int, Module> */
    private array $modules;
    private ServiceRegistry $services;
    private Config $config;
    private Environment $environment;
    private EventBus $events;
    private Logger $logger;
    private Clock $clock;
    private MonotonicClock $monotonicClock;
    private CancellationTokenSource $cancellation;

    public function __construct()
    {
        $this->modules = [];
        $this->services = new ServiceRegistry();
        $this->config = new ArrayConfig([]);
        $this->environment = new Environment('production', false);
        $this->events = new SynchronousEventBus();
        $this->logger = new NullLogger();
        $this->clock = new SystemClock();
        $this->monotonicClock = new SystemMonotonicClock();
        $this->cancellation = new CancellationTokenSource();
    }

    public function withModule(Module $module): self { $this->modules[] = $module; return $this; }
    public function withConfig(Config $config): self { $this->config = $config; return $this; }
    public function withEnvironment(Environment $environment): self { $this->environment = $environment; return $this; }
    public function withEventBus(EventBus $events): self { $this->events = $events; return $this; }
    public function withLogger(Logger $logger): self { $this->logger = $logger; return $this; }
    public function withClock(Clock $clock): self { $this->clock = $clock; return $this; }
    public function withMonotonicClock(MonotonicClock $clock): self { $this->monotonicClock = $clock; return $this; }
    public function withCancellation(CancellationTokenSource $cancellation): self { $this->cancellation = $cancellation; return $this; }
    public function services(): ServiceRegistry { return $this->services; }

    public function build(): Application
    {
        $count = count($this->modules);
        for ($index = 0; $index < $count; $index++) {
            $module = $this->modules[$index];
            if ($module->apiVersion() !== ModuleApi::VERSION) {
                throw new LifecycleException('Unsupported module API version');
            }
            $module->register($this->services);
        }

        $context = new ApplicationContext(
            $this->services,
            $this->config,
            $this->environment,
            $this->events,
            $this->logger,
            $this->clock,
            $this->monotonicClock,
            $this->cancellation
        );
        return new Application($context, $this->modules);
    }
}
