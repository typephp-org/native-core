<?php

$roots = [__DIR__ . '/../src', __DIR__ . '/../hosts', __DIR__ . '/../examples', __DIR__];
$failures = 0;
foreach ($roots as $root) {
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));
    foreach ($iterator as $file) {
        if (!$file->isFile() || $file->getExtension() !== 'php') {
            continue;
        }
        $output = [];
        $code = 0;
        exec(escapeshellarg(PHP_BINARY) . ' -l ' . escapeshellarg($file->getPathname()), $output, $code);
        if ($code !== 0) {
            $failures++;
            echo implode("\n", $output) . "\n";
        }
    }
}
exit($failures === 0 ? 0 : 1);
