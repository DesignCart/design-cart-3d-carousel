<?php
/**
 * Design Cart 3D Carousel
 * Autor: Paweł Nosko → https://www.designcart.pl/pawel-nosko.html
 * Firma: Design Cart → https://www.designcart.pl/
 */

if (!defined('ABSPATH')) {
    exit;
}

final class DC_3D_Carousel_Admin_List {
    public static function render(): void {
        if (!current_user_can('manage_options')) {
            return;
        }

        $query = new WP_Query([
            'post_type' => DC_3D_Carousel_Data::POST_TYPE,
            'posts_per_page' => -1,
            'post_status' => 'any',
            'orderby' => 'date',
            'order' => 'DESC',
        ]);

        $create_url = wp_nonce_url(
            admin_url('admin-post.php?action=dc_3d_carousel_create'),
            'dc_3d_carousel_create'
        );

        DC_3D_Carousel_Admin_Helpers::page_start(__('Lista karuzeli', 'design-cart-3d-carousel'));
        DC_3D_Carousel_Admin_Helpers::render_hero(
            __('3D Carousel', 'design-cart-3d-carousel'),
            [
                [
                    'href' => $create_url,
                    'label' => __('Dodaj karuzelę', 'design-cart-3d-carousel'),
                    'icon' => 'fa fa-plus',
                    'class' => 'dc-btn dc-btn--light',
                ],
            ],
            [
                ['href' => admin_url('plugins.php'), 'label' => __('Wtyczki', 'design-cart-3d-carousel')],
                ['label' => __('Design Cart 3D Carousel', 'design-cart-3d-carousel')],
                ['label' => __('Lista', 'design-cart-3d-carousel')],
            ]
        );

        echo '<div class="dc-page__wrap"><div class="dc-form-card"><div class="dc-interface">';
        DC_3D_Carousel_Admin_Helpers::render_notices();
        echo '<div class="dc-form-card__body">';
        DC_3D_Carousel_Admin_Helpers::section_start(
            'fa fa-cube',
            __('Twoje karuzele', 'design-cart-3d-carousel'),
            __('Shortcode: [dc_3d_carousel id="ID"]', 'design-cart-3d-carousel')
        );

        echo '<div class="dc-3d-carousel-list-toolbar">';
        echo '<a class="dc-btn dc-btn--primary" href="' . esc_url($create_url) . '">';
        echo '<i class="fa fa-plus"></i> ' . esc_html__('Dodaj karuzelę', 'design-cart-3d-carousel');
        echo '</a></div>';

        if ($query->have_posts()) {
            echo '<div class="dc-3d-carousel-list">';
            while ($query->have_posts()) {
                $query->the_post();
                $id = (int) get_the_ID();
                $data = DC_3D_Carousel_Data::get_carousel($id);
                $count = count($data['images']);
                $edit = admin_url('admin.php?page=dc-3d-carousel-edit&id=' . $id);
                $copy = wp_nonce_url(
                    admin_url('admin-post.php?action=dc_3d_carousel_copy&id=' . $id),
                    'dc_3d_carousel_copy_' . $id
                );
                $delete = wp_nonce_url(
                    admin_url('admin-post.php?action=dc_3d_carousel_delete&id=' . $id),
                    'dc_3d_carousel_delete_' . $id
                );

                echo '<div class="dc-3d-carousel-item">';
                echo '<div class="dc-3d-carousel-item__main">';
                echo '<strong class="dc-3d-carousel-item__title">' . esc_html(get_the_title()) . '</strong>';
                echo '<code class="dc-3d-carousel-item__shortcode">[dc_3d_carousel id="' . esc_html((string) $id) . '"]</code>';
                echo '<span class="dc-3d-carousel-item__meta">' .
                    esc_html(sprintf(
                        /* translators: %d: number of images */
                        _n('%d obraz', '%d obrazów', $count, 'design-cart-3d-carousel'),
                        $count
                    )) .
                    '</span>';
                echo '</div>';
                echo '<div class="dc-3d-carousel-item__actions">';
                echo '<a class="dc-btn dc-btn--secondary" href="' . esc_url($edit) . '" title="' . esc_attr__('Edytuj', 'design-cart-3d-carousel') . '"><i class="fa fa-pencil"></i></a>';
                echo '<a class="dc-btn dc-btn--secondary" href="' . esc_url($copy) . '" title="' . esc_attr__('Kopiuj', 'design-cart-3d-carousel') . '"><i class="fa fa-files-o"></i></a>';
                echo '<a class="dc-btn dc-btn--secondary dc-3d-carousel-delete" href="' . esc_url($delete) . '" title="' . esc_attr__('Usuń', 'design-cart-3d-carousel') . '"><i class="fa fa-trash"></i></a>';
                echo '</div></div>';
            }
            echo '</div>';
            wp_reset_postdata();
        } else {
            echo '<p class="dc-hint">' . esc_html__('Brak karuzeli. Kliknij „Dodaj karuzelę”, aby utworzyć pierwszą.', 'design-cart-3d-carousel') . '</p>';
        }

        DC_3D_Carousel_Admin_Helpers::section_end();
        echo '</div></div></div></div>';
        DC_3D_Carousel_Admin_Helpers::page_end();
    }
}
