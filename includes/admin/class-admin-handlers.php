<?php
/**
 * Design Cart 3D Carousel
 * Autor: Paweł Nosko → https://www.designcart.pl/pawel-nosko.html
 * Firma: Design Cart → https://www.designcart.pl/
 */

if (!defined('ABSPATH')) {
    exit;
}

final class DC_3D_Carousel_Admin_Handlers {
    public static function boot(): void {
        add_action('admin_post_dc_3d_carousel_create', [__CLASS__, 'create_carousel']);
        add_action('admin_post_dc_3d_carousel_save', [__CLASS__, 'save_carousel']);
        add_action('admin_post_dc_3d_carousel_delete', [__CLASS__, 'delete_carousel']);
        add_action('admin_post_dc_3d_carousel_copy', [__CLASS__, 'copy_carousel']);
        add_action('wp_ajax_dc_3d_carousel_upload', [__CLASS__, 'ajax_upload']);
        add_action('wp_ajax_dc_3d_carousel_delete_image', [__CLASS__, 'ajax_delete_image']);
    }

    public static function create_carousel(): void {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Brak uprawnień.', 'design-cart-3d-carousel'));
        }
        check_admin_referer('dc_3d_carousel_create');

        $id = wp_insert_post([
            'post_type' => DC_3D_Carousel_Data::POST_TYPE,
            'post_status' => 'publish',
            'post_title' => __('Nowa karuzela 3D', 'design-cart-3d-carousel'),
        ], true);

        if (is_wp_error($id)) {
            wp_die(esc_html($id->get_error_message()));
        }

        update_post_meta((int) $id, DC_3D_Carousel_Data::META_SETTINGS, DC_3D_Carousel_Data::default_settings());
        update_post_meta((int) $id, DC_3D_Carousel_Data::META_IMAGES, []);
        DC_3D_Carousel_Storage::ensure_dir((int) $id);

