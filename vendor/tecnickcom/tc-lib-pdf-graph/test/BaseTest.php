<?php

/**
 * BaseTest.php
 *
 * @since     2011-05-23
 * @category  Library
 * @package   PdfGraph
 * @author    Nicola Asuni <info@tecnick.com>
 * @copyright 2011-2024 Nicola Asuni - Tecnick.com LTD
 * @license   http://www.gnu.org/copyleft/lesser.html GNU-LGPL v3 (see LICENSE.TXT)
 * @link      https://github.com/tecnickcom/tc-lib-pdf-graph
 *
 * This file is part of tc-lib-pdf-graph software library.
 */

namespace Test;

/**
 * Base Test
 *
 * @since     2011-05-23
 * @category  Library
 * @package   PdfGraph
 * @author    Nicola Asuni <info@tecnick.com>
 * @copyright 2011-2024 Nicola Asuni - Tecnick.com LTD
 * @license   http://www.gnu.org/copyleft/lesser.html GNU-LGPL v3 (see LICENSE.TXT)
 * @link      https://github.com/tecnickcom/tc-lib-pdf-graph
 */
class BaseTest extends TestUtil
{
    protected function getTestObject(): \Com\Tecnick\Pdf\Graph\Draw
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

    public function testGetOutExtGState(): void
    {
        $draw = $this->getTestObject();
        $res = $draw->getOutExtGState(10);
        $this->assertEquals(
            '',
            $res
        );

        $draw->getOverprint();
        $draw->getAlpha();

        $res = $draw->getOutExtGState(10);
        $this->assertEquals(
            '11 0 obj' . "\n"
            . '<< /Type /ExtGState /OP true /op true /OPM 0.000000 >>' . "\n"
            . 'endobj' . "\n"
            . '12 0 obj' . "\n"
            . '<< /Type /ExtGState /CA 1.000000 /ca 1.000000 /BM /Normal /AIS false >>' . "\n"
            . 'endobj' . "\n",
            $res
        );

        $this->assertEquals(12, $draw->getObjectNumber());
    }

    /**
     * @SuppressWarnings(PHPMD.LongVariable)
     */
    public function testGetOutExtGStateResourcesEmpty(): void
    {
        $draw = $this->getTestObject();
        $outExtGStateResources = $draw->getOutExtGStateResources();
        $this->assertEquals('', $outExtGStateResources);
    }

    public function testGetOutGradientResourcesEmpty(): void
    {
        $draw = $this->getTestObject();
        $outGradientResources = $draw->getOutGradientResources();
        $this->assertEquals(
            '',
            $outGradientResources
        );
    }

    public function testGetOutGradientShaders(): void
    {
        $draw = $this->getTestObject();
        $res = $draw->getOutGradientShaders(10);
        $this->assertEquals(
            '',
            $res
        );

        $draw->getCoonsPatchMeshWithCoords(3, 5, 7, 11);
        $draw->getOutGradientShaders(11);
        $this->assertEquals(13, $draw->getObjectNumber());

        $res = $draw->getOutGradientResources();
        $this->assertEquals(
            ' /Pattern << /p1 13 0 R >>' . "\n"
            . ' /Shading << /Sh1 12 0 R >>' . "\n",
            $res
        );
    }

    public function testGetOutShaders(): void
    {
        $draw = $this->getTestObject();
        $stops = [
            [
                'color' => 'red',
                'exponent' => 1,
                'opacity' => 0.5,
            ],
            [
                'color' => 'blue',
                'exponent' => 1,
                'offset' => 0.2,
                'opacity' => 0.6,
            ],
            [
                'color' => '#98fb98',
                'exponent' => 1,
                'opacity' => 0.7,
            ],
            [
                'color' => 'rgb(64,128,191)',
                'exponent' => 1,
                'offset' => 0.8,
                'opacity' => 0.8,
            ],
            [
                'color' => 'skyblue',
                'exponent' => 1,
                'opacity' => 0.9,
            ],
        ];
        $this->assertEquals(
            '/TGS1 gs' . "\n"
            . '/Sh1 sh' . "\n",
            $draw->getGradient(2, [0, 0, 1, 0], $stops, '', false)
        );

        $draw->getOverprint();
        $draw->getAlpha();
        $draw->getOutExtGState($draw->getObjectNumber());

        $draw->getOutGradientShaders($draw->getObjectNumber());
        $this->assertEquals(19, $draw->getObjectNumber());

        $res = $draw->getOutExtGStateResources();
        $this->assertEquals(
            ' /ExtGState << /GS1 1 0 R /GS2 2 0 R /TGS1 20 0 R >>' . "\n",
            $res
        );
    }

    public function testGetOutShadersRadial(): void
    {
        $draw = $this->getTestObject();
        $out = $draw->getGradient(
            3,
            [0.6, 0.5, 0.4, 0.3, 1],
            [
                [
                    'color' => 'red',
                    'exponent' => 1,
                    'offset' => 0,
                ],
                [
                    'color' => 'green',
                    'exponent' => 1,
                    'offset' => 1,
                ],
            ],
            'white',
            true
        );

        $this->assertEquals(
            '/Sh1 sh' . "\n",
            $out
        );

        $this->assertEquals(0, $draw->getObjectNumber());
        $draw->getOutGradientShaders($draw->getObjectNumber());
        $this->assertEquals(4, $draw->getObjectNumber());
    }
}
