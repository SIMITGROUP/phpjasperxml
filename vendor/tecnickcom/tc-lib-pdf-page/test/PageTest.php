<?php

/**
 * PageTest.php
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
class PageTest extends TestUtil
{
    protected function getTestObject(): \Com\Tecnick\Pdf\Page\Page
    {
        $pdf = new \Com\Tecnick\Color\Pdf();
        $encrypt = new \Com\Tecnick\Pdf\Encrypt\Encrypt(false);
        return new \Com\Tecnick\Pdf\Page\Page('mm', $pdf, $encrypt, false, true, false);
    }

    public function testGetKUnit(): void
    {
        $page = $this->getTestObject();
        $this->bcAssertEqualsWithDelta(2.83464566929134, $page->getKUnit(), 0.001);
    }

    public function testEnableSignatureApproval(): void
    {
        $page = $this->getTestObject();
        $res = $page->enableSignatureApproval(true);
        $this->assertNotNull($res);
    }

    public function testAdd(): void
    {
        $page = $this->getTestObject();
        // 1
        $res = $page->add();

        $box = [
            'llx' => 0,
            'lly' => 0,
            'urx' => 595.2765,
            'ury' => 841.890,
            'bci' => [
                'color' => '#000000',
                'width' => 0.353,
                'style' => 'S',
                'dash' => [
                    0 => 3,
                ],
            ],
        ];

        $exp = [
            'group' => 0,
            'rotation' => 0,
            'zoom' => 1,
            'orientation' => 'P',
            'format' => 'A4',
            'pheight' => 841.890,
            'pwidth' => 595.2765,
            'width' => 210,
            'height' => 297,
            'box' => [
                'MediaBox' => $box,
                'CropBox' => $box,
                'BleedBox' => $box,
                'TrimBox' => $box,
                'ArtBox' => $box,
            ],
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
            'ContentWidth' => 210,
            'ContentHeight' => 297,
            'HeaderHeight' => 0,
            'FooterHeight' => 0,
            'region' => [[
                'RX' => 0,
                'RY' => 0,
                'RW' => 210,
                'RH' => 297,
                'RL' => 210,
                'RR' => 0.0,
                'RT' => 297,
                'RB' => 0.0,
                'x' => 0.0,
                'y' => 0.0,
            ]],
            'currentRegion' => 0,
            'columns' => 1,
            'content' => [
                0 => '',
            ],
            'annotrefs' => [],
            'content_mark' => [
                0 => 0,
            ],
            'autobreak' => true,
        ];

        unset($res['time']);
        $exp['pid'] = 0;
        $this->bcAssertEqualsWithDelta($exp, $res);

        // 2
        $res = $page->add();
        unset($res['time']);
        $exp['pid'] = 1;
        $this->bcAssertEqualsWithDelta($exp, $res);

        // 3
        $res = $page->add(
            [
                'group' => 1,
            ]
        );
        unset($res['time']);
        $exp['pid'] = 2;
        $exp['group'] = 1;
        $this->bcAssertEqualsWithDelta($exp, $res);

        // 3
        $res = $page->add(
            [
                'columns' => 2,
            ]
        );
        unset($res['time']);
        $exp['pid'] = 3;
        $exp['group'] = 0;
        $exp['columns'] = 2;
        $exp['region'] = [
            0 => [
                'RX' => 0,
                'RY' => 0,
                'RW' => 105,
                'RH' => 297,
                'RL' => 105,
                'RR' => 105,
                'RT' => 297,
                'RB' => 0,
                'x' => 0,
                'y' => 0,
            ],
            1 => [
                'RX' => 105,
                'RY' => 0,
                'RW' => 105,
                'RH' => 297,
                'RL' => 210,
                'RR' => 0.0,
                'RT' => 297,
                'RB' => 0,
                'x' => 105,
                'y' => 0,
            ],
        ];
        $this->bcAssertEqualsWithDelta($exp, $res);
    }

    public function testGetNextPage(): void
    {
        $page = $this->getTestObject();
        $page->add();
        $page->add();
        $page->add();
        $page->add();

        $page->setCurrentPage(2);
        $page->getNextPage();
        $page->enableAutoPageBreak(false);
        $page->getNextPage();
        $page->enableAutoPageBreak(true);
        $page->getNextPage();
        $page->getNextPage();

        $this->assertCount(6, $page->getPages());
    }

    public function testDelete(): void
    {
        $page = $this->getTestObject();
        $page->add();
        $page->add();
        $page->add();
        $this->assertCount(3, $page->getPages());
        $res = $page->delete(1);
        $this->assertCount(2, $page->getPages());
        $this->assertArrayHasKey('time', $res);
    }

    public function testDeleteEx(): void
    {
        $this->bcExpectException('\\' . \Com\Tecnick\Pdf\Page\Exception::class);
        $page = $this->getTestObject();
        $page->delete(2);
    }

    public function testPop(): void
    {
        $page = $this->getTestObject();
        $page->add();
        $page->add();
        $page->add();
        $this->assertCount(3, $page->getPages());
        $res = $page->pop();
        $this->assertCount(2, $page->getPages());
        $this->assertArrayHasKey('time', $res);
    }

    public function testMove(): void
    {
        $page = $this->getTestObject();
        $page->add();
        $page->add(
            [
                'group' => 1,
            ]
        );
        $page->add(
            [
                'group' => 2,
            ]
        );
        $page->add(
            [
                'group' => 3,
            ]
        );

        $this->assertEquals($page->getPage(3), $page->getPage());

        $page->move(3, 0);
        $this->assertCount(4, $page->getPages());

        $res = $page->getPage(0);
        $this->assertEquals(3, $res['group']);
    }

    public function testMoveEx(): void
    {
        $this->bcExpectException('\\' . \Com\Tecnick\Pdf\Page\Exception::class);
        $page = $this->getTestObject();
        $page->move(1, 2);
    }

    public function testGetPageEx(): void
    {
        $this->bcExpectException('\\' . \Com\Tecnick\Pdf\Page\Exception::class);
        $page = $this->getTestObject();
        $page->getPage(2);
    }

    public function testContent(): void
    {
        $testObj = $this->getTestObject();
        $testObj->add();
        $testObj->addContent('Lorem');
        $testObj->addContent('ipsum');
        $testObj->addContentMark();
        $testObj->addContent('dolor');
        $testObj->addContent('sit');
        $testObj->addContent('amet');

        $this->assertEquals('amet', $testObj->popContent());

        $page = $testObj->getPage();
        $this->assertEquals([0, 3], $page['content_mark']);
        $this->assertEquals(['', 'Lorem', 'ipsum', 'dolor', 'sit'], $page['content']);

        $testObj->popContentToLastMark();
        $page = $testObj->getPage();
        $this->assertEquals([0], $page['content_mark']);
        $this->assertEquals(['', 'Lorem', 'ipsum'], $page['content']);
    }

    public function testGetPdfPages(): void
    {
        $page = $this->getTestObject();
        $page->add();
        $page->addContent('TEST1');
        $page->add();
        $page->addContent('TEST2');
        $page->add(
            [
                'group' => 1,
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
                'annotrefs' => [10, 20],
            ]
        );
        $page->addContent('TEST2');

        $pon = 0;
        $out = $page->getPdfPages($pon);
        $this->assertEquals(1, $page->getResourceDictObjID());
        $this->assertEquals(2, $page->getRootObjID());
        $this->bcAssertStringContainsString('<< /Type /Pages /Kids [ 3 0 R 4 0 R 5 0 R ] /Count 3 >>', $out);
    }

    public function testaddAnnotRef(): void
    {
        $testObj = $this->getTestObject();
        $testObj->add();
        $testObj->addAnnotRef(13);
        $testObj->addAnnotRef(17);

        $page = $testObj->getPage();
        $this->assertEquals(13, $page['annotrefs'][0]);
        $this->assertEquals(17, $page['annotrefs'][1]);
    }
}
