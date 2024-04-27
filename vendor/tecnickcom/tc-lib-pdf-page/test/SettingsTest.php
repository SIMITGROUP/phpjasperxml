<?php

/**
 * SettingsTest.php
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

/**
 * Settings Test
 *
 * @since     2011-05-23
 * @category  Library
 * @package   PdfPage
 * @author    Nicola Asuni <info@tecnick.com>
 * @copyright 2011-2024 Nicola Asuni - Tecnick.com LTD
 * @license   http://www.gnu.org/copyleft/lesser.html GNU-LGPL v3 (see LICENSE.TXT)
 * @link      https://github.com/tecnickcom/tc-lib-pdf-page
 */
class SettingsTest extends TestUtil
{
    protected function getTestObject(): \Com\Tecnick\Pdf\Page\Page
    {
        $pdf = new \Com\Tecnick\Color\Pdf();
        $encrypt = new \Com\Tecnick\Pdf\Encrypt\Encrypt(false);
        return new \Com\Tecnick\Pdf\Page\Page('mm', $pdf, $encrypt, false, false);
    }

    public function testSanitizePageNumber(): void
    {
        $page = $this->getTestObject();
        $data = [];
        $page->sanitizePageNumber($data);
        $this->assertEquals([], $data);

        $data = [
            'num' => -1,
        ];
        $page->sanitizePageNumber($data);
        $this->assertEquals(
            [
                'num' => 0,
            ],
            $data
        );

        $data = [
            'num' => 0,
        ];
        $page->sanitizePageNumber($data);
        $this->assertEquals(
            [
                'num' => 0,
            ],
            $data
        );

        $data = [
            'num' => 1,
        ];
        $page->sanitizePageNumber($data);
        $this->assertEquals(
            [
                'num' => 1,
            ],
            $data
        );
    }

    public function testSanitizeTime(): void
    {
        $page = $this->getTestObject();
        $data = [];
        $page->sanitizeTime($data);
        $this->assertNotEmpty($data['time']); /* @phpstan-ignore-line */

        $data = [
            'time' => -1,
        ];
        $page->sanitizeTime($data);
        $this->assertEquals(
            [
                'time' => 0,
            ],
            $data
        );

        $data = [
            'time' => 0,
        ];
        $page->sanitizeTime($data);
        $this->assertNotEmpty($data['time']);

        $data = [
            'time' => 1,
        ];
        $page->sanitizeTime($data);
        $this->assertEquals(
            [
                'time' => 1,
            ],
            $data
        );
    }

    public function testSanitizeGroup(): void
    {
        $page = $this->getTestObject();
        $data = [];
        $page->sanitizeGroup($data);
        $this->assertEquals(
            [
                'group' => 0,
            ],
            $data
        );

        $data = [
            'group' => -1,
        ];
        $page->sanitizeGroup($data);
        $this->assertEquals(
            [
                'group' => 0,
            ],
            $data
        );

        $data = [
            'group' => 0,
        ];
        $page->sanitizeGroup($data);
        $this->assertEquals(
            [
                'group' => 0,
            ],
            $data
        );

        $data = [
            'group' => 1,
        ];
        $page->sanitizeGroup($data);
        $this->assertEquals(
            [
                'group' => 1,
            ],
            $data
        );
    }

    public function testSanitizeContent(): void
    {
        $page = $this->getTestObject();
        $data = [];
        $page->sanitizeContent($data);
        $this->assertEquals(
            [
                'content' => [''],
            ],
            $data
        );

        $data = [
            'content' => 'test',
        ];
        $page->sanitizeContent($data);
        $this->assertEquals(
            [
                'content' => ['test'],
            ],
            $data
        );
    }

    public function testSanitizeAnnotRefs(): void
    {
        $page = $this->getTestObject();
        $data = [];
        $page->sanitizeAnnotRefs($data);
        $this->assertEquals(
            [
                'annotrefs' => [],
            ],
            $data
        );
    }

