<?php

/**
 * Encoding.php
 *
 * @since     2011-05-23
 * @category  Library
 * @package   Unicode
 * @author    Nicola Asuni <info@tecnick.com>
 * @copyright 2011-2024 Nicola Asuni - Tecnick.com LTD
 * @license   http://www.gnu.org/copyleft/lesser.html GNU-LGPL v3 (see LICENSE.TXT)
 * @link      https://github.com/tecnickcom/tc-lib-unicode
 *
 * This file is part of tc-lib-unicode software library.
 */

namespace Com\Tecnick\Unicode\Convert;

use Com\Tecnick\Unicode\Data\Latin as Latin;

/**
 * Com\Tecnick\Unicode\Convert\Encoding
 *
 * @since     2015-07-13
 * @category  Library
 * @package   Unicode
 * @author    Nicola Asuni <info@tecnick.com>
 * @copyright 2011-2024 Nicola Asuni - Tecnick.com LTD
 * @license   http://www.gnu.org/copyleft/lesser.html GNU-LGPL v3 (see LICENSE.TXT)
 * @link      https://github.com/tecnickcom/tc-lib-unicode
 */
class Encoding
{
    /**
     * Converts UTF-8 code array to Latin1 codes
     *
     * @param array<int> $ordarr Array containing UTF-8 code points
     *
     * @return array<int> Array containing Latin1 code points
     */
    public function uniArrToLatinArr(array $ordarr): array
    {
        $latarr = [];
        foreach ($ordarr as $chr) {
            if ($chr < 256) {
                $latarr[] = $chr;
            } elseif (array_key_exists($chr, Latin::SUBSTITUTE)) {
                $latarr[] = Latin::SUBSTITUTE[$chr];
            } elseif ($chr !== 0xFFFD) {
                $latarr[] = 63; // '?' character
            }
        }

        return $latarr;
    }

    /**
     * Converts an array of Latin1 code points to a string
     *
     * @param array<int> $latarr Array of Latin1 code points
     */
    public function latinArrToStr(array $latarr): string
    {
        return implode('', array_map('chr', $latarr));
    }

    /**
     * Convert a string to an hexadecimal string (byte string) representation (as in the PDF standard)
     *
     * @param string $str String to convert
     */
    public function strToHex(string $str): string
    {
        $hexstr = '';
        $len = strlen($str);
        for ($idx = 0; $idx < $len; ++$idx) {
            $hexstr .= sprintf('%02s', dechex(ord($str[$idx])));
        }

        return $hexstr;
    }

    /**
     * Convert an hexadecimal string (byte string - as in the PDF standard) to string
     *
     * @param string $hex Hex code to convert
     */
    public function hexToStr(string $hex): string
    {
        if (strlen($hex) == 0) {
            return '';
        }

        $str = '';
        $bytes = str_split($hex, 2);
        foreach ($bytes as $byte) {
            $str .= chr((int) hexdec($byte));
        }

        return $str;
    }

    /**
     * Converts a string with an unknown encoding to UTF-8
     *
     * @param string $str String to convert
     * @param null|string|array<string>  $enc Array or comma separated list string of encodings
     *
     * @return string UTF-8 encoded string
     */
    public function toUTF8(string $str, null|string|array $enc = null): string
    {
        if ($enc === null) {
            $enc = (array) mb_detect_order();
        }

        $chrenc = mb_detect_encoding($str, $enc);
        if ($chrenc === false) {
            $chrenc = null;
        }

        return mb_convert_encoding($str, 'UTF-8', $chrenc);
    }

    /**
     * Converts an UTF-8 string to UTF-16BE
     *
     * @param string $str UTF-8 String to convert
     *
     * @return string UTF-16BE encoded string
     */
    public function toUTF16BE(string $str): string
    {
        return mb_convert_encoding($str, 'UTF-16BE', 'UTF-8');
    }
}
