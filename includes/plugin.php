<?php

const GBVC_REVIEW_PROMPT_DELAY = 7 * DAY_IN_SECONDS;

// Set the first date when the review prompt may be shown on new installs.
function gbvc_set_initial_review_prompt_delay(): void {
	add_option( 'gbvc_review_prompt_eligible_after', time() + GBVC_REVIEW_PROMPT_DELAY );
}

register_activation_hook( GBVC_PLUGIN_FILE, 'gbvc_set_initial_review_prompt_delay' );

// Delete plugin options when Freemius completes the uninstall flow.
function gbvc_delete_options(): void {
	delete_option( 'gbvc_mobile_breakpoint' );
	delete_option( 'gbvc_tablet_breakpoint' );
	delete_option( 'gbvc_disable_styles_on_non_gutenberg_pages' );
	delete_option( 'gbvc_review_prompt_eligible_after' );
	delete_option( 'gbvc_review_prompt_later_until' );
	delete_option( 'gbvc_review_prompt_dismissed' );
}

function gbvc_fs_uninstall_cleanup(): void {
	gbvc_delete_options();
}

if ( function_exists( 'gbvc_fs' ) ) {
	gbvc_fs()->add_action( 'after_uninstall', 'gbvc_fs_uninstall_cleanup' );
}

// Load text domain for translations
function gbvc_load_text_domain(): void {
	load_plugin_textdomain( 'visibility-controls-for-editor-blocks', false, dirname( plugin_basename( GBVC_PLUGIN_FILE ) ) . '/languages' );
}

add_action( 'plugins_loaded', 'gbvc_load_text_domain' );

// Enqueue block editor assets (JavaScript and CSS) for Gutenberg.
function gbvc_enqueue_block_editor_assets(): void {
	$asset_file = include plugin_dir_path( GBVC_PLUGIN_FILE ) . 'build/index.asset.php';

	wp_enqueue_script(
		'gbvc-editor',
		plugins_url( 'build/index.js', GBVC_PLUGIN_FILE ),
		$asset_file['dependencies'],
		$asset_file['version'],
		true
	);

	wp_add_inline_script(
		'gbvc-editor',
		'window.gbvcEditorData = Object.assign( {}, window.gbvcEditorData, ' . wp_json_encode(
			array(
				'settingsPageUrl' => esc_url_raw( admin_url( 'options-general.php?page=gbvc-settings' ) ),
			)
		) . ' );',
		'before'
	);

	wp_set_script_translations( 'gbvc-editor', 'visibility-controls-for-editor-blocks', plugin_dir_path( GBVC_PLUGIN_FILE ) . 'languages' );
}

add_action( 'enqueue_block_editor_assets', 'gbvc_enqueue_block_editor_assets' );

// Enqueue frontend styles for the block visibility controls.
function gbvc_enqueue_frontend_styles(): void {
	if ( gbvc_should_load_assets() ) {
		$mobile_breakpoint     = get_option( 'gbvc_mobile_breakpoint', '600' );
		$tablet_min_breakpoint = $mobile_breakpoint + 1;
		$tablet_breakpoint     = get_option( 'gbvc_tablet_breakpoint', '1024' );
		$desktop_breakpoint    = $tablet_breakpoint + 1;
		$inline_style          = "@media screen and (max-width: " . esc_attr( $mobile_breakpoint ) . "px) {.gbvc-hide-on-mobile {display: none !important}}@media screen and (min-width: " . esc_attr( $tablet_min_breakpoint ) . "px) and (max-width: " . esc_attr( $tablet_breakpoint ) . "px) {.gbvc-hide-on-tablet {display: none !important}}@media screen and (min-width: " . esc_attr( $desktop_breakpoint ) . "px) {.gbvc-hide-on-desktop {display: none !important}}";

		if ( ! is_admin() ) {
			$inline_style .= "body.logged-in .gbvc-hide-for-logged-in {display: none !important;}body:not(.logged-in) .gbvc-hide-for-non-logged-in {display: none !important;}";
		} else {
			$inline_style .= ".gbvc-hide-for-logged-in, .gbvc-hide-for-non-logged-in { position: relative; opacity: 0.6; pointer-events: none; } .gbvc-hide-for-logged-in:after, .gbvc-hide-for-non-logged-in::after { content: ''; position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: repeating-linear-gradient(45deg, rgba(255, 0, 0, 0.1), rgba(255, 0, 0, 0.1) 5px, transparent 5px, transparent 10px); z-index: 5; pointer-events: none; }";
		}

		wp_register_style( 'gbvc-styles', false, array(), '1.0.0' );
		wp_add_inline_style( 'gbvc-styles', $inline_style );
		wp_enqueue_style( 'gbvc-styles' );
	}
}

