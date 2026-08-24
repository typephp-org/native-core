<?php

namespace TypePHP\NativeCore\Time;

final class SystemClock implements Clock
{
    public function nowMilliseconds(): int
    {
        return (int) floor(microtime(true) * 1000);
    }
}
