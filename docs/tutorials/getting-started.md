# Get started with With Site Tools

This tutorial installs the plugin, enables one feature, and confirms that the
feature is active. You need a WordPress 7.0+ site with PHP 8.0+ and permission
to install plugins.

## 1. Install the plugin

1. Download `with-site-tools.zip` from the
   [latest GitHub release](https://github.com/studioleismann/with-site-tools/releases/latest).
2. In WordPress, open **Plugins > Add Plugin > Upload Plugin**.
3. Select the ZIP, choose **Install Now**, and activate **With Site Tools**.

The Plugins screen now shows **With Site Tools** with a **Settings** link.

## 2. Enable a feature

1. Open **Tools > Site Tools**.
2. Enable **Disable emoji assets**.
3. Save the settings.

The feature status changes to enabled. All other features remain disabled.

## 3. Verify the result

Open the HTML source of a frontend page and search for `wp-emoji`. WordPress
emoji scripts and styles should no longer be present.

Return to **Tools > Site Tools**, disable the feature, and save again if you do
not want to keep it active.

You now know the complete feature workflow: install the plugin, choose only the
behavior the site needs, save, and verify the visible result.

## Next steps

- Look up all available behavior in the
  [feature reference](../reference/features-and-settings.md).
- Follow [Develop the plugin locally](../how-to/develop.md) to work on the
  source code.
