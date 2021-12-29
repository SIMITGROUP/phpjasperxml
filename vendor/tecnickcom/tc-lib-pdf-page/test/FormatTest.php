<?php
/**
 * FormatTest.php
 *
 * @since       2011-05-23
 * @category    Library
 * @package     PdfPage
 * @author      Nicola Asuni <info@tecnick.com>
 * @copyright   2011-2015 Nicola Asuni - Tecnick.com LTD
 * @license     http://www.gnu.org/copyleft/lesser.html GNU-LGPL v3 (see LICENSE.TXT)
 * @link        https://github.com/tecnickcom/tc-lib-pdf-page
 *
 * This file is part of tc-lib-pdf-page software library.
 */

namespace Test;

use PHPUnit\Framework\TestCase;

/**
 * Format Test
 *
 * @since       2011-05-23
 * @category    Library
 * @package     PdfPage
 * @author      Nicola Asuni <info@tecnick.com>
 * @copyright   2011-2015 Nicola Asuni - Tecnick.com LTD
 * @license     http://www.gnu.org/copyleft/lesser.html GNU-LGPL v3 (see LICENSE.TXT)
 * @link        https://github.com/tecnickcom/tc-lib-pdf-page
 */
class FormatTest extends TestUtil
{
    protected function getTestObject()
    {
        $col = new \Com\Tecnick\Color\Pdf;
        $enc = new \Com\Tecnick\Pdf\Encrypt\Encrypt(false);
        return new \Com\Tecnick\Pdf\Page\Page('mm', $col, $enc, false, false);
    }
    
    public function testGetPageSize()
    {
        $testObj = $this->getTestObject();
        $dims = $testObj->getPageFormatSize('A0');
        $this->assertEquals(array(2383.937, 3370.394, 'P'), $dims);

        $dims = $testObj->getPageFormatSize('A4', '', 'in', 2);
        $this->assertEquals(array(8.27, 11.69, 'P'), $dims);

        $dims = $testObj->getPageFormatSize('LEGAL', '', 'mm', 0);
        $this->assertEquals(array(216, 356, 'P'), $dims);

        $dims = $testObj->getPageFormatSize('LEGAL', 'P', 'mm', 0);
        $this->assertEquals(array(216, 356, 'P'), $dims);

        $dims = $testObj->getPageFormatSize('LEGAL', 'L', 'mm', 0);
        $this->assertEquals(array(356, 216, 'L'), $dims);
    }

    public function testGetPageSizeEx()
    {
        $this->bcExpectException('\Com\Tecnick\Pdf\Page\Exception');
        $testObj = $this->getTestObject();
        $testObj->getPageFormatSize('*ERROR*');
    }
    
    public function testGetPageOrientedSize()
    {
        $testObj = $this->getTestObject();
        $dims = $testObj->getPageOrientedSize(10, 20);
        $this->assertEquals(array(10, 20, 'P'), $dims);
        
        $dims = $testObj->getPageOrientedSize(10, 20, 'P');
        $this->assertEquals(array(10, 20, 'P'), $dims);
        
        $dims = $testObj->getPageOrientedSize(10, 20, 'L');
        $this->assertEquals(array(20, 10, 'L'), $dims);
        
        $dims = $testObj->getPageOrientedSize(20, 10, 'P');
        $this->assertEquals(array(10, 20, 'P'), $dims);
        
        $dims = $testObj->getPageOrientedSize(20, 10, 'L');
        $this->assertEquals(array(20, 10, 'L'), $dims);

        $dims = $testObj->getPageOrientedSize(20, 10);
        $this->assertEquals(array(20, 10, 'L'), $dims);
    }
    
    public function testGetPageOrientation()
    {
        $testObj = $this->getTestObject();
        $orient = $testObj->getPageOrientation(10, 20);
        $this->assertEquals('P', $orient);

        $orient = $testObj->getPageOrientation(20, 10);
        $this->assertEquals('L', $orient);
    }
}
