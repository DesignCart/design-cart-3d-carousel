<?php
/**
 * Design Cart 3D Carousel
 * Autor: Paweł Nosko → https://www.designcart.pl/pawel-nosko.html
 * Firma: Design Cart → https://www.designcart.pl/
 */

if (!defined('ABSPATH')) {
    exit;
}

final class DC_3D_Carousel_Admin_Form {
    public static function render(): void {
        if (!current_user_can('manage_options')) {
            return;
        }

        $carousel_id = DC_3D_Carousel_Admin_Helpers::query_id('id');
        if ($carousel_id <= 0) {
            // Submenu „Dodaj karuzelę” — utwórz od razu, żeby dropzone miała ID katalogu.
            $create_url = wp_nonce_url(
                admin_url('admin-post.php?action=dc_3d_carousel_create'),
                'dc_3d_carousel_create'
            );
            wp_safe_redirect($create_url);
            exit;
        }

        $post = get_post($carousel_id);
        if (!$post || $post->post_type !== DC_3D_Carousel_Data::POST_TYPE) {
            wp_safe_redirect(admin_url('admin.php?page=dc-3d-carousel'));
            exit;
        }

        $carousel = DC_3D_Carousel_Data::get_carousel($carousel_id);
        $settings = $carousel['settings'];
        $images = $carousel['images'];
        $page_title = __('Edytuj karuzelę', 'design-cart-3d-carousel');

        DC_3D_Carousel_Admin_Helpers::page_start($page_title);
        DC_3D_Carousel_Admin_Helpers::render_hero(
            $page_title,
            [
                [
                    'form' => 'dc-3d-carousel-form',
                    'label' => __('Zapisz', 'design-cart-3d-carousel'),
                    'icon' => 'fa fa-save',
                    'class' => 'dc-btn dc-btn--light',
                ],
                [
                    'href' => admin_url('admin.php?page=dc-3d-carousel'),
                    'label' => '',
                    'icon' => 'fa fa-arrow-left',
                    'class' => 'dc-btn dc-btn--ghost',
                    'title' => __('Wróć do listy', 'design-cart-3d-carousel'),
                ],
            ],
            [
                ['href' => admin_url('plugins.php'), 'label' => __('Wtyczki', 'design-cart-3d-carousel')],
                ['href' => admin_url('admin.php?page=dc-3d-carousel'), 'label' => __('3D Carousel', 'design-cart-3d-carousel')],
                ['label' => $page_title],
            ]
        );

        echo '<div class="dc-page__wrap"><div class="dc-form-card"><div class="dc-interface">';
        DC_3D_Carousel_Admin_Helpers::render_notices();

        echo '<form class="dc-form dc-form--full" id="dc-3d-carousel-form" method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
        echo '<input type="hidden" name="action" value="dc_3d_carousel_save">';
        echo '<input type="hidden" name="carousel_id" id="dc-3d-carousel-id" value="' . esc_attr((string) $carousel_id) . '">';
        wp_nonce_field('dc_3d_carousel_save');

        DC_3D_Carousel_Admin_Helpers::render_nav([
            'tab-images' => ['fa fa-picture-o', __('Obrazy', 'design-cart-3d-carousel')],
            'tab-settings' => ['fa fa-paint-brush', __('Ustawienia', 'design-cart-3d-carousel')],
        ]);

        echo '<div class="dc-form-card__body">';
        self::render_images_tab($carousel['title'], $images, $carousel_id);
        self::render_settings_tab($settings);

        echo '<div class="dc-actions">';
        echo '<button type="submit" class="dc-btn dc-btn--primary"><i class="fa fa-save"></i> ' .
            esc_html__('Zapisz karuzelę', 'design-cart-3d-carousel') . '</button>';
        echo '</div></div></form></div></div></div>';

        DC_3D_Carousel_Admin_Helpers::page_end();
    }

