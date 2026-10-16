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

function suh_shortcode_defs() {
	return array(
		'swissunihockey_league_games'       => array( 'uniho-league-games', array( 'game_class', 'league', 'season', 'group' ) ),
		'swissunihockey_club_team_games'    => array( 'uniho-club-team-games', array( 'club_id', 'season', 'page_size' ) ),
		'swissunihockey_team_games'         => array( 'uniho-team-games', array( 'team_id', 'season', 'page_size' ) ),
		'swissunihockey_club_games'         => array( 'uniho-club-games', array( 'club_id', 'season' ) ),
		'swissunihockey_ranking'            => array( 'uniho-ranking', array( 'season', 'league', 'game_class', 'group' ) ),
		'swissunihockey_mobiliar_topscorer' => array( 'uniho-mobiliar-topscorer', array( 'club_id', 'season' ) ),
	);
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
		<h2>Shortcodes</h2>
		<p>IDs finden: <a href="https://www.swissunihockey.ch/de/administration/services/vereins-ids-finder-fuer-web-component/" target="_blank" rel="noopener">Vereins-IDs-Finder</a></p>
		<ul>
			<?php foreach ( suh_shortcode_defs() as $tag => $def ) : ?>
				<li><code>[<?php echo esc_html( $tag . ' ' . implode( '="" ', $def[1] ) ); ?>=""]</code></li>
			<?php endforeach; ?>
		</ul>
		<p>Beispiel: <code>[swissunihockey_ranking league="3" game_class="21" group="Gruppe 1"]</code></p>
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

foreach ( suh_shortcode_defs() as $suh_tag => $suh_def ) {
	add_shortcode( $suh_tag, function ( $atts ) use ( $suh_def ) {
		list( $element, $keys ) = $suh_def;
		$atts = shortcode_atts( array_fill_keys( $keys, '' ), $atts );
		if ( in_array( 'season', $keys, true ) && ! preg_match( '/^\d{4}$/', (string) $atts['season'] ) ) {
			$atts['season'] = suh_default_season();
		}
		suh_enqueue_assets();
		$attrs = '';
		foreach ( $atts as $key => $value ) {
			if ( '' === $value ) {
				continue;
			}
			$attrs .= ' ' . str_replace( '_', '-', $key ) . '="' . esc_attr( $value ) . '"';
		}
		return '<' . $element . $attrs . '></' . $element . '>';
	} );
}
