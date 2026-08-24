# Release and compatibility

Native Core uses Semantic Versioning.

- `0.1.x`: alpha; source-compatible changes are preferred but not guaranteed.
- `0.2.0`: first intended API freeze after Linux and long-run evidence.
- Module compatibility is independently guarded by `ModuleApi::VERSION`.

A release must pass:

1. Zend lint and tests on Windows/Linux.
2. TypePHP Windows Core build and AOT integration run.
3. Desktop Spike compile and smoke close.
4. Dependency inventory review.
5. License review for the exact TypePHP distribution.
6. Package generation and SHA-256 manifest verification.

Create the source archive with an explicit version matching the immutable Git
tag:

```powershell
php build/package.php 0.1.0-alpha.1
```

Native binaries are not standalone on the validated Windows package. Bundle
the exact matching PHPX/PHP runtime DLL set or install it app-locally, plus a
supported Microsoft Visual C++ Redistributable. Never copy DLLs from a
different PHP/PHPX build.
