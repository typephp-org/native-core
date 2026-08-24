# Web API feasibility

Assessment date: 2026-08-18.

## Short answer

TypePHP Native Core can be the application/lifecycle foundation of a Web API,
but the current repository is not a Web server or an API framework. It has no
HTTP Host, router, request/response model, middleware executor, controller
dispatcher, database pool, authentication layer, or production Web test.

A Laravel-like *clarity* is achievable with explicit routes, typed controllers,
request DTOs, middleware objects, named factories and generated code. Full
Laravel compatibility or the same degree of runtime magic is not a realistic
goal for the AOT profile: reflection scanning, automatic runtime injection,
dynamic proxies, closure-heavy extension APIs and runtime class discovery cut
against this project's static-call-graph rules.

## Recommended shape

```text
Swoole/Swow/native HTTP adapter
        -> HttpHost
        -> Request / Router / object middleware
        -> typed Controller / application service
        -> Response / JSON encoder
        -> server adapter response
```

The protocol/value types and deterministic dispatch contracts may live in a
future host-neutral HTTP package. Socket loops, server request objects,
coroutines, TLS and platform handles remain in `hosts/http-*` adapters. This
keeps dependency direction `Host -> Core` and lets Zend tests exercise routing
without opening a network port.

Explicit container bindings, middleware objects, model-like value mappings,
jobs and transactions can be expressed with a static call graph. This is a
design direction, not evidence that Laravel itself compiles or runs under
TypePHP.

## What “elegant” should mean here

The target should be concise application code with compile-time-visible wiring:

- generated route tables instead of reflection/attribute scanning;
- controller factories and concrete constructor injection instead of runtime
  autowiring;
- named middleware/listener objects instead of public closure callbacks;
- typed request DTOs plus explicit validators and JSON serializers;
- an exception-to-response mapper and consistent result/error envelope;
- development-time generators that emit ordinary PHP checked into or listed by
  the build, without runtime code generation.

This can feel Laravel-like to application authors while remaining predictable
under both Zend and TypePHP. It will be more explicit and less magical than
Laravel, and should not promise drop-in package compatibility.

## Distribution boundary

Native output is cross-platform by rebuilding per target, not by shipping one
universal executable. Official TypePHP docs describe Linux, macOS and Windows
toolchains plus `--target-platform`, but native outputs remain tied to OS, CPU,
PHP/PHPX ABI and system libraries. The official distribution guide says native
programs are dynamically linked: Linux packages normally carry the executable,
`libphp.so`, `libphpx.so`, required extension libraries and any dynamically
loaded `vendor/` sources; Windows packages analogously need their runtime DLLs.

The official docs also describe WASI 0.2 Component output as a more portable,
self-contained `.wasm`, but it requires a compatible Component runtime. Its
documented HTTP support is outbound client access and does not establish a
production inbound Web-server Host for this project.

Current repository evidence confirms only Windows x64 native packaging and
runtime. Linux TypePHP, macOS, documented cross compilation, WASI, an HTTP
adapter, load behavior and production operations remain unverified.

## Minimum credible API milestone

Before describing this project as a deployable API framework, require:

1. host-neutral Request, Response, Router and middleware contracts;
2. one Zend in-memory adapter and one real TypePHP HTTP Host;
3. regression tests for route precedence, decoding, validation, exceptions,
   cancellation, middleware cleanup and response streaming;
4. database connection lifecycle/pooling and graceful shutdown behavior;
5. native smoke tests on every advertised OS/CPU package;
6. load, leak, restart, TLS/reverse-proxy and 24-72 hour stability evidence;
7. an explicitly licensed TypePHP release and a complete third-party notice.

Official references: [TypePHP docs source](https://github.com/swoole/typephp-docs),
[FAQ/licensing](https://github.com/swoole/typephp-docs/blob/master/question.md),
[distribution](https://github.com/swoole/typephp-docs/blob/master/best_practice.md),
[compiler options](https://github.com/swoole/typephp-docs/blob/master/options.md),
and [WASM](https://github.com/swoole/typephp-docs/blob/master/wasm.md).
