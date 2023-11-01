<?php

/**
 * SettingsTest.php
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
 * Settings Test
 *
 * @since       2011-05-23
 * @category    Library
 * @package     PdfPage
 * @author      Nicola Asuni <info@tecnick.com>
 * @copyright   2011-2023 Nicola Asuni - Tecnick.com LTD
 * @license     http://www.gnu.org/copyleft/lesser.html GNU-LGPL v3 (see LICENSE.TXT)
 * @link        https://github.com/tecnickcom/tc-lib-pdf-page
 */
class SettingsTest extends TestUtil
{
    protected function getTestObject()
    {
        $col = new \Com\Tecnick\Color\Pdf();
        $enc = new \Com\Tecnick\Pdf\Encrypt\Encrypt(false);
        return new \Com\Tecnick\Pdf\Page\Page('mm', $col, $enc, false, false);
    }

    public function testSanitizePageNumber()
    {
        $testObj = $this->getTestObject();
        $data = array();
        $testObj->sanitizePageNumber($data);
        $this->assertEquals(array(), $data);

        $data = array('num' => -1);
        $testObj->sanitizePageNumber($data);
        $this->assertEquals(array('num' => 0), $data);


        $data = array('num' => 0);
        $testObj->sanitizePageNumber($data);
        $this->assertEquals(array('num' => 0), $data);


        $data = array('num' => 1);
        $testObj->sanitizePageNumber($data);
        $this->assertEquals(array('num' => 1), $data);
    }

    public function testSanitizeTime()
    {
        $testObj = $this->getTestObject();
        $data = array();
        $testObj->sanitizeTime($data);
        $this->assertNotEmpty($data['time']); /* @phpstan-ignore-line */

        $data = array('time' => -1);
        $testObj->sanitizeTime($data);
        $this->assertEquals(array('time' => 0), $data);

        $data = array('time' => 0);
        $testObj->sanitizeTime($data);
        $this->assertNotEmpty($data['time']);

        $data = array('time' => 1);
        $testObj->sanitizeTime($data);
        $this->assertEquals(array('time' => 1), $data);
    }

    public function testSanitizeGroup()
    {
        $testObj = $this->getTestObject();
        $data = array();
        $testObj->sanitizeGroup($data);
        $this->assertEquals(array('group' => 0), $data);

        $data = array('group' => -1);
        $testObj->sanitizeGroup($data);
        $this->assertEquals(array('group' => 0), $data);


        $data = array('group' => 0);
        $testObj->sanitizeGroup($data);
        $this->assertEquals(array('group' => 0), $data);


        $data = array('group' => 1);
        $testObj->sanitizeGroup($data);
        $this->assertEquals(array('group' => 1), $data);
    }

    public function testSanitizeContent()
    {
        $testObj = $this->getTestObject();
        $data = array();
        $testObj->sanitizeContent($data);
        $this->assertEquals(array('content' => array('')), $data);

        $data = array('content' => 'test');
        $testObj->sanitizeContent($data);
        $this->assertEquals(array('content' => array('test')), $data);
    }

    public function testSanitizeAnnotRefs()
    {
        $testObj = $this->getTestObject();
        $data = array();
        $testObj->sanitizeAnnotRefs($data);
        $this->assertEquals(array('annotrefs' => array()), $data);
    }

    public function testSanitizeRotation()
    {
        $testObj = $this->getTestObject();
        $data = array();
        $testObj->sanitizeRotation($data);
        $this->assertEquals(array('rotation' => 0), $data);

        $data = array('rotation' => 0);
        $testObj->sanitizeRotation($data);
        $this->assertEquals(array('rotation' => 0), $data);

        $data = array('rotation' => 100);
        $testObj->sanitizeRotation($data);
        $this->assertEquals(array('rotation' => 0), $data);

        $data = array('rotation' => 90);
        $testObj->sanitizeRotation($data);
        $this->assertEquals(array('rotation' => 90), $data);

        $data = array('rotation' => 180);
        $testObj->sanitizeRotation($data);
        $this->assertEquals(array('rotation' => 180), $data);

        $data = array('rotation' => 270);
        $testObj->sanitizeRotation($data);
        $this->assertEquals(array('rotation' => 270), $data);

        $data = array('rotation' => 360);
        $testObj->sanitizeRotation($data);
        $this->assertEquals(array('rotation' => 360), $data);
    }

