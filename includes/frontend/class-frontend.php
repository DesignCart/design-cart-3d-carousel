<?php
/**
 * Design Cart 3D Carousel
 * Autor: Paweł Nosko → https://www.designcart.pl/pawel-nosko.html
 * Firma: Design Cart → https://www.designcart.pl/
 */

if (!defined('ABSPATH')) {
    exit;
}

final class DC_3D_Carousel_Frontend {
    /** @var self|null */
    private static $instance = null;

    /** @var array<int, true> */
    private $rendered = [];

    public static function instance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function boot(): void {
        add_action('wp_enqueue_scripts', [$this, 'register_assets']);
        add_shortcode('dc_3d_carousel', [$this, 'render_shortcode']);
        add_shortcode('dc-3d-carousel', [$this, 'render_shortcode']);
        add_shortcode('design-cart-3d-carousel', [$this, 'render_shortcode']);
    }

    public function register_assets(): void {
        $base = DC_3D_CAROUSEL_URL . 'assets/frontend/';
        $ver = DC_3D_CAROUSEL_VERSION;

        wp_register_style(
            'dc-3d-carousel-front',
            $base . 'css/front.css',
            [],
            $ver
        );

        wp_register_script(
            'dc-3d-carousel-three',
            $base . 'vendor/three/three.min.js',
            [],
            'r128',
            true
        );

        wp_register_script(
            'dc-3d-carousel-core',
            $base . 'js/carousel.js',
            ['dc-3d-carousel-three'],
            $ver,
            true
        );

        wp_register_script(
            'dc-3d-carousel-front',
            $base . 'js/front.js',
            ['dc-3d-carousel-core'],
            $ver,
            true
        );
    }

    /**
     * @param array<string, mixed>|string $atts
     */
    public function render_shortcode($atts): string {
        $atts = shortcode_atts(['id' => 0], $atts, 'dc_3d_carousel');
        return $this->render_carousel((int) $atts['id']);
    }

    public function render_carousel(int $carousel_id): string {
        if ($carousel_id <= 0) {
            return '';
        }

        if (isset($this->rendered[$carousel_id])) {
            return '';
        }

        $post = get_post($carousel_id);
        if (!$post || $post->post_type !== DC_3D_Carousel_Data::POST_TYPE) {
            return '';
        }

        $data = DC_3D_Carousel_Data::get_carousel($carousel_id);
        if (!$data['images']) {
            return '';
        }

        $config = DC_3D_Carousel_Data::frontend_config(
            $data['settings'],
            $data['images'],
            $carousel_id
        );

        if (empty($config['images'])) {
            return '';
        }

        $this->rendered[$carousel_id] = true;

        wp_enqueue_style('dc-3d-carousel-front');
        wp_enqueue_script('dc-3d-carousel-front');

        $uid = 'dc-3d-carousel-' . $carousel_id . '-' . wp_unique_id();
        $json = wp_json_encode($config, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if (!is_string($json)) {
            return '';
        }

        return '<div class="dc-3d-carousel-wrap" id="' . esc_attr($uid) . '"'
            . ' data-dc-3d-carousel'
            . ' data-config="' . esc_attr($json) . '"'
            . '></div>';
    }
}

/**
 * @param int $id
 */
function dc_3d_carousel_render(int $id): string {
    return DC_3D_Carousel_Frontend::instance()->render_carousel($id);
}
