# Development command reference

Run commands from the repository root with Node.js 20 or newer.

| Command | Description |
| --- | --- |
| `npm start` | Watch JavaScript and SCSS sources and rebuild development assets. |
| `npm run build` | Create production assets and synchronize the npm version with the plugin header and PHP constant. |
| `npm run check` | Build and run JavaScript, CSS, WPCS, PHP syntax, and version checks. |
| `npm run check:version` | Check that npm and PHP versions match; accepts an optional expected version argument. |
| `npm run lint:js` | Run the WordPress JavaScript linter. |
| `npm run lint:css` | Run the WordPress style linter. |
| `npm run lint:css:fix` | Apply safe style-lint fixes. |
| `npm run lint:php` | Run PHP_CodeSniffer with `phpcs.xml.dist`. |
| `npm run lint:syntax` | Run `php -l` on shipped PHP files. |
| `npm run format` | Format supported source and configuration files. |
| `npm run zip` | Build `dist/with-site-tools.zip`. |
| `npm run sync:check -- --target <path>` | Preview a managed runtime sync. |
| `npm run sync -- --target <path>` | Apply a managed runtime sync. |
| `npm run env:start` | Start `wp-env` on an available port. |
| `npm run env:stop` | Stop `wp-env`. |
| `npm run env:clean` | Delete all `wp-env` data for this project. |
