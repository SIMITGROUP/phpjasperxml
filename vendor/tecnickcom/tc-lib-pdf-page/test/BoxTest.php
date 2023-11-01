<?php

/**
 * BoxTest.php
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
 * Box Test
 *
 * @since       2011-05-23
 * @category    Library
 * @package     PdfPage
 * @author      Nicola Asuni <info@tecnick.com>
 * @copyright   2011-2023 Nicola Asuni - Tecnick.com LTD
 * @license     http://www.gnu.org/copyleft/lesser.html GNU-LGPL v3 (see LICENSE.TXT)
 * @link        https://github.com/tecnickcom/tc-lib-pdf-page
 */
class BoxTest extends TestUtil
{
    protected function getTestObject()
    {
        $col = new \Com\Tecnick\Color\Pdf();
        $enc = new \Com\Tecnick\Pdf\Encrypt\Encrypt(false);
        return new \Com\Tecnick\Pdf\Page\Page('mm', $col, $enc, false, false);
    }

    public function testSetBox()
    {
        $testObj = $this->getTestObject();
        $dims = $testObj->setBox(array(), 'CropBox', 2, 4, 6, 8);
        $this->bcAssertEqualsWithDelta(
            array(
                'CropBox' => array(
                    'llx' => 2,
                    'lly' => 4,
                    'urx' => 6,
                    'ury' => 8,
                    'bci' => array(
                        'color' => '#000000',
                        'width' => 0.353,
                        'style' => 'S',
                        'dash' => array(3),
                    )
                )
            ),
            $dims
        );

        $dims = $testObj->setBox(
            array(),
            'TrimBox',
            3,
            5,
            7,
            11,
            array(
                'color' => 'aquamarine',
                'width' => 2,
                'style' => 'D',
                'dash' => array(2,3,5,7),
            )
        );
        $this->bcAssertEqualsWithDelta(
            array(
                'TrimBox' => array(
                    'llx' => 3,
                    'lly' => 5,
                    'urx' => 7,
                    'ury' => 11,
                    'bci' => array(
                        'color' => 'aquamarine',
                        'width' => 2,
                        'style' => 'D',
                        'dash' => array(2,3,5,7),
                    )
                )
            ),
            $dims
        );
    }

    public function testSetBoxEx()
    {
        $this->bcExpectException('\Com\Tecnick\Pdf\Page\Exception');
        $testObj = $this->getTestObject();
        $testObj->setBox(array(), 'ERROR', 1, 2, 3, 4);
    }

    public function testSwapCoordinates()
    {
        $testObj = $this->getTestObject();
        $dims = array('CropBox' => array('llx' => 2, 'lly' => 4, 'urx' => 6, 'ury' => 8));
        $newpagedim = $testObj->swapCoordinates($dims);
        $this->assertEquals(array('CropBox' => array('llx' => 4, 'lly' => 2, 'urx' => 8, 'ury' => 6)), $newpagedim);
    }

    public function testSetPageBoxes()
    {
        $testObj = $this->getTestObject();
        $dims = $testObj->setPageBoxes(100, 200);
        $exp = array(
            'llx' => 0,
            'lly' => 0,
            'urx' => 100,
            'ury' => 200,
            'bci' => array(
                'color' => '#000000',
                'width' => 0.353,
                'style' => 'S',
                'dash' => array (3),
            )
        );
        $this->bcAssertEqualsWithDelta(
            array(
                'MediaBox' => $exp,
                'CropBox'  => $exp,
                'BleedBox' => $exp,
                'TrimBox'  => $exp,
                'ArtBox'   => $exp,
            ),
            $dims
        );
    }
}
