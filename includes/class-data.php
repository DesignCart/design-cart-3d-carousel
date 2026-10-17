<?php
/**
 * Design Cart 3D Carousel
 * Autor: Paweł Nosko → https://www.designcart.pl/pawel-nosko.html
 * Firma: Design Cart → https://www.designcart.pl/
 */

if (!defined('ABSPATH')) {
    exit;
}

final class DC_3D_Carousel_Data {
    public const POST_TYPE = 'dc_3d_carousel';
    public const META_SETTINGS = '_dc_3d_carousel_settings';
    public const META_IMAGES = '_dc_3d_carousel_images';

    /**
     * Defaults 1:1 z createCarousel / DEFAULTS (Karuzela 3D.html).
     *
     * @return array<string, mixed>
     */
    public static function default_settings(): array {
        return [
            'height' => '80vh',
            'aspectRatio' => '',
            'layers' => 3,
            'imagesPerLayer' => 24,
            'imageHeight' => 2.4,
            'aspect' => 1.5,
            'gap' => 1.4,
            'layerGap' => 0.4,
            'diameter' => '',
            'zoom' => 1,
            'cameraHeight' => 1,
            'maxPixelRatio' => 2,
            'wheel' => 'hover',
            'scrollSpeed' => 0.0018,
            'smoothing' => 6,
            'snap' => 0,
            'autoplay' => 0,
            'autoplaySpeed' => 0.07,
            'pauseOnHover' => 1,
            'dim' => 1,
            'focusSpeed' => 9,
            'popup' => 1,
            'popupDelay' => 380,
            'buttons' => 1,
            'bgColor' => '#140c2b',
            'transparent' => 0,
            'mistColor1' => '#8b3dff',
            'mistColor2' => '#ff4fb8',
            'mistSpeed' => 1,
            'mistIntensity' => 1,
            'labelPrev' => 'Poprzednie',
            'labelNext' => 'Następne',
            'labelClose' => 'Zamknij',
        ];
    }

    /**
     * @return array{title:string,settings:array<string,mixed>,images:array<int,array<string,mixed>>}
     */
    public static function get_carousel(int $id = 0): array {
        $settings = self::default_settings();
        $images = [];
        $title = '';

        if ($id > 0) {
            $post = get_post($id);
            if ($post && $post->post_type === self::POST_TYPE) {
                $title = $post->post_title;
                $raw_settings = get_post_meta($id, self::META_SETTINGS, true);
                $raw_images = get_post_meta($id, self::META_IMAGES, true);
                $settings = wp_parse_args(is_array($raw_settings) ? $raw_settings : [], $settings);
                $images = is_array($raw_images) ? array_values($raw_images) : [];
            }
        }

        return [
            'title' => $title,
            'settings' => $settings,
            'images' => $images,
        ];
    }

