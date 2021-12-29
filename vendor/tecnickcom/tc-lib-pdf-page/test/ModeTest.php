<?php
/**
 * ModeTest.php
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
 * Mode Test
 *
 * @since       2011-05-23
 * @category    Library
 * @package     PdfPage
 * @author      Nicola Asuni <info@tecnick.com>
 * @copyright   2011-2015 Nicola Asuni - Tecnick.com LTD
 * @license     http://www.gnu.org/copyleft/lesser.html GNU-LGPL v3 (see LICENSE.TXT)
 * @link        https://github.com/tecnickcom/tc-lib-pdf-page
 */
class ModeTest extends TestUtil
{
    protected function getTestObject()
    {
        $col = new \Com\Tecnick\Color\Pdf;
        $enc = new \Com\Tecnick\Pdf\Encrypt\Encrypt(false);
        return new \Com\Tecnick\Pdf\Page\Page('mm', $col, $enc, false, false);
    }

    public function testGetLayout()
    {
        $testObj = $this->getTestObject();
        $this->assertEquals('TwoColumnLeft', $testObj->getLayout('two'));
        $this->assertEquals('SinglePage', $testObj->getLayout(''));
        $this->assertEquals('SinglePage', $testObj->getLayout());
    }

    public function testGetDisplay()
    {
        $testObj = $this->getTestObject();
        $this->assertEquals('UseThumbs', $testObj->getDisplay('usethumbs'));
        $this->assertEquals('UseAttachments', $testObj->getDisplay(''));
        $this->assertEquals('UseNone', $testObj->getDisplay('something'));
    }
}
