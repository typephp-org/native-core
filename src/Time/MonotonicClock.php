<?php

namespace TypePHP\NativeCore\Time;

interface MonotonicClock
{
    public function elapsedMilliseconds(): int;
}
