=== Visibility Controls for Editor Blocks Pro ===
Contributors: denisdoroshchuk
Tags: block visibility, responsive blocks, gutenberg, conditional blocks, responsive design
Requires at least: 5.0
Tested up to: 6.9
Requires PHP: 7.2
Stable tag: 1.2.4
License: GPLv3.0
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Advanced Gutenberg block visibility by device, login status, user role, schedule, and URL/query rules.

== Description ==

**Visibility Controls for Editor Blocks Pro** adds advanced visibility controls directly inside the WordPress block editor. It includes all free plugin features and adds Pro rules for user roles, scheduled display windows, and URL/query parameters.

Use it to build cleaner Gutenberg layouts, personalize content for different visitors, schedule campaign blocks, and show landing-page content only when a matching URL parameter is present.

By default, Pro rules are processed on the server before block HTML is sent to the browser. For cached pages, you can switch scheduled visibility and URL/query rules to a frontend cache-friendly mode. Role-based rules always remain server-side for safety.

### Included Free Features:
- **Device visibility controls**: Hide blocks on mobile, tablet, or desktop.
- **Login status visibility**: Show or hide blocks for logged-in users or guests.
- **Custom breakpoints**: Define what counts as mobile and tablet for your theme.
- **Dynamic block support**: Works with server-rendered blocks, block themes, and modern WordPress layouts.
- **Native editor workflow**: Visibility options appear directly in the Gutenberg sidebar.

### Pro Features:
- **User role visibility**: Show or hide blocks for selected WordPress user roles.
- **Date and time scheduling**: Display blocks only during a selected time window.
- **URL and query rules**: Show blocks only when a URL parameter is present or matches a specific value.
- **Server-only mode**: Process Pro rules in PHP before block HTML reaches the browser.
- **Frontend cache-friendly mode**: Evaluate scheduled and URL/query rules in the browser when full-page cache is active.
- **Schedule date formats**: Choose a familiar date format for scheduling fields in the editor.
- **Displayed role controls**: Choose which WordPress roles appear in the editor controls.

### Ideal For:
- Membership sites that need different content for different roles.
- Campaign pages with scheduled banners, offers, or announcements.
- Marketing pages that change content based on URL/query parameters.
- Agencies building reusable Gutenberg layouts for client sites.
- Site owners who want advanced visibility controls without writing custom code.

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/visibility-controls-for-editor-blocks-pro` directory, or install the plugin zip through the WordPress plugins screen.
2. Activate the plugin through the **Plugins** screen in WordPress.
3. Activate your license when prompted.
4. Go to **Settings > Gutenberg Blocks Visibility** to configure breakpoints and Pro visibility settings.

== Frequently Asked Questions ==

= Does Pro include the free plugin features? =
Yes. The Pro package includes the free device, login status, breakpoint, and dynamic block visibility features.

= Do I need to keep the free plugin active? =
No. This is a premium version package. When Pro is active, it contains the full plugin functionality and can replace the free version.

= What Pro visibility rules are available? =
Pro adds user role visibility, date and time scheduling, and URL/query parameter rules.

= How does server-only mode work? =
In server-only mode, Pro rules are evaluated in PHP before the block HTML is sent to the browser. Use this for role-based visibility and any content that should not appear in the page source.

= How does frontend cache-friendly mode work? =
In frontend cache-friendly mode, scheduled visibility and URL/query rules are evaluated in the visitor browser. This helps when a page is served from full-page cache. Role rules remain server-side for safety.

= Can I choose which roles appear in the editor? =
Yes. The Pro settings page includes a displayed roles setting, so large sites can choose which user roles should appear in the block editor role visibility control.

= Does Pro work with dynamic blocks and block themes? =
Yes. Pro keeps the same dynamic block and block theme support as the free plugin.

== Screenshots ==

1. General settings for configuring responsive breakpoints.
2. Pro settings for rule processing mode, schedule date format, and displayed user roles.
3. Visibility settings in the Gutenberg editor sidebar.
4. Pro visibility settings for roles, scheduled display, and URL/query rules.

== Changelog ==

= 1.2.4 =
* Verified visibility controls in the WordPress Site Editor and template part editing flow.
* Updated Pro release metadata and kept the Pro package version aligned with the free plugin.

= 1.2.3 =
* Added Pro visibility rules for WordPress user roles.
* Added scheduled display rules with configurable date formats.
* Added URL/query parameter visibility rules.
* Added server-only and frontend cache-friendly processing modes.
* Added settings for choosing which user roles appear in the editor controls.
* Removed donation and review prompt UI from the Pro admin experience.
* Included all free visibility controls in the Pro package.

== Upgrade Notice ==

= 1.2.4 =
* Keeps the Pro package version aligned with the free plugin and confirms Site Editor visibility compatibility.

= 1.2.3 =
* Pro adds role visibility, scheduled display, URL/query rules, and cache-friendly processing options.

== License ==

This plugin is licensed under the GPLv3.0. You can find more information at [https://www.gnu.org/licenses/gpl-3.0.html](https://www.gnu.org/licenses/gpl-3.0.html).
