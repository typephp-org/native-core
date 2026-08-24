<?php

namespace TypePHP\NativeCore\Time;

interface Sleeper
{
    public function sleepMilliseconds(int $milliseconds): void;
}
