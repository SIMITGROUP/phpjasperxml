<?php
/**
 * BaseTest.php
 *
 * @since       2011-05-23
 * @category    Library
 * @package     PdfGraph
 * @author      Nicola Asuni <info@tecnick.com>
 * @copyright   2011-2016 Nicola Asuni - Tecnick.com LTD
 * @license     http://www.gnu.org/copyleft/lesser.html GNU-LGPL v3 (see LICENSE.TXT)
 * @link        https://github.com/tecnickcom/tc-lib-pdf-graph
 *
 * This file is part of tc-lib-pdf-graph software library.
 */

namespace Test;

use PHPUnit\Framework\TestCase;

/**
 * Base Test
 *
 * @since       2011-05-23
 * @category    Library
 * @package     PdfGraph
 * @author      Nicola Asuni <info@tecnick.com>
 * @copyright   2011-2016 Nicola Asuni - Tecnick.com LTD
 * @license     http://www.gnu.org/copyleft/lesser.html GNU-LGPL v3 (see LICENSE.TXT)
 * @link        https://github.com/tecnickcom/tc-lib-pdf-graph
 */
class BaseTest extends TestUtil
{
    protected function getTestObject()
    {
        return new \Com\Tecnick\Pdf\Graph\Draw(
            0.75,
            80,
            100,
            new \Com\Tecnick\Color\Pdf(),
            new \Com\Tecnick\Pdf\Encrypt\Encrypt(),
            false
        );
    }

    public function testGetOutExtGState()
    {
        $testObj = $this->getTestObject();
        $res = $testObj->getOutExtGState(10);
        $this->assertEquals(
            '',
            $res
        );

        $testObj->getOverprint();
        $testObj->getAlpha();
        $res = $testObj->getOutExtGState(10);
        $this->assertEquals(
            '11 0 obj'."\n"
            .'<< /Type /ExtGState /OP true /op true /OPM 0.000000 >>'."\n"
            .'endobj'."\n"
            .'12 0 obj'."\n"
            .'<< /Type /ExtGState /CA 1.000000 /ca 1.000000 /BM /Normal /AIS false >>'."\n"
            .'endobj'."\n",
            $res
        );

        $this->assertEquals(12, $testObj->getObjectNumber());
    }

    public function testGetOutExtGStateResourcesEmpty()
    {
        $testObj = $this->getTestObject();
        $res = $testObj->getOutExtGStateResources();
        $this->assertEquals(
            '',
            $res
        );
    }

    public function testGetOutGradientResourcesEmpty()
    {
        $testObj = $this->getTestObject();
        $res = $testObj->getOutGradientResources();
        $this->assertEquals(
            '',
            $res
        );
    }

    public function testGetOutGradientShaders()
    {
        $testObj = $this->getTestObject();
        $res = $testObj->getOutGradientShaders(10);
        $this->assertEquals(
            '',
            $res
        );

        $testObj->getCoonsPatchMesh(3, 5, 7, 11);
        $testObj->getOutGradientShaders(11);
        $this->assertEquals(13, $testObj->getObjectNumber());

        $res = $testObj->getOutGradientResources();
        $this->assertEquals(
            ' /Pattern << /p1 13 0 R /p2 13 0 R >>'."\n"
            .' /Shading << /Sh1 12 0 R /Sh2 12 0 R >>'."\n",
            $res
        );
    }

    public function testGetOutShaders()
    {
        $testObj = $this->getTestObject();
        $stops = array(
            array('color' => 'red', 'exponent' => 1, 'opacity' => 0.5),
            array('color' => 'blue', 'offset' => 0.2, 'exponent' => 1, 'opacity' => 0.6),
            array('color' => '#98fb98', 'exponent' => 1, 'opacity' => 0.7),
            array('color' => 'rgb(64,128,191)', 'offset' => 0.8, 'exponent' => 1, 'opacity' => 0.8),
            array('color' => 'skyblue', 'exponent' => 1, 'opacity' => 0.9),
        );
        $this->assertEquals(
            '/TGS1 gs'."\n"
            .'/Sh1 sh'."\n",
            $testObj->getGradient(2, array(0,0,1,0), $stops, '', false)
        );

        $testObj->getOverprint();
        $testObj->getAlpha();
        $testObj->getOutExtGState($testObj->getObjectNumber());

        $testObj->getOutGradientShaders($testObj->getObjectNumber());
        $this->assertEquals(19, $testObj->getObjectNumber());

        $res = $testObj->getOutExtGStateResources();
        $this->assertEquals(
            ' /ExtGState << /GS1 1 0 R /GS2 2 0 R /TGS1 20 0 R >>'."\n",
            $res
        );
    }

    public function testGetOutShadersRadial()
    {
        $testObj = $this->getTestObject();
        $testObj->getGradient(
            3,
            array(0.6,0.5,0.4,0.3,1),
            array(
                array(
                    'color' => 'red',
                    'offset' => 0,
                    'exponent' => 1,
                ),
                array(
                    'color' => 'green',
                    'offset' => 1,
                    'exponent' => 1,
                ),
            ),
            'white',
            true
        );

        $testObj->getOutGradientShaders($testObj->getObjectNumber());
        $this->assertEquals(4, $testObj->getObjectNumber());
    }
}
