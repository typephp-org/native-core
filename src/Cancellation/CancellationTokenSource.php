<?php

namespace TypePHP\NativeCore\Cancellation;

final class CancellationTokenSource
{
    private bool $cancelled;
    private CancellationToken $token;

    public function __construct()
    {
        $this->cancelled = false;
        $this->token = new CancellationToken($this);
    }

    public function token(): CancellationToken
    {
        return $this->token;
    }

    public function requestCancellation(): void
    {
        $this->cancelled = true;
    }

    public function isCancellationRequested(): bool
    {
        return $this->cancelled;
    }
}
