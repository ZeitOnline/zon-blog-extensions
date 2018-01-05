<?php
/**
 * @package ZEIT ONLINE Blog Options
 *
 * Author:            Arne Seemann, Moritz Stoltenburg
 * Author URI:        http://www.zeit.de
 * License:           GPL-3.0+
 * License URI:       http://www.gnu.org/licenses/gpl-3.0.txt
*/
class ZonOptions
{
	private static $initiated = false;

	public static function init() {
		if ( ! self::$initiated ) {
			self::init_hooks();
		}
	}

	/**
	 * Initializes WordPress hooks
	 */
	private static function init_hooks() {
		self::$initiated = true;

		add_filter( 'option_zon_bannerkennung', array( __CLASS__, 'process_bannerkennung' ) );
		add_filter( 'default_option_zon_bannerkennung', array( __CLASS__, 'default_bannerkennung' ) );
	}

	// if "zon_bannerkennung" is defined, check the value
	public static function process_bannerkennung( $option ) {
		if ( empty($option) ) {
			return static::defaults('bannerkennung');
		}
		elseif ( !preg_match( '/^zeitonline/', $option ) ) {
			$option = static::defaults('bannerkennung') . $option;
		}

		return $option;
	}

	// if "zon_bannerkennung" is not set at all, this will get called
	public static function default_bannerkennung( $option ) {
		// if we don't add the option here, it will never get saved. Seems like a bug.
		add_option( 'zon_bannerkennung', static::defaults('bannerkennung') );

		return static::defaults('bannerkennung');
	}

	public static function defaults( $key )
	{
		switch ( $key ) {
			case 'bannerkennung':
				return 'zeitonline/blogs';

			default:
				return null;
		}
	}
}
