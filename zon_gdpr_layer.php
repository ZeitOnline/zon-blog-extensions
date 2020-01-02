<?php
/**
 * @package ZEIT ONLINE Blog Options
 *
 * Author:            Nico Brünjes
 * Author URI:        http://www.zeit.de
 * License:           GPL-3.0+
 * License URI:       http://www.gnu.org/licenses/gpl-3.0.txt
*/

function gdpr_layer() {
	if ( get_option( 'zon_gdpr_activated', 1 ) == 1 ) {
		echo "\n<script>document.body.setAttribute('data-gdpr-layer', 'true');</script>\n";
	}
}