    /**
     * @param array<string, mixed> $settings
     * @return array<string, mixed>
     */
    public static function sanitize_settings(array $settings): array {
        $defaults = self::default_settings();
        $clean = [];

        $clean['height'] = isset($settings['height'])
            ? self::sanitize_css_size((string) $settings['height'], (string) $defaults['height'])
            : $defaults['height'];

        $ratio = isset($settings['aspectRatio']) ? trim((string) $settings['aspectRatio']) : '';
        $clean['aspectRatio'] = ($ratio !== '' && preg_match('/^\d+(\.\d+)?\s*\/\s*\d+(\.\d+)?$/', $ratio))
            ? preg_replace('/\s+/', ' ', $ratio)
            : '';

        $clean['layers'] = isset($settings['layers'])
            ? max(1, min(12, (int) $settings['layers']))
            : (int) $defaults['layers'];
        $clean['imagesPerLayer'] = isset($settings['imagesPerLayer'])
            ? max(3, min(60, (int) $settings['imagesPerLayer']))
            : (int) $defaults['imagesPerLayer'];

        foreach (['imageHeight' => [0.2, 10], 'aspect' => [0.3, 4], 'gap' => [0, 10], 'layerGap' => [0, 5]] as $key => $range) {
            $clean[$key] = isset($settings[$key])
                ? max($range[0], min($range[1], (float) $settings[$key]))
                : (float) $defaults[$key];
        }

        $diameter = isset($settings['diameter']) ? trim((string) $settings['diameter']) : '';
        if ($diameter === '' || $diameter === '0' || strtolower($diameter) === 'auto') {
            $clean['diameter'] = '';
        } else {
            $clean['diameter'] = max(1, (float) $diameter);
        }

        $clean['zoom'] = isset($settings['zoom'])
            ? max(0.5, min(2.5, (float) $settings['zoom']))
            : (float) $defaults['zoom'];
        $clean['cameraHeight'] = isset($settings['cameraHeight'])
            ? max(0, min(3, (float) $settings['cameraHeight']))
            : (float) $defaults['cameraHeight'];
        $clean['maxPixelRatio'] = isset($settings['maxPixelRatio'])
            ? max(1, min(3, (float) $settings['maxPixelRatio']))
            : (float) $defaults['maxPixelRatio'];

        $wheel = isset($settings['wheel']) ? sanitize_key((string) $settings['wheel']) : $defaults['wheel'];
        $clean['wheel'] = in_array($wheel, ['hover', 'ctrl', 'off'], true) ? $wheel : 'hover';

        $clean['scrollSpeed'] = isset($settings['scrollSpeed'])
            ? max(0.0001, min(0.02, (float) $settings['scrollSpeed']))
            : (float) $defaults['scrollSpeed'];
        $clean['smoothing'] = isset($settings['smoothing'])
            ? max(0.5, min(30, (float) $settings['smoothing']))
            : (float) $defaults['smoothing'];
        $clean['autoplaySpeed'] = isset($settings['autoplaySpeed'])
            ? max(0.01, min(2, (float) $settings['autoplaySpeed']))
            : (float) $defaults['autoplaySpeed'];
        $clean['dim'] = isset($settings['dim'])
            ? max(0, min(1, (float) $settings['dim']))
            : (float) $defaults['dim'];
        $clean['focusSpeed'] = isset($settings['focusSpeed'])
            ? max(0.5, min(30, (float) $settings['focusSpeed']))
            : (float) $defaults['focusSpeed'];
        $clean['popupDelay'] = isset($settings['popupDelay'])
            ? max(0, min(5000, (int) $settings['popupDelay']))
            : (int) $defaults['popupDelay'];
        $clean['mistSpeed'] = isset($settings['mistSpeed'])
            ? max(0, min(5, (float) $settings['mistSpeed']))
            : (float) $defaults['mistSpeed'];
        $clean['mistIntensity'] = isset($settings['mistIntensity'])
            ? max(0, min(1.5, (float) $settings['mistIntensity']))
            : (float) $defaults['mistIntensity'];

        foreach (['snap', 'autoplay', 'pauseOnHover', 'popup', 'buttons', 'transparent'] as $toggle) {
            $raw = $settings[$toggle] ?? 0;
            if (is_array($raw)) {
                $raw = end($raw);
            }
            $clean[$toggle] = ((string) $raw === '1' || $raw === 1 || $raw === true) ? 1 : 0;
        }

        foreach (['bgColor', 'mistColor1', 'mistColor2'] as $color_key) {
            $color = isset($settings[$color_key]) ? sanitize_hex_color((string) $settings[$color_key]) : '';
            $clean[$color_key] = $color ? $color : $defaults[$color_key];
        }

        $clean['labelPrev'] = isset($settings['labelPrev'])
            ? sanitize_text_field((string) $settings['labelPrev'])
            : $defaults['labelPrev'];
        $clean['labelNext'] = isset($settings['labelNext'])
            ? sanitize_text_field((string) $settings['labelNext'])
            : $defaults['labelNext'];
        $clean['labelClose'] = isset($settings['labelClose'])
            ? sanitize_text_field((string) $settings['labelClose'])
            : $defaults['labelClose'];

        if ($clean['labelPrev'] === '') {
            $clean['labelPrev'] = $defaults['labelPrev'];
        }
        if ($clean['labelNext'] === '') {
            $clean['labelNext'] = $defaults['labelNext'];
        }
        if ($clean['labelClose'] === '') {
            $clean['labelClose'] = $defaults['labelClose'];
        }

        return $clean;
    }

