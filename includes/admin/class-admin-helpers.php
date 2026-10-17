<?php
/**
 * Design Cart 3D Carousel
 * Autor: Paweł Nosko → https://www.designcart.pl/pawel-nosko.html
 * Firma: Design Cart → https://www.designcart.pl/
 */

if (!defined('ABSPATH')) {
    exit;
}

final class DC_3D_Carousel_Admin_Helpers {
    /**
     * @param array<int, array<string, string>> $actions
     * @param array<int, array<string, string>> $crumbs
     */
    public static function render_hero(string $title, array $actions, array $crumbs): void {
        $logo = DC_3D_CAROUSEL_URL . 'assets/admin/images/dc-logo-white.png';

        echo '<div class="dc-hero">';
        echo '<div class="dc-hero__mesh"></div>';
        echo '<img class="dc-hero__logo" src="' . esc_url($logo) . '" alt="" aria-hidden="true" width="384" height="384">';
        echo '<div class="dc-hero__orb dc-hero__orb--1"></div>';
        echo '<div class="dc-hero__orb dc-hero__orb--2"></div>';
        echo '<div class="dc-hero__inner"><div class="dc-hero__row">';
        echo '<div class="dc-hero__brand">';
        echo '<span class="dc-hero__icon"><i class="fa fa-cube"></i></span>';
        echo '<div><p class="dc-hero__eyebrow">Design Cart</p>';
        echo '<h1 class="dc-hero__title">' . esc_html($title) . '</h1></div></div>';
        echo '<div class="dc-hero__actions">';

        foreach ($actions as $action) {
            $class = $action['class'] ?? 'dc-btn dc-btn--light';
            $icon = $action['icon'] ?? '';
            $label = $action['label'] ?? '';
            $title_attr = $action['title'] ?? '';

            if (!empty($action['form'])) {
                echo '<button type="submit" form="' . esc_attr($action['form']) . '" class="' . esc_attr($class) . '"';
                if ($title_attr !== '') {
                    echo ' title="' . esc_attr($title_attr) . '"';
                }
                echo '>';
                if ($icon !== '') {
                    echo '<i class="' . esc_attr($icon) . '"></i> ';
                }
                echo esc_html($label) . '</button>';
            } else {
                echo '<a class="' . esc_attr($class) . '" href="' . esc_url($action['href'] ?? '') . '"';
                if ($title_attr !== '') {
                    echo ' title="' . esc_attr($title_attr) . '"';
                }
                echo '>';
                if ($icon !== '') {
                    echo '<i class="' . esc_attr($icon) . '"></i> ';
                }
                echo esc_html($label) . '</a>';
            }
        }

        echo '</div></div><ul class="dc-hero__bc">';
        foreach ($crumbs as $crumb) {
            if (!empty($crumb['href'])) {
                echo '<li><a href="' . esc_url($crumb['href']) . '">' . esc_html($crumb['label']) . '</a></li>';
            } else {
                echo '<li>' . esc_html($crumb['label']) . '</li>';
            }
        }
        echo '</ul></div></div>';
    }

    /**
     * @param array<string, array{0:string,1:string}> $tabs
     */
    public static function render_nav(array $tabs): void {
        echo '<nav class="dc-nav dc-tabs" role="tablist">';
        $first = true;
        foreach ($tabs as $id => $meta) {
            $active = $first ? ' dc-active' : '';
            $selected = $first ? 'true' : 'false';
            echo '<button type="button" class="dc-nav__btn dc-tabs__btn' . esc_attr($active) . '" data-dc-tab="' . esc_attr($id) . '" role="tab" aria-selected="' . esc_attr($selected) . '">';
            echo '<i class="' . esc_attr($meta[0]) . '"></i> ' . esc_html($meta[1]);
            echo '</button>';
            $first = false;
        }
        echo '</nav>';
    }

    public static function render_notices(): void {
        $map = [
            'updated' => __('Zapisano pomyślnie.', 'design-cart-3d-carousel'),
            'deleted' => __('Usunięto pomyślnie.', 'design-cart-3d-carousel'),
            'copied' => __('Utworzono kopię.', 'design-cart-3d-carousel'),
            'created' => __('Utworzono nową karuzelę. Dodaj obrazy i ustawienia.', 'design-cart-3d-carousel'),
        ];

        foreach ($map as $key => $message) {
            if (self::query_text($key) === '1') {
                echo '<div class="dc-3d-carousel-flash dc-3d-carousel-flash--ok"><i class="fa fa-check-circle"></i> ' .
                    esc_html($message) . '</div>';
            }
        }
    }

    public static function page_start(string $title): void {
        echo '<div class="wrap dc-3d-carousel-wrap"><h1>' . esc_html($title) . '</h1>';
        echo '<div class="dc-page dc-3d-carousel-page">';
    }

    public static function page_end(): void {
        echo '</div></div>';
    }

    public static function section_start(string $icon, string $title, string $sub = ''): void {
        echo '<div class="dc-section-card dc-section">';
        echo '<div class="dc-section-card__head">';
        echo '<span class="dc-section-card__icon"><i class="' . esc_attr($icon) . '"></i></span>';
        echo '<div><h3 class="dc-section-card__title">' . esc_html($title) . '</h3>';
        if ($sub !== '') {
            echo '<p class="dc-section-card__sub">' . esc_html($sub) . '</p>';
        }
        echo '</div></div>';
    }

    public static function section_end(): void {
        echo '</div>';
    }

