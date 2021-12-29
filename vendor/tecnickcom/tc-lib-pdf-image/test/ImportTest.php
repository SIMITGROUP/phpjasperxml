<?php
/**
 * ImportTest.php
 *
 * @since       2011-05-23
 * @category    Library
 * @package     PdfImage
 * @author      Nicola Asuni <info@tecnick.com>
 * @copyright   2011-2015 Nicola Asuni - Tecnick.com LTD
 * @license     http://www.gnu.org/copyleft/lesser.html GNU-LGPL v3 (see LICENSE.TXT)
 * @link        https://github.com/tecnickcom/tc-lib-pdf-image
 *
 * This file is part of tc-lib-pdf-image software library.
 */

namespace Test;

use PHPUnit\Framework\TestCase;

/**
 * Unit Test
 *
 * @since       2011-05-23
 * @category    Library
 * @package     PdfImage
 * @author      Nicola Asuni <info@tecnick.com>
 * @copyright   2011-2015 Nicola Asuni - Tecnick.com LTD
 * @license     http://www.gnu.org/copyleft/lesser.html GNU-LGPL v3 (see LICENSE.TXT)
 * @link        https://github.com/tecnickcom/tc-lib-pdf-image
 */
class ImportTest extends TestUtil
{
    protected function getTestObject()
    {
        $enc = new \Com\Tecnick\Pdf\Encrypt\Encrypt();
        return new \Com\Tecnick\Pdf\Image\Import(0.75, $enc, false);
    }

    public function testGetKey()
    {
        $testObj = $this->getTestObject();
        $result = $testObj->getKey('/images/200x100_RGB.png', 200, 100, 100);
        $this->assertEquals('6EvJjr-KnDm4EnAWVt-7wQ', $result);
    }

    public function testGetImageDataByKeyError()
    {
        $this->bcExpectException('\Com\Tecnick\Pdf\Image\Exception');
        $testObj = $this->getTestObject();
        $testObj->getImageDataByKey('missing');
    }

    public function testGetSetImageError()
    {
        $this->bcExpectException('\Com\Tecnick\Pdf\Image\Exception');
        $testObj = $this->getTestObject();
        $testObj->getSetImage(1, 2, 3, 5, 7, 17);
    }

    public function getBadAddValues()
    {
        return array(
            array(''),
            array(__DIR__.'/images/missing.png'),
            array('@'),
            array('@garbage'),
            array('*http://www.example.com/image.png'),
        );
    }

    /**
     * @dataProvider getBadAddValues
     */
    public function testAddError($bad)
    {
        $this->bcExpectException('\Com\Tecnick\Pdf\Image\Exception');
        $testObj = $this->getTestObject();
        $testObj->add($bad);
    }

