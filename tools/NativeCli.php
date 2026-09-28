<?php

final class NativeCli
{
    /** @param array<int, string> $arguments */
    public function run(array $arguments): int
    {
        $command = $arguments[1] ?? 'help';
        if ($command === 'doctor') {
            return $this->doctor($arguments[2] ?? getcwd());
        }
        if ($command === 'new:worker') {
            if (!isset($arguments[2])) {
                fwrite(STDERR, "Usage: native new:worker <directory> [ClassName]\n");
                return 2;
            }
            return $this->newWorker($arguments[2], $arguments[3] ?? 'AppWorker');
        }
        if ($command === 'help' || $command === '--help' || $command === '-h') {
            $this->help();
            return 0;
        }

        fwrite(STDERR, 'Unknown command: ' . $command . "\n\n");
        $this->help();
        return 2;
    }

    private function help(): void
    {
        echo "TypePHP Native application tools\n\n";
        echo "  native new:worker <directory> [ClassName]  Create a runnable Worker application\n";
        echo "  native doctor [project-directory]         Check PHP, project.yml and AOT sources\n";
    }

    private function doctor(string $directory): int
    {
        $projectDirectory = realpath($directory);
        if ($projectDirectory === false || !is_dir($projectDirectory)) {
            fwrite(STDERR, "FAIL project directory does not exist: {$directory}\n");
            return 1;
        }

        $failures = 0;
        if (PHP_VERSION_ID >= 80400) {
            echo 'PASS PHP ' . PHP_VERSION . " (>= 8.4)\n";
        } else {
            echo 'FAIL PHP ' . PHP_VERSION . " is too old; PHP 8.4 or newer is required\n";
            $failures++;
        }

        $projectFile = $projectDirectory . DIRECTORY_SEPARATOR . 'project.yml';
        if (!is_file($projectFile)) {
            echo "FAIL project.yml was not found\n";
            return 1;
        }
        echo "PASS project.yml found\n";

        $sources = $this->yamlList($projectFile, 'sources');
        if (count($sources) === 0) {
            echo "FAIL project.yml has no explicit sources\n";
            $failures++;
        }
        foreach ($sources as $source) {
            $path = $projectDirectory . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $source);
            if (file_exists($path)) {
                echo "PASS source {$source}\n";
            } else {
                echo "FAIL source does not exist: {$source}\n";
                $failures++;
            }
        }

        $mainFile = $projectDirectory . DIRECTORY_SEPARATOR . 'main.php';
        if (!is_file($mainFile)) {
            echo "FAIL main.php was not found\n";
            $failures++;
        } elseif (strpos((string) file_get_contents($mainFile), 'function main(') === false) {
            echo "FAIL main.php does not declare global main()\n";
            $failures++;
        } else {
            echo "PASS global main() found\n";
        }

        $typePhpHome = getenv('TYPEPHP_HOME');
        if ($typePhpHome === false || $typePhpHome === '') {
            echo "WARN TYPEPHP_HOME is not set; Zend development works, native build discovery may not\n";
        } elseif (is_file($typePhpHome . DIRECTORY_SEPARATOR . 'tpc.exe')
            || is_file($typePhpHome . DIRECTORY_SEPARATOR . 'tpc')) {
            echo "PASS TypePHP compiler found in TYPEPHP_HOME\n";
        } else {
            echo "FAIL TYPEPHP_HOME does not contain tpc or tpc.exe\n";
            $failures++;
        }