add_action( 'enqueue_block_assets', 'gbvc_enqueue_frontend_styles' );

// Decide if visibility assets should be loaded for the current request.
function gbvc_should_load_assets(): bool {
	if ( is_admin() ) {
		return true;
	}

	if ( ! get_option( 'gbvc_disable_styles_on_non_gutenberg_pages', false ) ) {
		return true;
	}

	return gbvc_is_gutenberg_page();
}

// Function to check if the page contains Gutenberg blocks or not
function gbvc_is_gutenberg_page(): bool {
	if ( is_singular() ) {
		$post = get_post();

		if ( $post instanceof WP_Post && has_blocks( $post->post_content ) ) {
			return true;
		}
	}

	if ( function_exists( 'wp_is_block_theme' ) && wp_is_block_theme() ) {
		return true;
	}

	if ( is_active_widget( false, false, 'block', true ) ) {
		return true;
	}

	return false;
}

// Function to add the settings page
function gbvc_add_settings_page(): void {
	add_options_page( __( 'Visibility Controls for Gutenberg Blocks', 'visibility-controls-for-editor-blocks' ), __( 'Gutenberg Blocks Visibility', 'visibility-controls-for-editor-blocks' ), 'manage_options', 'gbvc-settings', 'gbvc_render_settings_page' );
}

add_action( 'admin_menu', 'gbvc_add_settings_page' );

// Build a nonce-protected action URL for the review prompt controls.
function gbvc_get_review_prompt_action_url( string $action ): string {
	return wp_nonce_url(
		add_query_arg(
			'gbvc_review_prompt',
			$action,
			admin_url( 'options-general.php?page=gbvc-settings' )
		),
		'gbvc_review_prompt_' . $action,
		'gbvc_review_prompt_nonce'
	);
}

// Handle the lightweight review prompt actions on the settings page.
function gbvc_handle_review_prompt_action(): void {
	if ( ! current_user_can( 'manage_options' ) || empty( $_GET['gbvc_review_prompt'] ) ) {
		return;
	}

	$action = sanitize_key( wp_unslash( $_GET['gbvc_review_prompt'] ) );

	if ( ! in_array( $action, array( 'later', 'dismiss' ), true ) ) {
		return;
	}

	check_admin_referer( 'gbvc_review_prompt_' . $action, 'gbvc_review_prompt_nonce' );

	if ( 'later' === $action ) {
		update_option( 'gbvc_review_prompt_later_until', current_time( 'timestamp' ) + ( 30 * DAY_IN_SECONDS ) );
	}

	if ( 'dismiss' === $action ) {
		update_option( 'gbvc_review_prompt_dismissed', '1' );
	}

	wp_safe_redirect( admin_url( 'options-general.php?page=gbvc-settings' ) );
	exit;
}

add_action( 'admin_init', 'gbvc_handle_review_prompt_action' );

