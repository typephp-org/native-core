# Public API (alpha)

The initial API is intentionally small and may receive breaking changes until
`0.2.0`.

| Area | Stable intent | Alpha types |
|---|---|---|
| Application | Single-use run/stop lifecycle | `Application`, `ApplicationBuilder`, `NativeApplication`, `Host` |
| Hosts | Explicit adapters for console, daemon and Windows desktop loops | `ConsoleHost`, `ConsoleProgram`, `DaemonHost`, `DaemonWorker`, `WindowsDesktopHost`, `WindowsDesktopProgram` |
| Modules | Explicit registration and ordered lifecycle | `Module`, `ModuleApi::VERSION` |
| Services | Explicit factories and singleton cache | `ServiceRegistry`, `ServiceFactory` |
| Configuration | Replaceable typed scalar source | `Config`, `ArrayConfig`, `Environment` |
| Events | Synchronous object listeners | `EventBus`, `Event`, `EventListener` |
| Logging | Structured records | `Logger`, `JsonLineLogger`, `NullLogger` |
| Time | Separate testable wall and elapsed time, plus cooperative scheduling | `Clock`, `MonotonicClock`, `Sleeper`, `BlockingScheduler`, `ScheduledTask` |
| Shutdown | Cooperative cancellation and signal port | `CancellationTokenSource`, `CancellationToken`, `SignalSource` |
| Process/files | Lock, PID and basic filesystem | `FileLock`, `SingleInstance`, `PidFile`, `Filesystem`, `Path` |
| IPC | Transport-neutral message shape | `Channel`; `InMemoryChannel` is the reference/test adapter |
| Errors | Explicit exception families and result | `NativeCoreException` subtypes, `OperationResult` |

Alpha compatibility rules:

- `0.1.x` may add methods but avoids renaming existing lifecycle methods.
- A module must return `ModuleApi::VERSION`; incompatible modules fail at build.
- Platform behavior is promised only where the capability matrix says
  `confirmed`.
- Core never accepts a platform handle or requires a platform extension.

## Application and Host stop protocol

`Application::requestStop()` always requests the shared cancellation token. If
`Host::run()` is currently active, Application also calls
`Host::requestStop()` at most once. This gives native adapters a way to wake a
blocking message loop without putting platform knowledge in Core.

`Host::requestStop()` must return promptly and should be idempotent. Exceptions
from it are logged as operational shutdown failures; they do not undo
cancellation or skip module cleanup. The Host remains responsible for returning
from `run()`, and long-running work remains responsible for observing the
shared cancellation token.

`WindowsDesktopHost` delegates the native event loop to one
`WindowsDesktopProgram`. A program owns window creation, input, update,
rendering and native cleanup; its public boundary receives only an
`ApplicationContext`. Returning or throwing from the program requests
cooperative Application cancellation. If cancellation already exists before
the Host starts, the program is not run. `WindowsDesktopProgram::requestStop()`
must wake or close the native loop promptly and must not wait for it to finish.

## Time semantics

`Clock::nowMilliseconds()` is wall time and may move when the operating system
clock is corrected. `MonotonicClock::elapsedMilliseconds()` is non-decreasing
elapsed time for animation, frame deltas, deadlines, and timeouts. Both have
system defaults and can be replaced through `ApplicationBuilder`.
