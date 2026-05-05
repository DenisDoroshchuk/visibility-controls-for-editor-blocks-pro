=== Visibility Controls for Editor Blocks ===
Contributors: denisdoroshchuk
Donate link: https://coff.ee/denisdoroshchuk
Tags: block visibility, responsive blocks, gutenberg, conditional blocks, responsive design
Requires at least: 5.0
Tested up to: 6.9
Requires PHP: 7.2
Stable tag: 1.2.3
License: GPLv3.0
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Hide Gutenberg blocks by device, custom breakpoints, and user login status. No custom CSS needed.

== Description ==

**Visibility Controls for Editor Blocks** adds native visibility settings to the WordPress block editor, so you can hide or show Gutenberg blocks based on screen size and user login status without writing custom CSS.

Use it to hide blocks on mobile, tablet, or desktop, show different content to logged-in users and guests, and fine-tune responsive layouts with your own breakpoints. The controls appear directly in the block editor sidebar, so you can manage visibility while editing your content.

The plugin works with regular Gutenberg blocks and modern dynamic blocks, including block theme and site editor contexts.

### Key Features:
- **Device visibility controls**: Hide blocks on mobile, tablet, or desktop.
- **Login status visibility**: Show or hide blocks for logged-in users or non-logged-in visitors.
- **Custom breakpoints**: Define what counts as mobile and tablet for your own theme.
- **No custom CSS needed**: Manage block visibility directly from the editor sidebar.
- **Gutenberg integration**: Visibility options appear where you already edit block settings.
- **Dynamic block support**: Works with server-rendered blocks, block themes, and modern WordPress layouts.

### Ideal For:
- Content creators who want to hide large or secondary blocks on smaller screens.
- Site owners who want to show different calls to action to guests and logged-in users.
- Designers who need responsive Gutenberg layouts without writing one-off CSS.
- Developers and agencies who want simple block visibility controls for client sites.

With **Visibility Controls for Editor Blocks**, you can keep one clean editor workflow while tailoring the frontend experience for different devices and visitors.

### Video tips for using the Visibility Controls for Editor Blocks plugin:
https://youtube.com/playlist?list=PLUo5dzT4ZLuPG_2Pproj_kK_-WtHNJwzY&feature=shared

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/visibility-controls-for-editor-blocks` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Go to "Settings > Gutenberg Blocks Visibility" to configure the breakpoints for mobile, tablet, and desktop devices.

== Frequently Asked Questions ==

= How do I set custom breakpoints? =
In the WordPress admin area, navigate to **Settings > Gutenberg Block Visibility** to configure custom breakpoints for mobile and tablet devices.

= Can I hide a Gutenberg block on mobile only? =
Yes. Select a block in the editor and enable **Hide on Mobile** in the Visibility Settings panel.

= Can I show content only to logged-in users or guests? =
Yes. You can hide blocks for logged-in users or hide blocks for non-logged-in visitors, which makes it easy to show different content to members and guests.

= Does it work with dynamic blocks and block themes? =
Yes. The plugin supports modern dynamic Gutenberg blocks and block theme rendering contexts.

= Does this plugin support other block editors like Elementor or Beaver Builder? =
No, this plugin is designed to work specifically with the Gutenberg block editor.

= Will this plugin affect the performance of my website? =
The plugin only adds small visibility classes and the CSS needed to hide matching blocks. It is designed to stay lightweight and avoid changing your block content structure.

== Screenshots ==

1. Settings page for configuring breakpoints under **Settings > Gutenberg Blocks Visibility**.
2. Visibility settings in the Gutenberg editor sidebar, including device and login status controls.
3. Block visibility indicators shown inside the editor.
4. Example of how visibility settings affect the layout on a mobile device.

== Changelog ==

= 1.2.3 =
* Improved the settings page layout and removed extra branding from the page header.
* Moved the review prompt into a standard WordPress admin notice on the plugin settings page.
* Added a 7-day delay before showing the review prompt to new and existing users.
* Replaced the external Buy Me a Coffee script with a regular support link.
* Updated plugin author metadata.

= 1.2.2 =
* Tested with WordPress 6.9.
* Fixed dynamic block visibility handling for WordPress 6.9.4 and newer block rendering contexts.
* Fixed the settings button URL in the block editor for subdirectory installs.
* Improved visibility CSS loading for block themes and block widget contexts.

= 1.2.1 =
* Fixed a problem with index.js

= 1.2.0 =
* Refactored JavaScript build system to use @wordpress/scripts.
* Removed legacy Gulp-based setup for cleaner and more maintainable builds.
* No functional changes for end users

= 1.1.3 =
* Fixed a problem with renaming blocks

= 1.1.2 =
* Fixed a problem with some dynamic blocks

= 1.1.1 =
* Tested up to WordPress 6.8

= 1.1.0 =
* Tested up to WordPress 6.7.1

= 1.0.9 =
* Tested up to WordPress 6.7

= 1.0.8 =
* Added wp.blockEditor.InspectorControls instead wp.editor.InspectorControls

= 1.0.7 =
* Added video tips to the settings page

= 1.0.6 =
* Small fix

= 1.0.5 =
* Added option to hide blocks for logged-in users.
* Added option to hide blocks for non-logged-in users.
* Added visual indication (strikethrough and overlay) for blocks hidden based on user login status in Gutenberg editor.

= 1.0.4 =
* Added support for dynamic Gutenberg block, for example: Navigation, Site Logo, Post Content etc.

= 1.0.3 =
* Added new language - ru_RU.
* Fixed a small bug.

= 1.0.2 =
* Added new option - Disable CSS loading on pages without Gutenberg.

= 1.0.0 =
* Initial release with functionality to hide blocks on mobile, tablet, and desktop devices.
* User-configurable breakpoints for each device type.

== Upgrade Notice ==

= 1.2.3 =
* This update improves the settings page experience and adds a delayed, dismissible review prompt.

= 1.2.2 =
* This update improves compatibility with WordPress 6.9 and fixes dynamic block visibility behavior.

= 1.2.1 =
* Fixed a problem with index.js

= 1.2.0 =
* Internal build system updated to use @wordpress/scripts. No changes to plugin behavior. Safe to update.

= 1.1.2 =
* Fixed a problem with some dynamic blocks

= 1.1.1 =
* Tested up to WordPress 6.8

= 1.1.0 =
* Tested up to WordPress 6.7.1

= 1.0.9 =
* Tested up to WordPress 6.7

= 1.0.8 =
* This update replaces deprecated function

= 1.0.7 =
* This update adds video tips for using the plugin to the settings page

= 1.0.5 =
* This update adds new options to hide blocks for logged-in and non-logged-in users, improving visibility control in the editor. You will now see visual indicators for blocks that are hidden based on user login status.

= 1.0.0 =
Initial release.

== License ==

This plugin is licensed under the GPLv2 or later. You can find more information at [https://www.gnu.org/licenses/gpl-3.0.html](https://www.gnu.org/licenses/gpl-3.0.html).
