# TypePHP Native Core agent guide

This repository is an AOT-first, host-neutral application core that must run
under both Zend PHP and TypePHP AOT. Keep this file short; detailed rationale
belongs in `docs/`.

## Project boundaries

- `src/` is platform-neutral Core. It must not import Console, Daemon, Win32,
  SDL, WebView, or platform handles.
- `hosts/` contains host adapters. Platform experiments belong under
  `examples/` until their contracts are proven.
- Dependency direction is always Host/adapter -> Core.
- Public APIs are alpha. Preserve the documented lifecycle and update
  `docs/public-api.md` when changing public types or behavior.

## Required lifecycle semantics

- Modules register and start in insertion order.
- Successfully started modules stop in reverse order from a `finally` boundary.
- Host/start exceptions propagate only after cleanup.
- Stop and lifecycle-listener failures are logged and must not skip remaining
  cleanup.
- An `Application` instance is single-use.
- Long-running work observes the shared cooperative cancellation token.

## TypePHP AOT constraints

- PHP 8.4 is the minimum supported version for the compiler, generated
  programs, Zend verification and Composer consumers.
- AOT calls global `main()`; Zend does not. Each example therefore keeps an AOT
  `main.php` and a small Zend `run-zend.php` adapter.
- AOT sources are explicitly listed in `project.yml`. Do not rely on runtime
  discovery or dynamic file loading.
- Do not introduce reflection/attribute scanning, dynamic proxies, runtime code
  generation, variable variables, `extract()`, or `eval()`.
- Keep public callbacks as named objects/interfaces, not closures.
- Initialize fields explicitly and prefer concrete parameter/return/property
  types. Avoid nullable/union/intersection types in hot Core APIs.
- Generic `object` loses concrete-class optimization. Use the dynamic service
  registry only during bootstrap, then pass concrete services through typed
  constructors.
- Do not put executable application statements at file scope.
- C++ adapters use a `.stub.php` declaration and lowercase `php_*`
  implementation functions with PHPX types.

## Service and dependency rules

- Services use explicit string IDs and concrete `ServiceFactory` objects.
- Resolution is lazy, singleton-cached, and must retain distinct
  duplicate/missing/circular-dependency errors.
- Prefer small replaceable ports (`Config`, `EventBus`, `Logger`, `Clock`,
  `Sleeper`, `SignalSource`, `Filesystem`, `ProcessLock`, `Channel`) over
  platform checks inside Core.
- Add no runtime dependency unless the same behavior is verified under Zend and
  TypePHP or is isolated behind an adapter.

## Verification

Run the smallest relevant set, and never claim an unexecuted capability:

```powershell
build\windows\run-tests.cmd
build\windows\build-typephp.cmd
build\windows\build-aot-integration.cmd
build\windows\build-daemon-smoke.cmd
build\windows\build-desktop-spike.cmd
```

- Always run `run-tests.cmd` after PHP changes.
- Run AOT integration after Core, public API, factory, event, or lifecycle
  changes.
- Run the matching native smoke script after Daemon or Desktop changes.
- Add a Zend regression test for every fixed lifecycle/error-path bug.
- Update `docs/capability-matrix.md` only from actual command/runtime evidence.

## Local toolchain facts

- The configured directory alias is `D:\DevTools\TypePHP\v0.2.3`; the compiler
  inside currently reports TypePHP AOT `v0.5.0`.
- Build scripts must continue to honor `TYPEPHP_HOME`, `PHP_HOME`, `PHPX_HOME`,
  and `VS_BUILD_TOOLS`; never bake the alias into PHP APIs.
- `tpc` may print an embedded-path permission warning even on successful builds.
- A failed `tpc` invocation has been observed returning exit code 0. Delete or
  replace the expected old artifact, verify the new artifact exists, and run it;
  do not trust compiler exit status alone.
- Generated TypePHP/C++ objects and release artifacts stay ignored. Do not edit
  generated code as the source of truth.

## Current evidence boundary

Zend tests, Windows TypePHP `bin` builds, AOT lifecycle integration, the
foreground Daemon smoke test, and the reusable Win32 Desktop Host are confirmed.
Linux TypePHP, POSIX signal runtime behavior, Windows Service, TypePHP
`lib`/`ext`, remote CI, and a 24-72 hour stability run are not confirmed.
TypePHP project maintainer Han Tianfeng has confirmed that TypePHP is GPL open
source. Native Core is independently MIT and does not redistribute the compiler
or runtime toolchain. Record the exact toolchain release and checksum, preserve
its bundled notices, and keep compiler/runtime terms separate from Core.

Read `docs/architecture.md`, `docs/typephp-toolchain.md`, and
`docs/capability-matrix.md` before changing architecture or AOT policy.
