<?php
/**
 * @package ZEIT ONLINE Blog Options
 *
 * Author:            Arne Seemann
 * Author URI:        http://www.zeit.de
 * License:           GPL-3.0+
 * License URI:       http://www.gnu.org/licenses/gpl-3.0.txt
*/

function jwt_decode( $token ) {
	$base64Url = explode('.', $token)[1];
	$search = ['-', '_'];
	$replace = ['+', '/'];
	$base64 = str_replace($search, $replace, $base64Url);
	return json_encode(base64_decode( $base64 ));
}

function webtrekk_tracking_code() {
	/* collect variables */
	$sso_user_data = FALSE;
	$sso_id = NULL;
	$user_login_status = 'nicht_angemeldet';
	if (isset($_COOKIE[ 'zeit_sso_201501' ])) {
		$cookie = $_COOKIE[ 'zeit_sso_201501' ];
		$sso_user_data = jwt_decode( $cookie );
	}

	/* Track login status with entrypoint url */
	if( $sso_user_data ) {
		$sso_id = isset($sso_user_data->id) ? $sso_user_data->id : null;
		if ($sso_id) {
			$user_login_status = 'angemeldet';

			if (!empty($sso_user_data->entry_url)) {
				$user_login_status += sprintf('|%s', urldecode($sso_user_data->entry_url));
			}
		}
	}



	/* we need to user $_SERVER here, because the_permalink() and similar WP functions
	   return the wrong urls as our Freitext-Blog is accesible at www.zeit.de/blog/freitext
	   via Varnish but has blog.zeit.de/freitext as its internal url */
	$current_url = $_SERVER['HTTP_HOST'] . strtok($_SERVER['REQUEST_URI'],'?');

	$wt_script_path   = '//scripts.zeit.de/static/js/webtrekk/webtrekk_v3.js';
	$wt_division      = 'redaktion'; // 'redaktion' or 'verlag'
	$wt_ressort       = mb_strtolower( get_option( 'zon_ressort_main' ) );
	$wt_subressort    = mb_strtolower( get_option( 'zon_ressort_sub' ) );
	$wt_cluster       = mb_strtolower( get_bloginfo( 'name' ) ); // blog title
	$wt_pagetype      = is_singular() ? 'article' : 'centerpage'; // 'article' or 'centerpage'
	$wt_sourcetype    = 'online';
	$wt_uri           = $current_url;
	$wt_bannerchannel = mb_strtolower( get_option( 'zon_bannerkennung' ) );
	$wt_releasedate   = is_singular() ? get_the_date( 'Y-m-d' ) : '';
	$wt_site_version  = 'desktop.site';
	$wt_basename      = basename( strtok($_SERVER['REQUEST_URI'], '?') );

	$wt_content_id = $wt_division . '.' .
		$wt_ressort . '.' .
		$wt_subressort . '.' .
		$wt_cluster . '.' .
		$wt_pagetype . '.' .
		$wt_sourcetype . '|' .
		$wt_uri;

	/* output the HTML and JS needed for Webtrekk */

	$wt_parameter = array(
		'contentGroup' => array(
			1 => $wt_division,
			2 => $wt_pagetype,
			3 => $wt_ressort,
			4 => $wt_sourcetype,
			5 => $wt_subressort,
			6 => $wt_cluster,
			7 => $wt_basename,
			8 => $wt_bannerchannel,
			9 => $wt_releasedate,
		),
		'customParameter' => array(
			9  => $wt_bannerchannel,
			12 => $wt_site_version,
			13 => 'stationaer',
			23 => $user_login_status, # Login status with entrypoint url
			25 => 'original',         # Plattform
			30 => 'open',             # Paywall Schranke
			32 => 'unfeasible'        # Protokoll, set via JS (below)
		)
	);

?>
	<div id="login-state" data-sso-id="<?php echo $sso_id; ?>"></div>

	<!-- [Tracking] Webtrekk -->

	<script src="<?php echo $wt_script_path; ?>"></script>

	<script>

		var webtrekk = {
			linkTrack : "standard",
			heatmap : "0",
			linkTrackAttribute: "data-wt-click"
		};

		if ( typeof webtrekkV3 === "function" ) {
			var wt = new webtrekkV3(webtrekk);

			wt.cookie = "1"; // (3|1, 1st or 3rd party cookie)

			wt.contentGroup = {
<?php

end($wt_parameter['contentGroup']);
$last_key = key($wt_parameter['contentGroup']);

foreach ( $wt_parameter['contentGroup'] as $key => $value ) {
	$separator = ($key === $last_key) ? '' : ',';

	echo <<<EOM
				$key: "$value"$separator

EOM;

}

?>
			};

			wt.customParameter = {
<?php

end($wt_parameter['customParameter']);
$last_key = key($wt_parameter['customParameter']);

foreach ( $wt_parameter['customParameter'] as $key => $value ) {
	$separator = ($key === $last_key) ? '' : ',';

	switch ($key) {
		case 12:
			$value = 'window.Zeit.getSiteParam()';
			break;

		case 13:
			$value = 'window.Zeit.breakpoint.getTrackingBreakpoint()';
			break;

		case 32:
			$value = 'window.location.protocol.replace(":", "")';
			break;

		default:
			$value = '"' . $value . '"';
			break;
	}

	echo <<<EOM
				$key: $value$separator

EOM;

}

?>
			};

			var ls = document.getElementById('login-state'),
			    ssoId;

			if (ls) {
			    ssoId = ls.getAttribute('data-sso-id');
			    if (ssoId) {
			        wt.customerId = ssoId;
			    }
			}

			wt.contentId = "<?php echo $wt_content_id; ?>";
			wt.sendinfo();
		}

	</script>

	<noscript>
		<div>
			<img alt="" width="1" height="1" src="http://zeit01.webtrekk.net/981949533494636/wt.pl?p=432,<?php
				echo urlencode($wt_content_id);
				?>,0,0,0,0,0,0,0,0&amp;<?php
				echo http_build_query($wt_parameter['contentGroup'], 'cg');
				?>&amp;<?php
				echo http_build_query($wt_parameter['customParameter'], 'cp');
				?>&amp;cd=<?php
				echo urlencode($sso_id);
				?>">
		</div>
	</noscript>

	<!-- /[Tracking] Webtrekk -->

<?php
}
