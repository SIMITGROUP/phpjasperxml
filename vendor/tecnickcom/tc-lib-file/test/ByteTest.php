<?php

/**
 * ByteTest.php
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

namespace Test;

/**
 * Byte Color class test
 *
 * @since     2015-07-28
 * @category  Library
 * @package   File
 * @author    Nicola Asuni <info@tecnick.com>
 * @copyright 2015-2024 Nicola Asuni - Tecnick.com LTD
 * @license   http://www.gnu.org/copyleft/lesser.html GNU-LGPL v3 (see LICENSE.TXT)
 * @link      https://github.com/tecnickcom/tc-lib-file
 */
class ByteTest extends TestUtil
{
    protected function getTestObject(): \Com\Tecnick\File\Byte
    {
        $str = chr(0) . chr(0) . chr(0) . chr(0)
            . chr(1) . chr(3) . chr(7) . chr(15)
            . chr(31) . chr(63) . chr(127) . chr(255)
            . chr(254) . chr(252) . chr(248) . chr(240)
            . chr(224) . chr(192) . chr(128) . chr(0)
            . chr(255) . chr(255) . chr(255) . chr(255);
        return new \Com\Tecnick\File\Byte($str);
    }

    /**
     * @dataProvider getByteDataProvider
     */
    public function testGetByte(int $offset, int $expected): void
    {
        $byte = $this->getTestObject();
        $res = $byte->getByte($offset);
        $this->assertEquals($expected, $res);
    }

    /**
     * @return array<array{int, int}>
     */
    public static function getByteDataProvider(): array
    {
        return [
            [0, 0],
            [1, 0],
            [2, 0],
            [3, 0],
            [4, 1],
            [5, 3],
            [6, 7],
            [7, 15],
            [8, 31],
            [9, 63],
            [10, 127],
            [11, 255],
            [12, 254],
            [13, 252],
            [14, 248],
            [15, 240],
            [16, 224],
            [17, 192],
            [18, 128],
            [19, 0],
            [20, 255],
            [21, 255],
            [22, 255],
            [23, 255],
        ];
    }

    /**
     * @dataProvider getULongDataProvider
     */
    public function testGetULong(int $offset, int $expected): void
    {
        $byte = $this->getTestObject();
        $res = $byte->getULong($offset);
        $this->assertEquals($expected, $res);
    }

    /**
     * @return array<array{int, int}>
     */
    public static function getULongDataProvider(): array
    {
        return [
            [0, 0],
            [1, 1],
            [2, 259],
            [3, 66311],
            [4, 16_975_631],
            [5, 50_794_271],
            [6, 118_431_551],
            [7, 253_706_111],
            [8, 524_255_231],
            [9, 1_065_353_214],
            [10, 2_147_483_388],
            [11, 4_294_900_984],
            [12, 4_277_991_664],
            [13, 4_244_173_024],
            [14, 4_176_535_744],
            [15, 4_041_261_184],
            [16, 3_770_712_064],
            [17, 3_229_614_335],
            [18, 2_147_549_183],
            [19, 16_777_215],
            [20, 4_294_967_295],
        ];
    }

    /**
     * @dataProvider getUShortDataProvider
     */
    public function testGetUShort(int $offset, int $expected): void
    {
        $byte = $this->getTestObject();
        $res = $byte->getUShort($offset);
        $this->assertEquals($expected, $res);
    }

    /**
     * @dataProvider getUShortDataProvider
     */
    public function testGetUFWord(int $offset, int $expected): void
    {
        $byte = $this->getTestObject();
        $res = $byte->getUFWord($offset);
        $this->assertEquals($expected, $res);
    }

    /**
     * @return array<array{int, int}>
     */
    public static function getUShortDataProvider(): array
    {
        return [
            [0, 0],
            [1, 0],
            [2, 0],
            [3, 1],
            [4, 259],
            [5, 775],
            [6, 1807],
            [7, 3871],
            [8, 7999],
            [9, 16255],
            [10, 32767],
            [11, 65534],
            [12, 65276],
            [13, 64760],
            [14, 63728],
            [15, 61664],
            [16, 57536],
            [17, 49280],
            [18, 32768],
            [19, 255],
            [20, 65535],
            [21, 65535],
            [22, 65535],
        ];
    }

    /**
     * @dataProvider getShortDataProvider
     */
    public function testGetShort(int $offset, int $expected): void
    {
        $byte = $this->getTestObject();
        $res = $byte->getShort($offset);
        $this->assertEquals($expected, $res);
    }

    /**
     * @return array<array{int, int}>
     */
    public static function getShortDataProvider(): array
    {
        return [
            [0, 0],
            [1, 0],
            [2, 0],
            [3, 256],
            [4, 769],
            [5, 1795],
            [6, 3847],
            [7, 7951],
            [8, 16159],
            [9, 32575],
            [10, -129],
            [11, -257],
            [12, -770],
            [13, -1796],
            [14, -3848],
            [15, -7952],
            [16, -16160],
            [17, -32576],
            [18, 128],
            [19, -256],
            [20, -1],
            [21, -1],
            [22, -1],
        ];
    }

    /**
     * @dataProvider getFWordDataProvider
     */
    public function testGetFWord(int $offset, int $expected): void
    {
        $byte = $this->getTestObject();
        $res = $byte->getFWord($offset);
        $this->assertEquals($expected, $res);
    }

    /**
     * @return array<array{int, int}>
     */
    public static function getFWordDataProvider(): array
    {
        return [
            [0, 0],
            [1, 0],
            [2, 0],
            [3, 1],
            [4, 259],
            [5, 775],
            [6, 1807],
            [7, 3871],
            [8, 7999],
            [9, 16255],
            [10, 32767],
            [11, -2],
            [12, -260],
            [13, -776],
            [14, -1808],
            [15, -3872],
            [16, -8000],
            [17, -16256],
            [18, -32768],
            [19, 255],
            [20, -1],
            [21, -1],
            [22, -1],
        ];
    }

    /**
     * @dataProvider getFixedDataProvider
     */
    public function testGetFixed(int $offset, int|float $expected): void
    {
        $byte = $this->getTestObject();
        $res = $byte->getFixed($offset);
        $this->assertEquals($expected, $res);
    }

    /**
     * @return array<array{int, float}>
     */
    public static function getFixedDataProvider(): array
    {
        return [
            [0, 0],
            [1, 0.1],
            [2, 0.259],
            [3, 1.775],
            [4, 259.1807],
            [5, 775.3871],
            [6, 1807.7999],
            [7, 3871.16255],
            [8, 7999.32767],
            [9, 16255.65534],
            [10, 32767.65276],
            [11, -2.64760],
            [12, -260.63728],
            [13, -776.61664],
            [14, -1808.57536],
            [15, -3872.49280],
            [16, -8000.32768],
            [17, -16256.255],
            [18, -32768.65535],
            [19, 255.65535],
            [20, -1.65535],
        ];
    }
}
