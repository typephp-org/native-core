<?php

$root = dirname(__DIR__);
$version = $argv[1] ?? '';
if (preg_match('/^[0-9]+\.[0-9]+\.[0-9]+(?:-(?:alpha|beta|RC)\.?[0-9]*)?$/', $version) !== 1) {
    throw new InvalidArgumentException('Usage: php build/package.php VERSION');
}
$prefix = 'typephp-native-core-' . $version . '/';
$releaseDir = $root . '/build/release';
$archivePath = $releaseDir . '/typephp-native-core-' . $version . '.zip';
$manifestPath = $archivePath . '.sha256';
$fixedTimestamp = 1767225600;

if (!is_dir($releaseDir) && !mkdir($releaseDir, 0777, true) && !is_dir($releaseDir)) {
    throw new RuntimeException('Unable to create release directory');
}

$paths = [
    'src',
    'hosts',
    'examples',
    'template',
    'docs',
    '.github',
    'build/windows',
    'build/typephp/core.yml',
    'composer.json',
    '.gitignore',
    'AGENTS.md',
    'CHANGELOG.md',
    'README.md',
    'SECURITY.md',
    'LICENSE',
];

$files = [];
foreach ($paths as $path) {
    $absolute = $root . '/' . $path;
    if (is_file($absolute)) {
        $files[] = $path;
        continue;
    }
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($absolute, FilesystemIterator::SKIP_DOTS)
    );
    foreach ($iterator as $file) {
        if (!$file->isFile()) {
            continue;
        }
        $relative = str_replace('\\', '/', substr($file->getPathname(), strlen($root) + 1));
        if (preg_match('/\.(obj|exe|lib|exp|pdb)$/i', $relative) === 1) {
            continue;
        }
        $files[] = $relative;
    }
}
sort($files, SORT_STRING);

$zip = new ZipArchive();
if ($zip->open($archivePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
    throw new RuntimeException('Unable to create release archive');
}
foreach ($files as $relative) {
    $entry = $prefix . $relative;
    if (!$zip->addFile($root . '/' . $relative, $entry)) {
        throw new RuntimeException('Unable to add release file: ' . $relative);
    }
    $zip->setMtimeName($entry, $fixedTimestamp);
}
$zip->setArchiveComment('TypePHP Native Core ' . $version);
$zip->close();

$hash = hash_file('sha256', $archivePath);
if ($hash === false) {
    throw new RuntimeException('Unable to hash release archive');
}
file_put_contents($manifestPath, $hash . '  ' . basename($archivePath) . "\n");
echo 'PACKAGE files=' . count($files) . ' sha256=' . $hash . "\n";