    public function testAdd()
    {
        $testObj = $this->getTestObject();
        $iid = $testObj->add(__DIR__.'/images/200x100_RGB.png');
        $this->assertEquals(
            'q 150.000000 0 0 75.000000 2.250000 371.250000 cm /IMG1 Do Q',
            $testObj->getSetImage($iid, 3, 5, 200, 100, 600)
        );

        $iid = $testObj->add(__DIR__.'/images/200x100_GRAY.jpg');
        $this->assertEquals(
            'q 150.000000 0 0 75.000000 2.250000 371.250000 cm /IMG2 Do Q',
            $testObj->getSetImage($iid, 3, 5, 200, 100, 600)
        );

        $iid = $testObj->add(__DIR__.'/images/200x100_GRAY.png');
        $this->assertEquals(
            'q 150.000000 0 0 75.000000 2.250000 371.250000 cm /IMG3 Do Q',
            $testObj->getSetImage($iid, 3, 5, 200, 100, 600)
        );

        $iid = $testObj->add(__DIR__.'/images/200x100_INDEX16.png');
        $this->assertEquals(
            'q 150.000000 0 0 75.000000 2.250000 371.250000 cm /IMG4 Do Q',
            $testObj->getSetImage($iid, 3, 5, 200, 100, 600)
        );

        $iid = $testObj->add(__DIR__.'/images/200x100_INDEX256.png');
        $this->assertEquals(
            'q 150.000000 0 0 75.000000 2.250000 371.250000 cm /IMG5 Do Q',
            $testObj->getSetImage($iid, 3, 5, 200, 100, 600)
        );

        $iid = $testObj->add(__DIR__.'/images/200x100_RGB.jpg');
        $this->assertEquals(
            'q 150.000000 0 0 75.000000 2.250000 371.250000 cm /IMG6 Do Q',
            $testObj->getSetImage($iid, 3, 5, 200, 100, 600)
        );

        $iid = $testObj->add(__DIR__.'/images/200x100_RGB.png');
        $this->assertEquals(
            'q 150.000000 0 0 75.000000 2.250000 371.250000 cm /IMG7 Do Q',
            $testObj->getSetImage($iid, 3, 5, 200, 100, 600)
        );

        $iid = $testObj->add(__DIR__.'/images/200x100_RGBALPHA.png');
        $this->assertEquals(
            'q 150.000000 0 0 75.000000 2.250000 371.250000 cm /IMGmask8 Do /IMGplain8 Do Q',
            $testObj->getSetImage($iid, 3, 5, 200, 100, 600)
        );

        $iid = $testObj->add(__DIR__.'/images/200x100_INDEXALPHA.png');
        $this->assertEquals(
            'q 150.000000 0 0 75.000000 2.250000 371.250000 cm /IMG9 Do Q',
            $testObj->getSetImage($iid, 3, 5, 200, 100, 600)
        );

        // resize

        $iid = $testObj->add(__DIR__.'/images/200x100_RGB.png', 100, 50, true, 75, true);
        $this->assertEquals(
            'q 75.000000 0 0 37.500000 2.250000 408.750000 cm /IMGmask10 Do Q',
            $testObj->getSetImage($iid, 3, 5, 100, 50, 600)
        );

        $iid = $testObj->add(__DIR__.'/images/200x100_RGBALPHA.png', 100, 50, true, 75, true);
        $this->assertEquals(
            'q 75.000000 0 0 37.500000 2.250000 408.750000 cm /IMGmask11 Do Q',
            $testObj->getSetImage($iid, 3, 5, 100, 50, 600)
        );

        $iid = $testObj->add(__DIR__.'/images/200x100_INDEXALPHA.png', 100, 50, true, 75, true);
        $this->assertEquals(
            'q 75.000000 0 0 37.500000 2.250000 408.750000 cm /IMGmask12 Do Q',
            $testObj->getSetImage($iid, 3, 5, 100, 50, 600)
        );

        $iid = $testObj->add(__DIR__.'/images/200x100_RGB.jpg', 100, 50, false, 75, true, array(1, 2, 3));
        $this->assertEquals(
            'q 75.000000 0 0 37.500000 2.250000 408.750000 cm /IMG13 Do Q',
            $testObj->getSetImage($iid, 3, 5, 100, 50, 600)
        );

        // ICC

        $iid = $testObj->add(__DIR__.'/images/200x100_RGBICC.png');
        $this->assertEquals(
            'q 150.000000 0 0 75.000000 2.250000 371.250000 cm /IMG14 Do Q',
            $testObj->getSetImage($iid, 3, 5, 200, 100, 600)
        );

        $iid = $testObj->add(__DIR__.'/images/200x100_RGBICC.jpg');
        $this->assertEquals(
            'q 150.000000 0 0 75.000000 2.250000 371.250000 cm /IMG15 Do Q',
            $testObj->getSetImage($iid, 3, 5, 200, 100, 600)
        );

        $iid = $testObj->add(__DIR__.'/images/200x100_RGBINT.png');
        $this->assertEquals(
            'q 150.000000 0 0 75.000000 2.250000 371.250000 cm /IMGmask16 Do /IMGplain16 Do Q',
            $testObj->getSetImage($iid, 3, 5, 200, 100, 600)
        );


        $iid = $testObj->add(__DIR__.'/images/200x100_CMYK.jpg');
        $this->assertEquals(
            'q 150.000000 0 0 75.000000 2.250000 371.250000 cm /IMG17 Do Q',
            $testObj->getSetImage($iid, 3, 5, 200, 100, 600)
        );

        $key = $testObj->getKey(__DIR__.'/images/200x100_INDEX256.png');
        $data = $testObj->getImageDataByKey($key);
        $this->assertEquals($key, $data['key']);

        $iid = $testObj->add('@'.$data['raw']);
        $this->assertEquals(
            'q 150.000000 0 0 75.000000 2.250000 371.250000 cm /IMG18 Do Q',
            $testObj->getSetImage($iid, 3, 5, 200, 100, 600)
        );

        // disabled because of libpngerror
        // $iid = $testObj->add('*http://localhost:8000/200x100_INDEX16.png');
        // $this->assertEquals(
        //     'q 150.000000 0 0 75.000000 2.250000 371.250000 cm /IMG18 Do Q',
        //     $testObj->getSetImage($iid, 3, 5, 200, 100, 600)
        // );

        $out = $testObj->getOutImagesBlock(10);
        $this->assertNotEmpty($out);

        $this->assertEquals(38, $testObj->getObjectNumber());

        $xob = $testObj->getXobjectDict();
        $this->assertEquals(
            ' /IMG1 11 0 R /IMG2 12 0 R /IMG3 13 0 R /IMG4 15 0 R /IMG5 17 0 R /IMG6 18 0 R /IMG7 11 0 R'
            .' /IMG8 21 0 R /IMG9 23 0 R /IMG10 24 0 R /IMG11 25 0 R /IMG12 26 0 R /IMG13 28 0 R /IMG14 30 0 R'
            .' /IMG15 32 0 R /IMG16 34 0 R /IMG17 36 0 R /IMG18 38 0 R',
            $xob
        );
    }
}
