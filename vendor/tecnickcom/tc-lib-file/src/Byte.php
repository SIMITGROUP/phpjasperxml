<?php

/**
 * Byte.php
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

/**
 * Com\Tecnick\File\Byte
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
 */
class Byte
{
    /**
     * Initialize a new string to be processed
     *
     * @param string $str String from where to extract values
     */
    public function __construct(
        /**
         * String to process
         */
        protected string $str
    ) {
    }

    /**
     * Get BYTE from string (8-bit unsigned integer).
     *
     * @param int $offset Point from where to read the data.
     *
     * @return int 8 bit value
     */
    public function getByte(int $offset): int
    {
        $val = unpack('Ci', substr($this->str, $offset, 1));
        return $val === false ? 0 : $val['i'];
    }

    /**
     * Get ULONG from string (Big Endian 32-bit unsigned integer).
     *
     * @param int $offset Point from where to read the data
     *
     * @return int 32 bit value
     */
    public function getULong(int $offset): int
    {
        $val = unpack('Ni', substr($this->str, $offset, 4));
        return $val === false ? 0 : $val['i'];
    }

    /**
     * Get USHORT from string (Big Endian 16-bit unsigned integer).
     *
     * @param int $offset Point from where to read the data
     *
     * @return int 16 bit value
     */
    public function getUShort(int $offset): int
    {
        $val = unpack('ni', substr($this->str, $offset, 2));
        return $val === false ? 0 : $val['i'];
    }

    /**
     * Get SHORT from string (Big Endian 16-bit signed integer).
     *
     * @param int $offset Point from where to read the data.
     *
     * @return int 16 bit value
     */
    public function getShort(int $offset): int
    {
        $val = unpack('si', substr($this->str, $offset, 2));
        return $val === false ? 0 : $val['i'];
    }

    /**
     * Get UFWORD from string (Big Endian 16-bit unsigned integer).
     *
     * @param int $offset Point from where to read the data.
     *
     * @return int 16 bit value
     */
    public function getUFWord(int $offset): int
    {
        return $this->getUShort($offset);
    }

    /**
     * Get FWORD from string (Big Endian 16-bit signed integer).
     *
     * @param int $offset Point from where to read the data.
     *
     * @return int 16 bit value
     */
    public function getFWord(int $offset): int
    {
        $val = $this->getUShort($offset);
        if ($val > 0x7fff) {
            $val -= 0x10000;
        }

        return $val;
    }

    /**
     * Get FIXED from string (32-bit signed fixed-point number (16.16).
     *
     * @param int $offset Point from where to read the data.
     */
    public function getFixed(int $offset): float
    {
        // mantissa.fraction
        return (float) ($this->getFWord($offset) . '.' . $this->getUShort($offset + 2));
    }
}
