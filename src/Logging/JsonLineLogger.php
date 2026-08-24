<?php

namespace TypePHP\NativeCore\Logging;

final class JsonLineLogger implements Logger
{
    public function log(string $level, string $message, array $context): void
    {
        $record = [
            'level' => $level,
            'message' => $message,
            'context' => $context,
        ];
        $encoded = json_encode($record, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if ($encoded === false) {
            $encoded = '{"level":"error","message":"log encoding failed","context":{}}';
        }
        echo $encoded . "\n";
    }
}