    /**
     * @param array<int, array<string, mixed>> $images
     */
    private static function render_images_tab(string $title, array $images, int $carousel_id): void {
        echo '<div id="tab-images" class="dc-tab-panel dc-active" role="tabpanel">';

        DC_3D_Carousel_Admin_Helpers::section_start(
            'fa fa-tag',
            __('Nazwa', 'design-cart-3d-carousel'),
            __('Widoczna tylko w panelu administracyjnym', 'design-cart-3d-carousel')
        );
        DC_3D_Carousel_Admin_Helpers::text_field(
            'title',
            'carousel-title',
            __('Nazwa karuzeli', 'design-cart-3d-carousel'),
            $title,
            true
        );
        DC_3D_Carousel_Admin_Helpers::section_end();

        DC_3D_Carousel_Admin_Helpers::section_start(
            'fa fa-picture-o',
            __('Obrazy karuzeli', 'design-cart-3d-carousel'),
            __('Przeciągnij pliki, ustaw kolejność i tekst alternatywny (alt)', 'design-cart-3d-carousel')
        );

        echo '<div class="dc-3d-carousel-alert" role="note">';
        echo '<i class="fa fa-info-circle"></i><div>';
        echo '<strong>' . esc_html__('Zoptymalizuj obrazy przed wgraniem.', 'design-cart-3d-carousel') . '</strong> ';
        echo esc_html__('Karuzela 3D ładuje wiele tekstur naraz — zbyt ciężkie pliki spowalniają stronę. Możesz skompresować i przekonwertować obrazy narzędziem Design Cart:', 'design-cart-3d-carousel');
        echo ' <a href="https://www.designcart.pl/tools/dc-converter/" target="_blank" rel="noopener noreferrer">';
        echo 'https://www.designcart.pl/tools/dc-converter/</a>';
        echo '</div></div>';

        echo '<div class="dc-3d-carousel-drop" id="dc-3d-carousel-drop">';
        echo '<input class="dc-3d-carousel-drop__input" type="file" multiple accept="image/jpeg,image/png,image/gif,image/webp,image/avif,.jpg,.jpeg,.png,.gif,.webp,.avif">';
        echo '<div class="dc-3d-carousel-drop__zone">';
        echo '<svg class="dc-3d-carousel-drop__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M12 16V4m0 0l-4 4m4-4l4 4M4 17v2a2 2 0 002 2h12a2 2 0 002-2v-2"/></svg>';
        echo '<p class="dc-3d-carousel-drop__title">' . esc_html__('Przeciągnij obrazy tutaj', 'design-cart-3d-carousel') . '</p>';
        echo '<p class="dc-3d-carousel-drop__subtitle">' . esc_html__('lub kliknij, aby wybrać pliki', 'design-cart-3d-carousel') . '</p>';
        echo '<span class="dc-3d-carousel-drop__btn">' . esc_html__('Wybierz obrazy', 'design-cart-3d-carousel') . '</span>';
        echo '</div></div>';

        echo '<p class="dc-3d-carousel-upload-status" id="dc-3d-carousel-upload-status" hidden></p>';

        echo '<div class="dc-3d-carousel-tiles" id="dc-3d-carousel-tiles" data-carousel-id="' . esc_attr((string) $carousel_id) . '">';
        foreach ($images as $index => $image) {
            self::render_tile((int) $index, $image, $carousel_id);
        }
        echo '</div>';

        DC_3D_Carousel_Admin_Helpers::section_end();
        echo '</div>';
    }