    public function testSanitizeRotation(): void
    {
        $page = $this->getTestObject();
        $data = [];
        $page->sanitizeRotation($data);
        $this->assertEquals(
            [
                'rotation' => 0,
            ],
            $data
        );

        $data = [
            'rotation' => 0,
        ];
        $page->sanitizeRotation($data);
        $this->assertEquals(
            [
                'rotation' => 0,
            ],
            $data
        );

        $data = [
            'rotation' => 100,
        ];
        $page->sanitizeRotation($data);
        $this->assertEquals(
            [
                'rotation' => 0,
            ],
            $data
        );

        $data = [
            'rotation' => 90,
        ];
        $page->sanitizeRotation($data);
        $this->assertEquals(
            [
                'rotation' => 90,
            ],
            $data
        );

        $data = [
            'rotation' => 180,
        ];
        $page->sanitizeRotation($data);
        $this->assertEquals(
            [
                'rotation' => 180,
            ],
            $data
        );

        $data = [
            'rotation' => 270,
        ];
        $page->sanitizeRotation($data);
        $this->assertEquals(
            [
                'rotation' => 270,
            ],
            $data
        );

        $data = [
            'rotation' => 360,
        ];
        $page->sanitizeRotation($data);
        $this->assertEquals(
            [
                'rotation' => 360,
            ],
            $data
        );
    }

    public function testSanitizeZoom(): void
    {
        $page = $this->getTestObject();
        $data = [];
        $page->sanitizeZoom($data);
        $this->assertEquals(
            [
                'zoom' => 1,
            ],
            $data
        );

        $data = [
            'zoom' => 1.2,
        ];
        $page->sanitizeZoom($data);
        $this->assertEquals(
            [
                'zoom' => 1.2,
            ],
            $data
        );
    }

    public function testSanitizeTransitions(): void
    {
        $page = $this->getTestObject();
        $data = [];
        $page->sanitizeTransitions($data);
        $this->assertEquals([], $data);

        $data = [
            'transition' => [
                'Dur' => 0,
            ],
        ];
        $page->sanitizeTransitions($data);
        $exp = [
            'transition' => [
                'S' => 'R',
                'D' => 1,
                'B' => false,
            ],
        ];
        $this->assertEquals($exp, $data);

        $data = [
            'transition' => [
                'Dur' => 2,
                'D' => 3,
                'Dm' => 'V',
                'S' => 'Glitter',
                'M' => 'O',
                'Di' => 315,
                'SS' => 1.3,
                'B' => true,
            ],
        ];
        $page->sanitizeTransitions($data);
        $exp = [
            'transition' => [
                'Dur' => 2,
                'D' => 3,
                'S' => 'Glitter',
                'Di' => 315,
                'SS' => 1.3,
                'B' => true,
            ],
        ];
        $this->assertEquals($exp, $data);
    }

    public function testSanitizeMargins(): void
    {
        $page = $this->getTestObject();
        $data = [];
        $page->sanitizeMargins($data);
        $exp = [
            'margin' => [
                'PL' => 0,
                'PR' => 0,
                'PT' => 0,
                'HB' => 0,
                'CT' => 0,
                'CB' => 0,
                'FT' => 0,
                'PB' => 0,
            ],
            'orientation' => 'P',
            'height' => 297,
            'width' => 210,
            'ContentWidth' => 210,
            'ContentHeight' => 297,
            'HeaderHeight' => 0,
            'FooterHeight' => 0,
        ];
        $this->bcAssertEqualsWithDelta($exp, $data);

        $data = [
            'margin' => [
                'PL' => 11,
                'PR' => 12,
                'PT' => 13,
                'HB' => 14,
                'CT' => 15,
                'CB' => 15,
                'FT' => 13,
                'PB' => 11,
            ],
            'orientation' => 'P',
            'height' => 297,
            'width' => 210,
        ];
        $page->sanitizeMargins($data);
        $exp = [
            'margin' => [
                'PL' => 11,
                'PR' => 12,
                'PT' => 13,
                'HB' => 14,
                'CT' => 15,
                'CB' => 15,
                'FT' => 13,
                'PB' => 11,
            ],
            'orientation' => 'P',
            'height' => 297,
            'width' => 210,
            'ContentWidth' => 187,
            'ContentHeight' => 267,
            'HeaderHeight' => 1,
            'FooterHeight' => 2,
        ];
        $this->bcAssertEqualsWithDelta($exp, $data);
    }

