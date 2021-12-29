<?php
/**
 * CacheTest.php
 *
 * @since       2011-05-23
 * @category    Library
 * @package     File
 * @author      Nicola Asuni <info@tecnick.com>
 * @copyright   2011-2015 Nicola Asuni - Tecnick.com LTD
 * @license     http://www.gnu.org/copyleft/lesser.html GNU-LGPL v3 (see LICENSE.TXT)
 * @link        https://github.com/tecnickcom/tc-lib-pdf-filecache
 *
 * This file is part of tc-lib-pdf-filecache software library.
 */

namespace Test;

use PHPUnit\Framework\TestCase;

/**
 * Unit Test
 *
 * @since       2011-05-23
 * @category    Library
 * @package     File
 * @author      Nicola Asuni <info@tecnick.com>
 * @copyright   2011-2016 Nicola Asuni - Tecnick.com LTD
 * @license     http://www.gnu.org/copyleft/lesser.html GNU-LGPL v3 (see LICENSE.TXT)
 * @link        https://github.com/tecnickcom/tc-lib-pdf-filecache
 */
class CacheTest extends TestUtil
{
    protected function getTestObject()
    {
        return new \Com\Tecnick\File\Cache('1_2-a+B/c');
    }

    public function testAutoPrefix()
    {
        $obj = new \Com\Tecnick\File\Cache();
        $this->assertNotEmpty($obj->getFilePrefix());
    }
    
    public function testGetCachePath()
    {
        $testObj = $this->getTestObject();
        $val = $testObj->getCachePath();
        $this->assertEquals('/', $val[0]);
        $this->assertEquals('/', substr($val, -1));

        $testObj->setCachePath();
        $this->assertEquals($val, $testObj->getCachePath());

        $path = '/tmp';
        $testObj->setCachePath($path);
        $this->assertEquals('/tmp/', $testObj->getCachePath());
    }
    
    public function testGetFilePrefix()
    {
        $testObj = $this->getTestObject();
        $val = $testObj->getFilePrefix();
        $this->assertEquals('_1_2-a-B_c_', $val);
    }
    
    public function testGetNewFileName()
    {
        $testObj = $this->getTestObject();
        $val = $testObj->getNewFileName('tst', '0123');
        $this->bcAssertMatchesRegularExpression('/_1_2-a-B_c_tst_0123_/', $val);
    }
    
    public function testDelete()
    {
        $testObj = $this->getTestObject();
        $idk = 0;
        for ($idx = 1; $idx <=2; ++$idx) {
            for ($idy = 1; $idy <=2; ++$idy) {
                $file[$idk] = $testObj->getNewFileName($idx, $idy);
                file_put_contents($file[$idk], '');
                $this->assertTrue(file_exists($file[$idk]));
                ++$idk;
            }
        }

        $testObj->delete('2', '1');
        $this->assertFalse(file_exists($file[2]));

        $testObj->delete('1');
        $this->assertFalse(file_exists($file[0]));
        $this->assertFalse(file_exists($file[1]));
        $this->assertTrue(file_exists($file[3]));

        $testObj->delete();
        $this->assertFalse(file_exists($file[3]));
    }
}
