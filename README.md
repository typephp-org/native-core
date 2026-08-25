# TypePHP Native Core

[English](README.md) | [简体中文](README.zh-CN.md)

[![CI](https://github.com/typephp-org/native-core/actions/workflows/ci.yml/badge.svg)](https://github.com/typephp-org/native-core/actions/workflows/ci.yml)
[![PHP](https://img.shields.io/badge/PHP-%3E%3D%208.2-777BB4.svg)](composer.json)
[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)

An AOT-first, host-neutral application core for building CLI tools, workers,
foreground daemons, and native Windows desktop applications with TypePHP and
Zend PHP.

Native Core supplies the application lifecycle and portable service contracts;
hosts own the process loop and platform integration. It is intentionally not a
Web MVC/API framework. An HTTP host can be built on top, but routing,
request/response abstractions, middleware, and a production server are outside
the current scope.

Current release line: `0.1.0-alpha.1`. Public APIs are still alpha.

## Why TypePHP?

[TypePHP](https://swoole.com/aot/) brings an AOT compilation path to PHP. It
lets an explicitly structured PHP application become native code and provides
PHPX/C++ interop for operating-system APIs. Native Core is designed so the same
application graph can be developed and tested quickly on Zend PHP, then
validated and shipped through TypePHP AOT—without rewriting its lifecycle in a
second language.

The result is a practical split:

- Zend PHP provides the familiar, fast edit-test loop and Composer tooling.
- TypePHP provides native compilation and a path to C++/platform integration.
- Native Core keeps lifecycle, cancellation, configuration, events, and logging
  consistent across both runtimes.

## What Native Core provides

- Deterministic module startup and reverse-order cleanup from a `finally`
  boundary.
- Explicit modules and `ServiceFactory` objects instead of reflection or
  runtime scanning.
- Small replaceable ports for configuration, events, logging, clocks,
  cancellation, filesystems, process locks, and channels.
- AOT-friendly APIs: named callback objects, concrete types, explicit sources,
  and no runtime code generation.
- Console, foreground Daemon, and reusable Windows Desktop host contracts.
- Zero runtime Composer dependencies beyond PHP `>=8.2`.

Dependency direction is always:

```text
Application / Host adapter  ->  Native Core
```

Platform handles and Win32, SDL, WebView, or daemon-specific behavior never
enter `src/`.

## Installation

Install from Packagist:

```bash
composer require typephp/native-core:^0.1@alpha
```

The Composer package contains PHP source only. It does not bundle the TypePHP
compiler, PHPX, PHP Embed, native runtime libraries, or build tools. TypePHP
projects must list the Core and Host source files explicitly in `project.yml`.

## Quick start

```php
<?php

use TypePHP\NativeCore\Application\ApplicationContext;
use TypePHP\NativeCore\Application\NativeApplication;
use TypePHP\NativeCore\Host\Console\ConsoleHost;
use TypePHP\NativeCore\Host\Console\ConsoleProgram;

final class HelloProgram implements ConsoleProgram
{
    public function execute(ApplicationContext $context): int
    {
        $context->logger()->log('info', 'hello from native core');
        return 0;
    }
}

function main(): void
{
    NativeApplication::configure()
        ->build()
        ->run(new ConsoleHost(new HelloProgram()));
}
```

TypePHP AOT calls global `main()` directly. The Zend entrypoint is deliberately
small:

```php
<?php

require __DIR__ . '/../../vendor/autoload.php';
require __DIR__ . '/main.php';

main();
```

See [`examples/hello-console`](examples/hello-console) for the complete example.

## Hosts

| Host | Intended use | Boundary |
|---|---|---|
| Console | Commands and one-shot tools | The program returns an exit code. |
| Daemon | Foreground workers and service-manager processes | Work observes cooperative cancellation; service installation is external. |
| Windows Desktop | Win32 desktop application loops | The application owns windows, messages, rendering, and native resources through `WindowsDesktopProgram`. |

## Project layout

```text
src/                  Host-neutral Core
hosts/                Console / Daemon / Windows Desktop adapters
examples/             Minimal Console / Worker / Daemon / Desktop examples
tests/                Dependency-free Zend PHP regressions
build/                TypePHP verification and packaging scripts
docs/                 Architecture, public API, toolchain, and evidence
template/             Minimal native application starter
```

## Verification

Zend PHP:

```powershell
php examples/hello-console/run-zend.php
build\windows\run-tests.cmd
```

TypePHP AOT on the currently verified Windows toolchain:

```powershell
build\windows\build-typephp.cmd
build\windows\build-aot-integration.cmd
build\windows\build-daemon-smoke.cmd
build\windows\build-desktop-spike.cmd
```

Build scripts honor `TYPEPHP_HOME`, `PHP_HOME`, `PHPX_HOME`, and
`VS_BUILD_TOOLS`; local installation paths are never part of the PHP API.

See the [implementation status](docs/status.md) and
[capability matrix](docs/capability-matrix.md) for the exact evidence boundary.
The [architecture](docs/architecture.md), [public API](docs/public-api.md), and
[Web API roadmap](docs/web-api.md) describe the intended extension points.

## License

TypePHP Native Core is released under the [MIT License](LICENSE). TypePHP is a
separate GPL open-source project and is not bundled with this package; use its
compiler and runtime under the license shipped with the TypePHP release. See
the [TypePHP toolchain notes](docs/typephp-toolchain.md) for build and ABI
details.
