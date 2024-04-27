<?php

/**
 * Convert.php
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

namespace Com\Tecnick\Unicode;

use Com\Tecnick\Unicode\Exception as UniException;

/**
 * Com\Tecnick\Unicode\Convert
 *
 * @since     2015-07-13
 * @category  Library
 * @package   Unicode
 * @author    Nicola Asuni <info@tecnick.com>
 * @copyright 2011-2024 Nicola Asuni - Tecnick.com LTD
 * @license   http://www.gnu.org/copyleft/lesser.html GNU-LGPL v3 (see LICENSE.TXT)
 * @link      https://github.com/tecnickcom/tc-lib-unicode
 */
class Convert extends \Com\Tecnick\Unicode\Convert\Encoding
{
    /**
     * Returns the unicode string containing the character specified by value
     *
     * @param int $ord Unicode character value to convert
     *
     * @return string Returns the unicode string
     */
    public function chr(int $ord): string
    {
        return mb_convert_encoding(pack('N', $ord), 'UTF-8', 'UCS-4BE');
    }

    /**
     * Returns the unicode value of the specified character
     *
     * @param string $chr Unicode character
     *
     * @return int Returns the unicode value
     */
    public function ord(string $chr): int
    {
        $uni = unpack('N', mb_convert_encoding($chr, 'UCS-4BE', 'UTF-8'));
        if ($uni === false) {
            throw new UniException('Error converting string');
        }

        return $uni[1];
    }

    /**
     * Converts an UTF-8 string to an array of UTF-8 codepoints (integer values)
     *
     * @param string $str String to convert
     *
     * @return array<int, string>
     */
    public function strToChrArr(string $str): array
    {
        $ret = preg_split('//u', $str, -1, PREG_SPLIT_NO_EMPTY);
        if ($ret === false) {
            throw new UniException('Error splitting string');
        }

        return $ret;
    }

    /**
     * Converts an array of UTF-8 chars to an array of codepoints (integer values)
     *
     * @param array<string> $chars Array of UTF-8 chars
     *
     * @return array<int>
     */
    public function chrArrToOrdArr(array $chars): array
    {
        return array_map(fn (string $chr): int => $this->ord($chr), $chars);
    }

    /**
     * Converts an array of UTF-8 code points array of chars
     *
     * @param array<int> $ords Array of UTF-8 code points
     *
     * @return array<string>
     */
    public function ordArrToChrArr(array $ords): array
    {
        return array_map(fn (int $ord): string => $this->chr($ord), $ords);
    }

    /**
     * Converts an UTF-8 string to an array of UTF-8 codepoints (integer values)
     *
     * @param string $str Convert to convert
     *
     * @return array<int>
     */
    public function strToOrdArr(string $str): array
    {
        return $this->chrArrToOrdArr($this->strToChrArr($str));
    }

    /**
     * Extract a slice of the $uniarr array and return it as string
     *
     * @param array<string> $uniarr The input array of characters
     * @param int   $start  The position of the starting element
     * @param int|null   $end    The position of the first element that will not be returned.
     */
    public function getSubUniArrStr(array $uniarr, int $start = 0, ?int $end = null): string
    {
        if ($end === null) {
            $end = count($uniarr);
        }

        return implode('', array_slice($uniarr, $start, ($end - $start)));
    }
}
