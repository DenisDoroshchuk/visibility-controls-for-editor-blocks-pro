import {
	PanelBody,
	ToggleControl,
	Button,
	SelectControl,
	TextControl,
	CheckboxControl,
	DateTimePicker,
	Dropdown,
} from '@wordpress/components';
import { InspectorControls } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';

const settingsPageUrl = window.gbvcEditorData?.settingsPageUrl;
const userRoles = window.gbvcEditorData?.userRoles || [];
const proRuleProcessingMode =
	window.gbvcEditorData?.proRuleProcessingMode || 'server';
const scheduleDateFormat =
	window.gbvcEditorData?.scheduleDateFormat || 'Y-m-d H:i';
const scheduleDateExample =
	window.gbvcEditorData?.scheduleDateExample || '2026-05-06 15:37';
const canUseProFeatures = Boolean( window.gbvcEditorData?.canUseProFeatures );

const monthNames = [
	'Jan',
	'Feb',
	'Mar',
	'Apr',
	'May',
	'Jun',
	'Jul',
	'Aug',
	'Sep',
	'Oct',
	'Nov',
	'Dec',
];

const pad = ( value ) => String( value ).padStart( 2, '0' );

const getTimestampFromParts = ( year, month, day, hour, minute ) => {
	const date = new Date( year, month - 1, day, hour, minute );

	return Number.isNaN( date.getTime() )
		? 0
		: Math.floor( date.getTime() / 1000 );
};

const parseFormattedDate = ( value ) => {
	const trimmedValue = value.trim();
	let match;

	if ( scheduleDateFormat === 'Y-m-d H:i' ) {
		match = trimmedValue.match(
			/^(\d{4})-(\d{1,2})-(\d{1,2})\s+(\d{1,2}):(\d{2})$/
		);

		if ( match ) {
			return getTimestampFromParts(
				Number( match[ 1 ] ),
				Number( match[ 2 ] ),
				Number( match[ 3 ] ),
				Number( match[ 4 ] ),
				Number( match[ 5 ] )
			);
		}
	}

	if ( scheduleDateFormat === 'd/m/Y H:i' ) {
		match = trimmedValue.match(
			/^(\d{1,2})\/(\d{1,2})\/(\d{4})\s+(\d{1,2}):(\d{2})$/
		);

		if ( match ) {
			return getTimestampFromParts(
				Number( match[ 3 ] ),
				Number( match[ 2 ] ),
				Number( match[ 1 ] ),
				Number( match[ 4 ] ),
				Number( match[ 5 ] )
			);
		}
	}

	if ( scheduleDateFormat === 'm/d/Y h:i A' ) {
		match = trimmedValue.match(
			/^(\d{1,2})\/(\d{1,2})\/(\d{4})\s+(\d{1,2}):(\d{2})\s*(AM|PM)$/i
		);

		if ( match ) {
			let hour = Number( match[ 4 ] ) % 12;

			if ( match[ 6 ].toUpperCase() === 'PM' ) {
				hour += 12;
			}

			return getTimestampFromParts(
				Number( match[ 3 ] ),
				Number( match[ 1 ] ),
				Number( match[ 2 ] ),
				hour,
				Number( match[ 5 ] )
			);
		}
	}

	if ( scheduleDateFormat === 'd.m.Y H:i' ) {
		match = trimmedValue.match(
			/^(\d{1,2})\.(\d{1,2})\.(\d{4})\s+(\d{1,2}):(\d{2})$/
		);

		if ( match ) {
			return getTimestampFromParts(
				Number( match[ 3 ] ),
				Number( match[ 2 ] ),
				Number( match[ 1 ] ),
				Number( match[ 4 ] ),
				Number( match[ 5 ] )
			);
		}
	}

	if ( scheduleDateFormat === 'M j, Y H:i' ) {
		match = trimmedValue.match(
			/^(Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)\s+(\d{1,2}),\s*(\d{4})\s+(\d{1,2}):(\d{2})$/i
		);

		if ( match ) {
			return getTimestampFromParts(
				Number( match[ 3 ] ),
				monthNames.findIndex(
					( month ) =>
						month.toLowerCase() === match[ 1 ].toLowerCase()
				) + 1,
				Number( match[ 2 ] ),
				Number( match[ 4 ] ),
				Number( match[ 5 ] )
			);
		}
	}

	return 0;
};

