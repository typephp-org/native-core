<?php

namespace TypePHP\NativeCore\Events;

interface EventListener
{
    public function supports(string $eventName): bool;

    public function handle(Event $event): void;
}
