<?php

namespace TypePHP\NativeCore\Time;

final class SystemMonotonicClock implements MonotonicClock
{
    public function elapsedMilliseconds(): int
    {
        return (int) floor(hrtime(true) / 1000000);
    }
}
