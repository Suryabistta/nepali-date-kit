/* Editor UI for nepali-date-kit/today. Plain JS, no build step. */
( function ( wp ) {
	var el = wp.element.createElement;
	var __ = wp.i18n.__;
	var be = wp.blockEditor;
	var c = wp.components;

	wp.blocks.registerBlockType( 'nepali-date-kit/today', {
		edit: function ( props ) {
			var a = props.attributes;
			var set = props.setAttributes;
			return el(
				'div',
				be.useBlockProps(),
				el(
					be.InspectorControls,
					null,
					el(
						c.PanelBody,
						{ title: __( 'Settings', 'nepali-date-kit' ) },
						el( c.TextControl, {
							label: __( 'BS format', 'nepali-date-kit' ),
							help: __( 'PHP date characters, e.g. "l, j F Y" or "Y-m-d".', 'nepali-date-kit' ),
							value: a.format,
							onChange: function ( v ) {
								set( { format: v } );
							},
						} ),
						el( c.SelectControl, {
							label: __( 'Language', 'nepali-date-kit' ),
							value: a.lang,
							options: [
								{ label: __( 'Site default', 'nepali-date-kit' ), value: '' },
								{ label: 'नेपाली', value: 'ne' },
								{ label: 'English', value: 'en' },
							],
							onChange: function ( v ) {
								set( { lang: v } );
							},
						} ),
						el( c.ToggleControl, {
							label: __( 'Also show the AD date', 'nepali-date-kit' ),
							checked: a.showAd,
							onChange: function ( v ) {
								set( { showAd: v } );
							},
						} ),
						a.showAd &&
							el( c.TextControl, {
								label: __( 'AD format', 'nepali-date-kit' ),
								value: a.adFormat,
								onChange: function ( v ) {
									set( { adFormat: v } );
								},
							} )
					)
				),
				el( wp.serverSideRender, { block: 'nepali-date-kit/today', attributes: a } )
			);
		},
		save: function () {
			return null;
		},
	} );
} )( window.wp );