    /**
     * @param array<int, mixed> $images
     * @param int               $carousel_id
     * @return array<int, array{file:string,alt:string}>
     */
    public static function sanitize_images(array $images, int $carousel_id): array {
        $clean = [];
        $dir = DC_3D_Carousel_Storage::dir($carousel_id);

        foreach ($images as $image) {
            if (!is_array($image)) {
                continue;
            }

            $file = isset($image['file']) ? sanitize_file_name((string) $image['file']) : '';
            if ($file === '' || strpos($file, '..') !== false || strpos($file, '/') !== false) {
                continue;
            }

            $path = $dir . '/' . $file;
            if (!is_file($path)) {
                continue;
            }

            $clean[] = [
                'file' => $file,
                'alt' => isset($image['alt']) ? sanitize_text_field((string) $image['alt']) : '',
            ];
        }

        return $clean;
    }

    /**
     * Config for createCarousel() — maps stored settings to JS options.
     *
     * @param array<string, mixed>             $settings
     * @param array<int, array<string,mixed>>  $images
     * @param int                              $carousel_id
     * @return array<string, mixed>
     */
    public static function frontend_config(array $settings, array $images, int $carousel_id): array {
        $settings = wp_parse_args($settings, self::default_settings());
        $urls = [];
        $alts = [];

        foreach ($images as $image) {
            $url = DC_3D_Carousel_Storage::url($carousel_id, (string) ($image['file'] ?? ''));
            if ($url === '') {
                continue;
            }
            $urls[] = $url;
            $alts[] = (string) ($image['alt'] ?? '');
        }

        $config = [
            'height' => $settings['height'],
            'layers' => (int) $settings['layers'],
            'imagesPerLayer' => (int) $settings['imagesPerLayer'],
            'imageHeight' => (float) $settings['imageHeight'],
            'aspect' => (float) $settings['aspect'],
            'gap' => (float) $settings['gap'],
            'layerGap' => (float) $settings['layerGap'],
            'images' => $urls,
            'imageAlts' => $alts,
            'zoom' => (float) $settings['zoom'],
            'cameraHeight' => (float) $settings['cameraHeight'],
            'maxPixelRatio' => (float) $settings['maxPixelRatio'],
            'wheel' => (string) $settings['wheel'],
            'scrollSpeed' => (float) $settings['scrollSpeed'],
            'smoothing' => (float) $settings['smoothing'],
            'snap' => !empty($settings['snap']),
            'autoplay' => !empty($settings['autoplay']),
            'autoplaySpeed' => (float) $settings['autoplaySpeed'],
            'pauseOnHover' => !empty($settings['pauseOnHover']),
            'dim' => (float) $settings['dim'],
            'focusSpeed' => (float) $settings['focusSpeed'],
            'popup' => !empty($settings['popup']),
            'popupDelay' => (int) $settings['popupDelay'],
            'buttons' => !empty($settings['buttons']),
            'bgColor' => (string) $settings['bgColor'],
            'transparent' => !empty($settings['transparent']),
            'mistColors' => [
                (string) $settings['mistColor1'],
                (string) $settings['mistColor2'],
            ],
            'mistSpeed' => (float) $settings['mistSpeed'],
            'mistIntensity' => (float) $settings['mistIntensity'],
            'labels' => [
                'prev' => (string) $settings['labelPrev'],
                'next' => (string) $settings['labelNext'],
                'close' => (string) $settings['labelClose'],
            ],
        ];

        if ($settings['aspectRatio'] !== '' && $settings['aspectRatio'] !== null) {
            $config['aspectRatio'] = (string) $settings['aspectRatio'];
            $config['height'] = null;
        }

        if ($settings['diameter'] !== '' && $settings['diameter'] !== null) {
            $config['diameter'] = (float) $settings['diameter'];
        } else {
            $config['diameter'] = null;
        }

        return $config;
    }

    private static function sanitize_css_size(string $value, string $fallback): string {
        $value = trim($value);
        if ($value === '') {
            return $fallback;
        }

        if (preg_match('/^\d+(\.\d+)?(px|vh|vw|%)$/i', $value)) {
            return $value;
        }

        if (preg_match('/^\d+$/', $value)) {
            return $value . 'px';
        }

        return $fallback;
    }
}
