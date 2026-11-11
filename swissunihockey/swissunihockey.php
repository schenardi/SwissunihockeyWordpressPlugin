<?php
/**
 * Plugin Name: Swissunihockey Web Components
 * Description: Bindet die Swissunihockey Web Components (Spielpläne, Ranglisten, Topscorer) per Shortcode ein. Für die Nutzung ist eine Registrierung bei Swissunihockey Voraussetzung (https://www.swissunihockey.ch/de/administration/services/webmaster/)
 * Version: 1.0.0
 * Requires PHP: 7.4
 * License: GPL-2.0-or-later
 * Text Domain: swissunihockey
 * Autor: Markus Schenardi (Schenardi Consulting, Leiter Vereinsbüro)
 */

defined( 'ABSPATH' ) || exit;

const SUH_OPTION = 'suh_settings';
const SUH_DEFAULT_JS  = 'https://cdn.jsdelivr.net/gh/swissunihockey/swissunihockey-webcomponents@master/swissunihockey-webcomponents.js';
const SUH_DEFAULT_CSS = 'https://cdn.jsdelivr.net/gh/swissunihockey/swissunihockey-webcomponents@master/swissunihockey-webcomponents.css';

function suh_get_settings() {
	$s = get_option( SUH_OPTION, array() );
	return wp_parse_args( is_array( $s ) ? $s : array(), array(
		'js_url'  => SUH_DEFAULT_JS,
		'css_url' => SUH_DEFAULT_CSS,
		'season'  => '',
	) );
}

function suh_default_season() {
	$s = suh_get_settings();
	if ( preg_match( '/^\d{4}$/', $s['season'] ) ) {
		return $s['season'];
	}
	$year = (int) wp_date( 'Y' );
	return (string) ( (int) wp_date( 'n' ) < 7 ? $year - 1 : $year );
}

/* ---------- Settings ---------- */

add_action( 'admin_menu', function () {
	add_options_page( 'Swissunihockey', 'Swissunihockey', 'manage_options', 'swissunihockey', 'suh_render_settings' );
} );

add_action( 'admin_init', function () {
	register_setting( 'suh_group', SUH_OPTION, array(
		'type'              => 'array',
		'sanitize_callback' => function ( $in ) {
			$in  = is_array( $in ) ? $in : array();
			$js  = isset( $in['js_url'] ) ? esc_url_raw( trim( $in['js_url'] ), array( 'https', 'http' ) ) : '';
			$css = isset( $in['css_url'] ) ? esc_url_raw( trim( $in['css_url'] ), array( 'https', 'http' ) ) : '';
			$season = isset( $in['season'] ) && preg_match( '/^\d{4}$/', trim( $in['season'] ) ) ? trim( $in['season'] ) : '';
			return array(
				'js_url'  => $js ? $js : SUH_DEFAULT_JS,
				'css_url' => $css ? $css : SUH_DEFAULT_CSS,
				'season'  => $season,
			);
		},
	) );
} );

function suh_render_settings() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$s = suh_get_settings();
	?>
	<div class="wrap">
		<h1>Swissunihockey</h1>
		<form method="post" action="options.php">
			<?php settings_fields( 'suh_group' ); ?>
			<table class="form-table">
				<tr><th><label for="suh_js">JavaScript-URL</label></th>
					<td><input id="suh_js" class="large-text" type="url" name="<?php echo esc_attr( SUH_OPTION ); ?>[js_url]" value="<?php echo esc_attr( $s['js_url'] ); ?>"></td></tr>
				<tr><th><label for="suh_css">CSS-URL</label></th>
					<td><input id="suh_css" class="large-text" type="url" name="<?php echo esc_attr( SUH_OPTION ); ?>[css_url]" value="<?php echo esc_attr( $s['css_url'] ); ?>"></td></tr>
				<tr><th><label for="suh_season">Standard-Saison</label></th>
					<td><input id="suh_season" type="text" pattern="\d{4}" maxlength="4" name="<?php echo esc_attr( SUH_OPTION ); ?>[season]" value="<?php echo esc_attr( $s['season'] ); ?>">
					<p class="description">Startjahr der Saison. Leer = automatisch - Wechsel im Juli (aktuell: <?php echo esc_html( suh_default_season() ); ?>).</p></td></tr>
			</table>
			<?php submit_button(); ?>
		</form>
		<h2>Shortcode</h2>
		<p>IDs finden: <a href="https://www.swissunihockey.ch/de/administration/services/vereins-ids-finder-fuer-web-component/" target="_blank" rel="noopener">Vereins-IDs-Finder</a></p>
		<p>Syntax: <code>[swissunihockey component="web-component-name" attribute="value"]</code></p>
		<p>Beispiel: <code>[swissunihockey component="uniho-ranking" league="3" game_class="21" group="Gruppe 1"]</code></p>
	</div>
	<?php
}

/* ---------- Assets ---------- */

function suh_enqueue_assets() {
	$s = suh_get_settings();
	wp_enqueue_style( 'swissunihockey-wc', $s['css_url'], array(), null );
	wp_enqueue_script( 'swissunihockey-wc', $s['js_url'], array(), null, true );
}

add_filter( 'script_loader_tag', function ( $tag, $handle, $src ) {
	if ( 'swissunihockey-wc' === $handle ) {
		return '<script type="module" src="' . esc_url( $src ) . '"></script>' . "\n";
	}
	return $tag;
}, 10, 3 );

/* ---------- Shortcodes ---------- */

add_shortcode( 'swissunihockey', function ( $atts ) {
	$atts = is_array( $atts ) ? $atts : array();
	$component = isset( $atts['component'] ) && is_scalar( $atts['component'] )
		? strtolower( trim( (string) $atts['component'] ) )
		: '';

	if ( ! preg_match( '/^[a-z][a-z0-9._-]*-[a-z0-9._-]+$/', $component ) ) {
		return '';
	}

	unset( $atts['component'] );
	$season = isset( $atts['season'] ) && is_scalar( $atts['season'] ) ? (string) $atts['season'] : '';
	if ( ! preg_match( '/^\d{4}$/', $season ) ) {
		$atts['season'] = suh_default_season();
	}

	suh_enqueue_assets();
	$attrs = '';
	foreach ( $atts as $key => $value ) {
		if ( ! preg_match( '/^[a-zA-Z_:][a-zA-Z0-9:._-]*$/', $key ) || ! is_scalar( $value ) || '' === (string) $value ) {
			continue;
		}
		$attrs .= ' ' . str_replace( '_', '-', $key ) . '="' . esc_attr( $value ) . '"';
	}
	return '<' . $component . $attrs . '></' . $component . '>';
} );
