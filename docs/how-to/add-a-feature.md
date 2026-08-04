# Add a feature

Use this guide when reusable site behavior should become an independently
toggleable With Site Tools feature.

## 1. Choose the owning path

Create one feature directory in a supported semantic location:

```text
src/blocks/<namespace>/<block>/<feature>/
src/media/<feature>/
src/site/<feature>/
src/plugins/<plugin-directory>/<feature>/
```

Put the feature's PHP, JavaScript, SCSS, and static assets together in that
directory. Do not add a central registry entry or metadata file.

## 2. Register the feature

Create `index.php` in the feature directory:

```php
<?php
/**
 * Describe the feature.
 *
 * @package WithSiteTools
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

$with_site_tools_feature_slug = with_site_tools_register_feature(
	__DIR__,
	__( 'Feature label', 'with-site-tools' ),
	__( 'Describe the user-facing result.', 'with-site-tools' )
);

if ( ! with_site_tools_is_feature_enabled( $with_site_tools_feature_slug ) ) {
	return;
}

// Add prefixed callbacks and WordPress hooks here.
```

For a path below `src/plugins/`, also return early when
`with_site_tools_is_feature_available()` is false.

## 3. Add optional assets

Use `editor.js` for block-editor behavior and `style.scss` for frontend or
editor block styles. The build discovers these filenames and mirrors their
semantic path below `build/`.

## 4. Build and verify both states

```bash
npm run check
```

In WordPress, verify that:

1. the feature appears under **Tools > Site Tools**;
2. its disabled state adds no behavior or assets;
3. enabling it produces the documented result;
4. disabling it again removes that result; and
5. an optional plugin integration is unavailable when its dependency is not
   active.
