<?php
/**
 * Design Cart 3D Carousel
 * Autor: Paweł Nosko → https://www.designcart.pl/pawel-nosko.html
 * Firma: Design Cart → https://www.designcart.pl/
 */

if (!defined('ABSPATH')) {
    exit;
}

final class DC_3D_Carousel_Plugin {
    /** @var self|null */
    private static $instance = null;

    public static function instance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public static function activate(): void {
        self::instance()->register_post_types();
        flush_rewrite_rules(false);
    }

    public function boot(): void {
        add_action('init', [$this, 'register_post_types']);
        add_filter('plugin_action_links_' . DC_3D_CAROUSEL_BASENAME, [$this, 'action_links']);

        DC_3D_Carousel_Admin::instance()->boot();
        DC_3D_Carousel_Frontend::instance()->boot();
    }

    public function register_post_types(): void {
        register_post_type(DC_3D_Carousel_Data::POST_TYPE, [
            'labels' => [
                'name' => __('Karuzele 3D', 'design-cart-3d-carousel'),
                'singular_name' => __('Karuzela 3D', 'design-cart-3d-carousel'),
            ],
            'public' => false,
            'show_ui' => false,
            'supports' => ['title'],
        ]);
    }

    /**
     * @param array<int, string> $links
     * @return array<int, string>
     */
    public function action_links(array $links): array {
        array_unshift(
            $links,
            '<a href="' . esc_url(admin_url('admin.php?page=dc-3d-carousel')) . '">' .
            esc_html__('Karuzele', 'design-cart-3d-carousel') .
            '</a>'
        );

        return $links;
    }
}
