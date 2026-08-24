<?php

namespace TypePHP\NativeCore\Services;

interface ServiceFactory
{
    public function create(ServiceRegistry $services): object;
}
