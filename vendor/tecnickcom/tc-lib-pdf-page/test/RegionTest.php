<?php

/**
 * RegionTest.php
 *
 * @since       2011-05-23
 * @category    Library
 * @package     PdfPage
 * @author      Nicola Asuni <info@tecnick.com>
 * @copyright   2011-2023 Nicola Asuni - Tecnick.com LTD
 * @license     http://www.gnu.org/copyleft/lesser.html GNU-LGPL v3 (see LICENSE.TXT)
 * @link        https://github.com/tecnickcom/tc-lib-pdf-page
 *
 * This file is part of tc-lib-pdf-page software library.
 */

namespace Test;

use PHPUnit\Framework\TestCase;

/**
 * Page Test
 *
 * @since       2011-05-23
 * @category    Library
 * @package     PdfPage
 * @author      Nicola Asuni <info@tecnick.com>
 * @copyright   2011-2023 Nicola Asuni - Tecnick.com LTD
 * @license     http://www.gnu.org/copyleft/lesser.html GNU-LGPL v3 (see LICENSE.TXT)
 * @link        https://github.com/tecnickcom/tc-lib-pdf-page
 */
class RegionTest extends TestUtil
{
    protected function getTestObject()
    {
        $col = new \Com\Tecnick\Color\Pdf();
        $enc = new \Com\Tecnick\Pdf\Encrypt\Encrypt(false);
        return new \Com\Tecnick\Pdf\Page\Page('mm', $col, $enc, false, false);
    }

    public function testRegion()
    {
        $testObj = $this->getTestObject();
        $testObj->add(array('columns' => 3));
        $res = $testObj->selectRegion(1);
        $exp = array(
            'RX' => 70,
            'RY' => 0,
            'RW' => 70,
            'RH' => 297,
            'RL' => 140,
            'RR' => 70,
            'RT' => 297,
            'RB' => 0,
            'x'  => 70,
            'y'  => 0,
        );
        $this->bcAssertEqualsWithDelta($exp, $res);

        $res = $testObj->getRegion();
        $this->bcAssertEqualsWithDelta($exp, $res);

        $res = $testObj->getNextRegion();
        $this->bcAssertEqualsWithDelta(2, $res['currentRegion']);

        $res = $testObj->getNextRegion();
        $this->bcAssertEqualsWithDelta(0, $res['currentRegion']);

        $testObj->setCurrentPage(0);
        $res = $testObj->getNextRegion();
        $this->bcAssertEqualsWithDelta(0, $res['currentRegion']);

        $res = $testObj->checkRegionBreak(1000);
        $this->bcAssertEqualsWithDelta(1, $res['currentRegion']);

        $res = $testObj->checkRegionBreak();
        $this->bcAssertEqualsWithDelta(1, $res['currentRegion']);

        $testObj->setX(13)->setY(17);
        $this->bcAssertEqualsWithDelta(13, $testObj->getX());
        $this->bcAssertEqualsWithDelta(17, $testObj->getY());
    }

    public function testRegionBoundaries()
    {
        $testObj = $this->getTestObject();
        $testObj->add(array('columns' => 3));
        $region = $testObj->getRegion();

        $res = $testObj->isYOutRegion(null, 1);
        $this->assertFalse($res);
        $res = $testObj->isYOutRegion(-1);
        $this->assertTrue($res);
        $res = $testObj->isYOutRegion($region['RY']);
        $this->assertFalse($res);
        $res = $testObj->isYOutRegion(0);
        $this->assertFalse($res);
        $res = $testObj->isYOutRegion(100);
        $this->assertFalse($res);
        $res = $testObj->isYOutRegion(297);
        $this->assertFalse($res);
        $res = $testObj->isYOutRegion($region['RT']);
        $this->assertFalse($res);
        $res = $testObj->isYOutRegion(298);
        $this->assertTrue($res);

        $testObj->getNextRegion();
        $region = $testObj->getRegion();

        $res = $testObj->isXOutRegion(null, 1);
        $this->assertFalse($res);
        $res = $testObj->isXOutRegion(69);
        $this->assertTrue($res);
        $res = $testObj->isXOutRegion($region['RX']);
        $this->assertFalse($res);
        $res = $testObj->isXOutRegion(70);
        $this->assertFalse($res);
        $res = $testObj->isXOutRegion(90);
        $this->assertFalse($res);
        $res = $testObj->isXOutRegion(140);
        $this->assertFalse($res);
        $res = $testObj->isXOutRegion($region['RL']);
        $this->assertFalse($res);
        $res = $testObj->isXOutRegion(141);
        $this->assertTrue($res);
    }
}
