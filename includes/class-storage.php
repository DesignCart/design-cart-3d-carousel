<?php
/**
 * Design Cart 3D Carousel
 * Autor: Paweł Nosko → https://www.designcart.pl/pawel-nosko.html
 * Firma: Design Cart → https://www.designcart.pl/
 */

if (!defined('ABSPATH')) {
    exit;
}

final class DC_3D_Carousel_Storage {
    public const SUBDIR = 'design-cart-3d-carousel';

    /**
     * @return array{basedir:string,baseurl:string}
     */
    public static function root(): array {
        $uploads = wp_upload_dir();
        $basedir = trailingslashit($uploads['basedir']) . self::SUBDIR;
        $baseurl = trailingslashit($uploads['baseurl']) . self::SUBDIR;

        return [
            'basedir' => $basedir,
            'baseurl' => $baseurl,
        ];
    }

    public static function dir(int $carousel_id): string {
        return self::root()['basedir'] . '/' . max(0, $carousel_id);
    }

    public static function url_base(int $carousel_id): string {
        return self::root()['baseurl'] . '/' . max(0, $carousel_id);
    }

    public static function ensure_dir(int $carousel_id): string {
        $dir = self::dir($carousel_id);
        if (!is_dir($dir)) {
            wp_mkdir_p($dir);
        }

        $index = $dir . '/index.php';
        if (!is_file($index)) {
            // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
            file_put_contents(
                $index,
                "<?php\n// Silence is golden.\n"
            );
        }

        $htaccess = $dir . '/.htaccess';
        if (!is_file($htaccess)) {
            // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
            file_put_contents(
                $htaccess,
                "<IfModule mod_php.c>\nphp_flag engine off\n</IfModule>\n"
            );
        }

        return $dir;
    }

    public static function url(int $carousel_id, string $file): string {
        $file = sanitize_file_name($file);
        if ($file === '' || $carousel_id <= 0) {
            return '';
        }

        $path = self::dir($carousel_id) . '/' . $file;
        if (!is_file($path)) {
            return '';
        }

        return self::url_base($carousel_id) . '/' . $file;
    }

    /**
     * @return array{file:string,url:string,alt:string}|WP_Error
     */
    public static function save_upload(int $carousel_id, array $file, string $alt = '') {
        if ($carousel_id <= 0) {
            return new WP_Error('invalid_id', __('Nieprawidłowa karuzela.', 'design-cart-3d-carousel'));
        }

        if (!isset($file['tmp_name'], $file['name'], $file['error']) || (int) $file['error'] !== UPLOAD_ERR_OK) {
            return new WP_Error('upload_error', __('Nie udało się wgrać pliku.', 'design-cart-3d-carousel'));
        }

        $check = wp_check_filetype_and_ext($file['tmp_name'], $file['name']);
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif'];
        $ext = strtolower((string) ($check['ext'] ?? ''));
        $type = (string) ($check['type'] ?? '');

        if ($ext === '' || !in_array($ext, $allowed, true) || strpos($type, 'image/') !== 0) {
            return new WP_Error('invalid_type', __('Dozwolone są tylko obrazy (JPG, PNG, GIF, WebP, AVIF).', 'design-cart-3d-carousel'));
        }

        // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- validate real image payload.
        if (!@getimagesize($file['tmp_name'])) {
            return new WP_Error('invalid_image', __('Plik nie jest prawidłowym obrazem.', 'design-cart-3d-carousel'));
        }

        if (!function_exists('wp_handle_upload')) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }

        self::ensure_dir($carousel_id);

        $upload_dir_filter = static function (array $dirs) use ($carousel_id): array {
            $subdir = '/' . self::SUBDIR . '/' . $carousel_id;
            $dirs['subdir'] = $subdir;
            $dirs['path'] = $dirs['basedir'] . $subdir;
            $dirs['url'] = $dirs['baseurl'] . $subdir;

            return $dirs;
        };

        add_filter('upload_dir', $upload_dir_filter);

        $result = wp_handle_upload(
            $file,
            [
                'test_form' => false,
                'mimes' => [
                    'jpg|jpeg|jpe' => 'image/jpeg',
                    'png' => 'image/png',
                    'gif' => 'image/gif',
                    'webp' => 'image/webp',
                    'avif' => 'image/avif',
                ],
            ]
        );

        remove_filter('upload_dir', $upload_dir_filter);

        if (!is_array($result) || !empty($result['error'])) {
            $message = is_array($result) && !empty($result['error'])
                ? (string) $result['error']
                : __('Nie udało się zapisać pliku na serwerze.', 'design-cart-3d-carousel');

            return new WP_Error('move_failed', $message);
        }

        $filename = basename((string) $result['file']);

        return [
            'file' => $filename,
            'url' => isset($result['url']) ? (string) $result['url'] : self::url($carousel_id, $filename),
            'alt' => sanitize_text_field($alt),
        ];
    }

    public static function delete_file(int $carousel_id, string $file): bool {
        $file = sanitize_file_name($file);
        if ($file === '' || $carousel_id <= 0) {
            return false;
        }

        $path = self::dir($carousel_id) . '/' . $file;
        if (!is_file($path)) {
            return false;
        }

        return (bool) wp_delete_file($path);
    }

    public static function delete_carousel_dir(int $carousel_id): void {
        if ($carousel_id <= 0) {
            return;
        }

        $dir = self::dir($carousel_id);
        if (!is_dir($dir)) {
            return;
        }

        $files = list_files($dir, 1000);
        if (is_array($files)) {
            foreach ($files as $path) {
                if (is_file($path)) {
                    wp_delete_file($path);
                }
            }
        }

        // Remove leftover index.php / .htaccess if list_files skipped them.
        foreach (['index.php', '.htaccess'] as $extra) {
            $path = $dir . '/' . $extra;
            if (is_file($path)) {
                wp_delete_file($path);
            }
        }

        if (is_dir($dir)) {
            // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_rmdir
            @rmdir($dir);
        }
    }

    /**
     * Copy image files from one carousel folder to another.
     *
     * @param array<int, array<string,mixed>> $images
     * @return array<int, array{file:string,alt:string}>
     */
    public static function copy_images(int $from_id, int $to_id, array $images): array {
        $copied = [];
        $from = self::dir($from_id);
        $to = self::ensure_dir($to_id);

        foreach ($images as $image) {
            if (!is_array($image)) {
                continue;
            }
            $file = sanitize_file_name((string) ($image['file'] ?? ''));
            if ($file === '') {
                continue;
            }
            $src = $from . '/' . $file;
            if (!is_file($src)) {
                continue;
            }

            $ext = pathinfo($file, PATHINFO_EXTENSION);
            $base = pathinfo($file, PATHINFO_FILENAME);
            $new = $base . '-copy-' . wp_generate_password(6, false, false) . ($ext ? '.' . $ext : '');
            $dest = $to . '/' . $new;

            if (@copy($src, $dest)) { // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
                $copied[] = [
                    'file' => $new,
                    'alt' => isset($image['alt']) ? sanitize_text_field((string) $image['alt']) : '',
                ];
            }
        }

        return $copied;
    }
}
