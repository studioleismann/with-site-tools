# Migrate features from with-base

Use this checklist for a site or theme that still loads the original
`with-base` implementations.

## 1. Install and configure the plugin

1. Back up the database and theme files.
2. Install and activate the latest With Site Tools release.
3. Enable only the features currently provided by the theme.
4. Verify existing content, including legacy `withBase*` block attributes.

## 2. Remove the old theme sources

Remove a theme implementation only after its enabled and disabled plugin states
have been tested against the matrix below.

| Previous with-base source | With Site Tools owner | Migration decision |
| --- | --- | --- |
| `inc/with-base/gdpr-remove-emojis.php` | `src/site/disable-emojis/` | Enable the plugin feature, verify the frontend, then remove the theme file and loader. |
| Accordion editor and schema files | `src/blocks/core/accordion/faq-schema/` | Existing `is-faqs` classes remain supported. |
| Columns editor, render, and style files | `src/blocks/core/columns/responsive-reverse/` | Existing `withBaseReverseColumnsOn` attributes remain readable. |
| File preview default | `src/blocks/core/file/disable-preview/` | Verify a newly inserted File block before removing the theme default. |
| Group link editor, render, and style files | `src/blocks/core/group/group-link/` | Existing `withBaseGroupLink*` attributes remain readable; unrelated Group defaults stay in the theme. |
| List icon editor, render, and style files | `src/blocks/core/list/custom-icons/` | Existing `withBaseListIcon` attributes remain readable; theme style variations stay in the theme. |
| Post Terms link control | `src/blocks/core/post-terms/disable-links/` | Existing `withBaseLinkTerms` attributes remain readable. |
| Spacer margin restriction | `src/blocks/core/spacer/disable-margin/` | Verify block supports in the editor before removing the theme filter. |
| Featured-image fallback files | `src/media/featured-image-fallback/` | The plugin consolidates Featured Image and Cover fallback behavior. |
| ASE Pro Form Builder honeypot | `src/plugins/admin-site-enhancements-pro/form-builder-honeypot/` | Enable only while the dependency is active. |
| Empty Post Terms workaround | WordPress Core 7.0+ | Delete it; Core now returns empty output when no terms exist. |
| Accordion styles in `theme.json` | Theme | Keep them because visual design remains theme-owned. |

## 3. Verify the final boundary

Confirm that the theme boots with the plugin deactivated. New theme patterns
and templates must not require `withBase*` or `withSiteTools*` feature
attributes. Existing customer content remains supported through the plugin's
legacy readers.
