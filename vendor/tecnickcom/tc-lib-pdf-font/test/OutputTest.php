<?php

/**
 * OutputTest.php
 *
 * @since     2011-05-23
 * @category  Library
 * @package   PdfFont
 * @author    Nicola Asuni <info@tecnick.com>
 * @copyright 2011-2024 Nicola Asuni - Tecnick.com LTD
 * @license   http://www.gnu.org/copyleft/lesser.html GNU-LGPL v3 (see LICENSE.TXT)
 * @link      https://github.com/tecnickcom/tc-lib-pdf-font
 *
 * This file is part of tc-lib-pdf-font software library.
 */

namespace Test;

/**
 * Output Test
 *
 * @since     2011-05-23
 * @category  Library
 * @package   PdfFont
 * @author    Nicola Asuni <info@tecnick.com>
 * @copyright 2011-2024 Nicola Asuni - Tecnick.com LTD
 * @license   http://www.gnu.org/copyleft/lesser.html GNU-LGPL v3 (see LICENSE.TXT)
 * @link      https://github.com/tecnickcom/tc-lib-pdf-font
 *
 * @SuppressWarnings(PHPMD.LongVariable)
 */
class OutputTest extends TestUtil
{
    public function testOutput(): void
    {
        $this->setupTest();
        $indir = dirname(__DIR__) . '/util/vendor/tecnickcom/tc-font-mirror/';

        $objnum = 1;
        $stack = new \Com\Tecnick\Pdf\Font\Stack(1);

        new \Com\Tecnick\Pdf\Font\Import($indir . 'pdfa/pfb/PDFASymbol.pfb', '', 'Type1', 'symbol');
        $stack->add($objnum, 'pdfasymbol');

        new \Com\Tecnick\Pdf\Font\Import($indir . 'core/Helvetica.afm');
        $stack->add($objnum, 'helvetica');

        new \Com\Tecnick\Pdf\Font\Import($indir . 'core/Helvetica-Bold.afm');
        $stack->add($objnum, 'helvetica', 'B');

        new \Com\Tecnick\Pdf\Font\Import($indir . 'core/Helvetica-BoldOblique.afm');
        $stack->add($objnum, 'helveticaBI');

        new \Com\Tecnick\Pdf\Font\Import($indir . 'core/Helvetica-Oblique.afm');
        $stack->add($objnum, 'helvetica', 'I');

        new \Com\Tecnick\Pdf\Font\Import($indir . 'freefont/FreeSans.ttf');
        $stack->add($objnum, 'freesans', '');

        new \Com\Tecnick\Pdf\Font\Import($indir . 'freefont/FreeSansBold.ttf');
        $stack->add($objnum, 'freesans', 'B');

        new \Com\Tecnick\Pdf\Font\Import($indir . 'freefont/FreeSansOblique.ttf');
        $stack->add($objnum, 'freesans', 'I');

        new \Com\Tecnick\Pdf\Font\Import($indir . 'freefont/FreeSansBoldOblique.ttf');
        $stack->add($objnum, 'freesans', 'BIUDO', '', true);

        new \Com\Tecnick\Pdf\Font\Import($indir . 'cid0/cid0jp.ttf', '', 'CID0JP');
        $stack->add($objnum, 'cid0jp');

        $fonts = $stack->getFonts();
        $this->assertCount(10, $fonts);

        $encrypt = new \Com\Tecnick\Pdf\Encrypt\Encrypt();
        $output = new \Com\Tecnick\Pdf\Font\Output($fonts, $objnum, $encrypt);

        $this->assertEquals(37, $output->getObjectNumber());

        $this->assertNotEmpty($output->getFontsBlock());
    }
}
