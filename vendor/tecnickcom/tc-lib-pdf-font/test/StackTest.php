<?php
/**
 * StackTest.php
 *
 * @since       2011-05-23
 * @category    Library
 * @package     PdfFont
 * @author      Nicola Asuni <info@tecnick.com>
 * @copyright   2011-2015 Nicola Asuni - Tecnick.com LTD
 * @license     http://www.gnu.org/copyleft/lesser.html GNU-LGPL v3 (see LICENSE.TXT)
 * @link        https://github.com/tecnickcom/tc-lib-pdf-font
 *
 * This file is part of tc-lib-pdf-font software library.
 */

namespace Test;

use PHPUnit\Framework\TestCase;

/**
 * Buffer Test
 *
 * @since       2011-05-23
 * @category    Library
 * @package     PdfFont
 * @author      Nicola Asuni <info@tecnick.com>
 * @copyright   2011-2015 Nicola Asuni - Tecnick.com LTD
 * @license     http://www.gnu.org/copyleft/lesser.html GNU-LGPL v3 (see LICENSE.TXT)
 * @link        https://github.com/tecnickcom/tc-lib-pdf-font
 *
 * @SuppressWarnings(PHPMD.LongVariable)
 */
class StackTest extends TestUtil
{
    protected $preserveGlobalState = false;
    protected $runTestInSeparateProcess = true;

    protected function setupTest()
    {
        define('K_PATH_FONTS', dirname(__DIR__).'/target/tmptest/');
        system('rm -rf '.K_PATH_FONTS.' && mkdir -p '.K_PATH_FONTS);
    }

    public function testStack()
    {
        $this->setupTest();
        $indir = dirname(__DIR__).'/util/vendor/tecnickcom/tc-font-mirror/';

        $objnum = 1;
        $stack = new \Com\Tecnick\Pdf\Font\Stack(0.75, true, true, true);

        new \Com\Tecnick\Pdf\Font\Import($indir.'freefont/FreeSans.ttf');
        $cfont = $stack->insert($objnum, 'freesans', '', 12, -0.1, 0.9, '', null);
        $this->assertNotEmpty($cfont);
        $this->assertNotEmpty($cfont['cbbox']);
        $this->assertEquals(array(0.2160, 0, 9.3744, 11.664), $stack->getCharBBox(65));

        new \Com\Tecnick\Pdf\Font\Import($indir.'pdfa/pfb/PDFATimes.pfb');
        $afont = $stack->insert($objnum, 'times', '', 14, 0.3, 1.2, '', null);
        $this->assertNotEmpty($afont);

        new \Com\Tecnick\Pdf\Font\Import($indir.'pdfa/pfb/PDFAHelveticaBoldOblique.pfb');
        $bfont = $stack->insert($objnum, 'helvetica', 'BIUDO', null, null, null, '', null);
        $this->assertNotEmpty($bfont);

        $this->assertEquals('BT /F2 14.000000 Tf ET', $bfont['out']);
        $this->assertEquals('pdfahelveticaBI', $bfont['key']);
        $this->bcAssertEqualsWithDelta(14, $bfont['size'], 0.0001);
        $this->bcAssertEqualsWithDelta(0.3, $bfont['spacing'], 0.0001);
        $this->bcAssertEqualsWithDelta(1.2, $bfont['stretching'], 0.0001);
        $this->bcAssertEqualsWithDelta(18.6667, $bfont['usize'], 0.0001);
        $this->bcAssertEqualsWithDelta(0.0187, $bfont['cratio'], 0.0001);
        $this->bcAssertEqualsWithDelta(-2.0720, $bfont['up'], 0.0001);
        $this->bcAssertEqualsWithDelta(1.288, $bfont['ut'], 0.0001);
        $this->bcAssertEqualsWithDelta(6.2272, $bfont['dw'], 0.0001);
        $this->bcAssertEqualsWithDelta(17.7893, $bfont['ascent'], 0.0001);
        $this->bcAssertEqualsWithDelta(-4.1067, $bfont['descent'], 0.0001);
        $this->bcAssertEqualsWithDelta(13.5147, $bfont['capheight'], 0.0001);
        $this->bcAssertEqualsWithDelta(10.08, $bfont['xheight'], 0.0001);
        $this->bcAssertEqualsWithDelta(12.6560, $bfont['avgwidth'], 0.0001);
        $this->bcAssertEqualsWithDelta(22.4000, $bfont['maxwidth'], 0.0001);
        $this->bcAssertEqualsWithDelta(6.2272, $bfont['missingwidth'], 0.0001);
        $this->bcAssertEqualsWithDelta(array (-1.456, -4.1067, 24.7968, 17.7893), $bfont['fbbox'], 0.0001);

        $font = $stack->getCurrentFont();
        $this->assertEquals($bfont, $font);

        $this->assertTrue($stack->isCharDefined(65));
        $this->assertFalse($stack->isCharDefined(300));

        $this->assertEquals(75, $stack->replaceChar(65, 75));
        $this->assertEquals(65, $stack->replaceChar(65, 300));

        $this->assertEquals(array(0, 0, 0, 0), $stack->getCharBBox(300));

        $this->bcAssertEqualsWithDelta(16.1728, $stack->getCharWidth(65), 0.0001);
        $this->bcAssertEqualsWithDelta(0, $stack->getCharWidth(173), 0.0001);
        $this->bcAssertEqualsWithDelta(6.2272, $stack->getCharWidth(300), 0.0001);

        $uniarr = array(65, 173, 300);
        $this->bcAssertEqualsWithDelta(23.12, $stack->getOrdArrWidth($uniarr), 0.0001);

        $subs = array(65 => array(400, 75), 173 => array(76, 300), 300 => array(400, 77));
        $this->assertEquals(array(65, 173, 77), $stack->replaceMissingChars($uniarr, $subs));

        $font = $stack->popLastFont();
        $this->assertEquals($bfont, $font);

        $font = $stack->getCurrentFont();
        $this->assertEquals($afont, $font);
    }

    public function testEmptyStack()
    {
        $this->bcExpectException('\Com\Tecnick\Pdf\Font\Exception');
        $this->setupTest();
        $stack = new \Com\Tecnick\Pdf\Font\Stack(1);
        $stack->popLastFont();
    }

    public function testStackMIssingFont()
    {
        $this->bcExpectException('\Com\Tecnick\Pdf\Font\Exception');
        $this->setupTest();
        $stack = new \Com\Tecnick\Pdf\Font\Stack(1);
        $objnum = 1;
        $stack->insert($objnum, 'missing');
    }
}
