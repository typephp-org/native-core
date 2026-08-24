<?php

namespace TypePHP\NativeCore\Error;

final class OperationResult
{
    private bool $successful;
    private mixed $value;
    private string $error;

    private function __construct(bool $successful, mixed $value, string $error)
    {
        $this->successful = $successful;
        $this->value = $value;
        $this->error = $error;
    }

    public static function success(mixed $value): self
    {
        return new self(true, $value, '');
    }

    public static function failure(string $error): self
    {
        return new self(false, null, $error);
    }

    public function isSuccessful(): bool
    {
        return $this->successful;
    }

    public function value(): mixed
    {
        if (!$this->successful) {
            throw new NativeCoreException('A failed result has no value: ' . $this->error);
        }
        return $this->value;
    }

    public function error(): string
    {
        return $this->error;
    }
}
