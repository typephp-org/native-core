# TypePHP Native Core

TypePHP Native Core 是一个面向 CLI、Worker、Daemon、Service 与桌面宿主的
AOT-first 原生应用内核。它不是现成的 Web MVC/API 框架：Core 保持
Host-neutral，平台能力通过 `hosts/` 或外部适配器接入。基于它建设 Web
API 是可行的，但当前仓库还没有 HTTP Host、Router、Request/Response、
Middleware 和生产服务端验证；详见 [Web API 可行性与路线](docs/web-api.md)。

当前版本：`0.1.0-alpha.1`

公共 API 仍处于 alpha 阶段。

## 安装

```bash
composer require typephp/native-core:^0.1@alpha
```

Composer 包只提供 PHP 源码，不包含 TypePHP 编译器、PHPX、PHP Embed 或
原生运行库。TypePHP 项目必须在 `project.yml` 中显式列出使用的 Core 与
Host source。

## 快速开始

Zend PHP 开发与测试：

```powershell
php examples/hello-console/run-zend.php
build\windows\run-tests.cmd
```

TypePHP AOT 验证：

```powershell
build\windows\build-typephp.cmd
build\windows\build-aot-integration.cmd
build\windows\build-daemon-smoke.cmd
build\windows\build-desktop-spike.cmd
```

构建脚本优先读取 `TYPEPHP_HOME`、`PHP_HOME`、`PHPX_HOME` 与
`VS_BUILD_TOOLS`。当前机器保留了名为 `v0.2.3` 的安装目录作为环境别名，
其中实际编译器版本为 TypePHP AOT `v0.5.0`。

## 核心原则

- 使用显式 Module 与 ServiceFactory，不进行反射扫描。
- Application 负责完整 start/stop 生命周期；stop 逆序执行并受 `finally` 保护。
- Config、EventBus、Logger、Clock 等能力均由接口隔离。
- Zend PHP 用于快速开发测试，TypePHP AOT 用于原生发布验证。
- 平台专有能力不进入 `src/`。
- Windows 桌面应用通过 `WindowsDesktopHost` 接入；窗口、消息、渲染与原生
  资源仍由应用自己的 `WindowsDesktopProgram` 持有。
- 热路径优先使用具体类型构造注入，动态服务注册表只作为启动边界。

## 项目结构

```text
src/                  Host-neutral Core
hosts/                Console / Daemon / Windows Desktop adapters
examples/             最小 Console / Worker / Daemon / Desktop 示例
tests/                无第三方依赖的 Zend PHP 测试
build/                TypePHP 构建与发布脚本
docs/                 架构、工具链、状态与兼容策略
template/             最小原生应用模板
```

实现边界与复现命令见 [实施状态](docs/status.md) 和
[能力矩阵](docs/capability-matrix.md)。架构与稳定 API 说明见
[架构设计](docs/architecture.md) 和 [公共 API](docs/public-api.md)。

## 许可

本项目源码采用 MIT License。TypePHP 官方文档在 2026-08-17 改为宣布
编译器按 GPL 开源，允许商用和再分发；但当前文档没有写明 GPL 版本，
本机已验证的旧预览工具包仍附有禁止生产/商用的 `LICENSE.md`。在官方为
具体发布包附上明确的 GPL 原文前，不应假设旧二进制已自动换证。详见
[TypePHP 工具链调查](docs/typephp-toolchain.md)。
