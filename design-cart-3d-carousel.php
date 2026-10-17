<?php
/**
 * Design Cart 3D Carousel
 * Autor: Paweł Nosko → https://www.designcart.pl/pawel-nosko.html
 * Firma: Design Cart → https://www.designcart.pl/
 *
 * Plugin Name:       Design Cart 3D Carousel
 * Plugin URI:        https://www.designcart.pl/
 * Description:       Interaktywne karuzele 3D (Three.js). Zarządzaj wieloma karuzelami i wstawiaj je shortcodem.
 * Version:           1.0.1
 * Requires at least: 6.2
 * Requires PHP:      7.4
 * Author:            Paweł Nosko
 * Author URI:        https://www.designcart.pl/pawel-nosko.html
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       design-cart-3d-carousel
 * Domain Path:       /languages
 */

if (!defined('ABSPATH')) {
    exit;
}

define('DC_3D_CAROUSEL_VERSION', '1.0.1');
define('DC_3D_CAROUSEL_FILE', __FILE__);
define('DC_3D_CAROUSEL_DIR', plugin_dir_path(__FILE__));
define('DC_3D_CAROUSEL_URL', plugin_dir_url(__FILE__));
define('DC_3D_CAROUSEL_BASENAME', plugin_basename(__FILE__));

require_once DC_3D_CAROUSEL_DIR . 'includes/class-data.php';
require_once DC_3D_CAROUSEL_DIR . 'includes/class-storage.php';
require_once DC_3D_CAROUSEL_DIR . 'includes/admin/class-admin-helpers.php';
require_once DC_3D_CAROUSEL_DIR . 'includes/admin/class-admin-handlers.php';
require_once DC_3D_CAROUSEL_DIR . 'includes/admin/class-admin-list.php';
require_once DC_3D_CAROUSEL_DIR . 'includes/admin/class-admin-form.php';
require_once DC_3D_CAROUSEL_DIR . 'includes/admin/class-admin.php';
require_once DC_3D_CAROUSEL_DIR . 'includes/frontend/class-frontend.php';
require_once DC_3D_CAROUSEL_DIR . 'includes/class-plugin.php';

register_activation_hook(__FILE__, ['DC_3D_Carousel_Plugin', 'activate']);

DC_3D_Carousel_Plugin::instance()->boot();