    public function testSanitizeBoxData(): void
    {
        $page = $this->getTestObject();
        $data = [];
        $page->sanitizeBoxData($data);
        $exp = [
            'orientation' => 'P',
            'pheight' => 841.890,
            'pwidth' => 595.276,
            'box' => [
                'MediaBox' => [
                    'llx' => 0,
                    'lly' => 0,
                    'urx' => 595.276,
                    'ury' => 841.890,
                    'bci' => [
                        'color' => '#000000',
                        'width' => 0.353,
                        'style' => 'S',
                        'dash' => [
                            0 => 3,
                        ],
                    ],
                ],
                'CropBox' => [
                    'llx' => 0,
                    'lly' => 0,
                    'urx' => 595.276,
                    'ury' => 841.890,
                    'bci' => [
                        'color' => '#000000',
                        'width' => 0.353,
                        'style' => 'S',
                        'dash' => [
                            0 => 3,
                        ],
                    ],
                ],
                'BleedBox' => [
                    'llx' => 0,
                    'lly' => 0,
                    'urx' => 595.276,
                    'ury' => 841.890,
                    'bci' => [
                        'color' => '#000000',
                        'width' => 0.353,
                        'style' => 'S',
                        'dash' => [
                            0 => 3,
                        ],
                    ],
                ],
                'TrimBox' => [
                    'llx' => 0,
                    'lly' => 0,
                    'urx' => 595.276,
                    'ury' => 841.890,
                    'bci' => [
                        'color' => '#000000',
                        'width' => 0.353,
                        'style' => 'S',
                        'dash' => [
                            0 => 3,
                        ],
                    ],
                ],
                'ArtBox' => [
                    'llx' => 0,
                    'lly' => 0,
                    'urx' => 595.276,
                    'ury' => 841.890,
                    'bci' => [
                        'color' => '#000000',
                        'width' => 0.353,
                        'style' => 'S',
                        'dash' => [
                            0 => 3,
                        ],
                    ],
                ],
            ],
        ];
        $this->bcAssertEqualsWithDelta($exp, $data);

        $data = [
            'format' => 'MediaBox',
            'orientation' => 'L',
            'box' => [
                'MediaBox' => [
                    'llx' => 0,
                    'lly' => 0,
                    'urx' => 595.276,
                    'ury' => 841.890,
                    'bci' => [
                        'color' => '#000000',
                        'width' => 0.353,
                        'style' => 'S',
                        'dash' => [
                            0 => 3,
                        ],
                    ],
                ],
            ],
        ];
        $page->sanitizeBoxData($data);
        $exp = [
            'format' => 'CUSTOM',
            'orientation' => 'L',
            'box' => [
                'MediaBox' => [
                    'llx' => 0,
                    'lly' => 0,
                    'urx' => 841.890,
                    'ury' => 595.276,
                    'bci' => [
                        'color' => '#000000',
                        'width' => 0.353,
                        'style' => 'S',
                        'dash' => [
                            0 => 3,
                        ],
                    ],
                ],
                'CropBox' => [
                    'llx' => 0,
                    'lly' => 0,
                    'urx' => 841.890,
                    'ury' => 595.276,
                    'bci' => [
                        'color' => '#000000',
                        'width' => 0.353,
                        'style' => 'S',
                        'dash' => [
                            0 => 3,
                        ],
                    ],
                ],
                'BleedBox' => [
                    'llx' => 0,
                    'lly' => 0,
                    'urx' => 841.890,
                    'ury' => 595.276,
                    'bci' => [
                        'color' => '#000000',
                        'width' => 0.353,
                        'style' => 'S',
                        'dash' => [
                            0 => 3,
                        ],
                    ],
                ],
                'TrimBox' => [
                    'llx' => 0,
                    'lly' => 0,
                    'urx' => 841.890,
                    'ury' => 595.276,
                    'bci' => [
                        'color' => '#000000',
                        'width' => 0.353,
                        'style' => 'S',
                        'dash' => [
                            0 => 3,
                        ],
                    ],
                ],
                'ArtBox' => [
                    'llx' => 0,
                    'lly' => 0,
                    'urx' => 841.890,
                    'ury' => 595.276,
                    'bci' => [
                        'color' => '#000000',
                        'width' => 0.353,
                        'style' => 'S',
                        'dash' => [
                            0 => 3,
                        ],
                    ],
                ],
            ],
            'width' => 297,
            'height' => 210,
            'pwidth' => 841.890,
            'pheight' => 595.276,
        ];
        $this->bcAssertEqualsWithDelta($exp, $data);

        $data = [
            'width' => 210,
            'height' => 297,
            'pwidth' => 595.276,
            'pheight' => 841.890,
            'box' => [
                'CropBox' => [
                    'llx' => 0,
                    'lly' => 0,
                    'urx' => 595.276,
                    'ury' => 841.890,
                    'bci' => [
                        'color' => '#000000',
                        'width' => 0.353,
                        'style' => 'S',
                        'dash' => [
                            0 => 3,
                        ],
                    ],
                ],
            ],
        ];
        $page->sanitizeBoxData($data);
        $exp = [
            'width' => 210,
            'height' => 297,
            'pwidth' => 595.276,
            'pheight' => 841.890,
            'box' => [
                'CropBox' => [
                    'llx' => 0,
                    'lly' => 0,
                    'urx' => 595.276,
                    'ury' => 841.890,
                    'bci' => [
                        'color' => '#000000',
                        'width' => 0.353,
                        'style' => 'S',
                        'dash' => [
                            0 => 3,
                        ],
                    ],
                ],
                'MediaBox' => [
                    'llx' => 0,
                    'lly' => 0,
                    'urx' => 595.276,
                    'ury' => 841.890,
                    'bci' => [
                        'color' => '#000000',
                        'width' => 0.353,
                        'style' => 'S',
                        'dash' => [
                            0 => 3,
                        ],
                    ],
                ],
                'BleedBox' => [
                    'llx' => 0,
                    'lly' => 0,
                    'urx' => 595.276,
                    'ury' => 841.890,
                    'bci' => [
                        'color' => '#000000',
                        'width' => 0.353,
                        'style' => 'S',
                        'dash' => [
                            0 => 3,
                        ],
                    ],
                ],
                'TrimBox' => [
                    'llx' => 0,
                    'lly' => 0,
                    'urx' => 595.276,
                    'ury' => 841.890,
                    'bci' => [
                        'color' => '#000000',
                        'width' => 0.353,
                        'style' => 'S',
                        'dash' => [
                            0 => 3,
                        ],
                    ],
                ],
                'ArtBox' => [
                    'llx' => 0,
                    'lly' => 0,
                    'urx' => 595.276,
                    'ury' => 841.890,
                    'bci' => [
                        'color' => '#000000',
                        'width' => 0.353,
                        'style' => 'S',
                        'dash' => [
                            0 => 3,
                        ],
                    ],
                ],
            ],
            'orientation' => 'P',
        ];
        $this->bcAssertEqualsWithDelta($exp, $data);
    }

