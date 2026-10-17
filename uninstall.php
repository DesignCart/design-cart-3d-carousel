<?php
/**
 * Design Cart 3D Carousel
 * Autor: Paweł Nosko → https://www.designcart.pl/pawel-nosko.html
 * Firma: Design Cart → https://www.designcart.pl/
 */

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

$dc_3d_carousel_ids = get_posts([
    'post_type' => 'dc_3d_carousel',
    'posts_per_page' => -1,
    'post_status' => 'any',
    'fields' => 'ids',
]);

$dc_3d_carousel_uploads = wp_upload_dir();
$dc_3d_carousel_root = trailingslashit($dc_3d_carousel_uploads['basedir']) . 'design-cart-3d-carousel';

foreach ($dc_3d_carousel_ids as $dc_3d_carousel_id) {
    $dc_3d_carousel_dir = $dc_3d_carousel_root . '/' . (int) $dc_3d_carousel_id;
    if (is_dir($dc_3d_carousel_dir)) {
        $dc_3d_carousel_files = list_files($dc_3d_carousel_dir, 1000);
        if (is_array($dc_3d_carousel_files)) {
            foreach ($dc_3d_carousel_files as $dc_3d_carousel_path) {
                if (is_file($dc_3d_carousel_path)) {
                    wp_delete_file($dc_3d_carousel_path);
                }
            }
        }
        foreach (['index.php', '.htaccess'] as $dc_3d_carousel_extra) {
            $dc_3d_carousel_path = $dc_3d_carousel_dir . '/' . $dc_3d_carousel_extra;
            if (is_file($dc_3d_carousel_path)) {
                wp_delete_file($dc_3d_carousel_path);
            }
        }
        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_rmdir
        @rmdir($dc_3d_carousel_dir);
    }

    wp_delete_post((int) $dc_3d_carousel_id, true);
}

if (is_dir($dc_3d_carousel_root)) {
    $dc_3d_carousel_root_files = list_files($dc_3d_carousel_root, 100);
    if (is_array($dc_3d_carousel_root_files)) {
        foreach ($dc_3d_carousel_root_files as $dc_3d_carousel_path) {
            if (is_file($dc_3d_carousel_path)) {
                wp_delete_file($dc_3d_carousel_path);
            }
        }
    }
    // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_rmdir
    @rmdir($dc_3d_carousel_root);
}
