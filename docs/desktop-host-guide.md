# Desktop Host development guide

This guide defines the reusable Win32 adapter boundary proven by the desktop
examples. `WindowsDesktopHost` and `WindowsDesktopProgram` live under
`hosts/windows`; application-specific windows, rendering and native bridges
remain with their consumers.

## Public Host contract

`WindowsDesktopHost` runs one `WindowsDesktopProgram`. The program owns its
complete native loop and exposes only two object methods:

```php
interface WindowsDesktopProgram
{
    public function run(ApplicationContext $context): int;
    public function requestStop(): void;
}
```

This intentionally does not standardize Win32 messages or rendering. Existing
consumers use materially different loops: some pull events into PHP while
others keep update and rendering entirely in C++. Both fit the same lifecycle
boundary without exposing a native handle to Core.

The Host does not run a pre-cancelled program. Once a program is running, a
normal native close or an exception requests cooperative Application
cancellation from a `finally` boundary. Stop forwarding reaches the program at
most once; `requestStop()` must still be prompt and idempotent.

## Boundary and ownership

- Core owns application composition, module lifecycle, logging, cancellation,
  and host-neutral services.
- The desktop program owns the native window, event pump, renderer, input
  translation, timers, and platform resource cleanup. `WindowsDesktopHost`
  owns only the Application lifecycle bridge.
- Dependencies point from the Host adapter to Core. Do not pass window handles,
  drawing contexts, native events, or platform constants into `src/`.
- Keep the PHP/C++ boundary narrow. TypePHP declarations belong in a
  `.stub.php`; matching C++ entry points use lowercase `php_*` names and PHPX
  types.

## Lifecycle and shutdown

`Application::run($host)` starts modules before entering `Host::run()` and
stops successfully started modules in reverse order after it returns or throws.
The Host must not duplicate module lifecycle work.

`Application::requestStop()` requests shared cooperative cancellation and,
while `Host::run()` is active, forwards one call to `Host::requestStop()`.
The Host stop method must be fast and safe while its event loop is running. It
should wake or close a blocking native loop; it must not wait for that same loop
to finish. Repeated calls should be harmless even though Application forwards
at most once. A Host stop exception is logged and does not replace the
cancellation signal or bypass module cleanup.

Normal native close events may call `Application::requestStop()` before the
Host returns. Long-running PHP work must also observe
`ApplicationContext::cancellation()`.

## Time, update, and rendering

- Use `Clock::nowMilliseconds()` only for civil timestamps, logs, and persisted
  wall time.
- Use `MonotonicClock::elapsedMilliseconds()` for frame deltas, animation,
  input thresholds, cooldowns, and timeouts. It is unaffected by wall-clock
  corrections and is replaceable in tests.
- Keep event processing non-blocking. Update simulation state from elapsed
  time, render the latest state, and yield or wait through the native loop.
- Cache decoded images, fonts, brushes, and other expensive native resources.
  Do not decode assets or rebuild static geometry every frame.
- Separate deterministic game/application state from visual interpolation so
  logic tests do not depend on frame rate.

## Input and native bridge

Translate native input into a small application vocabulary: pointer position,
button transitions, drag intent, key commands, focus, resize, and close. Keep
hit testing and gesture state deterministic. The native bridge should transfer
plain scalars or stable value shapes rather than exposing platform objects.

Never execute long PHP work synchronously from paint or input handling. Record
intent, advance it in the update phase, and invalidate or request a frame.

## Assets and paths

Resolve packaged assets from the executable directory, not the caller's current
working directory. Give each application one documented sibling asset folder.
Missing or corrupt required assets should produce a clear startup error and a
non-zero smoke-test exit code.

## Windows build and portable staging

Use the shared compiler wrapper for any TypePHP GUI target:

```powershell
build\windows\build-typephp-gui.cmd path\to\project.yml path\to\output.exe
```

It initializes MSVC, deletes the expected old artifact, invokes TypePHP,
verifies that the expected EXE was recreated, and sets the PE subsystem to
`WINDOWS` so launching it does not open a console. It honors `TYPEPHP_HOME`,
`PHP_HOME`, `PHPX_HOME`, and `VS_BUILD_TOOLS`.

Stage the executable and six currently required TypePHP runtime DLLs with:

```powershell
build\windows\stage-typephp-gui-runtime.cmd path\to\app.exe path\to\package [runtime-dir]
```

Copy application assets after staging, then archive the controlled package
directory. The DLLs belong beside the EXE unless deployment explicitly manages
`PATH`; assets follow the application's documented sibling-folder contract.
The target machine also needs the matching Microsoft Visual C++ x64 runtime.

## Verification checklist

Every Desktop Host should provide non-interactive modes that run through the
real executable:

- a short create/pump/close smoke test;
- deterministic logic regressions for state transitions and edge cases;
- renderer captures at important states when visual output matters;
- shutdown triggered through `Application::requestStop()`;
- artifact-existence checks after compilation, because `tpc` exit status alone
  has not always reported failed artifact creation.

Run Zend Core tests and AOT integration whenever the adapter requires Core API
changes. Update `docs/capability-matrix.md` only after the native executable has
actually run successfully.
