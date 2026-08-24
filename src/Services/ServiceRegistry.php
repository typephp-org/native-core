<?php

namespace TypePHP\NativeCore\Services;

use TypePHP\NativeCore\Error\CircularDependencyException;
use TypePHP\NativeCore\Error\DuplicateServiceException;
use TypePHP\NativeCore\Error\MissingServiceException;

final class ServiceRegistry
{
    /** @var array<string, ServiceFactory> */
    private array $factories;
    /** @var array<string, object> */
    private array $instances;
    /** @var array<string, bool> */
    private array $resolving;

    public function __construct()
    {
        $this->factories = [];
        $this->instances = [];
        $this->resolving = [];
    }

    public function register(string $id, ServiceFactory $factory): void
    {
        if ($this->has($id)) {
            throw new DuplicateServiceException('Service already registered: ' . $id);
        }
        $this->factories[$id] = $factory;
    }

    public function registerInstance(string $id, object $instance): void
    {
        if ($this->has($id)) {
            throw new DuplicateServiceException('Service already registered: ' . $id);
        }
        $this->instances[$id] = $instance;
    }

    public function has(string $id): bool
    {
        return isset($this->factories[$id]) || isset($this->instances[$id]);
    }

    public function get(string $id): object
    {
        if (isset($this->instances[$id])) {
            return $this->instances[$id];
        }
        if (!isset($this->factories[$id])) {
            throw new MissingServiceException('Service is not registered: ' . $id);
        }
        if (isset($this->resolving[$id])) {
            throw new CircularDependencyException('Circular service dependency at: ' . $id);
        }

        $this->resolving[$id] = true;
        try {
            $instance = $this->factories[$id]->create($this);
            $this->instances[$id] = $instance;
            return $instance;
        } finally {
            unset($this->resolving[$id]);
        }
    }
}
