# Architecture

## Dependency direction

```text
Application / Module / Services / Config / Events / Logging / Time
                              ↑
             Console / Daemon / Desktop adapters
```

`src/` does not import Console、Daemon、Win32、SDL or WebView. A Host receives
an `Application`; the application never constructs or identifies a Host.

## Lifecycle contract

1. `ApplicationBuilder::build()` validates `Module::apiVersion()` and calls
   `register()` in insertion order.
2. `Application::run()` emits `application.starting`, then calls `start()` in
   insertion order.
3. The selected Host owns its event loop and returns an integer exit code.
4. A `finally` boundary calls `stop()` for successfully started modules in
   reverse order.
5. Host/start exceptions propagate after cleanup. A stop failure is logged and
   does not prevent the remaining modules from stopping.
6. Lifecycle listener exceptions are observational failures: they are logged
   and cannot bypass module cleanup.
7. An Application instance is single-use.

Cancellation is cooperative. `Application::requestStop()` sets the shared
`CancellationTokenSource` and, while `Host::run()` is active, forwards one
`Host::requestStop()` call so a blocking native loop can be awakened. A Host
stop failure is logged without bypassing cleanup. Long-running Hosts and tasks
must still observe the shared token.

## Service registry

Services use an explicit string ID plus a concrete `ServiceFactory` object.
Resolution is lazy and cached. Duplicate IDs, missing IDs, and recursive
resolution throw distinct exceptions. There is no constructor reflection,
attribute scanning, dynamic proxy, or runtime code generation.

For a future generated registry, a build-time generator may emit ordinary
factory classes; the runtime contract does not depend on such a generator.
Because TypePHP documents generic `object` return values as losing concrete
class information, dynamic `get()` is a bootstrap boundary rather than a hot
path. Modules should pass concrete services through typed constructors; a
future generated provider may add typed accessors without changing factories.

## Replaceable ports

- `Config`: typed scalar reads from an explicit source.
- `EventBus`: synchronous object listeners.
- `Logger`: structured `level/message/context` records.
- `Clock`: civil/wall time for timestamps and persisted time.
- `MonotonicClock`: elapsed time for animation, deadlines and scheduling.
- `Sleeper`: replaceable blocking wait.
- `SignalSource`: platform signal adapter.
- `Filesystem`, `ProcessLock`, `Channel`: small infrastructure ports.

Closures are deliberately absent from public Core APIs. Event listeners,
service factories, scheduled tasks and programs are objects, producing a
static and AOT-visible call graph.

## Error boundaries

Programming/configuration errors use `NativeCoreException` subtypes.
`OperationResult` is for expected operational failure where returning a value
is preferable to throwing. It is intentionally non-generic until TypePHP
generic/value semantics are available and measured.

## Host adapters

- `ConsoleHost`: executes one `ConsoleProgram`.
- `DaemonHost`: foreground loop driven by `DaemonWorker`, `SignalSource` and
  `Sleeper`; process detachment remains a deployment adapter concern.
- `WindowsDesktopHost`: runs one `WindowsDesktopProgram` and connects a
  program-owned Win32 loop to cooperative Application cancellation. Native
  handles, messages, rendering and C++ bridges remain in the program adapter.

An HTTP server belongs in a Host adapter, not in `src/`. A future Web/API layer
should translate a server-specific request into Core-owned HTTP value objects,
run an explicit object-based middleware/router/controller graph, and translate
the response back at the adapter boundary. See [Web API feasibility](web-api.md).

The adapter contract, event-loop rules, packaging helpers, and verification
checklist are documented in the
[Desktop Host development guide](desktop-host-guide.md).
