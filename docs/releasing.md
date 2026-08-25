# Release and compatibility

Native Core uses Semantic Versioning.

- `0.1.x`: alpha; source-compatible changes are preferred but not guaranteed.
- `0.2.0`: first intended API freeze after Linux and long-run evidence.
- Module compatibility is independently guarded by `ModuleApi::VERSION`.

## Release gate

A release must pass:

1. Zend lint and tests on Windows/Linux.
2. TypePHP Windows Core build and AOT integration run.
3. Desktop Spike compile and smoke close.
4. Dependency and license inventory review.
5. Package generation and SHA-256 manifest verification.
6. `CHANGELOG.md` and public documentation review.

Create the optional project source archive with an explicit version matching
the immutable Git tag:

```powershell
php build/package.php 0.1.0-alpha.1
```

This archive is a verification convenience, not an artifact Packagist needs.
Composer/Packagist resolves the package from VCS tags.

## First alpha release

Keep version selection and tag creation as an explicit maintainer decision. Once
the release gate is green:

```bash
git tag -a v0.1.0-alpha.1 -m "TypePHP Native Core v0.1.0-alpha.1"
git push origin v0.1.0-alpha.1
gh release create v0.1.0-alpha.1 --verify-tag --generate-notes --prerelease
```

GitHub automatically exposes source ZIP and tar.gz archives for the tag. A
GitHub Actions workflow may run the final `gh release create` step on tag pushes
with `contents: write`; it does not need the TypePHP compiler unless that same
workflow is also expected to rebuild or test native artifacts.

For this source-only Composer package, TypePHP AOT validation remains a separate
manual workflow on the pre-provisioned self-hosted Windows runner. Do not commit
or upload `tpc`, PHPX, PHP Embed, or their runtime DLLs to this repository.

## Packagist

There is no release file to upload to Packagist.

1. Make the GitHub repository public and ensure `composer validate --strict`
   passes on the release commit.
2. Sign in to Packagist, preferably through GitHub.
3. Choose **Submit**, then enter
   `https://github.com/typephp-org/native-core`.
4. Grant the Packagist GitHub application access to the `typephp-org`
   organization, or configure Packagist's documented GitHub push webhook.
5. Confirm that `typephp/native-core` exposes `0.1.0-alpha.1`.
6. Verify from a clean directory:

   ```bash
   composer require typephp/native-core:^0.1@alpha
   ```

The first repository submission is manual. With GitHub synchronization enabled,
future tag pushes are discovered automatically; a GitHub Release is useful for
humans and release notes but is not required by Packagist.

## Native binary boundary

Native binaries are not standalone on the validated Windows package. Bundle
the exact matching PHPX/PHP runtime DLL set or install it app-locally, plus a
supported Microsoft Visual C++ Redistributable. Never copy DLLs from a
different PHP/PHPX build.
