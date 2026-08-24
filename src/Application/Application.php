<?php

namespace TypePHP\NativeCore\Application;

use TypePHP\NativeCore\Error\LifecycleException;
use TypePHP\NativeCore\Events\ApplicationEvent;
use TypePHP\NativeCore\Module\Module;

final class Application
{
    public const CREATED = 'created';
    public const RUNNING = 'running';
    public const STOPPED = 'stopped';

    private ApplicationContext $context;
    /** @var array<int, Module> */
    private array $modules;
    private string $state;
    private Host $activeHost;
    private bool $hostRunning;
    private bool $hostStopForwarded;

    /** @param array<int, Module> $modules */
    public function __construct(ApplicationContext $context, array $modules)
    {
        $this->context = $context;
        $this->modules = $modules;
        $this->state = self::CREATED;
        $this->activeHost = new InactiveHost();
        $this->hostRunning = false;
        $this->hostStopForwarded = false;
    }

    public function context(): ApplicationContext
    {
        return $this->context;
    }

    public function state(): string
    {
        return $this->state;
    }

    public function requestStop(): void
    {
        $this->context->cancellation()->requestCancellation();

        if (!$this->hostRunning || $this->hostStopForwarded) {
            return;
        }

        $this->hostStopForwarded = true;
        try {
            $this->activeHost->requestStop();
        } catch (\Throwable $exception) {
            $this->context->logger()->log('error', 'host stop request failed', [
                'host' => get_class($this->activeHost),
                'error' => $exception->getMessage(),
            ]);
        }
    }

    public function run(Host $host): int
    {
        if ($this->state !== self::CREATED) {
            throw new LifecycleException('Application can only be run once');
        }

        $started = 0;
        $this->state = self::RUNNING;
        $this->dispatchLifecycle(ApplicationEvent::STARTING);

        try {
            $count = count($this->modules);
            for ($index = 0; $index < $count; $index++) {
                $this->modules[$index]->start($this->context);
                $started++;
            }
            $this->dispatchLifecycle(ApplicationEvent::STARTED);
            $this->activeHost = $host;
            $this->hostRunning = true;
            if ($this->context->cancellation()->isCancellationRequested()) {
                $this->requestStop();
            }
            return $host->run($this);
        } finally {
            $this->hostRunning = false;
            $this->dispatchLifecycle(ApplicationEvent::STOPPING);
            for ($index = $started - 1; $index >= 0; $index--) {
                try {
                    $this->modules[$index]->stop($this->context);
                } catch (\Throwable $exception) {
                    $this->context->logger()->log('error', 'module stop failed', [
                        'module' => get_class($this->modules[$index]),
                        'error' => $exception->getMessage(),
                    ]);
                }
            }
            $this->state = self::STOPPED;
            $this->dispatchLifecycle(ApplicationEvent::STOPPED);
        }
    }

    private function dispatchLifecycle(string $eventName): void
    {
        try {
            $this->context->events()->dispatch(new ApplicationEvent($eventName));
        } catch (\Throwable $exception) {
            $this->context->logger()->log('error', 'lifecycle event listener failed', [
                'event' => $eventName,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
