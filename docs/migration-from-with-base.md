# Migration from with-base

This matrix is the release checklist for the initial feature extraction. Theme
source may be removed only when the corresponding plugin behavior, legacy data,
enabled state, and disabled state have been verified.

| Previous with-base source | With Site Tools owner | Migration decision |
| --- | --- | --- |
| `inc/with-base/gpdr-remove-emojis.php` | `src/site/disable-emojis/` | Moved as an opt-in site feature. |
| `src/blocks/core/accordion/editor.js` and `schema-microdata.php` | `src/blocks/core/accordion/faq-schema/` | Moved together; the existing `is-faqs` class remains readable. |
| `src/blocks/core/columns/editor.js`, `reverse-responsive.php`, and the related part of `style.scss` | `src/blocks/core/columns/responsive-reverse/` | Moved together; `withBaseReverseColumnsOn` remains readable for existing content. |
| `src/blocks/core/file/disable-preview.php` | `src/blocks/core/file/disable-preview/` | Moved as an opt-in block-registration default. |
| `src/blocks/core/group/editor.js`, `group-link.php`, and the related part of `style.scss` | `src/blocks/core/group/group-link/` | Link behavior moved; unrelated Theme Tools Group defaults remain in the theme. Legacy `withBaseGroupLink*` attributes remain readable. |
| `src/blocks/core/list/editor.js`, `custom-list-icon.php`, and `style.scss` | `src/blocks/core/list/custom-icons/` | Moved together; existing theme style variations remain separate. Legacy `withBaseListIcon` remains readable. |
| `src/blocks/core/post-terms/editor.js` and `disable-term-links.php` | `src/blocks/core/post-terms/disable-links/` | Moved together; legacy `withBaseLinkTerms` remains readable. |
| `src/blocks/core/spacer/disable-margin.php` | `src/blocks/core/spacer/disable-margin/` | Moved as an opt-in block-support restriction. |
| `src/includes/media/featured-image-fallback.php`, `src/blocks/core/cover/featured-image-fallback.php`, and `src/blocks/core/post-featured-image/fallback.php` | `src/media/featured-image-fallback/` | Consolidated because all three files implement one shared media behavior. |
| `src/plugins/admin-site-enhancements-pro/form-builder-honeypot.php` | `src/plugins/admin-site-enhancements-pro/form-builder-honeypot/` | Moved as an availability-gated integration. |
| `src/blocks/core/post-terms/hide-empty.php` | WordPress Core 7.0+ | Not migrated. Core now returns an empty string when no terms exist, so the obsolete workaround is deleted rather than commented out. |
| `theme.json` styles for `core/accordion-panel` | `with-base` | Remains in the theme because it is visual theme ownership, not plugin behavior. |

New theme patterns and templates must not serialize `withBase*` or
`withSiteTools*` feature attributes. Existing customer content remains
supported through the legacy readers in the plugin, but the base theme must
remain semantically usable when the plugin is absent.