    public static function text_field(string $name, string $id, string $label, string $value, bool $required = false, string $hint = ''): void {
        $req = $required ? ' dc-label--required' : '';
        echo '<div class="dc-field">';
        echo '<label class="dc-label' . esc_attr($req) . '" for="' . esc_attr($id) . '">' . esc_html($label) . '</label>';
        echo '<input class="dc-input" type="text" id="' . esc_attr($id) . '" name="' . esc_attr($name) . '" value="' . esc_attr($value) . '"' . ($required ? ' required' : '') . '>';
        if ($hint !== '') {
            echo '<p class="dc-hint">' . esc_html($hint) . '</p>';
        }
        echo '</div>';
    }

    public static function number_field(string $name, string $id, string $label, $value, $min, $max, string $step = '1', string $hint = ''): void {
        self::range_field($name, $id, $label, $value, $min, $max, $step, $hint);
    }

    /**
     * Suwak z widocznym min / max / bieżącą wartością.
     *
     * @param int|float|string $value
     * @param int|float|string $min
     * @param int|float|string $max
     */
    public static function range_field(string $name, string $id, string $label, $value, $min, $max, string $step = '1', string $hint = ''): void {
        $decimals = self::step_decimals($step);
        $val = self::format_range_number($value, $decimals);
        $min_label = self::format_range_number($min, $decimals);
        $max_label = self::format_range_number($max, $decimals);

        echo '<div class="dc-field dc-field--range">';
        echo '<div class="dc-range-head">';
        echo '<label class="dc-label" for="' . esc_attr($id) . '">' . esc_html($label) . '</label>';
        echo '<output class="dc-range-value" for="' . esc_attr($id) . '" data-decimals="' . esc_attr((string) $decimals) . '">' .
            esc_html($val) . '</output>';
        echo '</div>';
        echo '<input class="dc-range" type="range" id="' . esc_attr($id) . '" name="' . esc_attr($name) . '"'
            . ' min="' . esc_attr((string) $min) . '"'
            . ' max="' . esc_attr((string) $max) . '"'
            . ' step="' . esc_attr($step) . '"'
            . ' value="' . esc_attr($val) . '">';
        echo '<div class="dc-range-labels"><span>' . esc_html($min_label) . '</span><span>' . esc_html($max_label) . '</span></div>';
        if ($hint !== '') {
            echo '<p class="dc-hint">' . esc_html($hint) . '</p>';
        }
        echo '</div>';
    }

    private static function step_decimals(string $step): int {
        if (strpos($step, '.') === false) {
            return 0;
        }
        return strlen(rtrim(substr($step, strpos($step, '.') + 1), '0')) ?: strlen(substr($step, strpos($step, '.') + 1));
    }

    /**
     * @param int|float|string $value
     */
    private static function format_range_number($value, int $decimals): string {
        $n = (float) $value;
        if ($decimals <= 0) {
            return (string) (int) round($n);
        }

        return number_format($n, $decimals, '.', '');
    }

    /**
     * @param array<string, string> $options
     */
    public static function radio_group(string $name, string $label, array $options, string $current): void {
        echo '<div class="dc-field"><span class="dc-label">' . esc_html($label) . '</span>';
        echo '<div class="dc-switch-group dc-switch-group--solid" role="radiogroup">';
        foreach ($options as $value => $text) {
            echo '<label class="dc-switch-btn">';
            echo '<input class="dc-switch-btn__input" type="radio" name="' . esc_attr($name) . '" value="' . esc_attr($value) . '"' . checked($current, $value, false) . '>';
            echo '<span class="dc-switch-btn__label">' . esc_html($text) . '</span></label>';
        }
        echo '</div></div>';
    }

    public static function toggle(string $name, string $label, bool $checked): void {
        echo '<div class="dc-toggle-wrap">';
        echo '<input type="hidden" name="' . esc_attr($name) . '" value="0">';
        echo '<label class="dc-toggle">';
        echo '<input class="dc-toggle__input" type="checkbox" name="' . esc_attr($name) . '" value="1"' . checked($checked, true, false) . '>';
        echo '<span class="dc-toggle__track"></span><span>' . esc_html($label) . '</span></label>';
        echo '</div>';
    }

    public static function color_field(string $name, string $label, string $value): void {
        echo '<div class="dc-field">';
        echo '<label class="dc-label">' . esc_html($label) . '</label>';
        echo '<div class="dc-colorpicker" data-dc-colorpicker data-name="' . esc_attr($name) . '" data-value="' . esc_attr($value) . '" data-label="' . esc_attr($label) . '"></div>';
        echo '</div>';
    }

    public static function query_text(string $key): string {
        $value = filter_input(INPUT_GET, $key, FILTER_UNSAFE_RAW);
        if (!is_string($value)) {
            return '';
        }

        return sanitize_text_field(wp_unslash($value));
    }

    public static function query_id(string $key): int {
        return absint(self::query_text($key));
    }

    public static function posted_text(string $key): string {
        $value = filter_input(INPUT_POST, $key, FILTER_UNSAFE_RAW);
        if (!is_string($value)) {
            return '';
        }

        return sanitize_text_field(wp_unslash($value));
    }

    public static function posted_id(string $key): int {
        return absint(self::posted_text($key));
    }

    /**
     * @return array<string, mixed>
     */
    public static function posted_array(string $key): array {
        if (
            // phpcs:ignore WordPress.Security.NonceVerification.Missing -- verified by caller.
            !isset($_POST[$key])
            // phpcs:ignore WordPress.Security.NonceVerification.Missing -- verified by caller.
            || !is_array($_POST[$key])
        ) {
            return [];
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        return wp_unslash($_POST[$key]);
    }
}
