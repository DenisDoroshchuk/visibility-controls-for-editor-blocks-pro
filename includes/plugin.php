<?php

// Delete plugin options when Freemius completes the uninstall flow.
function gbvc_delete_options(): void {
	delete_option( 'gbvc_mobile_breakpoint' );
	delete_option( 'gbvc_tablet_breakpoint' );
	delete_option( 'gbvc_disable_styles_on_non_gutenberg_pages' );
	delete_option( 'gbvc_pro_rule_processing_mode' );
	delete_option( 'gbvc_schedule_date_format' );
	delete_option( 'gbvc_visible_user_roles' );
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

// Check whether premium functionality can run on the current site.
function gbvc_can_use_premium_features(): bool {
	return function_exists( 'gbvc_fs' ) && gbvc_fs()->can_use_premium_code();
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
				'settingsPageUrl'        => esc_url_raw( admin_url( 'options-general.php?page=gbvc-settings' ) ),
				'userRoles'              => gbvc_can_use_premium_features() ? gbvc_get_user_role_choices() : array(),
				'proRuleProcessingMode'  => gbvc_get_pro_rule_processing_mode(),
				'scheduleDateFormat'     => gbvc_get_schedule_date_format(),
				'scheduleDateExample'    => gbvc_get_schedule_date_format_example(),
				'isPremiumBuild'         => true,
				'canUseProFeatures'      => gbvc_can_use_premium_features(),
			)
		) . ' );',
		'before'
	);

	wp_set_script_translations( 'gbvc-editor', 'visibility-controls-for-editor-blocks', plugin_dir_path( GBVC_PLUGIN_FILE ) . 'languages' );
}

add_action( 'enqueue_block_editor_assets', 'gbvc_enqueue_block_editor_assets' );

// Get all available user roles.
function gbvc_get_all_user_role_choices(): array {
	if ( ! function_exists( 'wp_roles' ) ) {
		return array();
	}

	$roles = array();

	foreach ( wp_roles()->roles as $role_slug => $role ) {
		$roles[] = array(
			'slug' => sanitize_key( $role_slug ),
			'name' => translate_user_role( $role['name'] ),
		);
	}

	return $roles;
}

// Get role slugs selected for display in the Pro editor controls.
function gbvc_get_visible_user_role_slugs(): array {
	$all_roles      = wp_list_pluck( gbvc_get_all_user_role_choices(), 'slug' );
	$selected_roles = get_option( 'gbvc_visible_user_roles', false );

	if ( false === $selected_roles ) {
		return $all_roles;
	}

	if ( ! is_array( $selected_roles ) ) {
		return array();
	}

	return array_values( array_intersect( array_map( 'sanitize_key', $selected_roles ), $all_roles ) );
}

// Get available user roles for the Pro role visibility controls.
function gbvc_get_user_role_choices(): array {
	$visible_roles = gbvc_get_visible_user_role_slugs();

	return array_values(
		array_filter(
			gbvc_get_all_user_role_choices(),
			static function ( array $role ) use ( $visible_roles ): bool {
				return in_array( $role['slug'], $visible_roles, true );
			}
		)
	);
}

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

// Get the selected settings tab.
function gbvc_get_settings_tab(): string {
	$tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'general';

	return in_array( $tab, array( 'general', 'pro' ), true ) ? $tab : 'general';
}

