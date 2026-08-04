# Publish a release

Use this guide to publish an immutable installable ZIP from `main`.

## 1. Set the version

Replace `0.2.1` with the intended semantic version:

```bash
npm version 0.2.1 --no-git-tag-version
npm run build
```

The build copies the package version into the plugin header and
`WITH_SITE_TOOLS_VERSION` constant.

## 2. Update and validate the release

Move the relevant entries from **Unreleased** into a dated section in
`CHANGELOG.md`, then run:

```bash
npm run check
npm run zip
unzip -t dist/with-site-tools.zip
```

Confirm that the ZIP contains one `with-site-tools/` root directory and does
not contain `node_modules/`, `vendor/`, or `dist/`.

## 3. Merge the release commit

Commit the version, changelog, source, and generated build output through the
normal pull-request workflow. Wait for the quality workflow on `main` to pass.

## 4. Publish the tag

Tag the validated commit on `main` and push the tag:

```bash
git tag v0.2.1
git push origin v0.2.1
```

The release workflow verifies the tag against npm and PHP versions, rebuilds
the plugin, runs all checks, validates the ZIP, creates a SHA-256 checksum, and
publishes both files to the corresponding GitHub release.

Never replace files attached to an existing release. Publish a patch version
instead.
