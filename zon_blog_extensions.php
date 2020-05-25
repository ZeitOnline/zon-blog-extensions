<?php
/**
 * @package ZEIT ONLINE Blog Options
 *
 * Plugin Name:       ZEIT ONLINE Blog Options
 * Plugin URI:        https://github.com/ZeitOnline/zon-blog-extensions
 * Description:       Add ZEIT ONLINE specific options to wordpress weblogs
 * Version:           1.3.0
 * Author:            Arne Seemann, Moritz Stoltenburg, Nico Brünjes
 * Author URI:        http://www.zeit.de
 * License:           GPL-3.0+
 * License URI:       http://www.gnu.org/licenses/gpl-3.0.txt
 * GitHub Plugin URI: https://github.com/ZeitOnline/zon-blog-extensions
*/

require_once plugin_dir_path( __FILE__ ) . 'class.zon-options.php';

add_action( 'init', array( 'ZonOptions', 'init' ) );

class ZonOptionsPage
{
	/**
	 * Holds the values to be used in the fields callbacks
	 */
	private $_ressort;
	private $_ad_id;
	private $_p_length;

	private $_zon_navigation_url = 'http://static.zeit.de/data/navigation-v2.xml';

	/**
	 * Start up
	 */
	public function __construct()
	{
		add_action( 'admin_menu', array( $this, 'add' ) );
		add_action( 'admin_init', array( $this, 'init' ) );

		// @params: action name, callback function, priority, number of parameters
		add_action( 'update_option_zon_blog_ressort', array( $this, 'convert_ressort_option' ), 10, 2 );
		add_action( 'add_option_zon_blog_ressort', array( $this, 'convert_ressort_option' ), 10, 2 );

		register_activation_hook( __FILE__, array( $this, 'plugin_activation' ) );

		// delete formerly registered options
		delete_option( 'zon_gdpr_activated' );
		delete_option( 'zon_ads_deactivated' );
	}

	/**
	 * Add options page
	 */
	public function add()
	{
		// Add sub menu page to the Settings menu.
		add_options_page(
			'ZEIT ONLINE Blog Einstellungen',	// HTML title
			'ZON Einstellungen',				// menu title
			'manage_options',					// required capability: 'manage_network_options' => Super Admin | 'manage_options' = Administrator
			'zon-options-page',					// menu slug
			array( $this, 'render' )			// callback function
		);
	}

	/**
	 * Options page callback
	 */
	public function render()
	{
		// Set class property
		$this->_ressort  = get_option( 'zon_blog_ressort' ); // Option name
		$this->_ad_id    = get_option( 'zon_bannerkennung' );
		$this->_no_ads 	 = get_option( 'zon_ads_no_ads' );
		$this->_p_length = get_option( 'zon_ads_paragraph_length', 200 );

		?>
		<div class="wrap">
			<h2>Einstellungen › <?php echo esc_html( get_admin_page_title() ); ?></h2>
			<form method="post" action="options.php">
			<?php

				// This prints out all hidden setting fields
				settings_fields( 'zon_blog_options' ); // Option group
				do_settings_sections( 'zon-options-page' );
				submit_button();
			?>
			</form>
		</div>
		<?php
	}

	/**
	 * Register and add settings
	 */
	public function init()
	{

		/**
		 * Register settings
		 */

		register_setting(
			'zon_blog_options',		// Option group
			'zon_blog_ressort',		// Option name
			'sanitize_text_field'	// Sanitize
		);

		register_setting(
			'zon_blog_options',
			'zon_bannerkennung',
			'sanitize_text_field'
		);

		register_setting(
			'zon_blog_options',
			'zon_ads_no_ads',
			'intval'
		);

		register_setting(
			'zon_blog_options',
			'zon_ads_paragraph_length',
			'intval'
		);

		/**
		 * Add sections
		 */

		add_settings_section(
			'settings_appearance',	// ID
			'Erscheinungsbild',		// Title
			null,					// Callback
			'zon-options-page'		// Page
		);

		add_settings_section(
			'settings_ads',
			'Produktmanagement',
			null,
			'zon-options-page'
		);

		/**
		 * Add settings fields
		 */

		add_settings_field(
			'zon_blog_ressort',					// ID
			'Ressort des Blogs',				// Title
			array( $this, 'render_field' ),		// Callback
			'zon-options-page',					// Page
			'settings_appearance',				// Section
			array( 'id' => 'zon_blog_ressort' )	// Arguments
		);

		add_settings_field(
			'zon_bannerkennung',
			'Bannerkennung',
			array( $this, 'render_field' ),
			'zon-options-page',
			'settings_ads',
			array( 'id' => 'zon_bannerkennung' )
		);

		add_settings_field(
			'zon_ads_paragraph_length',
			'Minimale Zeichenlänge eines Paragraphen nach dem Ads eingebaut werden',
			array( $this, 'render_field' ),
			'zon-options-page',
			'settings_ads',
			array( 'id' => 'zon_ads_paragraph_length' )
		);

		add_settings_field(
			'zon_ads_no_ads',
			'Ads deaktiviert (nur nach Absprache mit CR und GF!)',
			array( $this, 'render_field' ),
			'zon-options-page',
			'settings_ads',
			array( 'id' => 'zon_ads_no_ads' )
		);

	}

