<?php

/**
 * Cache.php
 *
 * @since     2011-05-23
 * @category  Library
 * @package   File
 * @author    Nicola Asuni <info@tecnick.com>
 * @copyright 2011-2024 Nicola Asuni - Tecnick.com LTD
 * @license   http://www.gnu.org/copyleft/lesser.html GNU-LGPL v3 (see LICENSE.TXT)
 * @link      https://github.com/tecnickcom/tc-lib-pdf-filecache
 *
 * This file is part of tc-lib-pdf-filecache software library.
 */

namespace Com\Tecnick\File;

/**
 * Com\Tecnick\Pdf\File\Cache
 *
 * @since     2011-05-23
 * @category  Library
 * @package   File
 * @author    Nicola Asuni <info@tecnick.com>
 * @copyright 2011-2024 Nicola Asuni - Tecnick.com LTD
 * @license   http://www.gnu.org/copyleft/lesser.html GNU-LGPL v3 (see LICENSE.TXT)
 * @link      https://github.com/tecnickcom/tc-lib-pdf-filecache
 */
class Cache
{
    /**
     * Cache path
     *
     * @var string
     */
    protected static $path = '';

    /**
     * File prefix
     */
    protected static string $prefix;

    /**
     * Set the file prefix (common name)
     *
     * @param string $prefix Common prefix to be used for all cache files
     */
    public function __construct($prefix = null)
    {
        $this->defineSystemCachePath();
        $this->setCachePath();
        if ($prefix === null) {
            $prefix = rtrim(base64_encode(pack('H*', md5(uniqid((string) random_int(0, mt_getrandmax()), true)))), '=');
        }

        self::$prefix = '_' . preg_replace('/[^a-zA-Z0-9_\-]/', '', strtr($prefix, '+/', '-_')) . '_';
    }

    /**
     * Get the cache directory path
     */
    public function getCachePath(): string
    {
        return self::$path;
    }

    /**
     * Set the default cache directory path
     *
     * @param ?string $path Cache directory path; if null use the K_PATH_CACHE value
     */
    public function setCachePath(?string $path = null): void
    {
        if (($path === null) || ! is_writable($path)) {
            /* @phpstan-ignore-next-line */
            self::$path = K_PATH_CACHE;
            return;
        }

        self::$path = $this->normalizePath($path);
    }

    /**
     * Get the file prefix
     */
    public function getFilePrefix(): string
    {
        return self::$prefix;
    }

    /**
     * Returns a temporary filename for caching files
     *
     * @param string $type Type of file
     * @param string $key  File key (used to retrieve file from cache)
     *
     * @return string|false filename
     */
    public function getNewFileName(string $type = 'tmp', string $key = '0'): string|bool
    {
        return tempnam(self::$path, self::$prefix . $type . '_' . $key . '_');
    }

    /**
     * Delete cached files
     *
     * @param ?string $type Type of files to delete
     * @param ?string $key  Specific file key to delete
     */
    public function delete(?string $type = null, ?string $key = null): void
    {
        $path = self::$path . self::$prefix;
        if ($type !== null) {
            $path .= $type . '_';
            if ($key !== null) {
                $path .= $key . '_';
            }
        }

        $path .= '*';
        $files = glob($path);
        if ($files === []) {
            return;
        }

        if ($files === false) {
            return;
        }

        array_map('unlink', $files);
    }

    /**
     * Set the K_PATH_CACHE constant (if not set) to the default system directory for temporary files
     */
    protected function defineSystemCachePath(): void
    {
        if (defined('K_PATH_CACHE')) {
            return;
        }

        $kPathCache = ini_get('upload_tmp_dir') ?: sys_get_temp_dir();
        define('K_PATH_CACHE', $this->normalizePath($kPathCache));
    }

    /**
     * Normalize cache path
     *
     * @param string $path Path to normalize
     */
    protected function normalizePath(string $path): string
    {
        $rpath = realpath($path);
        if ($rpath === false) {
            return '';
        }

        if (! str_ends_with($rpath, '/')) {
            $rpath .= '/';
        }

        return $rpath;
    }
}
