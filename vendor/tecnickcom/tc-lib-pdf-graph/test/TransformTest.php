<?php

/**
 * TransformTest.php
 *
 * @since       2011-05-23
 * @category    Library
 * @package     PdfGraph
 * @author      Nicola Asuni <info@tecnick.com>
 * @copyright   2011-2023 Nicola Asuni - Tecnick.com LTD
 * @license     http://www.gnu.org/copyleft/lesser.html GNU-LGPL v3 (see LICENSE.TXT)
 * @link        https://github.com/tecnickcom/tc-lib-pdf-graph
 *
 * This file is part of tc-lib-pdf-graph software library.
 */

namespace Test;

use PHPUnit\Framework\TestCase;

/**
 * Transform Test
 *
 * @since       2011-05-23
 * @category    Library
 * @package     PdfGraph
 * @author      Nicola Asuni <info@tecnick.com>
 * @copyright   2011-2023 Nicola Asuni - Tecnick.com LTD
 * @license     http://www.gnu.org/copyleft/lesser.html GNU-LGPL v3 (see LICENSE.TXT)
 * @link        https://github.com/tecnickcom/tc-lib-pdf-graph
 */
class TransformTest extends TestUtil
{
    protected function getTestObject()
    {
        $testObj = new \Com\Tecnick\Pdf\Graph\Draw(
            1,
            0,
            0,
            new \Com\Tecnick\Color\Pdf(),
            new \Com\Tecnick\Pdf\Encrypt\Encrypt(),
            false
        );
        $this->assertEquals(-1, $testObj->getTransformIndex());
        $this->assertEquals('q' . "\n", $testObj->getStartTransform());
        return $testObj;
    }

    public function testGetStartStopTransform()
    {
        $obj = new \Com\Tecnick\Pdf\Graph\Draw(
            1,
            0,
            0,
            new \Com\Tecnick\Color\Pdf(),
            new \Com\Tecnick\Pdf\Encrypt\Encrypt(),
            false
        );
        $this->assertEquals(-1, $obj->getTransformIndex());
        $this->assertEquals('q' . "\n", $obj->getStartTransform());
        $this->assertEquals(0, $obj->getTransformIndex());

        $tmx = array(0.1, 1.2, 2.3, 3.4, 4.5, 5.6);
        $this->assertEquals(
            '0.100000 1.200000 2.300000 3.400000 4.500000 5.600000 cm' . "\n",
            $obj->getTransformation($tmx)
        );

        $this->bcAssertEqualsWithDelta(
            array(0 => array(0 => array(0.1, 1.2, 2.3, 3.4, 4.5, 5.6))),
            $obj->getTransformStack(),
            0.0001,
            ''
        );

        $this->assertEquals('Q' . "\n", $obj->getStopTransform());
        $this->assertEquals(-1, $obj->getTransformIndex());
        $this->assertEquals('', $obj->getStopTransform());
        $this->assertEquals(-1, $obj->getTransformIndex());
    }


    public function testGetTransform()
    {
        $testObj = $this->getTestObject();
        $tmx = array(0.1, 1.2, 2.3, 3.4, 4.5, 5.6);
        $this->assertEquals(
            '0.100000 1.200000 2.300000 3.400000 4.500000 5.600000 cm' . "\n",
            $testObj->getTransformation($tmx)
        );
    }

    public function testSetPageHeight()
    {
        $obj = new \Com\Tecnick\Pdf\Graph\Draw(
            1,
            0,
            0,
            new \Com\Tecnick\Color\Pdf(),
            new \Com\Tecnick\Pdf\Encrypt\Encrypt(),
            false
        );
        $obj->setPageHeight(100);
        $this->assertEquals('q' . "\n", $obj->getStartTransform());
        $this->assertEquals(
            '3.000000 0.000000 0.000000 5.000000 -14.000000 -356.000000 cm' . "\n",
            $obj->getScaling(3, 5, 7, 11)
        );
    }

    public function testSetKUnit()
    {
        $obj = new \Com\Tecnick\Pdf\Graph\Draw(
            1,
            0,
            0,
            new \Com\Tecnick\Color\Pdf(),
            new \Com\Tecnick\Pdf\Encrypt\Encrypt(),
            false
        );
        $obj->setKUnit(0.75);
        $this->assertEquals('q' . "\n", $obj->getStartTransform());
        $this->assertEquals(
            '3.000000 0.000000 0.000000 5.000000 -10.500000 33.000000 cm' . "\n",
            $obj->getScaling(3, 5, 7, 11)
        );
    }

    public function testGetScaling()
    {
        $testObj = $this->getTestObject();
        $this->assertEquals(
            '3.000000 0.000000 0.000000 5.000000 -14.000000 44.000000 cm' . "\n",
            $testObj->getScaling(3, 5, 7, 11)
        );
        $this->assertEquals(
            '3.000000 0.000000 0.000000 3.000000 -14.000000 22.000000 cm' . "\n",
            $testObj->getScaling(3, 3, 7, 11)
        );
    }