    public function testSanitizeZoom()
    {
        $testObj = $this->getTestObject();
        $data = array();
        $testObj->sanitizeZoom($data);
        $this->assertEquals(array('zoom' => 1), $data);

        $data = array('zoom' => 1.2);
        $testObj->sanitizeZoom($data);
        $this->assertEquals(array('zoom' => 1.2), $data);
    }

    public function testSanitizeTransitions()
    {
        $testObj = $this->getTestObject();
        $data = array();
        $testObj->sanitizeTransitions($data);
        $this->assertEquals(array(), $data);

        $data = array('transition' => array('Dur' => 0));
        $testObj->sanitizeTransitions($data);
        $exp = array(
            'transition' => array(
                'S' => 'R',
                'D' => 1,
                'B' => false,
            )
        );
        $this->assertEquals($exp, $data);

        $data = array(
            'transition' => array(
                'Dur' => 2,
                'D' => 3,
                'Dm' => 'V',
                'S' => 'Glitter',
                'M' => 'O',
                'Di' => 315,
                'SS' => 1.3,
                'B' => true
            )
        );
        $testObj->sanitizeTransitions($data);
        $exp = array(
            'transition' => array(
                'Dur' => 2,
                'D' => 3,
                'S' => 'Glitter',
                'Di' => 315,
                'SS' => 1.3,
                'B' => true,
            )
        );
        $this->assertEquals($exp, $data);
    }

    public function testSanitizeMargins()
    {
        $testObj = $this->getTestObject();
        $data = array();
        $testObj->sanitizeMargins($data);
        $exp = array(
            'margin' => array(
                'PL' => 0,
                'PR' => 0,
                'PT' => 0,
                'HB' => 0,
                'CT' => 0,
                'CB' => 0,
                'FT' => 0,
                'PB' => 0,
            ),
            'orientation' => 'P',
            'height' => 297,
            'width' => 210,
            'ContentWidth' => 210,
            'ContentHeight' => 297,
            'HeaderHeight' => 0,
            'FooterHeight' => 0,
        );
        $this->bcAssertEqualsWithDelta($exp, $data);

        $data = array(
            'margin' => array(
                'PL' => 11,
                'PR' => 12,
                'PT' => 13,
                'HB' => 14,
                'CT' => 15,
                'CB' => 15,
                'FT' => 13,
                'PB' => 11,
            ),
            'orientation' => 'P',
            'height' => 297,
            'width' => 210,
        );
        $testObj->sanitizeMargins($data);
        $exp = array(
            'margin' => array(
                'PL' => 11,
                'PR' => 12,
                'PT' => 13,
                'HB' => 14,
                'CT' => 15,
                'CB' => 15,
                'FT' => 13,
                'PB' => 11,
            ),
            'orientation' => 'P',
            'height' => 297,
            'width' => 210,
            'ContentWidth' => 187,
            'ContentHeight' => 267,
            'HeaderHeight' => 1,
            'FooterHeight' => 2,
        );
        $this->bcAssertEqualsWithDelta($exp, $data);
    }