// Decide if the review prompt should be visible on the settings page.
function gbvc_should_show_review_prompt(): bool {
	if ( get_option( 'gbvc_review_prompt_dismissed', false ) ) {
		return false;
	}

	$eligible_after = absint( get_option( 'gbvc_review_prompt_eligible_after', 0 ) );

	if ( ! $eligible_after ) {
		$eligible_after = current_time( 'timestamp' ) + GBVC_REVIEW_PROMPT_DELAY;
		add_option( 'gbvc_review_prompt_eligible_after', $eligible_after );
	}

	if ( current_time( 'timestamp' ) < $eligible_after ) {
		return false;
	}

	$later_until = absint( get_option( 'gbvc_review_prompt_later_until', 0 ) );

	return ! $later_until || current_time( 'timestamp' ) >= $later_until;
}

// Render the settings page
function gbvc_render_settings_page(): void {
	?>
    <div class="wrap gbvc-settings-page">
        <style>
            .gbvc-settings-page .gbvc-settings-layout {
                display: grid;
                grid-template-columns: minmax(0, 1fr) 360px;
                gap: 32px;
                align-items: start;
            }

            .gbvc-settings-page .gbvc-settings-main {
                min-width: 0;
            }

            .gbvc-settings-page .gbvc-settings-sidebar {
                display: grid;
                gap: 16px;
                margin-top: 16px;
            }

            .gbvc-settings-page .gbvc-card {
                background: #fff;
                border: 1px solid #c3c4c7;
                box-shadow: 0 1px 1px rgba(0, 0, 0, 0.04);
                padding: 18px;
            }

            .gbvc-settings-page .gbvc-card h2 {
                margin: 0 0 8px;
                font-size: 16px;
                line-height: 1.35;
            }

            .gbvc-settings-page .gbvc-card p {
                margin: 0 0 14px;
                color: #646970;
            }

            .gbvc-settings-page .gbvc-video {
                width: 100%;
                aspect-ratio: 16 / 9;
                margin: 12px 0 14px;
                border: 0;
            }

            .gbvc-settings-page .gbvc-card-actions {
                display: flex;
                flex-wrap: wrap;
                gap: 8px 12px;
                align-items: center;
            }

            .gbvc-settings-page .gbvc-card-actions .button-link {
                min-height: 30px;
                line-height: 30px;
                text-decoration: none;
            }

            .gbvc-settings-page .gbvc-review-notice {
                max-width: 1420px;
            }

            .gbvc-settings-page .gbvc-review-notice p {
                max-width: 900px;
            }

            .gbvc-settings-page .gbvc-review-notice .gbvc-card-actions {
                margin: 8px 0 4px;
            }

            .gbvc-settings-page .gbvc-support-card {
                border-left: 4px solid #2271b1;
            }

            @media (max-width: 1100px) {
                .gbvc-settings-page .gbvc-settings-layout {
                    grid-template-columns: 1fr;
                }

                .gbvc-settings-page .gbvc-settings-sidebar {
                    max-width: 720px;
                }
            }
        </style>

        <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

		<?php if ( gbvc_should_show_review_prompt() ) : ?>
            <div class="notice notice-info gbvc-review-notice">
                <p>
                    <strong><?php esc_html_e( 'Enjoying Visibility Controls?', 'visibility-controls-for-editor-blocks' ); ?></strong>
					<?php esc_html_e( 'If this plugin saves you time, a quick review on WordPress.org helps other Gutenberg users find it.', 'visibility-controls-for-editor-blocks' ); ?>
                </p>
                <div class="gbvc-card-actions">
                    <a href="https://wordpress.org/support/plugin/visibility-controls-for-editor-blocks/reviews/#new-post" target="_blank"
                       class="button button-primary" rel="noopener noreferrer">
						<?php esc_html_e( 'Leave a Review', 'visibility-controls-for-editor-blocks' ); ?>
                    </a>
                    <a class="button-link" href="<?php echo esc_url( gbvc_get_review_prompt_action_url( 'later' ) ); ?>">
						<?php esc_html_e( 'Maybe later', 'visibility-controls-for-editor-blocks' ); ?>
                    </a>
                    <a class="button-link" href="<?php echo esc_url( gbvc_get_review_prompt_action_url( 'dismiss' ) ); ?>">
						<?php esc_html_e( 'Do not ask again', 'visibility-controls-for-editor-blocks' ); ?>
                    </a>
                </div>
            </div>
		<?php endif; ?>

        <div class="gbvc-settings-layout">
            <div class="gbvc-settings-main">
                <form method="post" action="options.php">
				<?php
				settings_fields( 'gbvc_settings_group' );
				do_settings_sections( 'gbvc-breakpoints-settings' );
				do_settings_sections( 'gbvc-advanced-settings' );
				submit_button();
				?>
                </form>
            </div>

            <aside class="gbvc-settings-sidebar">
                <div class="gbvc-card">
                    <h2><?php esc_html_e( 'Video guide', 'visibility-controls-for-editor-blocks' ); ?></h2>
                    <p><?php esc_html_e( 'Watch short tutorials for setting up block visibility rules.', 'visibility-controls-for-editor-blocks' ); ?></p>
                    <iframe class="gbvc-video"
                        src="https://www.youtube.com/embed/videoseries?si=gJZVVq6U8em0kqsR&amp;list=PLUo5dzT4ZLuPG_2Pproj_kK_-WtHNJwzY"
                        title="YouTube video player" frameborder="0"
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                        referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe>
                    <a class="button button-secondary" href="https://youtube.com/playlist?list=PLUo5dzT4ZLuPG_2Pproj_kK_-WtHNJwzY" target="_blank" rel="noopener noreferrer">
						<?php esc_html_e( 'Open video playlist', 'visibility-controls-for-editor-blocks' ); ?>
                    </a>
                </div>

                <div class="gbvc-card gbvc-support-card">
                    <h2><?php esc_html_e( 'Support the plugin', 'visibility-controls-for-editor-blocks' ); ?></h2>
                    <p><?php esc_html_e( 'Want to support future updates? You can buy me a coffee.', 'visibility-controls-for-editor-blocks' ); ?></p>
                    <a class="button button-secondary" href="https://buymeacoffee.com/denisdoroshchuk" target="_blank" rel="noopener noreferrer">
						<?php esc_html_e( 'Buy me a coffee', 'visibility-controls-for-editor-blocks' ); ?>
                    </a>
                </div>
            </aside>
        </div>
    </div>
	<?php
}

