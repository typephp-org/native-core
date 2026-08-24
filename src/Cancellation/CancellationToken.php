<?php

namespace TypePHP\NativeCore\Cancellation;

final class CancellationToken
{
    private CancellationTokenSource $source;

    public function __construct(CancellationTokenSource $source)
    {
        $this->source = $source;
    }

    public function isCancellationRequested(): bool
    {
        return $this->source->isCancellationRequested();
    }
}
