<?php

/**
 * StackTest.php
 *
 * @since       2011-05-23
 * @category    Library
 * @package     PdfFont
 * @author      Nicola Asuni <info@tecnick.com>
 * @copyright   2011-2023 Nicola Asuni - Tecnick.com LTD
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
 * @copyright   2011-2023 Nicola Asuni - Tecnick.com LTD
 * @license     http://www.gnu.org/copyleft/lesser.html GNU-LGPL v3 (see LICENSE.TXT)
 * @link        https://github.com/tecnickcom/tc-lib-pdf-font
 *
 * @SuppressWarnings(PHPMD.LongVariable)
 */
class StackTest extends TestUtil
{
    public function testStack()
    {
        $this->setupTest();
        $indir = dirname(__DIR__) . '/util/vendor/tecnickcom/tc-font-mirror/';

        $objnum = 1;
        $stack = new \Com\Tecnick\Pdf\Font\Stack(0.75, true, true, true);

        new \Com\Tecnick\Pdf\Font\Import($indir . 'freefont/FreeSans.ttf');
        $cfont = $stack->insert($objnum, 'freesans', '', 12, -0.1, 0.9, '', null);
        $this->assertNotEmpty($cfont);
        $this->assertNotEmpty($cfont['cbbox']);
        $this->bcAssertEqualsWithDelta(array(0.162, 0.0, 7.0308, 8.748), $stack->getCharBBox(65), 0.0001);

        new \Com\Tecnick\Pdf\Font\Import($indir . 'pdfa/pfb/PDFATimes.pfb');
        $afont = $stack->insert($objnum, 'times', '', 14, 0.3, 1.2, '', null);
        $this->assertNotEmpty($afont);

        new \Com\Tecnick\Pdf\Font\Import($indir . 'pdfa/pfb/PDFAHelveticaBoldOblique.pfb');
        $bfont = $stack->insert($objnum, 'helvetica', 'BIUDO', null, null, null, '', null);
        $this->assertNotEmpty($bfont);

        $this->assertEquals('BT /F3 14.000000 Tf ET' . "\r", $bfont['out']);
        $this->assertEquals('pdfahelveticaBI', $bfont['key']);
        $this->assertEquals('Type1', $bfont['type']);
        $this->bcAssertEqualsWithDelta(14, $bfont['size'], 0.0001);
        $this->bcAssertEqualsWithDelta(0.3, $bfont['spacing'], 0.0001);
        $this->bcAssertEqualsWithDelta(1.2, $bfont['stretching'], 0.0001);
        $this->bcAssertEqualsWithDelta(18.6667, $bfont['usize'], 0.0001);
        $this->bcAssertEqualsWithDelta(0.014, $bfont['cratio'], 0.0001);
        $this->bcAssertEqualsWithDelta(-1.554, $bfont['up'], 0.0001);
        $this->bcAssertEqualsWithDelta(0.966, $bfont['ut'], 0.0001);
        $this->bcAssertEqualsWithDelta(4.6704, $bfont['dw'], 0.0001);
        $this->bcAssertEqualsWithDelta(13.342, $bfont['ascent'], 0.0001);
        $this->bcAssertEqualsWithDelta(-3.08, $bfont['descent'], 0.0001);
        $this->bcAssertEqualsWithDelta(10.136, $bfont['capheight'], 0.0001);
        $this->bcAssertEqualsWithDelta(7.56, $bfont['xheight'], 0.0001);
        $this->bcAssertEqualsWithDelta(9.492, $bfont['avgwidth'], 0.0001);
        $this->bcAssertEqualsWithDelta(16.8, $bfont['maxwidth'], 0.0001);
        $this->bcAssertEqualsWithDelta(4.6704, $bfont['missingwidth'], 0.0001);
        $this->bcAssertEqualsWithDelta(array (-1.092, -3.08, 18.5976, 13.342), $bfont['fbbox'], 0.0001);

        $font = $stack->getCurrentFont();
        $this->assertEquals($bfont, $font);

        $this->assertTrue($stack->isCharDefined(65));
        $this->assertFalse($stack->isCharDefined(300));

        $this->assertEquals(75, $stack->replaceChar(65, 75));
        $this->assertEquals(65, $stack->replaceChar(65, 300));

        $this->assertEquals(array(0, 0, 0, 0), $stack->getCharBBox(300));

        $this->bcAssertEqualsWithDelta(12.1296, $stack->getCharWidth(65), 0.0001);
        $this->bcAssertEqualsWithDelta(0, $stack->getCharWidth(173), 0.0001);
        $this->bcAssertEqualsWithDelta(4.6704, $stack->getCharWidth(300), 0.0001);

        $uniarr = array(65, 173, 300);
        $this->bcAssertEqualsWithDelta(17.52, $stack->getOrdArrWidth($uniarr), 0.0001);

        $subs = array(65 => array(400, 75), 173 => array(76, 300), 300 => array(400, 77));
        $this->assertEquals(array(65, 173, 77), $stack->replaceMissingChars($uniarr, $subs));

        $font = $stack->popLastFont();
        $this->assertEquals($bfont, $font);

        $font = $stack->getCurrentFont();
        $this->assertEquals($afont, $font);

        $type = $stack->getCurrentFontType();
        $this->assertEquals('Type1', $type);

        $ftype = $stack->isCurrentUnicodeFont();
        $this->assertTrue($ftype);

        $ftype = $stack->isCurrentByteFont();
        $this->assertFalse($ftype);

        $uniarr = array(65, 173, 300, 32, 65, 173, 300, 32, 65, 173, 300);
        $widths = $stack->getOrdArrDims($uniarr);
        $this->assertEquals(11, $widths['chars']);
        $this->assertEquals(2, $widths['spaces']);
        $this->bcAssertEqualsWithDelta(60.9384, $widths['totwidth'], 0.0001);
        $this->bcAssertEqualsWithDelta(8.76, $widths['totspacewidth'], 0.0001);

        $outfont = $stack->getOutCurrentFont();
        $this->assertEquals('BT /F2 14.000000 Tf ET' . "\r", $outfont);
    }

    public function testEmptyStack()
    {
        $this->bcExpectException('\Com\Tecnick\Pdf\Font\Exception');
        $this->setupTest();
        $stack = new \Com\Tecnick\Pdf\Font\Stack(1);
        $stack->popLastFont();
    }

    public function testStackMissingFont()
    {
        $this->bcExpectException('\Com\Tecnick\Pdf\Font\Exception');
        $this->setupTest();
        $stack = new \Com\Tecnick\Pdf\Font\Stack(1);
        $objnum = 1;
        $stack->insert($objnum, 'missing');
    }
}
