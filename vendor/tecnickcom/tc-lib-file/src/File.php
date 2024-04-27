<?php

/**
 * File.php
 *
 * @since     2015-07-28
 * @category  Library
 * @package   File
 * @author    Nicola Asuni <info@tecnick.com>
 * @copyright 2015-2024 Nicola Asuni - Tecnick.com LTD
 * @license   http://www.gnu.org/copyleft/lesser.html GNU-LGPL v3 (see LICENSE.TXT)
 * @link      https://github.com/tecnickcom/tc-lib-file
 *
 * This file is part of tc-lib-file software library.
 */

namespace Com\Tecnick\File;

use Com\Tecnick\File\Exception as FileException;

/**
 * Com\Tecnick\File\File
 *
 * Function to read byte-level data
 *
 * @since     2015-07-28
 * @category  Library
 * @package   File
 * @author    Nicola Asuni <info@tecnick.com>
 * @copyright 2015-2024 Nicola Asuni - Tecnick.com LTD
 * @license   http://www.gnu.org/copyleft/lesser.html GNU-LGPL v3 (see LICENSE.TXT)
 * @link      https://github.com/tecnickcom/tc-lib-file
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class File
{
    /**
     * Wrapper to use fopen only with local files
     *
     * @param string $filename Name of the file to open
     * @param string $mode     The fopen mode parameter specifies the type of access you require to the stream
     *
     * @return resource Returns a file pointer resource on success
     *
     * @throws FileException in case of error
     */
    public function fopenLocal(string $filename, string $mode): mixed
    {
        if (! str_contains($filename, '://')) {
            $filename = 'file://' . $filename;
        } elseif (! str_starts_with($filename, 'file://')) {
            throw new FileException('this is not a local file');
        }

        $handler = @fopen($filename, $mode);
        if ($handler === false) {
            throw new FileException('unable to open the file: ' . $filename);
        }

        return $handler;
    }

    /**
     * Read a 4-byte (32 bit) integer from file.
     *
     * @param resource $resource A file system pointer resource that is typically created using fopen().
     *
     * @return int 4-byte integer
     */
    public function fReadInt(mixed $resource): int
    {
        $data = fread($resource, 4);
        if ($data === false) {
            throw new FileException('unable to read the file');
        }

        $val = unpack('Ni', $data);
        return $val === false ? 0 : $val['i'];
    }

    /**
     * Binary-safe file read.
     * Reads up to length bytes from the file pointer referenced by handle.
     * Reading stops as soon as one of the following conditions is met:
     * length bytes have been read; EOF (end of file) is reached.
     *
     * @param ?resource  $resource A file system pointer resource that is typically created using fopen().
     * @param int<0, max> $length   Number of bytes to read.
     *
     * @throws FileException in case of error
     */
    public function rfRead(mixed $resource, int $length): string
    {
        $data = false;
        if (is_resource($resource)) {
            $data = @fread($resource, $length);
        }

        if (($data === false) || ($resource === null)) {
            throw new FileException('unable to read the file');
        }

        $rest = ($length - strlen($data));
        if (($rest > 0) && ! feof($resource)) {
            $stream_meta_data = stream_get_meta_data($resource);
            if ($stream_meta_data['unread_bytes'] > 0) {
                $data .= $this->rfRead($resource, $rest);
            }
        }

        return $data;
    }

    /**
     * Reads entire file into a string.
     * The file can be also an URL.
     *
     * @param string $file Name of the file or URL to read.
     */
    public function fileGetContents(string $file): string
    {
        $alt = $this->getAltFilePaths($file);
        foreach ($alt as $path) {
            $ret = $this->getFileData($path);
            if ($ret !== false) {
                return $ret;
            }
        }

        throw new FileException('unable to read the file: ' . $file);
    }

    /**
     * Reads entire file into a string.
     * The file can be also an URL if the URL wrappers are enabled.
     *
     * @param string $file Name of the file or URL to read.
     *
     * @return string|false File content or FALSE in case the file is unreadable
     */
    public function getFileData(string $file): string|false
    {
        $ret = @file_get_contents($file);
        if ($ret !== false) {
            return $ret;
        }

        // try to use CURL for URLs
        return $this->getUrlData($file);
    }

    /**
     * Reads entire remote file into a string using CURL
     *
     * @param string $url URL to read.
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function getUrlData(string $url): string|false
    {
        if (
            (ini_get('allow_url_fopen') && ! defined('FORCE_CURL'))
            || (! function_exists('curl_init'))
            || preg_match('%^(https?|ftp)://%', $url) === 0
            || preg_match('%^(https?|ftp)://%', $url) === false
        ) {
            return false;
        }

        // try to get remote file data using cURL
        $curlHandle = curl_init();
        curl_setopt($curlHandle, CURLOPT_URL, $url);
        curl_setopt($curlHandle, CURLOPT_BINARYTRANSFER, true);
        curl_setopt($curlHandle, CURLOPT_FAILONERROR, true);
        curl_setopt($curlHandle, CURLOPT_RETURNTRANSFER, true);
        if ((ini_get('open_basedir') == '') && (ini_get('safe_mode') === '' || ini_get('safe_mode') === false)) {
            curl_setopt($curlHandle, CURLOPT_FOLLOWLOCATION, true);
        }

        curl_setopt($curlHandle, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($curlHandle, CURLOPT_TIMEOUT, 30);
        curl_setopt($curlHandle, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curlHandle, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curlHandle, CURLOPT_USERAGENT, 'tc-lib-file');
        curl_setopt($curlHandle, CURLOPT_MAXREDIRS, 5);
        if (defined('CURLOPT_PROTOCOLS')) {
            curl_setopt(
                $curlHandle,
                CURLOPT_PROTOCOLS,
                CURLPROTO_HTTPS | CURLPROTO_HTTP | CURLPROTO_FTP | CURLPROTO_FTPS
            );
        }

        $ret = curl_exec($curlHandle);
        curl_close($curlHandle);
        return $ret === true ? '' : $ret;
    }

    /**
     * Returns an array of possible alternative file paths or URLs
     *
     * @param string $file Name of the file or URL to read.
     *
     * @return array<string> List of possible alternative file paths or URLs.
     */
    public function getAltFilePaths(string $file): array
    {
        $alt = [$file];
        $alt[] = $this->getAltLocalUrlPath($file);
        $url = $this->getAltMissingUrlProtocol($file);
        $alt[] = $url;
        $alt[] = $this->getAltPathFromUrl($url);
        $alt[] = $this->getAltUrlFromPath($file);
        return array_unique($alt);
    }

    /**
     * Replace URL relative path with full real server path
     *
     * @param string $file Relative URL path
     */
    protected function getAltLocalUrlPath(string $file): string
    {
        if (
            (strlen($file) > 1)
            && ($file[0] === '/')
            && ($file[1] !== '/')
            && ! empty($_SERVER['DOCUMENT_ROOT'])
            && ($_SERVER['DOCUMENT_ROOT'] !== '/')
        ) {
            $findroot = strpos($file, (string) $_SERVER['DOCUMENT_ROOT']);
            if (($findroot === false) || ($findroot > 1)) {
                $file = htmlspecialchars_decode(urldecode($_SERVER['DOCUMENT_ROOT'] . $file));
            }
        }

        return $file;
    }

    /**
     * Add missing local URL protocol
     *
     * @param string $file Relative URL path
     *
     * @return string local path or original $file
     */
    protected function getAltMissingUrlProtocol(string $file): string
    {
        if (preg_match('%^//%', $file) && ! empty($_SERVER['HTTP_HOST'])) {
            $file = $this->getDefaultUrlProtocol() . ':' . str_replace(' ', '%20', $file);
        }

        return htmlspecialchars_decode($file);
    }

    /**
     * Get the default URL protocol (http or https)
     */
    protected function getDefaultUrlProtocol(): string
    {
        $protocol = 'http';
        if (! empty($_SERVER['HTTPS']) && (strtolower($_SERVER['HTTPS']) != 'off')) {
            $protocol .= 's';
        }

        return $protocol;
    }

    /**
     * Add missing local URL protocol
     *
     * @param string $url Relative URL path
     *
     * @return string local path or original $file
     */
    protected function getAltPathFromUrl(string $url): string
    {
        if (
            preg_match('%^(https?)://%', $url) === 0
            || preg_match('%^(https?)://%', $url) === false
            || empty($_SERVER['HTTP_HOST'])
            || empty($_SERVER['DOCUMENT_ROOT'])
        ) {
            return $url;
        }

        $urldata = parse_url($url);
        if (isset($urldata['query']) && $urldata['query'] !== '') {
            return $url;
        }

        $host = $this->getDefaultUrlProtocol() . '://' . $_SERVER['HTTP_HOST'];
        if (str_starts_with($url, $host)) {
            // convert URL to full server path
            $tmp = str_replace($host, $_SERVER['DOCUMENT_ROOT'], $url);
            return htmlspecialchars_decode(urldecode($tmp));
        }

        return $url;
    }

    /**
     * Get an alternate URL from a file path
     *
     * @param string $file File name and path
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function getAltUrlFromPath(string $file): string
    {
        if (
            isset($_SERVER['SCRIPT_URI'])
            && (preg_match('%^(https?|ftp)://%', $file) === 0
            || preg_match('%^(https?|ftp)://%', $file) === false)
            && (preg_match('%^//%', $file) === 0
            || preg_match('%^//%', $file) === false)
        ) {
            $urldata = @parse_url($_SERVER['SCRIPT_URI']);
            if (! is_array($urldata) || ! isset($urldata['scheme']) || ! isset($urldata['host'])) {
                return $file;
            }

            return $urldata['scheme'] . '://' . $urldata['host'] . (($file[0] == '/') ? '' : '/') . $file;
        }

        return $file;
    }
}