// Render the settings page
function gbvc_render_settings_page(): void {
	$current_tab = gbvc_get_settings_tab();
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

            .gbvc-settings-page .nav-tab-wrapper {
                margin-bottom: 20px;
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

        <nav class="nav-tab-wrapper" aria-label="<?php esc_attr_e( 'Settings tabs', 'visibility-controls-for-editor-blocks' ); ?>">
            <a href="<?php echo esc_url( admin_url( 'options-general.php?page=gbvc-settings&tab=general' ) ); ?>"
               class="nav-tab <?php echo 'general' === $current_tab ? 'nav-tab-active' : ''; ?>">
				<?php esc_html_e( 'General', 'visibility-controls-for-editor-blocks' ); ?>
            </a>
            <a href="<?php echo esc_url( admin_url( 'options-general.php?page=gbvc-settings&tab=pro' ) ); ?>"
               class="nav-tab <?php echo 'pro' === $current_tab ? 'nav-tab-active' : ''; ?>">
				<?php esc_html_e( 'Pro', 'visibility-controls-for-editor-blocks' ); ?>
            </a>
        </nav>

        <div class="gbvc-settings-layout">
            <div class="gbvc-settings-main">
				<?php
				if ( 'pro' === $current_tab && ! gbvc_can_use_premium_features() ) {
					$activation_url        = '';
					$activation_link_class = 'button button-primary';

					if ( function_exists( 'gbvc_fs' ) ) {
						$activation_url        = gbvc_fs()->get_account_url( false, array( 'activate_license' => 'true' ) );
						$activation_link_class .= ' activate-license-trigger ' . gbvc_fs()->get_unique_affix();
						add_action( 'admin_footer', array( gbvc_fs(), '_add_license_activation_dialog_box' ) );
					}
					?>
					<div class="notice notice-warning inline">
						<p>
							<strong><?php esc_html_e( 'Pro features are locked on this site.', 'visibility-controls-for-editor-blocks' ); ?></strong>
							<?php esc_html_e( 'Activate a valid license or trial to use role visibility, scheduled display, and URL/query visibility rules.', 'visibility-controls-for-editor-blocks' ); ?>
						</p>
						<?php if ( $activation_url ) : ?>
							<p>
								<a class="<?php echo esc_attr( $activation_link_class ); ?>" href="<?php echo esc_url( $activation_url ); ?>">
									<?php esc_html_e( 'Activate License', 'visibility-controls-for-editor-blocks' ); ?>
								</a>
							</p>
						<?php endif; ?>
					</div>
					<?php
				} else {
					?>
					<form method="post" action="options.php">
					<?php
					if ( 'pro' === $current_tab ) {
						settings_fields( 'gbvc_pro_settings_group' );
						do_settings_sections( 'gbvc-pro-settings' );
					} else {
						settings_fields( 'gbvc_general_settings_group' );
						do_settings_sections( 'gbvc-breakpoints-settings' );
						do_settings_sections( 'gbvc-advanced-settings' );
					}
					submit_button();
					?>
					</form>
					<?php
				}
				?>
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

            </aside>
        </div>
    </div>
	<?php
}

// Register the settings
function gbvc_register_settings(): void {
	// Register settings group
	register_setting( 'gbvc_general_settings_group', 'gbvc_mobile_breakpoint', array( 'sanitize_callback' => 'gbvc_sanitize_input_number' ) );
	register_setting( 'gbvc_general_settings_group', 'gbvc_tablet_breakpoint', array( 'sanitize_callback' => 'gbvc_sanitize_input_number' ) );
	register_setting( 'gbvc_general_settings_group', 'gbvc_disable_styles_on_non_gutenberg_pages', array( 'sanitize_callback' => 'gbvc_sanitize_checkbox' ) );
	register_setting( 'gbvc_pro_settings_group', 'gbvc_pro_rule_processing_mode', array( 'sanitize_callback' => 'gbvc_sanitize_pro_rule_processing_mode' ) );
	register_setting( 'gbvc_pro_settings_group', 'gbvc_schedule_date_format', array( 'sanitize_callback' => 'gbvc_sanitize_schedule_date_format' ) );
	register_setting( 'gbvc_pro_settings_group', 'gbvc_visible_user_roles', array( 'sanitize_callback' => 'gbvc_sanitize_visible_user_roles' ) );

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

	// Pro settings section
	add_settings_section( 'gbvc_pro_settings_section', __( 'Visibility Settings Pro', 'visibility-controls-for-editor-blocks' ), 'gbvc_pro_settings_section_callback', 'gbvc-pro-settings' );

	// Field for Pro rule processing mode
	add_settings_field( 'gbvc_pro_rule_processing_mode', __( 'Rule processing mode', 'visibility-controls-for-editor-blocks' ), 'gbvc_pro_rule_processing_mode_callback', 'gbvc-pro-settings', 'gbvc_pro_settings_section' );

	// Field for schedule date format
	add_settings_field( 'gbvc_schedule_date_format', __( 'Schedule date format', 'visibility-controls-for-editor-blocks' ), 'gbvc_schedule_date_format_callback', 'gbvc-pro-settings', 'gbvc_pro_settings_section' );

	// Field for role visibility controls
	add_settings_field( 'gbvc_visible_user_roles', __( 'Displayed user roles', 'visibility-controls-for-editor-blocks' ), 'gbvc_visible_user_roles_callback', 'gbvc-pro-settings', 'gbvc_pro_settings_section' );
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

// Get the selected Pro rule processing mode.
function gbvc_get_pro_rule_processing_mode(): string {
	$mode = get_option( 'gbvc_pro_rule_processing_mode', 'server' );

	return in_array( $mode, array( 'server', 'frontend' ), true ) ? $mode : 'server';
}

// Callback for sanitize Pro rule processing mode.
function gbvc_sanitize_pro_rule_processing_mode( $input ): string {
	return in_array( $input, array( 'server', 'frontend' ), true ) ? $input : 'server';
}

// Get popular date formats for scheduled visibility controls.
function gbvc_get_schedule_date_format_choices(): array {
	return array(
		'Y-m-d H:i'   => array(
			'label'   => __( 'ISO style', 'visibility-controls-for-editor-blocks' ),
			'example' => '2026-05-06 15:37',
		),
		'd/m/Y H:i'   => array(
			'label'   => __( 'Day / Month / Year', 'visibility-controls-for-editor-blocks' ),
			'example' => '06/05/2026 15:37',
		),
		'm/d/Y h:i A' => array(
			'label'   => __( 'Month / Day / Year', 'visibility-controls-for-editor-blocks' ),
			'example' => '05/06/2026 03:37 PM',
		),
		'd.m.Y H:i'   => array(
			'label'   => __( 'European dotted', 'visibility-controls-for-editor-blocks' ),
			'example' => '06.05.2026 15:37',
		),
		'M j, Y H:i'  => array(
			'label'   => __( 'Written month', 'visibility-controls-for-editor-blocks' ),
			'example' => 'May 6, 2026 15:37',
		),
	);
}

// Get the selected schedule date format.
function gbvc_get_schedule_date_format(): string {
	$format  = get_option( 'gbvc_schedule_date_format', 'Y-m-d H:i' );
	$choices = gbvc_get_schedule_date_format_choices();

	return isset( $choices[ $format ] ) ? $format : 'Y-m-d H:i';
}

// Get a human-readable example for the selected schedule date format.
function gbvc_get_schedule_date_format_example(): string {
	$choices = gbvc_get_schedule_date_format_choices();
	$format  = gbvc_get_schedule_date_format();

	return $choices[ $format ]['example'];
}

// Callback for sanitize schedule date format.
function gbvc_sanitize_schedule_date_format( $input ): string {
	$choices = gbvc_get_schedule_date_format_choices();

	return isset( $choices[ $input ] ) ? $input : 'Y-m-d H:i';
}

// Callback for sanitize visible user roles.
function gbvc_sanitize_visible_user_roles( $input ): array {
	if ( ! is_array( $input ) ) {
		return array();
	}

	$available_roles = wp_list_pluck( gbvc_get_all_user_role_choices(), 'slug' );
	$selected_roles  = array_filter( array_map( 'sanitize_key', $input ) );

	return array_values( array_intersect( $selected_roles, $available_roles ) );
}

// Callback for the Breakpoints settings section
function gbvc_breakpoints_settings_section_callback(): void {
	echo esc_html__( 'Configure the breakpoints for mobile, tablet and desktop devices.', 'visibility-controls-for-editor-blocks' );
}

// Callback for the Advanced settings section
function gbvc_advanced_settings_section_callback(): void {
	echo '';
}

// Callback for the Pro settings section
function gbvc_pro_settings_section_callback(): void {
	echo esc_html__( 'Configure premium visibility behavior and choose which user roles are shown in the block editor.', 'visibility-controls-for-editor-blocks' );
}

// Callback for disable styles on non gutenberg pages checkbox field
function gbvc_disable_styles_on_non_gutenberg_pages_callback(): void {
	$value = get_option( 'gbvc_disable_styles_on_non_gutenberg_pages', false );
	echo '<input type="checkbox" name="gbvc_disable_styles_on_non_gutenberg_pages" value="1"' . checked( 1, $value, false ) . '/>';
	echo '<label for="gbvc_disable_styles_on_non_gutenberg_pages">' . esc_html__( 'Disable CSS loading on pages without Gutenberg', 'visibility-controls-for-editor-blocks' ) . '</label>';
	echo '<p class="description"><small>' . esc_html__( 'When this option is enabled, the plugin will prevent loading its CSS code on pages that do not use the Gutenberg editor.', 'visibility-controls-for-editor-blocks' ) . '<br>' . esc_html__( 'This includes archive pages such as categories and tags, as well as other pages that do not contain Gutenberg blocks.', 'visibility-controls-for-editor-blocks' ) . '</small></p>';
}

// Callback for Pro rule processing mode.
function gbvc_pro_rule_processing_mode_callback(): void {
	$value = gbvc_get_pro_rule_processing_mode();
	?>
	<fieldset>
		<label>
			<input type="radio" name="gbvc_pro_rule_processing_mode" value="server" <?php checked( 'server', $value ); ?> />
			<strong><?php esc_html_e( 'Server-only (recommended)', 'visibility-controls-for-editor-blocks' ); ?></strong>
		</label>
		<p class="description">
			<small>
				<?php esc_html_e( 'All Pro rules are evaluated in PHP before the block HTML is sent to the browser. Use this mode for role-based visibility and any content that should not be exposed in the page source.', 'visibility-controls-for-editor-blocks' ); ?>
				<br>
				<?php esc_html_e( 'Note: full-page cache can keep an older version of scheduled or URL-parameter visibility until the cached page is refreshed.', 'visibility-controls-for-editor-blocks' ); ?>
				<br>
				<?php esc_html_e( 'Scheduled times are saved as exact moments using the editor browser timezone.', 'visibility-controls-for-editor-blocks' ); ?>
			</small>
		</p>

		<br>

		<label>
			<input type="radio" name="gbvc_pro_rule_processing_mode" value="frontend" <?php checked( 'frontend', $value ); ?> />
			<strong><?php esc_html_e( 'Frontend cache-friendly', 'visibility-controls-for-editor-blocks' ); ?></strong>
		</label>
		<p class="description">
			<small>
				<?php esc_html_e( 'Scheduled visibility and URL-parameter rules are evaluated in the visitor browser, which helps when a page is served from cache.', 'visibility-controls-for-editor-blocks' ); ?>
				<br>
				<?php esc_html_e( 'Role rules remain server-side for safety and require your cache to bypass or vary logged-in users. Do not use frontend mode for private or sensitive content because the block HTML may still exist in the cached page source.', 'visibility-controls-for-editor-blocks' ); ?>
			</small>
		</p>
	</fieldset>
	<?php
}

// Callback for schedule date format.
function gbvc_schedule_date_format_callback(): void {
	$value = gbvc_get_schedule_date_format();
	?>
	<select name="gbvc_schedule_date_format">
		<?php foreach ( gbvc_get_schedule_date_format_choices() as $format => $choice ) : ?>
			<option value="<?php echo esc_attr( $format ); ?>" <?php selected( $format, $value ); ?>>
				<?php echo esc_html( sprintf( '%1$s — %2$s', $choice['label'], $choice['example'] ) ); ?>
			</option>
		<?php endforeach; ?>
	</select>
	<p class="description">
		<small>
			<?php esc_html_e( 'Controls how Show from and Show until fields are displayed in the block editor. The plugin still saves an exact timestamp internally, so scheduled visibility does not depend on the WordPress timezone setting.', 'visibility-controls-for-editor-blocks' ); ?>
		</small>
	</p>
	<?php
}

// Callback for displayed user roles.
function gbvc_visible_user_roles_callback(): void {
	$roles         = gbvc_get_all_user_role_choices();
	$visible_roles = gbvc_get_visible_user_role_slugs();
	?>
	<fieldset>
		<input type="hidden" name="gbvc_visible_user_roles[]" value="" />

		<?php foreach ( $roles as $role ) : ?>
			<label style="display: block; margin-bottom: 8px;">
				<input type="checkbox"
					name="gbvc_visible_user_roles[]"
					value="<?php echo esc_attr( $role['slug'] ); ?>"
					<?php checked( in_array( $role['slug'], $visible_roles, true ) ); ?>
				/>
				<?php echo esc_html( $role['name'] ); ?>
				<code><?php echo esc_html( $role['slug'] ); ?></code>
			</label>
		<?php endforeach; ?>

		<p class="description">
			<small>
				<?php esc_html_e( 'Only selected roles will appear in the block editor role visibility control. Existing blocks keep working even if a role is hidden from this list.', 'visibility-controls-for-editor-blocks' ); ?>
			</small>
		</p>
	</fieldset>
	<?php
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

// Enqueue frontend assets for cache-friendly Pro visibility rules.
function gbvc_enqueue_pro_frontend_visibility_assets(): void {
	if ( is_admin() || ! gbvc_can_use_premium_features() || 'frontend' !== gbvc_get_pro_rule_processing_mode() ) {
		return;
	}

	wp_register_style( 'gbvc-pro-visibility', false, array(), '1.2.4' );
	wp_add_inline_style( 'gbvc-pro-visibility', '.gbvc-pro-hidden{display:none!important;}' );
	wp_enqueue_style( 'gbvc-pro-visibility' );

	wp_register_script( 'gbvc-pro-visibility', false, array(), '1.2.4', true );
	wp_add_inline_script(
		'gbvc-pro-visibility',
		<<<'JS'
(function () {
	function getRules(element) {
		try {
			return JSON.parse(element.getAttribute('data-gbvc-pro-rules') || '{}');
		} catch (error) {
			return {};
		}
	}

	function passesScheduleRule(rule) {
		if (!rule) {
			return true;
		}

		var now = Math.floor(Date.now() / 1000);

		if (rule.start && now < rule.start) {
			return false;
		}

		if (rule.end && now > rule.end) {
			return false;
		}

		return true;
	}

	function passesUrlParameterRule(rule) {
		if (!rule || !rule.name) {
			return true;
		}

		var params = new URLSearchParams(window.location.search);

		if (!params.has(rule.name)) {
			return false;
		}

		return !rule.value || params.get(rule.name) === rule.value;
	}

	function applyRules() {
		document.querySelectorAll('[data-gbvc-pro-rules]').forEach(function (element) {
			var rules = getRules(element);
			var isVisible = passesScheduleRule(rules.schedule) && passesUrlParameterRule(rules.get);

			element.classList.toggle('gbvc-pro-hidden', !isVisible);
		});
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', applyRules);
	} else {
		applyRules();
	}
}());
JS
	);
	wp_enqueue_script( 'gbvc-pro-visibility' );
}

add_action( 'wp_enqueue_scripts', 'gbvc_enqueue_pro_frontend_visibility_assets' );

// Add settings link on the plugins page
function gbvc_add_settings_link( $links ) {
	$settings_link = '<a href="options-general.php?page=gbvc-settings">' . esc_html__( 'Settings', 'visibility-controls-for-editor-blocks' ) . '</a>';
	array_unshift( $links, $settings_link );

	return $links;
}

add_filter( 'plugin_action_links_' . plugin_basename( GBVC_PLUGIN_FILE ), 'gbvc_add_settings_link' );

// Parse schedule date values as site-timezone timestamps for legacy blocks without saved timestamps.
function gbvc_parse_schedule_datetime_to_timestamp( string $value ): int {
	$value = trim( $value );

	if ( '' === $value ) {
		return 0;
	}

	$timezone = wp_timezone();
	$formats  = array_unique(
		array_merge(
			array( gbvc_get_schedule_date_format(), 'Y-m-d\TH:i' ),
			array_keys( gbvc_get_schedule_date_format_choices() )
		)
	);

	foreach ( $formats as $format ) {
		$date = DateTimeImmutable::createFromFormat( $format, $value, $timezone );

		if ( $date instanceof DateTimeImmutable ) {
			return $date->getTimestamp();
		}
	}

	try {
		$date = new DateTimeImmutable( $value, $timezone );
	} catch ( Exception $exception ) {
		return 0;
	}

	return $date->getTimestamp();
}

// Get a schedule timestamp, preferring the browser-timezone timestamp saved by the editor.
function gbvc_get_schedule_timestamp( $timestamp, string $legacy_value ): int {
	$timestamp = absint( $timestamp );

	return $timestamp ? $timestamp : gbvc_parse_schedule_datetime_to_timestamp( $legacy_value );
}

// Sanitize a URL parameter name while preserving case.
function gbvc_sanitize_url_parameter_name( $value ): string {
	return preg_replace( '/[^A-Za-z0-9_-]/', '', (string) $value );
}

// Extract Pro visibility rules from block attributes.
function gbvc_get_pro_visibility_rules( array $attributes ): array {
	$rules     = array();
	$role_rule = isset( $attributes['gbvcRoleRule'] ) ? sanitize_key( $attributes['gbvcRoleRule'] ) : 'none';
	$roles     = array();

	if ( ! empty( $attributes['gbvcUserRoles'] ) && is_array( $attributes['gbvcUserRoles'] ) ) {
		$roles = array_values( array_filter( array_map( 'sanitize_key', $attributes['gbvcUserRoles'] ) ) );
	}

	if ( in_array( $role_rule, array( 'show', 'hide' ), true ) && ! empty( $roles ) ) {
		$rules['role'] = array(
			'mode'  => $role_rule,
			'roles' => $roles,
		);
	}

	if ( ! empty( $attributes['gbvcScheduleEnabled'] ) ) {
		$start = isset( $attributes['gbvcScheduleStart'] ) ? sanitize_text_field( (string) $attributes['gbvcScheduleStart'] ) : '';
		$end   = isset( $attributes['gbvcScheduleEnd'] ) ? sanitize_text_field( (string) $attributes['gbvcScheduleEnd'] ) : '';

		$start_timestamp = gbvc_get_schedule_timestamp( $attributes['gbvcScheduleStartTimestamp'] ?? 0, $start );
		$end_timestamp   = gbvc_get_schedule_timestamp( $attributes['gbvcScheduleEndTimestamp'] ?? 0, $end );

		if ( $start_timestamp || $end_timestamp ) {
			$rules['schedule'] = array(
				'start' => $start_timestamp,
				'end'   => $end_timestamp,
			);
		}
	}

	if ( ! empty( $attributes['gbvcGetParamEnabled'] ) ) {
		$parameter_name = isset( $attributes['gbvcGetParamName'] ) ? gbvc_sanitize_url_parameter_name( $attributes['gbvcGetParamName'] ) : '';

		if ( '' !== $parameter_name ) {
			$rules['get'] = array(
				'name'  => $parameter_name,
				'value' => isset( $attributes['gbvcGetParamValue'] ) ? sanitize_text_field( (string) $attributes['gbvcGetParamValue'] ) : '',
			);
		}
	}

	return $rules;
}

// Check if the current user matches a role-based Pro rule.
function gbvc_current_user_matches_roles( array $roles ): bool {
	if ( ! is_user_logged_in() ) {
		return false;
	}

	$user = wp_get_current_user();

	if ( ! $user instanceof WP_User ) {
		return false;
	}

	return (bool) array_intersect( $roles, (array) $user->roles );
}

// Check if a Pro role rule should hide the block.
function gbvc_role_rule_hides_block( array $rules ): bool {
	if ( empty( $rules['role'] ) ) {
		return false;
	}

	$matches_role = gbvc_current_user_matches_roles( $rules['role']['roles'] );

	if ( 'show' === $rules['role']['mode'] ) {
		return ! $matches_role;
	}

	if ( 'hide' === $rules['role']['mode'] ) {
		return $matches_role;
	}

	return false;
}

// Check if a Pro schedule rule should hide the block.
function gbvc_schedule_rule_hides_block( array $rules ): bool {
	if ( empty( $rules['schedule'] ) ) {
		return false;
	}

	$now = current_time( 'timestamp' );

	if ( ! empty( $rules['schedule']['start'] ) && $now < $rules['schedule']['start'] ) {
		return true;
	}

	if ( ! empty( $rules['schedule']['end'] ) && $now > $rules['schedule']['end'] ) {
		return true;
	}

	return false;
}

// Check if a Pro URL parameter rule should hide the block.
function gbvc_get_parameter_rule_hides_block( array $rules ): bool {
	if ( empty( $rules['get']['name'] ) ) {
		return false;
	}

	$parameter_name = $rules['get']['name'];

	if ( ! isset( $_GET[ $parameter_name ] ) ) {
		return true;
	}

	$parameter_value = wp_unslash( $_GET[ $parameter_name ] );

	if ( is_array( $parameter_value ) ) {
		$parameter_value = reset( $parameter_value );
	}

	$parameter_value = sanitize_text_field( (string) $parameter_value );

	return '' !== $rules['get']['value'] && $parameter_value !== $rules['get']['value'];
}

// Get frontend-safe rules for cache-friendly evaluation.
function gbvc_get_frontend_pro_visibility_rules( array $rules ): array {
	return array_intersect_key(
		$rules,
		array(
			'schedule' => true,
			'get'      => true,
		)
	);
}

// Add HTML attributes to the first HTML tag in a block's rendered content.
function gbvc_add_attributes_to_block_content( string $block_content, array $attributes ): string {
	if ( empty( $block_content ) || empty( $attributes ) || ! class_exists( 'WP_HTML_Tag_Processor' ) ) {
		return $block_content;
	}

	$processor = new WP_HTML_Tag_Processor( $block_content );

	if ( ! $processor->next_tag() ) {
		return $block_content;
	}

	foreach ( $attributes as $attribute_name => $attribute_value ) {
		$processor->set_attribute( $attribute_name, $attribute_value );
	}

	return $processor->get_updated_html();
}

// Apply Pro visibility rules before regular responsive visibility classes are added.
function gbvc_apply_pro_visibility_rules( $block_content, $block ) {
	if ( ! gbvc_can_use_premium_features() ) {
		return $block_content;
	}

	if ( empty( $block['attrs'] ) || ! is_array( $block['attrs'] ) ) {
		return $block_content;
	}

	$rules = gbvc_get_pro_visibility_rules( $block['attrs'] );

	if ( empty( $rules ) ) {
		return $block_content;
	}

	if ( gbvc_role_rule_hides_block( $rules ) ) {
		return '';
	}

	if ( 'server' === gbvc_get_pro_rule_processing_mode() ) {
		if ( gbvc_schedule_rule_hides_block( $rules ) || gbvc_get_parameter_rule_hides_block( $rules ) ) {
			return '';
		}

		return $block_content;
	}

	$frontend_rules = gbvc_get_frontend_pro_visibility_rules( $rules );

	if ( empty( $frontend_rules ) ) {
		return $block_content;
	}

	return gbvc_add_attributes_to_block_content(
		$block_content,
		array(
			'data-gbvc-pro-rules' => wp_json_encode( $frontend_rules ),
		)
	);
}

add_filter( 'render_block', 'gbvc_apply_pro_visibility_rules', 8, 2 );

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
