const UNSUPPORTED_BLOCKS = [
	'core/archives',
	'core/calendar',
	'core/latest-comments',
	'core/rss',
	'core/tag-cloud',
];

export function isUnsupportedBlock( blockName ) {
	return UNSUPPORTED_BLOCKS.includes( blockName );
}