        wp_safe_redirect(
            admin_url('admin.php?page=dc-3d-carousel-edit&id=' . (int) $id . '&created=1')
        );
        exit;
    }

    public static function save_carousel(): void {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Brak uprawnień.', 'design-cart-3d-carousel'));
        }
        check_admin_referer('dc_3d_carousel_save');

        $carousel_id = DC_3D_Carousel_Admin_Helpers::posted_id('carousel_id');
        $title = DC_3D_Carousel_Admin_Helpers::posted_text('title');
        $settings = DC_3D_Carousel_Data::sanitize_settings(
            DC_3D_Carousel_Admin_Helpers::posted_array('settings')
        );

        if ($carousel_id <= 0) {
            wp_die(esc_html__('Nieprawidłowa karuzela.', 'design-cart-3d-carousel'));
        }

        $post = get_post($carousel_id);
        if (!$post || $post->post_type !== DC_3D_Carousel_Data::POST_TYPE) {
            wp_die(esc_html__('Nie znaleziono karuzeli.', 'design-cart-3d-carousel'));
        }

        $images = DC_3D_Carousel_Data::sanitize_images(
            DC_3D_Carousel_Admin_Helpers::posted_array('images'),
            $carousel_id
        );

        // Remove orphaned files that are no longer in the list.
        $keep = array_column($images, 'file');
        $existing = get_post_meta($carousel_id, DC_3D_Carousel_Data::META_IMAGES, true);
        if (is_array($existing)) {
            foreach ($existing as $row) {
                $file = isset($row['file']) ? (string) $row['file'] : '';
                if ($file !== '' && !in_array($file, $keep, true)) {
                    DC_3D_Carousel_Storage::delete_file($carousel_id, $file);
                }
            }
        }

        $result = wp_update_post([
            'ID' => $carousel_id,
            'post_title' => $title !== '' ? $title : __('Nowa karuzela 3D', 'design-cart-3d-carousel'),
            'post_status' => 'publish',
        ], true);

        if (is_wp_error($result)) {
            wp_die(esc_html($result->get_error_message()));
        }

        update_post_meta($carousel_id, DC_3D_Carousel_Data::META_SETTINGS, $settings);
        update_post_meta($carousel_id, DC_3D_Carousel_Data::META_IMAGES, $images);

        wp_safe_redirect(
            admin_url('admin.php?page=dc-3d-carousel-edit&id=' . $carousel_id . '&updated=1')
        );
        exit;
    }

    public static function delete_carousel(): void {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Brak uprawnień.', 'design-cart-3d-carousel'));
        }

        $id = DC_3D_Carousel_Admin_Helpers::query_id('id');
        check_admin_referer('dc_3d_carousel_delete_' . $id);

        if ($id > 0) {
            DC_3D_Carousel_Storage::delete_carousel_dir($id);
            wp_delete_post($id, true);
        }

        wp_safe_redirect(admin_url('admin.php?page=dc-3d-carousel&deleted=1'));
        exit;
    }

    public static function copy_carousel(): void {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Brak uprawnień.', 'design-cart-3d-carousel'));
        }

        $id = DC_3D_Carousel_Admin_Helpers::query_id('id');
        check_admin_referer('dc_3d_carousel_copy_' . $id);
        $post = get_post($id);

        if ($post && $post->post_type === DC_3D_Carousel_Data::POST_TYPE) {
            $new_id = wp_insert_post([
                'post_type' => DC_3D_Carousel_Data::POST_TYPE,
                'post_status' => 'publish',
                'post_title' => $post->post_title . ' ' . __('(Kopia)', 'design-cart-3d-carousel'),
            ]);

            if ($new_id && !is_wp_error($new_id)) {
                $images = get_post_meta($id, DC_3D_Carousel_Data::META_IMAGES, true);
                $images = is_array($images) ? $images : [];
                $copied = DC_3D_Carousel_Storage::copy_images($id, (int) $new_id, $images);

                update_post_meta(
                    (int) $new_id,
                    DC_3D_Carousel_Data::META_SETTINGS,
                    get_post_meta($id, DC_3D_Carousel_Data::META_SETTINGS, true)
                );
                update_post_meta((int) $new_id, DC_3D_Carousel_Data::META_IMAGES, $copied);
            }
        }

        wp_safe_redirect(admin_url('admin.php?page=dc-3d-carousel&copied=1'));
        exit;
    }

    public static function ajax_upload(): void {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Brak uprawnień.', 'design-cart-3d-carousel')], 403);
        }

        check_ajax_referer('dc_3d_carousel_ajax', 'nonce');

        $carousel_id = isset($_POST['carousel_id']) ? absint($_POST['carousel_id']) : 0;
        $post = get_post($carousel_id);
        if (!$post || $post->post_type !== DC_3D_Carousel_Data::POST_TYPE) {
            wp_send_json_error(['message' => __('Nie znaleziono karuzeli.', 'design-cart-3d-carousel')], 404);
        }

        if (empty($_FILES['file'])) {
            wp_send_json_error(['message' => __('Brak pliku.', 'design-cart-3d-carousel')], 400);
        }

        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- handled in Storage::save_upload.
        $result = DC_3D_Carousel_Storage::save_upload($carousel_id, $_FILES['file']);
        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()], 400);
        }

        $images = get_post_meta($carousel_id, DC_3D_Carousel_Data::META_IMAGES, true);
        if (!is_array($images)) {
            $images = [];
        }
        $images[] = [
            'file' => $result['file'],
            'alt' => '',
        ];
        update_post_meta($carousel_id, DC_3D_Carousel_Data::META_IMAGES, $images);

        wp_send_json_success($result);
    }

    public static function ajax_delete_image(): void {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Brak uprawnień.', 'design-cart-3d-carousel')], 403);
        }

        check_ajax_referer('dc_3d_carousel_ajax', 'nonce');

        $carousel_id = isset($_POST['carousel_id']) ? absint($_POST['carousel_id']) : 0;
        $file = isset($_POST['file']) ? sanitize_file_name(wp_unslash((string) $_POST['file'])) : '';
        $post = get_post($carousel_id);

        if (!$post || $post->post_type !== DC_3D_Carousel_Data::POST_TYPE || $file === '') {
            wp_send_json_error(['message' => __('Nieprawidłowe dane.', 'design-cart-3d-carousel')], 400);
        }

        DC_3D_Carousel_Storage::delete_file($carousel_id, $file);

        $images = get_post_meta($carousel_id, DC_3D_Carousel_Data::META_IMAGES, true);
        if (!is_array($images)) {
            $images = [];
        }
        $images = array_values(array_filter($images, static function ($row) use ($file) {
            return is_array($row) && (($row['file'] ?? '') !== $file);
        }));
        update_post_meta($carousel_id, DC_3D_Carousel_Data::META_IMAGES, $images);

        wp_send_json_success(['file' => $file]);
    }
}
