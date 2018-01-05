<?php
/**
 * @package ZEIT ONLINE Blog Options
 *
 * Author:            Arne Seemann
 * Author URI:        http://www.zeit.de
 * License:           GPL-3.0+
 * License URI:       http://www.gnu.org/licenses/gpl-3.0.txt
*/

function ivw_head() {
?>
	<!-- [Tracking] IVW 2.0 -->
	<script src="https://script.ioam.de/iam.js"></script>
	<!-- /[Tracking] IVW 2.0 -->
<?php
}

function ivw_body() {
	$main_ressort = get_option( 'zon_ressort_main' );
	$sub_ressort  = get_option( 'zon_ressort_sub' );

	$ivw_code = $main_ressort;
	if ( $sub_ressort ) {
		$ivw_code .= '/' . $sub_ressort;
	}
	$ivw_code .= '/bild-text';
	/* we need to user $_SERVER here, because the_permalink() and similar WP functions
	   return the wrong urls as our Freitext-Blog is accesible at www.zeit.de/blog/freitext
	   via Varnish but has blog.zeit.de/freitext as its internal url */
	$current_url = $_SERVER['HTTP_HOST'] . strtok($_SERVER["REQUEST_URI"],'?');
?>
	<!-- [Tracking] IVW 2.0 -->

	<!--SZM VERSION="2.0"-->
	<script>
		var iam_data = {
			"st" : "zeitonl", // dynamicallly set mobile|desktop
			"cp" : "<?php echo $ivw_code; ?>", // seitencode
			"sv" : "i2", // Befragungseinladung
			"co" : "URL: <?php echo $current_url; ?>" // comment
		};

		if ( typeof window.Zeit !== 'undefined' ) {
			if ( window.Zeit.isMobileView() || window.Zeit.isWrapped ) {
				iam_data.st = "mobzeit";
				iam_data.sv = "mo";
			}
		}

		if ( typeof iom !== "undefined" ) {
			iom.h( iam_data, 1 );
		}
	</script>
	<!--/SZM VERSION="2.0"-->

	<!-- /[Tracking] IVW 2.0 -->
<?php
}
