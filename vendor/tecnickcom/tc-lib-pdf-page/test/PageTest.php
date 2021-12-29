<?php
/**
 * PageTest.php
 *
 * @since       2011-05-23
 * @category    Library
 * @package     PdfPage
 * @author      Nicola Asuni <info@tecnick.com>
 * @copyright   2011-2015 Nicola Asuni - Tecnick.com LTD
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
 * @copyright   2011-2015 Nicola Asuni - Tecnick.com LTD
 * @license     http://www.gnu.org/copyleft/lesser.html GNU-LGPL v3 (see LICENSE.TXT)
 * @link        https://github.com/tecnickcom/tc-lib-pdf-page
 */
class PageTest extends TestUtil
{
    protected function getTestObject()
    {
        $col = new \Com\Tecnick\Color\Pdf;
        $enc = new \Com\Tecnick\Pdf\Encrypt\Encrypt(false);
        return new \Com\Tecnick\Pdf\Page\Page('mm', $col, $enc, false, false);
    }

    public function testGetKUnit()
    {
        $testObj = $this->getTestObject();
        $this->assertEquals(2.83464566929134, $testObj->getKUnit(), '', 0.001);
    }

    public function testEnableSignatureApproval()
    {
        $testObj = $this->getTestObject();
        $res = $testObj->enableSignatureApproval(true);
        $this->assertNotNull($res);
    }

    public function testAdd()
    {
        $testObj = $this->getTestObject();
        // 1
        $res = $testObj->add();

        $box = array(
            'llx' => 0,
            'lly' => 0,
            'urx' => 595.2765,
            'ury' => 841.890,
            'bci' => array(
                'color' => '#000000',
                'width' => 0.353,
                'style' => 'S',
                'dash' => array(0 => 3)
            )
        );

        $exp = array(
            'group' => 0,
            'rotation' => 0,
            'zoom' => 1,
            'orientation' => 'P',
            'format' => 'A4',
            'pheight' => 841.890,
            'pwidth' => 595.2765,
            'width' => 210,
            'height' => 297,
            'box' => array(
                'MediaBox' => $box,
                'CropBox'  => $box,
                'BleedBox' => $box,
                'TrimBox'  => $box,
                'ArtBox'   => $box,
            ),
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
            'ContentWidth' => 210,
            'ContentHeight' => 297,
            'HeaderHeight' => 0,
            'FooterHeight' => 0,
            'region' => array (
                array (
                    'RX' => 0,
                    'RY' => 0,
                    'RW' => 210,
                    'RH' => 297,
                    'RL' => 210,
                    'RR' => 0.0,
                    'RT' => 297,
                    'RB' => 0.0,
                    'x'  => 0.0,
                    'y'  => 0.0,
                ),
            ),
            'currentRegion' => 0,
            'columns' => 1,
            'content' => array(0 => ''),
            'annotrefs' => array(),
            'content_mark' => array(0 => 0),
            'autobreak' => true,
        );
        
        unset($res['time']);
        $this->bcAssertEqualsWithDelta($exp, $res);

        // 2
        $res = $testObj->add();
        unset($res['time']);
        $this->bcAssertEqualsWithDelta($exp, $res);

        // 3
        $res = $testObj->add(array('group' => 1));
        unset($res['time']);
        $exp['group'] = 1;
        $this->bcAssertEqualsWithDelta($exp, $res);

        // 3
        $res = $testObj->add(array('columns' => 2));
        unset($res['time']);
        $exp['group'] = 0;
        $exp['columns'] = 2;
        $exp['region'] = array (
            0 => array (
                'RX' => 0,
                'RY' => 0,
                'RW' => 105,
                'RH' => 297,
                'RL' => 105,
                'RR' => 105,
                'RT' => 297,
                'RB' => 0,
                'x'  => 0,
                'y'  => 0,
            ),
            1 => array (
                'RX' => 105,
                'RY' => 0,
                'RW' => 105,
                'RH' => 297,
                'RL' => 210,
                'RR' => 0.0,
                'RT' => 297,
                'RB' => 0,
                'x'  => 105,
                'y'  => 0,
            ),
        );
        $this->bcAssertEqualsWithDelta($exp, $res);
    }

