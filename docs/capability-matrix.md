# Capability matrix

`confirmed` means the public repository contains a matching test or runnable
smoke and the documented command has succeeded. `Unverified` capabilities are
not release promises.

| Capability | Status | Evidence / boundary |
|---|---|---|
| Zend PHP lint/tests | confirmed on Windows | PHP 8.4.24, 38 assertions |
| Composer PSR-4 autoload | confirmed | Core and Windows Host load from `vendor/autoload.php` |
| Hello Console on Zend | confirmed | structured log, exit 0 |
| Worker cancellation | confirmed | 3 ticks, exit 0 |
| Core TypePHP translation/link/run | confirmed on Windows x64 | native Console smoke, exit 0 |
| AOT lifecycle and service errors | confirmed on Windows x64 | 51 PHP sources, 57 C/C++ units, `PASS aot-integration` |
| Application-to-Host stop forwarding | confirmed on Zend and TypePHP/Windows | at-most-once regressions and AOT integration |
| Reusable Windows Desktop Host | confirmed on Zend and TypePHP/Windows | contract regressions plus real create/pump-3-frames/close smoke, exit 0 |
| Monotonic elapsed clock | confirmed on Zend and TypePHP/Windows | replaceable clock regression and native `hrtime(true)` run |
| Daemon foreground loop | confirmed on Zend and TypePHP/Windows | native smoke completed three ticks, exit 0 |
| module cleanup after Host exception | confirmed | automated Zend regression |
| duplicate/missing/cyclic services | confirmed | Zend and AOT integration |
| Config/Event/Logger replacements | confirmed | automated Zend regressions |
| scheduler immediate cancellation | confirmed | automated Zend regression |
| filesystem and file lock | confirmed on Zend/Windows | automated Zend regression |
| filesystem and file lock at AOT runtime | unverified | full code compiles; no dedicated native runtime smoke |
| POSIX SIGINT/SIGTERM adapter | unverified | requires Linux/pcntl runtime evidence |
| Windows Service | unverified | no adapter or consumer |
| Web/API server | unverified | no HTTP Host, router or production server smoke |
| TypePHP `lib` and `ext` modes | unverified | help output only; this repository verifies `bin` mode |
| Linux/macOS TypePHP build | unverified | current native runtime evidence is Windows x64 only |
| 24-72 hour RSS/resource run | unverified | only the short stability harness has run |
| TypePHP toolchain distribution | separated from this package | TypePHP is GPL; Native Core is MIT and does not redistribute the compiler, PHPX, PHP Embed or runtime DLLs |
