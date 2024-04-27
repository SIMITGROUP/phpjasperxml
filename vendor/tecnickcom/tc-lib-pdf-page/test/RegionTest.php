<?php

/**
 * RegionTest.php
 *
 * @since     2011-05-23
 * @category  Library
 * @package   PdfPage
 * @author    Nicola Asuni <info@tecnick.com>
 * @copyright 2011-2024 Nicola Asuni - Tecnick.com LTD
 * @license   http://www.gnu.org/copyleft/lesser.html GNU-LGPL v3 (see LICENSE.TXT)
 * @link      https://github.com/tecnickcom/tc-lib-pdf-page
 *
 * This file is part of tc-lib-pdf-page software library.
 */

namespace Test;

use Com\Tecnick\Color\Pdf;
use Com\Tecnick\Pdf\Encrypt\Encrypt;
use Com\Tecnick\Pdf\Page\Page;

/**
 * Page Test
 *
 * @since     2011-05-23
 * @category  Library
 * @package   PdfPage
 * @author    Nicola Asuni <info@tecnick.com>
 * @copyright 2011-2024 Nicola Asuni - Tecnick.com LTD
 * @license   http://www.gnu.org/copyleft/lesser.html GNU-LGPL v3 (see LICENSE.TXT)
 * @link      https://github.com/tecnickcom/tc-lib-pdf-page
 */
class RegionTest extends TestUtil
{
    protected function getTestObject(): \Com\Tecnick\Pdf\Page\Page
    {
        $pdf = new Pdf();
        $encrypt = new Encrypt(false);
        return new Page('mm', $pdf, $encrypt, false, false);
    }

    public function testRegion(): void
    {
        $page = $this->getTestObject();
        $page->add(
            [
                'columns' => 3,
            ]
        );

        $res = $page->selectRegion(1);
        $exp = [
            'RX' => 70,
            'RY' => 0,
            'RW' => 70,
            'RH' => 297,
            'RL' => 140,
            'RR' => 70,
            'RT' => 297,
            'RB' => 0,
            'x' => 70,
            'y' => 0,
        ];
        $this->bcAssertEqualsWithDelta($exp, $res);

        $res = $page->getRegion();
        $this->bcAssertEqualsWithDelta($exp, $res);

        $res = $page->getNextRegion();
        $this->bcAssertEqualsWithDelta(2, $res['currentRegion']);

        $res = $page->getNextRegion();
        $this->bcAssertEqualsWithDelta(0, $res['currentRegion']);

        $page->setCurrentPage(0);
        $res = $page->getNextRegion();
        $this->bcAssertEqualsWithDelta(0, $res['currentRegion']);

        $res = $page->checkRegionBreak(1000);
        $this->bcAssertEqualsWithDelta(1, $res['currentRegion']);

        $res = $page->checkRegionBreak();
        $this->bcAssertEqualsWithDelta(1, $res['currentRegion']);

        $page->setX(13)->setY(17);
        $this->bcAssertEqualsWithDelta(13, $page->getX());
        $this->bcAssertEqualsWithDelta(17, $page->getY());
    }

    public function testRegionBoundaries(): void
    {
        $page = $this->getTestObject();
        $page->add(
            [
                'columns' => 3,
            ]
        );

        $region = $page->getRegion();

        $res = $page->isYOutRegion(null, 1);
        $this->assertFalse($res);
        $res = $page->isYOutRegion(-1);
        $this->assertTrue($res);
        $res = $page->isYOutRegion($region['RY']);
        $this->assertFalse($res);
        $res = $page->isYOutRegion(0);
        $this->assertFalse($res);
        $res = $page->isYOutRegion(100);
        $this->assertFalse($res);
        $res = $page->isYOutRegion(297);
        $this->assertFalse($res);
        $res = $page->isYOutRegion($region['RT']);
        $this->assertFalse($res);
        $res = $page->isYOutRegion(298);
        $this->assertTrue($res);

        $page->getNextRegion();
        $region = $page->getRegion();

        $res = $page->isXOutRegion(null, 1);
        $this->assertFalse($res);
        $res = $page->isXOutRegion(69);
        $this->assertTrue($res);
        $res = $page->isXOutRegion($region['RX']);
        $this->assertFalse($res);
        $res = $page->isXOutRegion(70);
        $this->assertFalse($res);
        $res = $page->isXOutRegion(90);
        $this->assertFalse($res);
        $res = $page->isXOutRegion(140);
        $this->assertFalse($res);
        $res = $page->isXOutRegion($region['RL']);
        $this->assertFalse($res);
        $res = $page->isXOutRegion(141);
        $this->assertTrue($res);
    }
}