const formatTimestamp = ( timestamp ) => {
	if ( ! timestamp ) {
		return '';
	}

	const date = new Date( timestamp * 1000 );
	const hour12 = date.getHours() % 12 || 12;
	const replacements = {
		Y: date.getFullYear(),
		m: pad( date.getMonth() + 1 ),
		d: pad( date.getDate() ),
		H: pad( date.getHours() ),
		i: pad( date.getMinutes() ),
		h: pad( hour12 ),
		A: date.getHours() >= 12 ? 'PM' : 'AM',
		M: monthNames[ date.getMonth() ],
		j: date.getDate(),
	};

	return scheduleDateFormat.replace(
		/Y|m|d|H|i|h|A|M|j/g,
		( token ) => replacements[ token ]
	);
};

const getLocalTimestamp = ( value ) => {
	if ( ! value ) {
		return 0;
	}

	const parsedTimestamp = parseFormattedDate( value );

	if ( parsedTimestamp ) {
		return parsedTimestamp;
	}

	const timestamp = Date.parse( value );

	return Number.isNaN( timestamp ) ? 0 : Math.floor( timestamp / 1000 );
};

const getScheduleInputValue = ( value, timestamp ) => {
	if ( timestamp ) {
		return formatTimestamp( timestamp );
	}

	return value || '';
};

const getPickerDate = ( value, timestamp ) => {
	if ( timestamp ) {
		return new Date( timestamp * 1000 );
	}

	const parsedTimestamp = value ? getLocalTimestamp( value ) : 0;

	return parsedTimestamp ? new Date( parsedTimestamp * 1000 ) : new Date();
};

const getTimestampFromPickerValue = ( value ) => {
	const date = value instanceof Date ? value : new Date( value );

	return Number.isNaN( date.getTime() )
		? 0
		: Math.floor( date.getTime() / 1000 );
};

function ScheduleDateControl( { label, value, timestamp, onChange } ) {
	const inputValue = getScheduleInputValue( value, timestamp );

	const handlePickerChange = ( selectedDate ) => {
		const nextTimestamp = getTimestampFromPickerValue( selectedDate );

		if ( nextTimestamp ) {
			onChange( formatTimestamp( nextTimestamp ), nextTimestamp );
		}
	};

	return (
		<div style={ { marginBottom: '16px' } }>
			<TextControl
				label={ label }
				value={ inputValue }
				placeholder={ scheduleDateExample }
				onChange={ ( nextValue ) =>
					onChange( nextValue, getLocalTimestamp( nextValue ) )
				}
			/>
			<Dropdown
				renderToggle={ ( { isOpen, onToggle } ) => (
					<Button
						isSecondary
						onClick={ onToggle }
						aria-expanded={ isOpen }
					>
						{ __(
							'Select date',
							'visibility-controls-for-editor-blocks'
						) }
					</Button>
				) }
				renderContent={ () => (
					<div style={ { padding: '12px' } }>
						<DateTimePicker
							currentDate={ getPickerDate( value, timestamp ) }
							onChange={ handlePickerChange }
							is12Hour={ scheduleDateFormat.includes( 'h' ) }
						/>
					</div>
				) }
			/>
		</div>
	);
}

