<?php

namespace TypePHP\NativeCore\Application;

use TypePHP\NativeCore\Cancellation\CancellationTokenSource;
use TypePHP\NativeCore\Config\Config;
use TypePHP\NativeCore\Config\Environment;
use TypePHP\NativeCore\Events\EventBus;
use TypePHP\NativeCore\Logging\Logger;
use TypePHP\NativeCore\Services\ServiceRegistry;
use TypePHP\NativeCore\Time\Clock;
use TypePHP\NativeCore\Time\MonotonicClock;

final class ApplicationContext
{
    private ServiceRegistry $services;
    private Config $config;
    private Environment $environment;
    private EventBus $events;
    private Logger $logger;
    private Clock $clock;
    private MonotonicClock $monotonicClock;
    private CancellationTokenSource $cancellation;

    public function __construct(
        ServiceRegistry $services,
        Config $config,
        Environment $environment,
        EventBus $events,
        Logger $logger,
        Clock $clock,
        MonotonicClock $monotonicClock,
        CancellationTokenSource $cancellation
    ) {
        $this->services = $services;
        $this->config = $config;
        $this->environment = $environment;
        $this->events = $events;
        $this->logger = $logger;
        $this->clock = $clock;
        $this->monotonicClock = $monotonicClock;
        $this->cancellation = $cancellation;
    }

    public function services(): ServiceRegistry { return $this->services; }
    public function config(): Config { return $this->config; }
    public function environment(): Environment { return $this->environment; }
    public function events(): EventBus { return $this->events; }
    public function logger(): Logger { return $this->logger; }
    public function clock(): Clock { return $this->clock; }
    public function monotonicClock(): MonotonicClock { return $this->monotonicClock; }
    public function cancellation(): CancellationTokenSource { return $this->cancellation; }
}
