import { PanelBody, ToggleControl, Button } from '@wordpress/components';
import { InspectorControls } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';

const settingsPageUrl = window.gbvcEditorData?.settingsPageUrl;

export default function VisibilityPanel( { attributes, setAttributes } ) {
	const {
		hideOnMobile,
		hideOnTablet,
		hideOnDesktop,
		hideForLoggedInUsers,
		hideForNonLoggedInUsers,
	} = attributes;

	return (
		<InspectorControls>
			<PanelBody
				title={ __(
					'Visibility Settings',
					'visibility-controls-for-editor-blocks'
				) }
			>
				<ToggleControl
					label={ __(
						'Hide on Mobile',
						'visibility-controls-for-editor-blocks'
					) }
					checked={ hideOnMobile }
					onChange={ ( value ) =>
						setAttributes( { hideOnMobile: value } )
					}
				/>
				<ToggleControl
					label={ __(
						'Hide on Tablet',
						'visibility-controls-for-editor-blocks'
					) }
					checked={ hideOnTablet }
					onChange={ ( value ) =>
						setAttributes( { hideOnTablet: value } )
					}
				/>
				<ToggleControl
					label={ __(
						'Hide on Desktop',
						'visibility-controls-for-editor-blocks'
					) }
					checked={ hideOnDesktop }
					onChange={ ( value ) =>
						setAttributes( { hideOnDesktop: value } )
					}
				/>
				<ToggleControl
					label={ __(
						'Hide for Logged-in Users',
						'visibility-controls-for-editor-blocks'
					) }
					checked={ hideForLoggedInUsers }
					onChange={ ( value ) =>
						setAttributes( { hideForLoggedInUsers: value } )
					}
				/>
				<ToggleControl
					label={ __(
						'Hide for Non-Logged-in Users',
						'visibility-controls-for-editor-blocks'
					) }
					checked={ hideForNonLoggedInUsers }
					onChange={ ( value ) =>
						setAttributes( { hideForNonLoggedInUsers: value } )
					}
				/>
				{ settingsPageUrl && (
					<div style={ { marginTop: '10px' } }>
						<Button
							isSecondary
							href={ settingsPageUrl }
							target="_blank"
							rel="noopener noreferrer"
						>
							{ __(
								'Configure Breakpoints',
								'visibility-controls-for-editor-blocks'
							) }
						</Button>
					</div>
				) }
			</PanelBody>
		</InspectorControls>
	);
}
