# Distribution and deployment

## Development copy

The source repository lives below `/Developer/plugins/with-site-tools`. npm is
used only to run development tools; the plugin is not published to npm.

To build and copy the runtime into a local WordPress project:

```bash
npm run build
npm run sync:check -- --target /path/to/wp-content/plugins/with-site-tools
npm run sync -- --target /path/to/wp-content/plugins/with-site-tools
```

Sync is deterministic:

- unchanged files are not rewritten;
- managed files are tracked with SHA-256 hashes;
- stale files are removed only when their current hash still matches the
  manifest;
- locally modified files stop the complete sync before writes occur;
- development scripts, raw JavaScript/SCSS sources, `node_modules/`, and
  configuration files are never copied.

## Plugin ZIP

```bash
npm version 0.2.0 --no-git-tag-version
npm run build
npm run zip
```

The version command updates both npm metadata files. The build synchronizes the
WordPress plugin header and PHP version constant from `package.json`. Omit the
version command when rebuilding the current release.

The installable archive is written to:

```text
dist/with-site-tools.zip
```

No ZIP archive is kept loose in the repository root.

## GitHub releases

The public GitHub repository is the release source. Pushing a semantic version
tag such as `v0.2.0` runs the quality and release workflow. The workflow:

1. installs reproducible npm and WordPress Coding Standards dependencies;
2. verifies the tag, npm version, plugin header, and PHP version constant;
3. builds the plugin and runs PHP, JavaScript, and CSS checks;
4. rejects uncommitted generated output or an invalid ZIP structure;
5. publishes `with-site-tools.zip` and its SHA-256 checksum to a GitHub release.

Create tags only from a validated commit on `main`. Release assets are
version-specific even though the ZIP filename remains stable within each
release. Never replace an asset on an existing stable release; publish a new
patch version instead.

## WordPress dashboard updates

The plugin uses WordPress Core's `Update URI` mechanism to read the latest
stable public GitHub release. Responses are cached network-wide for 12 hours.
WordPress only offers an update when the release contains the validated
`with-site-tools.zip` asset and its semantic version is newer than the installed
plugin.

Production sites should use dashboard updates. The sync command remains a
development and migration tool for controlled local copies; do not alternate
between sync and dashboard updates on the same production installation.