    public function testSanitizePageFormat(): void
    {
        $page = $this->getTestObject();
        $data = [];
        $page->sanitizePageFormat($data);
        $exp = [
            'orientation' => 'P',
            'format' => 'A4',
            'pheight' => 841.890,
            'pwidth' => 595.276,
            'width' => 210,
            'height' => 297,
        ];
        $this->bcAssertEqualsWithDelta($exp, $data);

        $data = [
            'box' => [
                'MediaBox' => [
                    'llx' => 0,
                    'lly' => 0,
                    'urx' => 595.276,
                    'ury' => 841.890,
                    'bci' => [
                        'color' => '#000000',
                        'width' => 0.353,
                        'style' => 'S',
                        'dash' => [
                            0 => 3,
                        ],
                    ],
                ],
            ],
        ];
        $page->sanitizePageFormat($data);
        $exp = [
            'box' => [
                'MediaBox' => [
                    'llx' => 0,
                    'lly' => 0,
                    'urx' => 595.276,
                    'ury' => 841.890,
                    'bci' => [
                        'color' => '#000000',
                        'width' => 0.353,
                        'style' => 'S',
                        'dash' => [
                            0 => 3,
                        ],
                    ],
                ],
            ],
            'orientation' => 'P',
            'format' => 'A4',
            'pwidth' => 595.276,
            'pheight' => 841.890,
            'width' => 210.000,
            'height' => 297.000,
        ];
        $this->bcAssertEqualsWithDelta($exp, $data);
    }
}
