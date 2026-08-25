# TypePHP Native Core

[English](README.md) | [简体中文](README.zh-CN.md)

[![CI](https://github.com/typephp-org/native-core/actions/workflows/ci.yml/badge.svg)](https://github.com/typephp-org/native-core/actions/workflows/ci.yml)
[![PHP](https://img.shields.io/badge/PHP-%3E%3D%208.4-777BB4.svg)](composer.json)
[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)

一个面向 TypePHP 与 Zend PHP 的 AOT-first、Host-neutral 应用内核，用于构建
CLI 工具、Worker、前台 Daemon，以及 Windows 原生桌面应用。

Native Core 提供应用生命周期和可移植的服务契约，Host 负责进程循环与平台
集成。它并不是现成的 Web MVC/API 框架：可以在 Core 之上实现 HTTP Host，
但 Router、Request/Response、Middleware 和生产服务端不在当前范围内。

当前版本线：`0.1.0-alpha.1`。公共 API 仍处于 alpha 阶段。

## 为什么是 TypePHP？

[TypePHP](https://swoole.com/aot/) 为 PHP 提供 AOT 编译路径：结构明确的 PHP
应用可以编译为原生代码，并通过 PHPX/C++ 接入操作系统 API。Native Core
让同一套应用对象图既能在 Zend PHP 下快速开发、测试，也能通过 TypePHP AOT
验证和发布，而不必换一种语言重写应用生命周期。

这形成了很实用的分工：

- Zend PHP 保留熟悉而快速的开发反馈和 Composer 生态。
- TypePHP 提供原生编译以及与 C++、平台能力集成的路径。
- Native Core 保证两个运行时采用一致的生命周期、取消、配置、事件和日志模型。

## Native Core 提供什么？

- 确定性的模块启动顺序，以及由 `finally` 保护的逆序清理。
- 显式 Module 与 `ServiceFactory`，不依赖反射或运行时扫描。
- Config、Event、Logger、Clock、Cancellation、Filesystem、ProcessLock、
  Channel 等小型可替换端口。
- AOT-friendly API：具名回调对象、具体类型、显式 source，不做运行时代码生成。
- Console、前台 Daemon 和可复用的 Windows Desktop Host 契约。
- 要求 PHP `>=8.4`，与当前 TypePHP 编译器及生成程序的最低要求一致；没有
  其他 Composer 运行时依赖。

依赖方向始终是：

```text
Application / Host adapter  ->  Native Core
```

平台句柄以及 Win32、SDL、WebView 或 Daemon 专属行为不会进入 `src/`。

## 安装

通过 Packagist 安装：

```bash
composer require typephp/native-core:^0.1@alpha
```

Composer 包只包含 PHP 源码，不包含 TypePHP 编译器、PHPX、PHP Embed、原生
运行库或构建工具。TypePHP 项目需要在 `project.yml` 中显式列出 Core 与 Host
源码。

## 快速开始

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

TypePHP AOT 会直接调用全局 `main()`；Zend 入口则保持很薄：

```php
<?php

require __DIR__ . '/../../vendor/autoload.php';
require __DIR__ . '/main.php';

main();
```

完整代码见 [`examples/hello-console`](examples/hello-console)。

## Hosts

| Host | 适用场景 | 边界 |
|---|---|---|
| Console | 命令和一次性工具 | Program 返回退出码。 |
| Daemon | 前台 Worker 与服务管理器托管的进程 | 长任务观察协作式取消；服务安装由外部负责。 |
| Windows Desktop | Win32 桌面应用循环 | 应用通过 `WindowsDesktopProgram` 持有窗口、消息、渲染和原生资源。 |

## 项目结构

```text
src/                  Host-neutral Core
hosts/                Console / Daemon / Windows Desktop 适配器
examples/             最小 Console / Worker / Daemon / Desktop 示例
tests/                无第三方依赖的 Zend PHP 回归测试
build/                TypePHP 验证与打包脚本
docs/                 架构、公共 API、工具链与实证边界
template/             最小原生应用 starter
```

## 验证

Zend PHP：

```powershell
php examples/hello-console/run-zend.php
build\windows\run-tests.cmd
```

当前已验证的 Windows TypePHP AOT 工具链：

```powershell
build\windows\build-typephp.cmd
build\windows\build-aot-integration.cmd
build\windows\build-daemon-smoke.cmd
build\windows\build-desktop-spike.cmd
```

构建脚本读取 `TYPEPHP_HOME`、`PHP_HOME`、`PHPX_HOME` 与 `VS_BUILD_TOOLS`，
本机安装路径不会进入 PHP API。

准确的完成情况见[实施状态](docs/status.md)和[能力矩阵](docs/capability-matrix.md)。
[架构设计](docs/architecture.md)、[公共 API](docs/public-api.md)与
[Web API 路线](docs/web-api.md)说明了扩展边界。

## 许可

TypePHP Native Core 采用 [MIT License](LICENSE)。TypePHP 是独立的 GPL
开源项目，本包不捆绑 TypePHP；编译器与运行时按 TypePHP 具体发行版附带的
许可使用。构建和 ABI 细节见 [TypePHP 工具链说明](docs/typephp-toolchain.md)。