// Register the settings
function gbvc_register_settings(): void {
	// Register settings group
	register_setting( 'gbvc_settings_group', 'gbvc_mobile_breakpoint', array( 'sanitize_callback' => 'gbvc_sanitize_input_number' ) );
	register_setting( 'gbvc_settings_group', 'gbvc_tablet_breakpoint', array( 'sanitize_callback' => 'gbvc_sanitize_input_number' ) );
	register_setting( 'gbvc_settings_group', 'gbvc_disable_styles_on_non_gutenberg_pages', array( 'sanitize_callback' => 'gbvc_sanitize_checkbox' ) );

	// Breakpoints settings section
	add_settings_section( 'gbvc_breakpoints_settings_section', __( 'Breakpoints Settings', 'visibility-controls-for-editor-blocks' ), 'gbvc_breakpoints_settings_section_callback', 'gbvc-breakpoints-settings' );

	// Field for mobile breakpoint
	add_settings_field( 'gbvc_mobile_breakpoint', __( 'Mobile Breakpoint (px)', 'visibility-controls-for-editor-blocks' ), 'gbvc_mobile_breakpoint_callback', 'gbvc-breakpoints-settings', 'gbvc_breakpoints_settings_section' );

	// Field for tablet breakpoint
	add_settings_field( 'gbvc_tablet_breakpoint', __( 'Tablet Breakpoint (px)', 'visibility-controls-for-editor-blocks' ), 'gbvc_tablet_breakpoint_callback', 'gbvc-breakpoints-settings', 'gbvc_breakpoints_settings_section' );

	// Field for desktop breakpoint
	add_settings_field( 'gbvc_desktop_breakpoint', __( 'Desktop Breakpoint (px)', 'visibility-controls-for-editor-blocks' ), 'gbvc_desktop_breakpoint_callback', 'gbvc-breakpoints-settings', 'gbvc_breakpoints_settings_section' );

	// Advanced settings section
	add_settings_section( 'gbvc_advanced_settings_section', __( 'Advanced Settings', 'visibility-controls-for-editor-blocks' ), 'gbvc_advanced_settings_section_callback', 'gbvc-advanced-settings' );

	// Field for disable styles on non gutenberg pages
	add_settings_field( 'gbvc_desktop_breakpoint', __( 'Styles loading', 'visibility-controls-for-editor-blocks' ), 'gbvc_disable_styles_on_non_gutenberg_pages_callback', 'gbvc-advanced-settings', 'gbvc_advanced_settings_section' );
}