    /**
     * @param array<string, mixed> $image
     */
    private static function render_tile(int $index, array $image, int $carousel_id): void {
        $file = (string) ($image['file'] ?? '');
        $alt = (string) ($image['alt'] ?? '');
        $url = DC_3D_Carousel_Storage::url($carousel_id, $file);
        if ($file === '' || $url === '') {
            return;
        }

        echo '<div class="dc-3d-carousel-tile" data-file="' . esc_attr($file) . '">';
        echo '<button type="button" class="dc-3d-carousel-tile__handle" title="' . esc_attr__('Przeciągnij, aby zmienić kolejność', 'design-cart-3d-carousel') . '"><i class="fa fa-bars"></i></button>';
        echo '<div class="dc-3d-carousel-tile__thumb"><img src="' . esc_url($url) . '" alt=""></div>';
        echo '<div class="dc-3d-carousel-tile__fields">';
        echo '<input type="hidden" name="images[' . esc_attr((string) $index) . '][file]" value="' . esc_attr($file) . '" class="dc-3d-carousel-tile__file">';
        echo '<label class="dc-label" for="dc-3d-alt-' . esc_attr((string) $index) . '">' . esc_html__('Alt', 'design-cart-3d-carousel') . '</label>';
        echo '<input class="dc-input dc-3d-carousel-tile__alt" type="text" id="dc-3d-alt-' . esc_attr((string) $index) . '" name="images[' . esc_attr((string) $index) . '][alt]" value="' . esc_attr($alt) . '" placeholder="' . esc_attr__('Opis obrazu', 'design-cart-3d-carousel') . '">';
        echo '</div>';
        echo '<button type="button" class="dc-btn dc-btn--secondary dc-3d-carousel-tile__remove" title="' . esc_attr__('Usuń obraz', 'design-cart-3d-carousel') . '"><i class="fa fa-trash"></i></button>';
        echo '</div>';
    }

