<?php

namespace TypePHP\NativeCore\Events;

final class SynchronousEventBus implements EventBus
{
    /** @var array<int, EventListener> */
    private array $listeners;

    public function __construct()
    {
        $this->listeners = [];
    }

    public function addListener(EventListener $listener): void
    {
        $this->listeners[] = $listener;
    }

    public function dispatch(Event $event): void
    {
        $count = count($this->listeners);
        for ($index = 0; $index < $count; $index++) {
            $listener = $this->listeners[$index];
            if ($listener->supports($event->name())) {
                $listener->handle($event);
            }
        }
    }
}
