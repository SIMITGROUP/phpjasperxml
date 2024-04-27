<?php

/**
 * CacheTest.php
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

namespace Test;

/**
 * Unit Test
 *
 * @since     2011-05-23
 * @category  Library
 * @package   File
 * @author    Nicola Asuni <info@tecnick.com>
 * @copyright 2011-2024 Nicola Asuni - Tecnick.com LTD
 * @license   http://www.gnu.org/copyleft/lesser.html GNU-LGPL v3 (see LICENSE.TXT)
 * @link      https://github.com/tecnickcom/tc-lib-pdf-filecache
 */
class CacheTest extends TestUtil
{
    protected function getTestObject(): \Com\Tecnick\File\Cache
    {
        return new \Com\Tecnick\File\Cache('1_2-a+B/c');
    }

    public function testAutoPrefix(): void
    {
        $cache = new \Com\Tecnick\File\Cache();
        $this->assertNotEmpty($cache->getFilePrefix());
    }

    public function testGetCachePath(): void
    {
        $cache = $this->getTestObject();
        $cachePath = $cache->getCachePath();
        $this->assertEquals('/', $cachePath[0]);
        $this->assertEquals('/', substr($cachePath, -1));

        $cache->setCachePath();
        $this->assertEquals($cachePath, $cache->getCachePath());

        $path = '/tmp';
        $cache->setCachePath($path);
        $this->assertEquals('/tmp/', $cache->getCachePath());
    }

    public function testGetFilePrefix(): void
    {
        $cache = $this->getTestObject();
        $filePrefix = $cache->getFilePrefix();
        $this->assertEquals('_1_2-a-B_c_', $filePrefix);
    }

    public function testGetNewFileName(): void
    {
        $cache = $this->getTestObject();
        $val = $cache->getNewFileName('tst', '0123');
        $this->assertNotFalse($val);
        $this->bcAssertMatchesRegularExpression('/_1_2-a-B_c_tst_0123_/', $val);
    }

    public function testDelete(): void
    {
        $cache = $this->getTestObject();
        $idk = 0;
        for ($idx = 1; $idx <= 2; ++$idx) {
            for ($idy = 1; $idy <= 2; ++$idy) {
                $file[$idk] = $cache->getNewFileName((string) $idx, (string) $idy);
                $this->assertNotFalse($file[$idk]);
                file_put_contents($file[$idk], '');
                $this->assertTrue(file_exists($file[$idk]));
                ++$idk;
            }
        }

        $cache->delete('2', '1');
        $this->assertNotFalse($file[2]);
        $this->assertFalse(file_exists($file[2]));

        $cache->delete('1');
        $this->assertNotFalse($file[0]);
        $this->assertFalse(file_exists($file[0]));
        $this->assertNotFalse($file[1]);
        $this->assertFalse(file_exists($file[1]));
        $this->assertNotFalse($file[3]);
        $this->assertTrue(file_exists($file[3]));

        $cache->delete();
        $this->assertFalse(file_exists($file[3]));
    }
}