    /**
     * @param array<string, mixed> $settings
     */
    private static function render_settings_tab(array $settings): void {
        echo '<div id="tab-settings" class="dc-tab-panel" role="tabpanel">';

        DC_3D_Carousel_Admin_Helpers::section_start(
            'fa fa-arrows-alt',
            __('Wymiary i układ', 'design-cart-3d-carousel'),
            __('Wysokość kontenera, rzędy i geometria okręgu', 'design-cart-3d-carousel')
        );
        echo '<div class="dc-row dc-row--2">';
        DC_3D_Carousel_Admin_Helpers::text_field(
            'settings[height]',
            'set-height',
            __('Wysokość', 'design-cart-3d-carousel'),
            (string) $settings['height'],
            false,
            __('np. 80vh, 600px — ignorowane, gdy podasz proporcje', 'design-cart-3d-carousel')
        );
        DC_3D_Carousel_Admin_Helpers::text_field(
            'settings[aspectRatio]',
            'set-aspect-ratio',
            __('Proporcje kontenera', 'design-cart-3d-carousel'),
            (string) $settings['aspectRatio'],
            false,
            __('opcjonalnie, np. 16 / 9', 'design-cart-3d-carousel')
        );
        DC_3D_Carousel_Admin_Helpers::range_field('settings[layers]', 'set-layers', __('Liczba rzędów', 'design-cart-3d-carousel'), $settings['layers'], 1, 12, '1');
        DC_3D_Carousel_Admin_Helpers::range_field('settings[imagesPerLayer]', 'set-count', __('Zdjęć w okręgu', 'design-cart-3d-carousel'), $settings['imagesPerLayer'], 3, 60, '1');
        DC_3D_Carousel_Admin_Helpers::range_field('settings[imageHeight]', 'set-img-h', __('Wysokość zdjęcia (scena)', 'design-cart-3d-carousel'), $settings['imageHeight'], 0.2, 10, '0.1');
        DC_3D_Carousel_Admin_Helpers::range_field('settings[aspect]', 'set-aspect', __('Proporcje zdjęć (szer./wys.)', 'design-cart-3d-carousel'), $settings['aspect'], 0.3, 4, '0.05');
        DC_3D_Carousel_Admin_Helpers::range_field('settings[gap]', 'set-gap', __('Odstęp między zdjęciami', 'design-cart-3d-carousel'), $settings['gap'], 0, 10, '0.1');
        DC_3D_Carousel_Admin_Helpers::range_field('settings[layerGap]', 'set-layer-gap', __('Odstęp między rzędami', 'design-cart-3d-carousel'), $settings['layerGap'], 0, 5, '0.1');
        DC_3D_Carousel_Admin_Helpers::text_field(
            'settings[diameter]',
            'set-diameter',
            __('Średnica koła', 'design-cart-3d-carousel'),
            $settings['diameter'] === '' || $settings['diameter'] === null ? '' : (string) $settings['diameter'],
            false,
            __('puste = wyliczana automatycznie', 'design-cart-3d-carousel')
        );
        echo '</div>';
        DC_3D_Carousel_Admin_Helpers::section_end();

        DC_3D_Carousel_Admin_Helpers::section_start(
            'fa fa-video-camera',
            __('Kamera i ruch', 'design-cart-3d-carousel'),
            __('Zbliżenie, rolka myszy, autoplay', 'design-cart-3d-carousel')
        );
        echo '<div class="dc-row dc-row--2">';
        DC_3D_Carousel_Admin_Helpers::range_field('settings[zoom]', 'set-zoom', __('Zbliżenie kamery', 'design-cart-3d-carousel'), $settings['zoom'], 0.5, 2.5, '0.05');
        DC_3D_Carousel_Admin_Helpers::range_field('settings[cameraHeight]', 'set-cam-h', __('Wysokość kamery', 'design-cart-3d-carousel'), $settings['cameraHeight'], 0, 3, '0.1');
        DC_3D_Carousel_Admin_Helpers::range_field('settings[scrollSpeed]', 'set-scroll', __('Czułość rolki', 'design-cart-3d-carousel'), $settings['scrollSpeed'], 0.0001, 0.02, '0.0001');
        DC_3D_Carousel_Admin_Helpers::range_field('settings[smoothing]', 'set-smooth', __('Płynność ruchu', 'design-cart-3d-carousel'), $settings['smoothing'], 0.5, 30, '0.5');
        DC_3D_Carousel_Admin_Helpers::range_field('settings[autoplaySpeed]', 'set-auto-speed', __('Prędkość autoplay (rad/s)', 'design-cart-3d-carousel'), $settings['autoplaySpeed'], 0.01, 2, '0.01');
        DC_3D_Carousel_Admin_Helpers::range_field('settings[focusSpeed]', 'set-focus', __('Szybkość ustawiania rzędów', 'design-cart-3d-carousel'), $settings['focusSpeed'], 0.5, 30, '0.5');
        DC_3D_Carousel_Admin_Helpers::range_field('settings[dim]', 'set-dim', __('Przyciemnienie tyłu', 'design-cart-3d-carousel'), $settings['dim'], 0, 1, '0.05');
        DC_3D_Carousel_Admin_Helpers::range_field('settings[maxPixelRatio]', 'set-dpr', __('Max. gęstość pikseli', 'design-cart-3d-carousel'), $settings['maxPixelRatio'], 1, 3, '0.5');
        echo '</div>';

        DC_3D_Carousel_Admin_Helpers::radio_group(
            'settings[wheel]',
            __('Rolka myszy', 'design-cart-3d-carousel'),
            [
                'hover' => __('nad karuzelą', 'design-cart-3d-carousel'),
                'ctrl' => __('Ctrl + rolka', 'design-cart-3d-carousel'),
                'off' => __('wyłączona', 'design-cart-3d-carousel'),
            ],
            (string) $settings['wheel']
        );

        echo '<div class="dc-3d-carousel-toggles">';
        DC_3D_Carousel_Admin_Helpers::toggle('settings[autoplay]', __('Autoplay', 'design-cart-3d-carousel'), !empty($settings['autoplay']));
        DC_3D_Carousel_Admin_Helpers::toggle('settings[pauseOnHover]', __('Pauza autoplay przy najechaniu', 'design-cart-3d-carousel'), !empty($settings['pauseOnHover']));
        DC_3D_Carousel_Admin_Helpers::toggle('settings[snap]', __('Snap do najbliższego zdjęcia', 'design-cart-3d-carousel'), !empty($settings['snap']));
        DC_3D_Carousel_Admin_Helpers::toggle('settings[buttons]', __('Przyciski prev / next', 'design-cart-3d-carousel'), !empty($settings['buttons']));
        DC_3D_Carousel_Admin_Helpers::toggle('settings[popup]', __('Powiększenie po kliknięciu', 'design-cart-3d-carousel'), !empty($settings['popup']));
        echo '</div>';

        echo '<div class="dc-row dc-row--2">';
        DC_3D_Carousel_Admin_Helpers::range_field('settings[popupDelay]', 'set-popup-delay', __('Opóźnienie popup (ms)', 'design-cart-3d-carousel'), $settings['popupDelay'], 0, 5000, '10');
        echo '</div>';
        DC_3D_Carousel_Admin_Helpers::section_end();

        DC_3D_Carousel_Admin_Helpers::section_start(
            'fa fa-paint-brush',
            __('Kolorystyka i tło', 'design-cart-3d-carousel'),
            __('Mgła shaderowa oraz kolor tła karuzeli', 'design-cart-3d-carousel')
        );
        echo '<div class="dc-row dc-row--2">';
        DC_3D_Carousel_Admin_Helpers::color_field('settings[bgColor]', __('Kolor tła', 'design-cart-3d-carousel'), (string) $settings['bgColor']);
        DC_3D_Carousel_Admin_Helpers::color_field('settings[mistColor1]', __('Mgła – kolor 1', 'design-cart-3d-carousel'), (string) $settings['mistColor1']);
        DC_3D_Carousel_Admin_Helpers::color_field('settings[mistColor2]', __('Mgła – kolor 2', 'design-cart-3d-carousel'), (string) $settings['mistColor2']);
        DC_3D_Carousel_Admin_Helpers::range_field('settings[mistIntensity]', 'set-mist-int', __('Intensywność mgły', 'design-cart-3d-carousel'), $settings['mistIntensity'], 0, 1.5, '0.05');
        DC_3D_Carousel_Admin_Helpers::range_field('settings[mistSpeed]', 'set-mist-speed', __('Prędkość mgły', 'design-cart-3d-carousel'), $settings['mistSpeed'], 0, 5, '0.05');
        echo '</div>';
        echo '<div class="dc-3d-carousel-toggles">';
        DC_3D_Carousel_Admin_Helpers::toggle('settings[transparent]', __('Przezroczyste tło (tylko mgła na tle strony)', 'design-cart-3d-carousel'), !empty($settings['transparent']));
        echo '</div>';
        DC_3D_Carousel_Admin_Helpers::section_end();

        DC_3D_Carousel_Admin_Helpers::section_start(
            'fa fa-font',
            __('Etykiety dostępności', 'design-cart-3d-carousel'),
            __('Teksty aria-label przycisków', 'design-cart-3d-carousel')
        );
        echo '<div class="dc-row dc-row--2">';
        DC_3D_Carousel_Admin_Helpers::text_field('settings[labelPrev]', 'set-label-prev', __('Poprzednie', 'design-cart-3d-carousel'), (string) $settings['labelPrev']);
        DC_3D_Carousel_Admin_Helpers::text_field('settings[labelNext]', 'set-label-next', __('Następne', 'design-cart-3d-carousel'), (string) $settings['labelNext']);
        DC_3D_Carousel_Admin_Helpers::text_field('settings[labelClose]', 'set-label-close', __('Zamknij', 'design-cart-3d-carousel'), (string) $settings['labelClose']);
        echo '</div>';
        DC_3D_Carousel_Admin_Helpers::section_end();

        echo '</div>';
    }
}
