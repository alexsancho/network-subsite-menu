<?php
/*
Plugin Name: Network Subsite Menu
Description: Show a menu with the network subsites.
Version: 1.0.0
Author: Alex Sancho <asancho@keclab.com>
*/

namespace Asancho;

/**
 * Composer Include
 */
require __DIR__ . '/vendor/autoload.php';

class NetworkSubsiteMenu extends \WordPress_SimpleSettings {

	/**
	 * Plugin prefix
	 *
	 * @var string
	 */
	public $prefix = '_asnm';

	/**
	 * For WordPress Simple Settings
	 *
	 * @var bool
	 */
	public $network_only = true;

	/**
	 * CGD_NetworkSubsiteMenu constructor.
	 */
	public function __construct() {
		parent::__construct();

		add_action( 'network_admin_menu', [ $this, 'add_menu' ] );
		add_action( 'pre_wp_nav_menu', [ $this, 'show_menu' ], 100, 2 );
	}

	/**
	 * Add network menu
	 */
	public function add_menu() {
		add_submenu_page( 'settings.php', 'Network Menu', 'Network Menu', 'manage_options', 'network-menu', [ $this, 'show_admin' ] );
	}

	/**
	 * Show menu
	 *
	 * @param string $result Result menu.
	 * @param array  $args Menu args.
	 *
	 * @return string
	 */
	public function show_menu( $result, $args ) {
		$blog_id = get_current_blog_id();

		$menu_settings = apply_filters( 'asnm_network_subsite_menu_settings', $this->get_setting( 'site_settings' ) );

		// Only run if we have the right theme location input
		if ( $args->menu !== 'network_subsite_menu' ) {
			return $result;
		}

		// If we don't have any subsites, return
		if ( empty( $menu_settings['enabled_sites'] ) ) {
			return $result;
		}

		$result = '';
		$result .= '<ul id="network_subsite_menu" class="menu menu-network-subsite">';

		$result              .= apply_filters( 'asnm_network_subsite_menu_before', '' );
		$current_already_set = false;

		$enabled_sites = $menu_settings['enabled_sites'];

		if ( $this->get_setting( 'menu_order' ) !== false ) {
			$ordered_sites = explode( ',', $this->get_setting( 'menu_order' ) );

			if ( \count( $ordered_sites ) === \count( $enabled_sites ) ) {
				$enabled_sites = $ordered_sites;
			}
		}

		foreach ( $enabled_sites as $site_id ) {
			$site_info = apply_filters( 'asnm_network_subsite_menu_preload_site_info', false, $site_id );

			if ( ! $site_info ) {
				$site_info = get_blog_details( $site_id, true );

				if ( ! \is_object( $site_info ) ) {
					continue;
				}
			}

			$site_info->blogname = $menu_settings['labels'][ $site_id ] ?? $site_info->blogname;
			$site_info->siteurl = $menu_settings['urls'][ $site_id ] ?? get_site_url( $site_id );

			// Set current
			$site_info->current = false;
			if ( apply_filters( 'asnm_network_subsite_menu_is_current_site', $blog_id === (int) $site_id, $site_id ) ) {
				$site_info->current = true;
			}

			// Allow for extensibility
			$site_info = apply_filters( 'asnm_network_subsite_menu_site_info', $site_info, $site_id );

			$class = '';
			if ( $site_info->current && ! $current_already_set ) {
				$class = 'current-menu-item';
			}

			$_id = apply_filters( 'nav_menu_item_id', 'menu-item-' . $site_id, $site_info, $args );
			$_id = $_id ? ' id="' . esc_attr( $_id ) . '"' : '';

			$result .= sprintf(
				'<li %1$s class="menu-item menu-item-type-network-subsite menu-item-%1$s %2$s"><a href="%3$s">%4$s</a></li>',
				$_id,
				$class,
				$site_info->siteurl,
				$site_info->blogname
			);

			if ( $site_info->current ) {
				$current_already_set = true; // only set one menu item as current
			}
		}

		$result .= apply_filters( 'asnm_network_subsite_menu_after', '', $blog_id );

		$result .= '</ul>';

		return $result;
	}

	/**
	 * Show plugin options
	 */
	public function show_admin() {
		$sites = get_sites();
		$sites = apply_filters( 'asnm_network_subsite_menu_sites', $sites );

		$menu_settings = $this->get_setting( 'site_settings' );
		$network_sites = $menu_settings['enabled_sites'] ?? [];
		?>

		<div class="wrap">
			<h2>Network Menu Settings</h2>
			<form method="post" action="<?php echo filter_var( 'REQUEST_URI', INPUT_SERVER ); ?>">
				<?php $this->the_nonce(); ?>

				<table class="form-table">
					<tbody>
					<?php
					/* @var \WP_Site $s */
					foreach ( $sites as $s ) :
						?>
						<tr>
							<th scope="row" valign="top">
								<label><?php echo $s->id; ?> - <?php echo $s->siteurl; ?></label>
							</th>
							<td>
								<p>
									<label>
										<input type="checkbox"
										       name="<?php echo $this->get_field_name( 'site_settings' ); ?>[enabled_sites][]"
										       value="<?php echo $s->id; ?>"<?php checked( in_array( $s->id, $network_sites ), true ); ?> /> Show in menu.
									</label>
								</p>

								<p>
									<label>
										<input type="text"
										       name="<?php echo $this->get_field_name( 'site_settings' ); ?>[urls][<?php echo $s->id; ?>]; ?>"
										       value="<?php echo isset( $menu_settings['urls'][ $s->id ] ) ? htmlspecialchars( $menu_settings['urls'][ $s->id ] ) : $s->siteurl ?>"/><br/>
										Menu url for <?php echo $s->blogname; ?> entry.
									</label>
								</p>


								<p>
									<label>
										<input type="text"
										       name="<?php echo $this->get_field_name( 'site_settings' ); ?>[labels][<?php echo $s->id; ?>]; ?>"
										       value="<?php echo isset( $menu_settings['labels'][ $s->id ] ) ? htmlspecialchars( $menu_settings['labels'][ $s->id ] ) : $s->blogname ?>"/><br/>
										Menu label for <?php echo $s->blogname; ?> entry.
									</label>
								</p>

								<p>
									<label>
										<input type="text"
										       name="<?php echo $this->get_field_name( 'site_settings' ); ?>[mobile_labels][<?php echo $s->id; ?>]; ?>"
										       value="<?php echo isset( $menu_settings['mobile_labels'][ $s->id ] ) ? htmlspecialchars( $menu_settings['mobile_labels'][ $s->id ] ) : $s->blogname ?>"/><br/>
										Mobile Menu label for <?php echo $s->blogname; ?> entry. (Default: Desktop label)
									</label>
								</p>
							</td>
						</tr>
					<?php endforeach; ?>
					<tr>
						<th scope="row" valign="top">
							<label>Menu Order</label>
						</th>
						<td>
							<label>
								<input type="text"
								       name="<?php echo $this->get_field_name( 'menu_order' ); ?>"
								       value="<?php echo $this->get_setting( 'menu_order' ); ?>"/><br/>
								Menu order. Separated by commas. Must include all menu items above.
							</label>
						</td>
					</tr>
					</tbody>
				</table>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}
}

new NetworkSubsiteMenu();