    public function testSanitizeBoxData()
    {
        $testObj = $this->getTestObject();
        $data = array();
        $testObj->sanitizeBoxData($data);
        $exp = array(
            'orientation' => 'P',
            'pheight' => 841.890,
            'pwidth' => 595.276,
            'box' => array(
                'MediaBox' => array(
                    'llx' => 0,
                    'lly' => 0,
                    'urx' => 595.276,
                    'ury' => 841.890,
                    'bci' => array(
                        'color' => '#000000',
                        'width' => 0.353,
                        'style' => 'S',
                        'dash' => array(
                            0 => 3,
                        ),
                    ),
                ),
                'CropBox' => array(
                    'llx' => 0,
                    'lly' => 0,
                    'urx' => 595.276,
                    'ury' => 841.890,
                    'bci' => array(
                        'color' => '#000000',
                        'width' => 0.353,
                        'style' => 'S',
                        'dash' => array(
                            0 => 3,
                        ),
                    ),
                ),
                'BleedBox' => array(
                    'llx' => 0,
                    'lly' => 0,
                    'urx' => 595.276,
                    'ury' => 841.890,
                    'bci' => array(
                        'color' => '#000000',
                        'width' => 0.353,
                        'style' => 'S',
                        'dash' => array(
                            0 => 3,
                        ),
                    ),
                ),
                'TrimBox' => array(
                    'llx' => 0,
                    'lly' => 0,
                    'urx' => 595.276,
                    'ury' => 841.890,
                    'bci' => array(
                        'color' => '#000000',
                        'width' => 0.353,
                        'style' => 'S',
                        'dash' => array(
                            0 => 3,
                        ),
                    ),
                ),
                'ArtBox' => array(
                    'llx' => 0,
                    'lly' => 0,
                    'urx' => 595.276,
                    'ury' => 841.890,
                    'bci' => array(
                        'color' => '#000000',
                        'width' => 0.353,
                        'style' => 'S',
                        'dash' => array(
                            0 => 3,
                        ),
                    ),
                ),
            )
        );
        $this->bcAssertEqualsWithDelta($exp, $data);

        $data = array(
            'format' => 'MediaBox',
            'orientation' => 'L',
            'box' => array(
                'MediaBox' => array(
                    'llx' => 0,
                    'lly' => 0,
                    'urx' => 595.276,
                    'ury' => 841.890,
                    'bci' => array(
                        'color' => '#000000',
                        'width' => 0.353,
                        'style' => 'S',
                        'dash' => array(
                            0 => 3,
                        )
                    )
                )
            )
        );
        $testObj->sanitizeBoxData($data);
        $exp = array(
            'format' => 'CUSTOM',
            'orientation' => 'L',
            'box' => array(
                'MediaBox' => array(
                    'llx' => 0,
                    'lly' => 0,
                    'urx' => 841.890,
                    'ury' => 595.276,
                    'bci' => array(
                        'color' => '#000000',
                        'width' => 0.353,
                        'style' => 'S',
                        'dash' => array(0 => 3)
                    )
                ),
                'CropBox' => array(
                    'llx' => 0,
                    'lly' => 0,
                    'urx' => 841.890,
                    'ury' => 595.276,
                    'bci' => array(
                        'color' => '#000000',
                        'width' => 0.353,
                        'style' => 'S',
                        'dash' => array(0 => 3)
                    )
                ),
                'BleedBox' => array(
                    'llx' => 0,
                    'lly' => 0,
                    'urx' => 841.890,
                    'ury' => 595.276,
                    'bci' => array(
                        'color' => '#000000',
                        'width' => 0.353,
                        'style' => 'S',
                        'dash' => array(0 => 3)
                    )
                ),
                'TrimBox' => array(
                    'llx' => 0,
                    'lly' => 0,
                    'urx' => 841.890,
                    'ury' => 595.276,
                    'bci' => array(
                        'color' => '#000000',
                        'width' => 0.353,
                        'style' => 'S',
                        'dash' => array(0 => 3)
                    )
                ),
                'ArtBox' => array(
                    'llx' => 0,
                    'lly' => 0,
                    'urx' => 841.890,
                    'ury' => 595.276,
                    'bci' => array(
                        'color' => '#000000',
                        'width' => 0.353,
                        'style' => 'S',
                        'dash' => array(0 => 3)
                    )
                ),
            ),
            'width' => 297,
            'height' => 210,
            'pwidth' => 841.890,
            'pheight' => 595.276,
        );
        $this->bcAssertEqualsWithDelta($exp, $data);

        $data = array(
            'width' => 210,
            'height' => 297,
            'pwidth' => 595.276,
            'pheight' => 841.890,
            'box' => array(
                'CropBox' => array(
                    'llx' => 0,
                    'lly' => 0,
                    'urx' => 595.276,
                    'ury' => 841.890,
                    'bci' => array(
                        'color' => '#000000',
                        'width' => 0.353,
                        'style' => 'S',
                        'dash' => array(
                            0 => 3,
                        )
                    )
                )
            )
        );
        $testObj->sanitizeBoxData($data);
        $exp = array(
            'width' => 210,
            'height' => 297,
            'pwidth' => 595.276,
            'pheight' => 841.890,
            'box' => array(
                'CropBox' => array(
                    'llx' => 0,
                    'lly' => 0,
                    'urx' => 595.276,
                    'ury' => 841.890,
                    'bci' => array(
                        'color' => '#000000',
                        'width' => 0.353,
                        'style' => 'S',
                        'dash' => array(0 => 3)
                    )
                ),
                'MediaBox' => array(
                    'llx' => 0,
                    'lly' => 0,
                    'urx' => 595.276,
                    'ury' => 841.890,
                    'bci' => array(
                        'color' => '#000000',
                        'width' => 0.353,
                        'style' => 'S',
                        'dash' => array(0 => 3)
                    )
                ),
                'BleedBox' => array(
                    'llx' => 0,
                    'lly' => 0,
                    'urx' => 595.276,
                    'ury' => 841.890,
                    'bci' => array(
                        'color' => '#000000',
                        'width' => 0.353,
                        'style' => 'S',
                        'dash' => array(0 => 3)
                    )
                ),
                'TrimBox' => array(
                    'llx' => 0,
                    'lly' => 0,
                    'urx' => 595.276,
                    'ury' => 841.890,
                    'bci' => array(
                        'color' => '#000000',
                        'width' => 0.353,
                        'style' => 'S',
                        'dash' => array(0 => 3)
                    )
                ),
                'ArtBox' => array(
                    'llx' => 0,
                    'lly' => 0,
                    'urx' => 595.276,
                    'ury' => 841.890,
                    'bci' => array(
                        'color' => '#000000',
                        'width' => 0.353,
                        'style' => 'S',
                        'dash' => array(0 => 3)
                    )
                ),
            ),
            'orientation' => 'P',
        );
        $this->bcAssertEqualsWithDelta($exp, $data);
    }

