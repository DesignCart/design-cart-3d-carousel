<?php
/**
 * Design Cart 3D Carousel
 * Autor: Paweł Nosko → https://www.designcart.pl/pawel-nosko.html
 * Firma: Design Cart → https://www.designcart.pl/
 */

if (!defined('ABSPATH')) {
    exit;
}

final class DC_3D_Carousel_Admin {
    public const PAGE_LIST = 'dc-3d-carousel';
    public const PAGE_EDIT = 'dc-3d-carousel-edit';

    /** @var self|null */
    private static $instance = null;

    public static function instance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function boot(): void {
        add_action('admin_menu', [$this, 'register_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
        add_filter('admin_body_class', [$this, 'admin_body_class']);
        DC_3D_Carousel_Admin_Handlers::boot();
    }

    public function register_menu(): void {
        add_menu_page(
            __('3D Carousel', 'design-cart-3d-carousel'),
            __('3D Carousel', 'design-cart-3d-carousel'),
            'manage_options',
            self::PAGE_LIST,
            ['DC_3D_Carousel_Admin_List', 'render'],
            'dashicons-images-alt2',
            28
        );

        add_submenu_page(
            self::PAGE_LIST,
            __('Lista karuzeli', 'design-cart-3d-carousel'),
            __('Lista karuzeli', 'design-cart-3d-carousel'),
            'manage_options',
            self::PAGE_LIST,
            ['DC_3D_Carousel_Admin_List', 'render']
        );

        add_submenu_page(
            self::PAGE_LIST,
            __('Dodaj / edytuj', 'design-cart-3d-carousel'),
            __('Dodaj karuzelę', 'design-cart-3d-carousel'),
            'manage_options',
            self::PAGE_EDIT,
            ['DC_3D_Carousel_Admin_Form', 'render']
        );
    }

    public function admin_body_class(string $classes): string {
        if ($this->is_plugin_page()) {
            $classes .= ' dc-3d-carousel-admin';
        }

        return $classes;
    }

    public function enqueue_assets(string $hook): void {
        if (!$this->is_plugin_page($hook)) {
            return;
        }

        $base = DC_3D_CAROUSEL_URL . 'assets/admin/';
        $css = $base . 'css/';
        $js = $base . 'js/';
        $ver = DC_3D_CAROUSEL_VERSION;

        wp_enqueue_script('jquery-ui-sortable');

        wp_enqueue_style(
            'dc-3d-carousel-font-awesome',
            $base . 'vendor/font-awesome/font-awesome.min.css',
            [],
            '4.7.0'
        );

        $sheets = [
            'dc-tokens',
            'dc-hero',
            'dc-layout',
            'dc-tabs',
            'dc-accordion',
            'dc-switch',
            'dc-dimension',
            'dc-forms',
            'dc-colorpicker',
        ];
        $prev = 'dc-3d-carousel-font-awesome';
        foreach ($sheets as $sheet) {
            $handle = 'dc-3d-carousel-' . $sheet;
            wp_enqueue_style($handle, $css . $sheet . '.css', [$prev], $ver);
            $prev = $handle;
        }

        wp_enqueue_style(
            'dc-3d-carousel-admin',
            $css . 'admin-overrides.css',
            [$prev],
            $ver
        );

        wp_enqueue_script('dc-3d-carousel-colorpicker', $js . 'dc-colorpicker.js', [], $ver, true);
        wp_enqueue_script('dc-3d-carousel-dimension', $js . 'dc-dimension.js', [], $ver, true);
        wp_enqueue_script(
            'dc-3d-carousel-interface',
            $js . 'dc-interface.js',
            ['dc-3d-carousel-colorpicker', 'dc-3d-carousel-dimension'],
            $ver,
            true
        );
        wp_enqueue_script(
            'dc-3d-carousel-admin',
            $js . 'admin.js',
            ['jquery', 'jquery-ui-sortable', 'dc-3d-carousel-interface'],
            $ver,
            true
        );

        wp_localize_script('dc-3d-carousel-admin', 'dc3dCarouselAdmin', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('dc_3d_carousel_ajax'),
            'i18n' => [
                'confirmDelete' => __('Usunąć karuzelę? Obrazy zostaną skasowane z serwera.', 'design-cart-3d-carousel'),
                'confirmRemoveImage' => __('Usunąć ten obraz z serwera?', 'design-cart-3d-carousel'),
                'uploading' => __('Wgrywanie…', 'design-cart-3d-carousel'),
                'uploadDone' => __('Wgrano obrazy.', 'design-cart-3d-carousel'),
                'uploadError' => __('Błąd wgrywania.', 'design-cart-3d-carousel'),
                'alt' => __('Alt', 'design-cart-3d-carousel'),
                'altPlaceholder' => __('Opis obrazu', 'design-cart-3d-carousel'),
                'dragSort' => __('Przeciągnij, aby zmienić kolejność', 'design-cart-3d-carousel'),
                'removeImage' => __('Usuń obraz', 'design-cart-3d-carousel'),
            ],
        ]);
    }

    private function is_plugin_page(string $hook = ''): bool {
        $page = DC_3D_Carousel_Admin_Helpers::query_text('page');
        if (in_array($page, [self::PAGE_LIST, self::PAGE_EDIT], true)) {
            return true;
        }

        if ($hook === '') {
            return false;
        }

        return strpos($hook, self::PAGE_LIST) !== false;
    }
}