    public function testGetScalingEx()
    {
        $this->bcExpectException('\Com\Tecnick\Pdf\Graph\Exception');
        $testObj = $this->getTestObject();
        $testObj->getScaling(0, 0, 7, 11);
    }

    public function testGetHorizScaling()
    {
        $testObj = $this->getTestObject();
        $this->assertEquals(
            '3.000000 0.000000 0.000000 1.000000 -14.000000 0.000000 cm' . "\n",
            $testObj->getHorizScaling(3, 7, 11)
        );
    }

    public function testGetVertScaling()
    {
        $testObj = $this->getTestObject();
        $this->assertEquals(
            '1.000000 0.000000 0.000000 5.000000 0.000000 44.000000 cm' . "\n",
            $testObj->getVertScaling(5, 7, 11)
        );
    }

    public function testGetPropScaling()
    {
        $testObj = $this->getTestObject();
        $this->assertEquals(
            '3.000000 0.000000 0.000000 3.000000 -14.000000 22.000000 cm' . "\n",
            $testObj->getPropScaling(3, 7, 11)
        );
    }

    public function testGetRotation()
    {
        $testObj = $this->getTestObject();
        $this->assertEquals(
            '0.707107 0.707107 -0.707107 0.707107 -5.727922 -8.171573 cm' . "\n",
            $testObj->getRotation(45, 7, 11)
        );
    }

    public function testGetHorizMirroring()
    {
        $testObj = $this->getTestObject();
        $this->assertEquals(
            '-1.000000 0.000000 0.000000 1.000000 14.000000 0.000000 cm' . "\n",
            $testObj->getHorizMirroring(7)
        );
    }

    public function testGetVertMirroring()
    {
        $testObj = $this->getTestObject();
        $this->assertEquals(
            '1.000000 0.000000 0.000000 -1.000000 0.000000 -22.000000 cm' . "\n",
            $testObj->getVertMirroring(11)
        );
    }

    public function testGetPointMirroring()
    {
        $testObj = $this->getTestObject();
        $this->assertEquals(
            '-1.000000 0.000000 0.000000 -1.000000 14.000000 -22.000000 cm' . "\n",
            $testObj->getPointMirroring(7, 11)
        );
    }

    public function testGetReflection()
    {
        $testObj = $this->getTestObject();
        $this->assertEquals(
            '-1.000000 0.000000 0.000000 1.000000 14.000000 0.000000 cm' . "\n"
            . '0.000000 1.000000 -1.000000 0.000000 -4.000000 -18.000000 cm' . "\n",
            $testObj->getReflection(45, 7, 11)
        );
    }

    public function testGetTranslation()
    {
        $testObj = $this->getTestObject();
        $this->assertEquals(
            '1.000000 0.000000 0.000000 1.000000 3.000000 -5.000000 cm' . "\n",
            $testObj->getTranslation(3, 5)
        );
    }

    public function testGetHorizTranslation()
    {
        $testObj = $this->getTestObject();
        $this->assertEquals(
            '1.000000 0.000000 0.000000 1.000000 3.000000 0.000000 cm' . "\n",
            $testObj->getHorizTranslation(3)
        );
    }

    public function testGetVertTranslation()
    {
        $testObj = $this->getTestObject();
        $this->assertEquals(
            '1.000000 0.000000 0.000000 1.000000 0.000000 -5.000000 cm' . "\n",
            $testObj->getVertTranslation(5)
        );
    }

    public function testGetSkewing()
    {
        $testObj = $this->getTestObject();
        $this->assertEquals(
            '1.000000 0.087489 0.052408 1.000000 0.576486 -0.612421 cm' . "\n",
            $testObj->getSkewing(3, 5, 7, 11)
        );
    }

    public function testGetSkewingEx()
    {
        $testObj = $this->getTestObject();
        $this->bcExpectException('\Com\Tecnick\Pdf\Graph\Exception');
        $testObj->getSkewing(90, -90, 7, 11);
    }

    public function testGetHorizSkewing()
    {
        $testObj = $this->getTestObject();
        $this->assertEquals(
            '1.000000 0.000000 0.052408 1.000000 0.576486 0.000000 cm' . "\n",
            $testObj->getHorizSkewing(3, 7, 11)
        );
    }

    public function testGetVertSkewing()
    {
        $testObj = $this->getTestObject();
        $this->assertEquals(
            '1.000000 0.087489 0.000000 1.000000 0.000000 -0.612421 cm' . "\n",
            $testObj->getVertSkewing(5, 7, 11)
        );
    }

    public function testGetCtmProduct()
    {
        $testObj = $this->getTestObject();
        $tma = array(3.1, 5.2, 7.3, 11.4, 13.5, 17.6);
        $tmb = array(19.1, 23.2, 29.3, 31.4, 37.5, 41.6);
        $ctm = $testObj->getCtmProduct($tma, $tmb);
        $this->bcAssertEqualsWithDelta(array(228.570, 363.800, 320.050, 510.320, 433.430, 686.840), $ctm, 0.001);
    }
}