	/**
	 * Sanitize setting field
	 *
	 * @param array $input
	 */
	public function sanitize_array( $input )
	{
		return array_map( 'sanitize_text_field', $input );
	}

	/**
	 * Print form elements
	 */
	public function render_field( $args )
	{
		switch( $args['id'] )
		{
			case 'zon_blog_ressort':
				printf(
					'<select id="%1$s" name="%1$s">%2$s</select><p class="description">%3$s</p>',
					$args['id'],
					$this->print_ressorts(),
					'Bitte ein Hauptressort oder Unterressort auswählen. Es ist auch möglich "kein Ressort" auszuwählen.'
				);
				break;

			case 'zon_bannerkennung':
				printf(
					'<input type="text" id="%1$s" name="%1$s" value="%2$s" class="regular-text" /><p class="description">%3$s</p>',
					$args['id'],
					esc_attr( $this->_ad_id ),
					nl2br(
						'Die iqd-Bannerkennung. Standard ist <code>' . ZonOptions::defaults('bannerkennung') . '</code>. ' .
						'Ein eigener Wert zur gezielteren Vermarktung kann hier eingetragen werden. ' .
						'Dieser sollte mit <code>' . ZonOptions::defaults('bannerkennung') . '</code> beginnen.'
					)
				);
				break;

			case 'zon_ads_paragraph_length':
				printf(
					'<input type="text" id="%1$s" name="%1$s" value="%2$s" class="regular-text" /><p class="description">%3$s</p>',
					$args['id'],
					esc_attr( $this->_p_length ),
					'Absätze werden so lange zusammengezählt, bis die minimale Zeichenlänge erreicht ist. Standard ist 200.'
				);
				break;

			case 'zon_ads_no_ads':
				printf(
					'<label><input type="checkbox" id="%1$s" name="%1$s" value="1" %2$s> Ads deaktiviert.</label><p class="description">%3$s</p>',
					$args['id'],
					checked( 1, $this->_no_ads, false ),
					nl2br(
						'Diese Option ist ausschließlich für einzelne Blogs vorgesehen, in denen sich Chefredaktion und ' .
						'Geschäftsführung auf das Deaktivieren von Ads geeinigt haben. Bitte nicht selbständig aktivieren. ' .
						'Ist der Haken gesetzt, werden - sofern das Theme darauf vorbereitet ist - keine Anzeigen ausgespielt.'
					)
				);
				break;
		}
	}

	private function print_ressorts()
	{
		$options = array();
		$ressorts = $this->get_ressorts();

		foreach ( $ressorts as $key => $value ) {
			$options[] = '<option value="' . esc_attr( $key ) . '"' . selected( $this->_ressort, $key, false ) . '>' . esc_html( $value ) . '</option>';
		}

		return implode("\n", $options);
	}

