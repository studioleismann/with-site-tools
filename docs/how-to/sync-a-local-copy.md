# Sync a local plugin copy

Use the sync command only for controlled local development or migration copies.
Production installations should use WordPress dashboard updates.

## 1. Build the runtime

```bash
npm run build
```

## 2. Preview the sync

Pass the final plugin directory, not its parent:

```bash
npm run sync:check -- --target /path/to/wp-content/plugins/with-site-tools
```

Resolve every reported conflict before continuing. A conflict means a managed
target file changed since the previous sync.

## 3. Apply the sync

```bash
npm run sync -- --target /path/to/wp-content/plugins/with-site-tools
```

The command leaves unchanged files untouched, tracks managed files by SHA-256,
and removes stale files only when their current content still matches the
manifest. It never copies development dependencies, raw JavaScript or SCSS,
release output, or configuration files.

Do not alternate between sync and WordPress dashboard updates on the same
production installation.
