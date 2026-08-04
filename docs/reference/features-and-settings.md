# Features and settings reference

## Compatibility

| Requirement | Value |
| --- | --- |
| WordPress | 7.0 or newer |
| PHP | 8.0 or newer |
| Plugin directory | `with-site-tools` |
| Settings screen | **Tools > Site Tools** |

## Features

All features default to disabled. Optional plugin integrations are available
only while their owning plugin directory is active.

| Slug | Label | Behavior | Dependency |
| --- | --- | --- | --- |
| `blocks/core/accordion/faq-schema` | Accordion FAQ schema | Adds FAQPage, Question, and Answer microdata when a Core Accordion has the `is-faqs` class. | None |
| `blocks/core/columns/responsive-reverse` | Responsive Columns order | Adds mobile, tablet, and desktop controls for reversing Core Columns. | None |
| `blocks/core/file/disable-preview` | Disable file preview by default | Starts newly inserted File blocks without an embedded PDF preview. | None |
| `blocks/core/group/group-link` | Linked Group | Adds the Core link interface to Group blocks and renders an accessible overlay link. | None |
| `blocks/core/list/custom-icons` | Custom List icons | Adds a toolbar picker for supported Dashicons used as list markers. | None |
| `blocks/core/post-terms/disable-links` | Optional Post Terms links | Adds a setting that renders taxonomy terms as plain text. | None |
| `blocks/core/spacer/disable-margin` | Disable Spacer margins | Removes margin controls from the Spacer block. | None |
| `media/featured-image-fallback` | Featured image fallback | Uses the first suitable content image, then a neutral placeholder, for empty Featured Image and featured-image Cover blocks. | None |
| `plugins/admin-site-enhancements-pro/form-builder-honeypot` | Form Builder honeypot | Adds a signed honeypot and timing check to ASE Pro forms. | `admin-site-enhancements-pro` |
| `plugins/complianz-gdpr/hide-scan-column-by-default` | Hide Complianz scan column by default | Adds `cmplz_scan` to the default hidden columns on post-list screens. A user's saved Screen Options continue to take precedence. | `complianz-gdpr` |
| `site/disable-emojis` | Disable emoji assets | Removes WordPress emoji scripts, styles, conversions, and related DNS hints. | None |

## Stored option

`with_site_tools_feature_settings` is an associative array keyed by feature
slug. Only explicit boolean `true` values are stored. Missing keys and invalid
or unavailable features are disabled.

When the plugin is deleted through WordPress, the option is removed from every
site in a multisite network. Deactivation alone preserves it.

## Updates

The plugin uses the WordPress `Update URI` mechanism and the latest stable
public GitHub release. Release data is cached network-wide for 12 hours;
failed requests are cached for one hour. An update requires:

- a newer semantic version tag;
- a release returned by the configured GitHub `releases/latest` endpoint; and
- an asset named exactly `with-site-tools.zip`.

Version 0.1.0 does not contain this updater. Update that version manually to
0.2.0 or newer once; subsequent releases can use the Plugins screen.