        if ($failures === 0) {
            echo "READY project satisfies the checked Zend/AOT prerequisites\n";
            return 0;
        }
        echo "NOT READY failures={$failures}\n";
        return 1;
    }

    private function newWorker(string $directory, string $className): int
    {
        if (preg_match('/^[A-Z][A-Za-z0-9]*$/', $className) !== 1) {
            fwrite(STDERR, "Worker class must start with an uppercase letter and contain only letters or digits\n");
            return 2;
        }

        if (is_dir($directory)) {
            $entries = scandir($directory);
            if ($entries === false || count($entries) > 2) {
                fwrite(STDERR, "Refusing to write into a non-empty directory: {$directory}\n");
                return 1;
            }
        } elseif (!mkdir($directory, 0777, true)) {
            fwrite(STDERR, "Unable to create directory: {$directory}\n");
            return 1;
        }

        $sourceDirectory = $directory . DIRECTORY_SEPARATOR . 'src';
        if (!is_dir($sourceDirectory) && !mkdir($sourceDirectory, 0777, true)) {
            fwrite(STDERR, "Unable to create source directory\n");
            return 1;
        }

        $packageName = strtolower((string) preg_replace('/(?<!^)[A-Z]/', '-$0', $className));
        $files = [
            'composer.json' => $this->composerTemplate($packageName),
            'main.php' => $this->mainTemplate($className),
            'run-zend.php' => $this->zendTemplate(),
            'project.yml' => $this->projectTemplate($packageName),
            'src' . DIRECTORY_SEPARATOR . $className . '.php' => $this->workerTemplate($className),
        ];

        foreach ($files as $relative => $contents) {
            $path = $directory . DIRECTORY_SEPARATOR . $relative;
            if (file_put_contents($path, $contents) === false) {
                fwrite(STDERR, "Unable to write {$path}\n");
                return 1;
            }
            echo "CREATE {$path}\n";
        }

        echo "\nNext: cd {$directory} && composer install && php run-zend.php\n";
        return 0;
    }

    /** @return array<int, string> */
    private function yamlList(string $file, string $key): array
    {
        $lines = file($file, FILE_IGNORE_NEW_LINES);
        if ($lines === false) {
            return [];
        }
        $values = [];
        $inside = false;
        foreach ($lines as $line) {
            if (trim($line) === $key . ':') {
                $inside = true;
                continue;
            }
            if (!$inside) {
                continue;
            }
            if (preg_match('/^\s+-\s+(.+)\s*$/', $line, $matches) === 1) {
                $values[] = trim($matches[1], " \t\n\r\0\x0B\"'");
                continue;
            }
            if (trim($line) !== '' && $line[0] !== ' ' && $line[0] !== "\t") {
                break;
            }
        }
        return $values;
    }

    private function composerTemplate(string $packageName): string
    {
        return str_replace('__PACKAGE__', $packageName, <<<'JSON'
{
  "name": "app/__PACKAGE__",
  "type": "project",
  "require": {
    "php": ">=8.4",
    "typephp-org/native-core": "^0.1@alpha"
  },
  "autoload": {
    "psr-4": {"App\\": "src/"}
  }
}
JSON
        ) . "\n";
    }

    private function mainTemplate(string $className): string
    {
        return str_replace('__CLASS__', $className, <<<'PHP'
<?php

use App\__CLASS__;
use TypePHP\NativeCore\Application\NativeApplication;
use TypePHP\NativeCore\Host\Worker\WorkerHost;
use TypePHP\NativeCore\Logging\JsonLineLogger;

function main(): void
{
    $application = NativeApplication::configure()
        ->withLogger(new JsonLineLogger())
        ->build();
    $application->run(WorkerHost::defaults(new __CLASS__()));
}
PHP
        ) . "\n";
    }

    private function zendTemplate(): string
    {
        return <<<'PHP'
<?php

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/main.php';

main();
PHP
        . "\n";
    }

    private function projectTemplate(string $packageName): string
    {
        return str_replace('__PACKAGE__', $packageName, <<<'YAML'
name: __PACKAGE__
version: 0.1.0
mode: bin
output: build/__PACKAGE__
build-dir: build/typephp
optimize: 0
no-progress: true

sources:
  - vendor/typephp-org/native-core/src
  - vendor/typephp-org/native-core/hosts/worker
  - src
  - main.php

ignore:
  - vendor/typephp-org/native-core/src/bootstrap.php
YAML
        ) . "\n";
    }

    private function workerTemplate(string $className): string
    {
        return str_replace('__CLASS__', $className, <<<'PHP'
<?php

namespace App;

use TypePHP\NativeCore\Application\ApplicationContext;
use TypePHP\NativeCore\Host\Worker\Worker;
use TypePHP\NativeCore\Host\Worker\WorkResult;

final class __CLASS__ implements Worker
{
    public function handle(ApplicationContext $context): WorkResult
    {
        $context->logger()->log('info', 'worker handled one iteration', []);
        return WorkResult::stop();
    }
}
PHP
        ) . "\n";
    }
}