	private function get_ressorts()
	{
		$ressorts = array(
			'blogs' => 'kein Ressort' // wichtig dass hier "blogs" als Key steht, da der Wert auch für die IVW-Kennung verwendet wird.
		);

		if ( ! ( $naviXML = simplexml_load_file( $this->_zon_navigation_url ) ) ) {
			return $ressorts;
		};

		foreach ( $naviXML->list as $list ) {
			// Wenn <list> Element mit der ID Sitemap (hier steckt die Navi) erreicht, dieses bearbeiten
			if ( $list['id'] == 'sitemap' ) {
				foreach ( $list->list as $topRessort ) {
					//  Hauptressorts
					$label = trim( $topRessort->item->link['label'] );

					if ( ! empty( $label ) && $label != 'start' ) {
						$ressorts[ $label ] = trim( $topRessort->item->link );
					}

					if ( ! empty( $topRessort->list->item ) ) {
						foreach ( $topRessort->list->item as $subRessort ) {
							//  Subressorts
							$label = trim( $subRessort->link['label'] );

							if ( ! empty( $label ) )
								$ressorts[ $label ] = '-- ' . trim( $subRessort->link );
						}
					}
				}
			}
		}

		return $ressorts;
	}

	private function zon_get_blog_main_ressort( $blog_ressort, $navigation_file_url, $debug_mode=FALSE ) {

		// load xml file with navigation sitemap
		if ( !( $naviXML = simplexml_load_file( $navigation_file_url ) ) ) {
			return FALSE;
		};

		// only continue if $blog_ressort isn't a main-ressort
		$xpath_main_ressort = "/lists/list[ @id = 'sitemap' ]/list/item/link[ @label = '" . $blog_ressort . "' ]";
		if ( $naviXML->xpath( $xpath_main_ressort ) ) {
			return FALSE;
		}

		// $blog_ressort must be a genuine sub-ressort
		// iterate over xml tree to see under which main-ressort $blog_ressort resides
		foreach ( $naviXML->xpath( "/lists/list[ @id = 'sitemap' ]/list" ) as $main_ressort ) {

			$main_ressort_link = $main_ressort->item->link;

			if ( $debug_mode ) {
				echo "\nmain-ressort: ".$main_ressort_link;
				echo "\n\turl: ".$main_ressort_link['href'];
				echo "\n\tclass: ".$main_ressort_link['class'];
				echo "\n\tlabel: ".$main_ressort_link['label'];
			}

			// check if the $blog_ressort is present
			$xpath = "./list/item/link[ @label = '" . $blog_ressort . "' ]";
			if ( $main_ressort->xpath( $xpath ) ) {
				if ( $debug_mode ) {
					echo "\nmain-ressort for blog-ressort found: ".$main_ressort_link['label'];
				}
				// cast to string, otherwise a SimpleXMLElement gets returned
				return (string) $main_ressort_link['label'];
			}
		}

		// if all things go wrong, just exit
		return FALSE;
	}

	/**
	 * Save blog ressort as main- and sub-ressort to separate option fields
	 *
	 * In the »ZON options« settings page the user chooses the blog ressort,
	 * which can be a main-ressort (like 'Politik') or a sub-ressort (like 'Ausland').
	 * This function checks which main- and sub-ressort combination is adequate and
	 * updates two option fields, one for the main-ressort and one for the sub-ressort.
	 *
	 * @param string $old_value former value of option field
	 * @param string $value     new value of option field – of interest for us
	 * @return no return value needed
	 */
	public function convert_ressort_option( $old_value, $value )
	{
		$main_ressort = $this->zon_get_blog_main_ressort( $value, $this->_zon_navigation_url );

		if ( $main_ressort ) {
			update_option( 'zon_ressort_main', $main_ressort );
			update_option( 'zon_ressort_sub', $value );
		} else {
			update_option( 'zon_ressort_main', $value );
			update_option( 'zon_ressort_sub', '' );
		}
	}

	/**
	 * Convert existing ressort option on plugin activation
	 */
	public function plugin_activation()
	{
		$zon_blog_ressort = get_option( 'zon_blog_ressort' );

		if ( $zon_blog_ressort ) {
			$this->convert_ressort_option( 'activation HOOKED :D', $zon_blog_ressort );
		}
	}
}

// register options page
if ( is_admin() ) {
	new ZonOptionsPage();
}


/**
 * register Webtrekk tracking
 */
require plugin_dir_path( __FILE__ ) . 'zon_webtrekk.php';
add_action( 'wp_footer', 'webtrekk_tracking_code' );

/**
 * register IVW tracking
 */
include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

if ( !is_plugin_active( 'zon-get-frame-from-api/zon-get-frame-from-api.php' ) ) {
	require plugin_dir_path( __FILE__ ) . 'zon_ivw.php';
	add_action( 'wp_head', 'ivw_head', 20 );
	add_action( 'wp_body_start', 'ivw_body' );
}
