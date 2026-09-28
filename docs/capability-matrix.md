# Capability matrix

`confirmed` means the public repository contains a matching test or runnable
smoke and the documented command has succeeded. `Unverified` capabilities are
not release promises.

| Capability | Status | Evidence / boundary |
|---|---|---|
| Zend PHP 8.4 lint/tests | confirmed on Windows and Linux | local PHP 8.4.26, 54 assertions; [Ubuntu and Windows CI](https://github.com/typephp-org/native-core/actions/runs/36369690512) passed |
| Composer PSR-4 autoload | confirmed | Core, Windows Host and Worker Host load from `vendor/autoload.php` |
| Hello Console on Zend | confirmed | structured log, exit 0 |
| Worker cancellation | confirmed | 3 ticks, exit 0 |
| Core TypePHP translation/link/run | confirmed on Windows x64 | TypePHP v0.9.3 native Console smoke, exit 0 |
| AOT lifecycle and service errors | confirmed on Windows x64 | TypePHP v0.9.3, 51 PHP sources, `PASS aot-integration` |
| Application-to-Host stop forwarding | confirmed on Zend and TypePHP/Windows | at-most-once regressions and AOT integration |
| Reusable Windows Desktop Host | confirmed on Zend and TypePHP/Windows | contract regressions plus real create/pump-3-frames/close smoke, exit 0 |
| Monotonic elapsed clock | confirmed on Zend and TypePHP/Windows | replaceable clock regression and native `hrtime(true)` run |
| Legacy DaemonHost foreground loop | confirmed on Zend and earlier TypePHP/Windows toolchain | prior native smoke completed three ticks; not rerun with v0.9.3 |
| WorkerHost loop and clean stop | confirmed on Zend and TypePHP v0.9.3/Windows | native three-iteration Worker smoke, exit 0 |
| Worker retry, failure limit and health state | confirmed on Zend; AOT behavior unverified | automated Zend regressions; current native smoke covers normal stop only |
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
