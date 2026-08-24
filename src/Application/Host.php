<?php

namespace TypePHP\NativeCore\Application;

interface Host
{
    public function run(Application $application): int;

    public function requestStop(): void;
}