add_action( 'admin_init', 'gbvc_register_settings' );

// Callback for sanitize input number
function gbvc_sanitize_input_number( $input ): int {
	return absint( $input );
}

// Callback for sanitize checkbox
function gbvc_sanitize_checkbox( $input ): bool {
	return isset( $input ) && $input;
}

// Callback for the Breakpoints settings section
function gbvc_breakpoints_settings_section_callback(): void {
	echo esc_html__( 'Configure the breakpoints for mobile, tablet and desktop devices.', 'visibility-controls-for-editor-blocks' );
}

// Callback for the Advanced settings section
function gbvc_advanced_settings_section_callback(): void {
	echo '';
}

// Callback for disable styles on non gutenberg pages checkbox field
function gbvc_disable_styles_on_non_gutenberg_pages_callback(): void {
	$value = get_option( 'gbvc_disable_styles_on_non_gutenberg_pages', false );
	echo '<input type="checkbox" name="gbvc_disable_styles_on_non_gutenberg_pages" value="1"' . checked( 1, $value, false ) . '/>';
	echo '<label for="gbvc_disable_styles_on_non_gutenberg_pages">' . esc_html__( 'Disable CSS loading on pages without Gutenberg', 'visibility-controls-for-editor-blocks' ) . '</label>';
	echo '<p class="description"><small>' . esc_html__( 'When this option is enabled, the plugin will prevent loading its CSS code on pages that do not use the Gutenberg editor.', 'visibility-controls-for-editor-blocks' ) . '<br>' . esc_html__( 'This includes archive pages such as categories and tags, as well as other pages that do not contain Gutenberg blocks.', 'visibility-controls-for-editor-blocks' ) . '</small></p>';
}

// Callback for mobile breakpoint input field
function gbvc_mobile_breakpoint_callback(): void {
	$value = get_option( 'gbvc_mobile_breakpoint', '600' );
	echo '<input type="number" name="gbvc_mobile_breakpoint" value="' . esc_attr( $value ) . '" /> px';
	echo '<p class="description"><small>' . esc_html__( 'This option defines the screen width (in pixels) that determines a mobile device.', 'visibility-controls-for-editor-blocks' ) . '<br>' . esc_html__( 'Blocks hidden on mobile will be hidden on screens this size or smaller.', 'visibility-controls-for-editor-blocks' ) . '<br><strong>' . esc_html__( 'Recommended: between 320px and 600px for mobiles.', 'visibility-controls-for-editor-blocks' ) . '</strong></small></p>';
}

// Callback for tablet breakpoint input field
function gbvc_tablet_breakpoint_callback(): void {
	$value = get_option( 'gbvc_tablet_breakpoint', '1024' );
	echo '<input type="number" name="gbvc_tablet_breakpoint" value="' . esc_attr( $value ) . '" /> px';
	echo '<p class="description"><small>' . esc_html__( 'This option defines the screen width (in pixels) that determines a tablet device.', 'visibility-controls-for-editor-blocks' ) . '<br>' . esc_html__( 'Blocks hidden on tablets will be hidden on screens this size or smaller.', 'visibility-controls-for-editor-blocks' ) . '<br><strong>' . esc_html__( 'Recommended: between 601px and 1024px for tablets.', 'visibility-controls-for-editor-blocks' ) . '</strong></small></p>';
}