    public function testGetNextPage()
    {
        $testObj = $this->getTestObject();
        $testObj->add();
        $testObj->add();
        $testObj->add();
        $testObj->add();

        $testObj->setCurrentPage(2);
        $testObj->getNextPage();
        $testObj->enableAutoPageBreak(false);
        $testObj->getNextPage();
        $testObj->enableAutoPageBreak(true);
        $testObj->getNextPage();
        $testObj->getNextPage();

        $this->assertCount(6, $testObj->getPages());
    }

    public function testDelete()
    {
        $testObj = $this->getTestObject();
        $testObj->add();
        $testObj->add();
        $testObj->add();
        $this->assertCount(3, $testObj->getPages());
        $res = $testObj->delete(1);
        $this->assertCount(2, $testObj->getPages());
        $this->assertArrayHasKey('time', $res);
    }

    public function testDeleteEx()
    {
        $this->bcExpectException('\Com\Tecnick\Pdf\Page\Exception');
        $testObj = $this->getTestObject();
        $testObj->delete(2);
    }

    public function testPop()
    {
        $testObj = $this->getTestObject();
        $testObj->add();
        $testObj->add();
        $testObj->add();
        $this->assertCount(3, $testObj->getPages());
        $res = $testObj->pop();
        $this->assertCount(2, $testObj->getPages());
        $this->assertArrayHasKey('time', $res);
    }

    public function testMove()
    {
        $testObj = $this->getTestObject();
        $testObj->add();
        $testObj->add(array('group' => 1));
        $testObj->add(array('group' => 2));
        $testObj->add(array('group' => 3));

        $this->assertEquals($testObj->getPage(3), $testObj->getCurrentPage());
        
        $testObj->move(3, 0);
        $this->assertCount(4, $testObj->getPages());

        $res = $testObj->getPage(0);
        $this->assertEquals(3, $res['group']);
    }

    public function testMoveEx()
    {
        $this->bcExpectException('\Com\Tecnick\Pdf\Page\Exception');
        $testObj = $this->getTestObject();
        $testObj->move(1, 2);
    }

    public function testGetPageEx()
    {
        $this->bcExpectException('\Com\Tecnick\Pdf\Page\Exception');
        $testObj = $this->getTestObject();
        $testObj->getPage(2);
    }

    public function testContent()
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

        $page = $testObj->getCurrentPage();
        $this->assertEquals(array(0, 3), $page['content_mark']);
        $this->assertEquals(array('', 'Lorem', 'ipsum', 'dolor', 'sit'), $page['content']);

        $testObj->popContentToLastMark();
        $page = $testObj->getCurrentPage();
        $this->assertEquals(array(0), $page['content_mark']);
        $this->assertEquals(array('', 'Lorem', 'ipsum'), $page['content']);
    }

    public function testGetPdfPages()
    {
        $testObj = $this->getTestObject();
        $testObj->add();
        $testObj->addContent('TEST1');
        $testObj->add();
        $testObj->addContent('TEST2');
        $testObj->add(
            array(
                'group' => 1,
                'transition' => array(
                    'Dur' => 2,
                    'D' => 3,
                    'Dm' => 'V',
                    'S' => 'Glitter',
                    'M' => 'O',
                    'Di' => 315,
                    'SS' => 1.3,
                    'B' => true
                ),
                'annotrefs' => array(10, 20),
            )
        );
        $testObj->addContent('TEST2');
        $pon = 0;
        $out = $testObj->getPdfPages($pon);
        $this->assertEquals(2, $testObj->getResourceDictObjID());
        $this->bcAssertStringContainsString('<< /Type /Pages /Kids [ 3 0 R 4 0 R 5 0 R ] /Count 3 >>', $out);
    }
}
