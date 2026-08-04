# With Site Tools

Reusable, opt-in site and Core block behavior for WordPress projects.

The plugin is independent from `with-base`: the theme works without this plugin,
and every plugin feature is disabled until it is explicitly enabled under
**Tools > Site Tools**.

## Quick start

```bash
npm install
npm run build
npm run env:start
```

The repository root is an installable WordPress plugin. `wp-env` mounts and
activates it directly. npm is used only for development tooling; this repository
is not an npm package intended for publication.

## Features

- Accordion FAQ schema
- Responsive Columns order
- File preview default
- Linked Group
- Custom List icons
- Optional Post Terms links
- Spacer margin restrictions
- Featured image fallback
- Emoji asset removal
- Admin Site Enhancements Pro Form Builder honeypot
- Complianz Website Scan column hidden by default

See [docs/architecture.md](docs/architecture.md) for feature ownership and
[docs/migration-from-with-base.md](docs/migration-from-with-base.md) for the
initial extraction contract. Distribution is documented in
[docs/deployment.md](docs/deployment.md).