export default function VisibilityPanel( { attributes, setAttributes } ) {
	const {
		hideOnMobile,
		hideOnTablet,
		hideOnDesktop,
		hideForLoggedInUsers,
		hideForNonLoggedInUsers,
		gbvcRoleRule,
		gbvcUserRoles = [],
		gbvcScheduleEnabled,
		gbvcScheduleStart,
		gbvcScheduleEnd,
		gbvcScheduleStartTimestamp,
		gbvcScheduleEndTimestamp,
		gbvcGetParamEnabled,
		gbvcGetParamName,
		gbvcGetParamValue,
	} = attributes;

	const updateRole = ( roleSlug, isChecked ) => {
		const nextRoles = isChecked
			? [ ...new Set( [ ...gbvcUserRoles, roleSlug ] ) ]
			: gbvcUserRoles.filter(
					( selectedRole ) => selectedRole !== roleSlug
			  );

		setAttributes( { gbvcUserRoles: nextRoles } );
	};

	const updateScheduleStart = (
		value,
		timestamp = getLocalTimestamp( value )
	) => {
		setAttributes( {
			gbvcScheduleStart: value,
			gbvcScheduleStartTimestamp: timestamp,
		} );
	};

	const updateScheduleEnd = (
		value,
		timestamp = getLocalTimestamp( value )
	) => {
		setAttributes( {
			gbvcScheduleEnd: value,
			gbvcScheduleEndTimestamp: timestamp,
		} );
	};

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
			{ canUseProFeatures && (
				<PanelBody
					title={ __(
						'Visibility Settings Pro',
						'visibility-controls-for-editor-blocks'
					) }
					initialOpen={ false }
				>
					<p>
						{ proRuleProcessingMode === 'frontend'
							? __(
									'Frontend cache-friendly mode is enabled. Schedule and URL parameter rules will be checked in the browser; role rules stay server-side and require cache to bypass or vary logged-in users.',
									'visibility-controls-for-editor-blocks'
							  )
							: __(
									'Server-only mode is enabled. Pro rules are checked before the block HTML is sent to the browser.',
									'visibility-controls-for-editor-blocks'
							  ) }
					</p>
					<SelectControl
						label={ __(
							'User role visibility',
							'visibility-controls-for-editor-blocks'
						) }
						value={ gbvcRoleRule || 'none' }
						options={ [
							{
								label: __(
									'No role rule',
									'visibility-controls-for-editor-blocks'
								),
								value: 'none',
							},
							{
								label: __(
									'Show only to selected roles',
									'visibility-controls-for-editor-blocks'
								),
								value: 'show',
							},
							{
								label: __(
									'Hide from selected roles',
									'visibility-controls-for-editor-blocks'
								),
								value: 'hide',
							},
						] }
						onChange={ ( value ) =>
							setAttributes( {
								gbvcRoleRule: value,
								gbvcUserRoles:
									value === 'none' ? [] : gbvcUserRoles,
							} )
						}
						help={ __(
							'Role rules are always processed on the server.',
							'visibility-controls-for-editor-blocks'
						) }
					/>
					{ gbvcRoleRule && gbvcRoleRule !== 'none' && (
						<div style={ { marginBottom: '16px' } }>
							{ userRoles.map( ( role ) => (
								<CheckboxControl
									key={ role.slug }
									label={ role.name }
									checked={ gbvcUserRoles.includes(
										role.slug
									) }
									onChange={ ( isChecked ) =>
										updateRole( role.slug, isChecked )
									}
								/>
							) ) }
						</div>
					) }
					<ToggleControl
						label={ __(
							'Scheduled display',
							'visibility-controls-for-editor-blocks'
						) }
						checked={ gbvcScheduleEnabled }
						onChange={ ( value ) =>
							setAttributes( { gbvcScheduleEnabled: value } )
						}
						help={ __(
							'Show this block only during a selected date and time window.',
							'visibility-controls-for-editor-blocks'
						) }
					/>
					{ gbvcScheduleEnabled && (
						<>
							<ScheduleDateControl
								label={ __(
									'Show from',
									'visibility-controls-for-editor-blocks'
								) }
								value={ gbvcScheduleStart }
								timestamp={ gbvcScheduleStartTimestamp }
								onChange={ updateScheduleStart }
							/>
							<ScheduleDateControl
								label={ __(
									'Show until',
									'visibility-controls-for-editor-blocks'
								) }
								value={ gbvcScheduleEnd }
								timestamp={ gbvcScheduleEndTimestamp }
								onChange={ updateScheduleEnd }
							/>
							<p>
								{ __(
									'Leave either field empty for an open-ended schedule. Use the date format selected on the plugin settings page. The time is saved as an exact moment using your browser timezone.',
									'visibility-controls-for-editor-blocks'
								) }
							</p>
						</>
					) }
					<ToggleControl
						label={ __(
							'Show by URL parameter',
							'visibility-controls-for-editor-blocks'
						) }
						checked={ gbvcGetParamEnabled }
						onChange={ ( value ) =>
							setAttributes( { gbvcGetParamEnabled: value } )
						}
						help={ __(
							'Show this block only when the URL contains a matching GET parameter.',
							'visibility-controls-for-editor-blocks'
						) }
					/>
					{ gbvcGetParamEnabled && (
						<>
							<TextControl
								label={ __(
									'Parameter name',
									'visibility-controls-for-editor-blocks'
								) }
								value={ gbvcGetParamName || '' }
								placeholder="utm_campaign"
								onChange={ ( value ) =>
									setAttributes( {
										gbvcGetParamName: value,
									} )
								}
							/>
							<TextControl
								label={ __(
									'Required value (optional)',
									'visibility-controls-for-editor-blocks'
								) }
								value={ gbvcGetParamValue || '' }
								placeholder="spring-sale"
								onChange={ ( value ) =>
									setAttributes( {
										gbvcGetParamValue: value,
									} )
								}
								help={ __(
									'Leave empty to show the block when the parameter exists with any value.',
									'visibility-controls-for-editor-blocks'
								) }
							/>
						</>
					) }
				</PanelBody>
			) }
		</InspectorControls>
	);
}
