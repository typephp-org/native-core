<?php

namespace TypePHP\NativeCore\Events;

interface EventBus
{
    public function addListener(EventListener $listener): void;

    public function dispatch(Event $event): void;
}
