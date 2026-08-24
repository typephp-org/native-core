<?php

namespace TypePHP\NativeCore\Time;

interface Clock
{
    public function nowMilliseconds(): int;
}
