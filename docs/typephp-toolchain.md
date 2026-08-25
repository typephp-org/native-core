# TypePHP toolchain boundary

Investigation updated: 2026-08-25.

Primary references:

- [TypePHP overview](https://swoole.com/aot/)
- [official documentation](https://swoole.com/aot/docs/)
- [installation](https://swoole.com/aot/docs/install)
- [execution model](https://swoole.com/aot/docs/execution)
- [type system](https://swoole.com/aot/docs/types)
- [compatibility](https://swoole.com/aot/docs/compatible)
- [project.yml](https://swoole.com/aot/docs/project-yml)
- [C++ interop](https://swoole.com/aot/docs/cxx)
- [distribution](https://swoole.com/aot/docs/best_practice)
- [official repository](https://github.com/swoole/typephp)

## Confirmed local toolchain

| Item | Observed value |
|---|---|
| Configured install alias | `D:\DevTools\TypePHP\v0.2.3` |
| Compiler reported version | TypePHP AOT `v0.5.0` |
| Embedded PHP | 8.4.24, ZTS, Visual C++ 2022, x64 |
| C++ compiler | MSVC 19.44.35228, x64, C++17 |
| Native mode exercised | `bin` on Windows x64 |

The install directory is a local alias, not the reported compiler version.
Build scripts honor `TYPEPHP_HOME`, `PHP_HOME`, `PHPX_HOME` and
`VS_BUILD_TOOLS`; applications must not bake those local paths into PHP APIs.

## Dual-runtime rules

- The current TypePHP compiler and generated programs require PHP 8.4 or newer.
  Native Core uses the same minimum for Zend development and Composer installs.
- TypePHP AOT calls global `main()`; Zend does not. Zend adapters call it
  explicitly.
- AOT consumes every PHP source listed in `project.yml`; it must not depend on
  Composer runtime discovery.
- Keep executable application statements out of file scope.
- Prefer explicitly initialized fields and concrete types at hot call sites.
- Avoid reflection scanning, runtime code generation, dynamic proxies,
  variable variables, `extract()` and `eval()` in the AOT profile.
- Public callbacks are named objects/interfaces rather than closures.
- Generic `object` and nullable/union declarations lose concrete native type
  optimization; the dynamic service registry is a bootstrap boundary.
- C++ adapters declare functions in `.stub.php`, implement lowercase `php_*`
  functions and exchange PHPX types.

Zend development uses Composer PSR-4 or the static `src/bootstrap.php` loader.
TypePHP builds list the same source directories explicitly. This is an
entrypoint/build adapter difference, not a fork of Core.

## Windows ABI and distribution

The verified output dynamically links the matching PHPX/PHP Embed runtime and
MSVC runtime. Do not mix PHP/PHPX builds across PHP minor version, architecture,
ZTS/NTS, Debug/Release or compiler runtime. Native artifacts are tied to their
OS, CPU and ABI; they are not Go-style standalone binaries.

The shared GUI build wrapper deletes the expected old artifact, runs the
compiler, verifies that a new executable exists and applies the Windows PE
subsystem. This is necessary because a failed compiler invocation has been
observed returning exit code 0. Native release validation must run the newly
created artifact rather than trusting compiler status alone.

The tested compiler may print an embedded-path permission warning during an
otherwise successful build. Report it, but judge success from artifact
replacement and runtime evidence.

## Licensing

TypePHP project maintainer Han Tianfeng has confirmed that TypePHP is GPL open
source. Native Core is independently MIT licensed and deliberately does not
bundle the TypePHP compiler, PHPX, PHP Embed, or generated runtime dependencies.

Use each toolchain component under the license shipped with its concrete
release. For reproducible native builds and binary redistribution, record the
exact TypePHP version and checksum, preserve bundled notices, and repeat native
and stability verification for that release.