// Callback for desktop breakpoint input field
function gbvc_desktop_breakpoint_callback(): void {
	echo '<p class="description"><small><strong>' . esc_html__( 'Note: ', 'visibility-controls-for-editor-blocks' ) . '</strong>' . esc_html__( 'There\'s no input for the desktop breakpoint because it is automatically defined', 'visibility-controls-for-editor-blocks' ) . '<br>' . esc_html__( 'Any screen wider than the tablet breakpoint will be considered a desktop.', 'visibility-controls-for-editor-blocks' ) . '</small></p>';
}

// Add settings link on the plugins page
function gbvc_add_settings_link( $links ) {
	$settings_link = '<a href="options-general.php?page=gbvc-settings">' . esc_html__( 'Settings', 'visibility-controls-for-editor-blocks' ) . '</a>';
	array_unshift( $links, $settings_link );

	$links[] = '<a href="https://buymeacoffee.com/denisdoroshchuk" target="_blank">' . esc_html__( 'Donate', 'visibility-controls-for-editor-blocks' ) . '</a>';

	return $links;
}

add_filter( 'plugin_action_links_' . plugin_basename( GBVC_PLUGIN_FILE ), 'gbvc_add_settings_link' );

// Get the CSS classes for the current visibility settings.
function gbvc_get_visibility_classes( array $attributes ): array {
	$classes = array();

	if ( ! empty( $attributes['hideOnMobile'] ) ) {
		$classes[] = 'gbvc-hide-on-mobile';
	}

	if ( ! empty( $attributes['hideOnTablet'] ) ) {
		$classes[] = 'gbvc-hide-on-tablet';
	}

	if ( ! empty( $attributes['hideOnDesktop'] ) ) {
		$classes[] = 'gbvc-hide-on-desktop';
	}

	if ( ! empty( $attributes['hideForLoggedInUsers'] ) ) {
		$classes[] = 'gbvc-hide-for-logged-in';
	}

	if ( ! empty( $attributes['hideForNonLoggedInUsers'] ) ) {
		$classes[] = 'gbvc-hide-for-non-logged-in';
	}

	return $classes;
}

// Check if a block is rendered on the server.
function gbvc_is_dynamic_block( string $block_name ): bool {
	$registered_block = WP_Block_Type_Registry::get_instance()->get_registered( $block_name );

	return $registered_block instanceof WP_Block_Type && $registered_block->is_dynamic();
}

// Add visibility classes to the first HTML tag in a block's rendered content.
function gbvc_add_classes_to_block_content( string $block_content, array $classes ): string {
	if ( empty( $block_content ) || empty( $classes ) || ! class_exists( 'WP_HTML_Tag_Processor' ) ) {
		return $block_content;
	}

	$processor = new WP_HTML_Tag_Processor( $block_content );

	if ( ! $processor->next_tag() ) {
		return $block_content;
	}

	foreach ( $classes as $class_name ) {
		$processor->add_class( $class_name );
	}

	return $processor->get_updated_html();
}

// Add visibility classes to dynamic blocks.
function gbvc_add_visibility_classes( $block_content, $block ) {
	if ( empty( $block['blockName'] ) || empty( $block['attrs'] ) || ! is_array( $block['attrs'] ) ) {
		return $block_content;
	}

	$classes = gbvc_get_visibility_classes( $block['attrs'] );

	if ( empty( $classes ) || ! gbvc_is_dynamic_block( $block['blockName'] ) ) {
		return $block_content;
	}

	return gbvc_add_classes_to_block_content( $block_content, $classes );
}

add_filter( 'render_block', 'gbvc_add_visibility_classes', 10, 2 );
