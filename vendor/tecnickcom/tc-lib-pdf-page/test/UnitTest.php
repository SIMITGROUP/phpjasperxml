<?php
/**
 * UnitTest.php
 *
 * @since       2011-05-23
 * @category    Library
 * @package     PdfPage
 * @author      Nicola Asuni <info@tecnick.com>
 * @copyright   2011-2017 Nicola Asuni - Tecnick.com LTD
 * @license     http://www.gnu.org/copyleft/lesser.html GNU-LGPL v3 (see LICENSE.TXT)
 * @link        https://github.com/tecnickcom/tc-lib-pdf-page
 *
 * This file is part of tc-lib-pdf-page software library.
 */

namespace Test;

use PHPUnit\Framework\TestCase;

/**
 * Unit Test
 *
 * @since       2011-05-23
 * @category    Library
 * @package     PdfPage
 * @author      Nicola Asuni <info@tecnick.com>
 * @copyright   2011-2017 Nicola Asuni - Tecnick.com LTD
 * @license     http://www.gnu.org/copyleft/lesser.html GNU-LGPL v3 (see LICENSE.TXT)
 * @link        https://github.com/tecnickcom/tc-lib-pdf-page
 */
class UnitTest extends TestUtil
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
        $val = $testObj->convertPoints(72, 'in', 3);
        $this->assertEquals(1, $val);

        $val = $testObj->convertPoints(72, 'mm', 3);
        $this->assertEquals(25.4, $val);
    }

    public function testGetPageSizeEx()
    {
        $this->bcExpectException('\Com\Tecnick\Pdf\Page\Exception');
        $testObj = $this->getTestObject();
        $testObj->convertPoints(1, '*ERROR*', 2);
    }
}