    public function testSanitizePageFormat()
    {
        $testObj = $this->getTestObject();
        $data = array();
        $testObj->sanitizePageFormat($data);
        $exp = array(
            'orientation' => 'P',
            'format' => 'A4',
            'pheight' => 841.890,
            'pwidth' => 595.276,
            'width' => 210,
            'height' => 297,
        );
        $this->bcAssertEqualsWithDelta($exp, $data);

        $data = array(
            'box' => array(
                'MediaBox' => array(
                    'llx' => 0,
                    'lly' => 0,
                    'urx' => 595.276,
                    'ury' => 841.890,
                    'bci' => array(
                        'color' => '#000000',
                        'width' => 0.353,
                        'style' => 'S',
                        'dash' => array(
                            0 => 3,
                        )
                    )
                )
            )
        );
        $testObj->sanitizePageFormat($data);
        $exp = array(
            'box' => array(
                'MediaBox' => array(
                    'llx' => 0,
                    'lly' => 0,
                    'urx' => 595.276,
                    'ury' => 841.890,
                    'bci' => array(
                        'color' => '#000000',
                        'width' => 0.353,
                        'style' => 'S',
                        'dash' => array(0 => 3),
                    ),
                ),
            ),
            'orientation' => '',
            'format' => 'MediaBox',
        );
        $this->bcAssertEqualsWithDelta($exp, $data);
    }
}
