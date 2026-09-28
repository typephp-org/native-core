<?php

namespace TypePHP\NativeCore\Host\Worker;

final class WorkResult
{
    public const CONTINUE = 1;
    public const RETRY = 2;
    public const STOP = 3;

    private int $action;
    private int $delayMilliseconds;
    private string $reason;
    private bool $usesPolicyDelay;

    private function __construct(int $action, int $delayMilliseconds, string $reason, bool $usesPolicyDelay)
    {
        $this->action = $action;
        $this->delayMilliseconds = $delayMilliseconds;
        $this->reason = $reason;
        $this->usesPolicyDelay = $usesPolicyDelay;
    }

    public static function next(): self
    {
        return new self(self::CONTINUE, 0, '', true);
    }

    public static function continueAfter(int $delayMilliseconds): self
    {
        return new self(self::CONTINUE, self::validDelay($delayMilliseconds), '', false);
    }

    public static function retry(string $reason): self
    {
        return new self(self::RETRY, 0, $reason, true);
    }

    public static function retryAfter(string $reason, int $delayMilliseconds): self
    {
        return new self(self::RETRY, self::validDelay($delayMilliseconds), $reason, false);
    }

    public static function stop(): self
    {
        return new self(self::STOP, 0, '', false);
    }

    public function action(): int { return $this->action; }
    public function delayMilliseconds(): int { return $this->delayMilliseconds; }
    public function reason(): string { return $this->reason; }
    public function usesPolicyDelay(): bool { return $this->usesPolicyDelay; }

    private static function validDelay(int $delayMilliseconds): int
    {
        if ($delayMilliseconds < 0) {
            throw new \InvalidArgumentException('Worker delay must not be negative');
        }
        return $delayMilliseconds;
    }
}
