<?php

namespace TypePHP\NativeCore\Application;

final class InactiveHost implements Host
{
    public function run(Application $application): int
    {
        return 0;
    }

    public function requestStop(): void
    {
    }
}
