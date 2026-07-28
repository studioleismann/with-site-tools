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

## WordPress dashboard updates

A private GitHub repository alone does not provide WordPress dashboard updates.
That requires a separate authenticated updater or release service. Until that
delivery mechanism is deliberately implemented, releases are installed via the
ZIP or the controlled development sync. The plugin does not declare an Update
URI until that updater contract exists.
