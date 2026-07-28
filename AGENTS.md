# With Site Tools agent rules

- Develop against stable WordPress Core 7.0+ and PHP 8.0+.
- Prefer WordPress Core APIs and official `@wordpress/*` packages.
- Keep the plugin fully independent from themes and customer-specific code.
- Every new feature is opt-in and must remain disabled when its option key is
  missing.
- Keep every feature source in one semantic path below `src/`; colocate its
  PHP, JavaScript, and SCSS there.
- Do not add `feature.json` files or a hand-maintained central feature list.
- Discover feature `index.php` entrypoints directly with PHP. Do not generate a
  PHP loader with Node or couple PHP feature registration to npm.
- Treat `src/` as the single feature source and never edit `build/` manually.
- Do not introduce interfaces, abstract classes, service containers, or wrapper
  layers.
- Use strict types, prefixed global PHP symbols, sanitization, capability checks,
  and late escaping.
- Run the production build, PHP syntax check, JS lint, and CSS lint before
  release.
- Do not trigger npm, build, sync, shell, or release processes from WordPress
  admin.
