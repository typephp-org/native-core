# Implementation status

## Delivered

- Host-neutral Application/Module lifecycle with ordered startup, reverse
  cleanup, error boundaries and single-use enforcement.
- Explicit lazy singleton factories, configuration, synchronous events,
  structured logging, wall/monotonic clocks and cooperative cancellation.
- Filesystem, process-lock, PID, single-instance and transport-neutral Channel
  ports.
- Console, foreground Daemon and reusable Windows Desktop Host contracts.
- Minimal Zend examples and real TypePHP/Windows Console, Daemon, lifecycle and
  Win32 Desktop smokes.
- Composer PSR-4 package metadata, native app template, public API docs and
  Windows/Linux Zend PHP 8.4 plus self-hosted TypePHP workflow definitions.

## Not declared complete

- Public APIs remain alpha until downstream consumers validate them.
- There is no HTTP Host, router, request/response model or Web server.
- Linux/macOS TypePHP, POSIX signals, Windows Service and TypePHP `lib`/`ext`
  modes do not have matching runtime evidence here.
- The stability harness has not completed a 24-72 hour production-style run.
- Public CI does not provision the TypePHP toolchain yet; native validation
  currently uses a separately installed self-hosted Windows runner.

## Reproduce

```powershell
build\windows\run-tests.cmd
build\windows\build-typephp.cmd
build\windows\build-aot-integration.cmd
build\windows\build-daemon-smoke.cmd
build\windows\build-desktop-spike.cmd
```
