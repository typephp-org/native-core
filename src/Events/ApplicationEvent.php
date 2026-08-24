<?php

namespace TypePHP\NativeCore\Events;

final class ApplicationEvent implements Event
{
    public const STARTING = 'application.starting';
    public const STARTED = 'application.started';
    public const STOPPING = 'application.stopping';
    public const STOPPED = 'application.stopped';

    private string $eventName;

    public function __construct(string $eventName)
    {
        $this->eventName = $eventName;
    }

    public function name(): string
    {
        return $this->eventName;
    }
}
