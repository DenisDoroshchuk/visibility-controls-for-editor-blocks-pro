import { addFilter } from '@wordpress/hooks';
import { Fragment, cloneElement } from '@wordpress/element';
import VisibilityPanel from './controls/visibility-panel';
import { isUnsupportedBlock } from './utils/block-support';
import { __ } from '@wordpress/i18n';

const canUseProFeatures = Boolean( window.gbvcEditorData?.canUseProFeatures );

const visibilityAttributes = {
	hideOnMobile: { type: 'boolean', default: false },
	hideOnTablet: { type: 'boolean', default: false },
	hideOnDesktop: { type: 'boolean', default: false },
	hideForLoggedInUsers: { type: 'boolean', default: false },
	hideForNonLoggedInUsers: { type: 'boolean', default: false },
	gbvcRoleRule: { type: 'string', default: 'none' },
	gbvcUserRoles: { type: 'array', default: [] },
	gbvcScheduleEnabled: { type: 'boolean', default: false },
	gbvcScheduleStart: { type: 'string', default: '' },
	gbvcScheduleEnd: { type: 'string', default: '' },
	gbvcScheduleStartTimestamp: { type: 'number', default: 0 },
	gbvcScheduleEndTimestamp: { type: 'number', default: 0 },
	gbvcGetParamEnabled: { type: 'boolean', default: false },
	gbvcGetParamName: { type: 'string', default: '' },
	gbvcGetParamValue: { type: 'string', default: '' },
};

// Register custom attributes
addFilter(
	'blocks.registerBlockType',
	'gbvc/custom-attributes',
	( settings ) => {
		if ( isUnsupportedBlock( settings.name ) ) {
			return settings;
		}

		settings.attributes = {
			...( settings.attributes || {} ),
			...visibilityAttributes,
		};

		if ( settings.name !== 'core/block' ) {
			settings.__experimentalLabel = function ( attributes ) {
				const customLabel = attributes?.metadata?.name;
				const {
					hideOnMobile,
					hideOnTablet,
					hideOnDesktop,
					hideForLoggedInUsers,
					hideForNonLoggedInUsers,
					gbvcRoleRule,
					gbvcScheduleEnabled,
					gbvcGetParamEnabled,
				} = attributes;

				let label = '';
				if ( hideOnMobile ) {
					label += __(
						'Hidden on Mobile',
						'visibility-controls-for-editor-blocks'
					);
				}
				if ( hideOnTablet ) {
					label += label
						? __(
								'and Tablet',
								'visibility-controls-for-editor-blocks'
						  )
						: __(
								'Hidden on Tablet',
								'visibility-controls-for-editor-blocks'
						  );
				}
				if ( hideOnDesktop ) {
					label += label
						? __(
								'and Desktop',
								'visibility-controls-for-editor-blocks'
						  )
						: __(
								'Hidden on Desktop',
								'visibility-controls-for-editor-blocks'
						  );
				}
				if ( hideForLoggedInUsers ) {
					label += label
						? __(
								'and Logged-in Users',
								'visibility-controls-for-editor-blocks'
						  )
						: __(
								'Hidden for Logged-in Users',
								'visibility-controls-for-editor-blocks'
						  );
				}
				if ( hideForNonLoggedInUsers ) {
					label += label
						? __(
								'and Non-Logged-in Users',
								'visibility-controls-for-editor-blocks'
						  )
						: __(
								'Hidden for Non-Logged-in Users',
								'visibility-controls-for-editor-blocks'
						  );
				}
				if (
					canUseProFeatures &&
					( ( gbvcRoleRule && gbvcRoleRule !== 'none' ) ||
						gbvcScheduleEnabled ||
						gbvcGetParamEnabled )
				) {
					label += label
						? __(
								'and Pro Rules',
								'visibility-controls-for-editor-blocks'
						  )
						: __(
								'Pro Visibility Rules',
								'visibility-controls-for-editor-blocks'
						  );
				}

				const baseLabel = customLabel || settings.title;
				return label ? `${ baseLabel } (${ label })` : baseLabel;
			};
		}

		return settings;
	}
);

// Add Inspector Controls
addFilter(
	'editor.BlockEdit',
	'gbvc/custom-controls',
	( BlockEdit ) => ( props ) => {
		if ( isUnsupportedBlock( props.name ) ) {
			return <BlockEdit { ...props } />;
		}

		return (
			<Fragment>
				<BlockEdit { ...props } />
				<VisibilityPanel
					attributes={ props.attributes }
					setAttributes={ props.setAttributes }
				/>
			</Fragment>
		);
	}
);

// Add save-time classes
addFilter(
	'blocks.getSaveElement',
	'gbvc/custom-save-element',
	( element, blockType, attributes ) => {
		if ( isUnsupportedBlock( blockType.name ) ) {
			return element;
		}

		const {
			hideOnMobile,
			hideOnTablet,
			hideOnDesktop,
			hideForLoggedInUsers,
			hideForNonLoggedInUsers,
		} = attributes;

		let classes = '';
		if ( hideOnMobile ) {
			classes += ' gbvc-hide-on-mobile';
		}
		if ( hideOnTablet ) {
			classes += ' gbvc-hide-on-tablet';
		}
		if ( hideOnDesktop ) {
			classes += ' gbvc-hide-on-desktop';
		}
		if ( hideForLoggedInUsers ) {
			classes += ' gbvc-hide-for-logged-in';
		}
		if ( hideForNonLoggedInUsers ) {
			classes += ' gbvc-hide-for-non-logged-in';
		}

		if ( classes && element?.props ) {
			const existing = element.props.className || '';
			return cloneElement( element, {
				className: `${ existing } ${ classes }`.trim(),
			} );
		}

		return element;
	}
);

// Add editor-time classes
addFilter(
	'editor.BlockListBlock',
	'gbvc/custom-editor-class',
	( BlockListBlock ) => ( props ) => {
		if ( isUnsupportedBlock( props.name ) ) {
			return <BlockListBlock { ...props } />;
		}

		const {
			hideOnMobile,
			hideOnTablet,
			hideOnDesktop,
			hideForLoggedInUsers,
			hideForNonLoggedInUsers,
		} = props.attributes;

		let classes = '';
		if ( hideOnMobile ) {
			classes += ' gbvc-hide-on-mobile';
		}
		if ( hideOnTablet ) {
			classes += ' gbvc-hide-on-tablet';
		}
		if ( hideOnDesktop ) {
			classes += ' gbvc-hide-on-desktop';
		}
		if ( hideForLoggedInUsers ) {
			classes += ' gbvc-hide-for-logged-in';
		}
		if ( hideForNonLoggedInUsers ) {
			classes += ' gbvc-hide-for-non-logged-in';
		}

		if ( classes ) {
			const existing = props.className || '';
			return (
				<BlockListBlock
					{ ...props }
					className={ `${ existing } ${ classes }`.trim() }
				/>
			);
		}

		return <BlockListBlock { ...props } />;
	}
);
