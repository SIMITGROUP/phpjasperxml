<?php
/**
 * StyleTest.php
 *
 * @since       2011-05-23
 * @category    Library
 * @package     PdfGraph
 * @author      Nicola Asuni <info@tecnick.com>
 * @copyright   2011-2017 Nicola Asuni - Tecnick.com LTD
 * @license     http://www.gnu.org/copyleft/lesser.html GNU-LGPL v3 (see LICENSE.TXT)
 * @link        https://github.com/tecnickcom/tc-lib-pdf-graph
 *
 * This file is part of tc-lib-pdf-graph software library.
 */

namespace Test;

use PHPUnit\Framework\TestCase;

/**
 * Style Test
 *
 * @since       2011-05-23
 * @category    Library
 * @package     PdfGraph
 * @author      Nicola Asuni <info@tecnick.com>
 * @copyright   2011-2017 Nicola Asuni - Tecnick.com LTD
 * @license     http://www.gnu.org/copyleft/lesser.html GNU-LGPL v3 (see LICENSE.TXT)
 * @link        https://github.com/tecnickcom/tc-lib-pdf-graph
 */
class StyleTest extends TestUtil
{
    protected function getTestObject()
    {
        return new \Com\Tecnick\Pdf\Graph\Draw(
            1,
            0,
            0,
            new \Com\Tecnick\Color\Pdf(),
            new \Com\Tecnick\Pdf\Encrypt\Encrypt(),
            false
        );
    }

    public function testStyle()
    {
        $testObj = $this->getTestObject();
        $style = array();
        $res = $testObj->add($style, true);
        $exp1 = '1.000000 w'."\n"
            .'0 J'."\n"
            .'0 j'."\n"
            .'10.000000 M'."\n"
            .'/CS1 CS 1.000000 SCN'."\n"
            .'/CS1 cs 1.000000 scn'."\n";
        $this->assertEquals($exp1, $res);

        $style = array(
            'lineWidth'  => 3,
            'lineCap'    => 'round',
            'lineJoin'   => 'bevel',
            'miterLimit' => 11,
            'dashArray'  => array(5, 7),
            'dashPhase'  => 1,
            'lineColor'  => 'greenyellow',
            'fillColor'  => '["RGB",0.250000,0.500000,0.750000]',
        );
        $res = $testObj->add($style, false);
        $exp2 = '3.000000 w'."\n"
            .'1 J'."\n"
            .'2 j'."\n"
            .'11.000000 M'."\n"
            .'[5.000000 7.000000] 1.000000 d'."\n"
            .'0.678431 1.000000 0.184314 RG'."\n"
            .'0.250000 0.500000 0.750000 rg'."\n";
        $this->assertEquals($exp2, $res);
        $this->assertEquals($style, $testObj->getCurrentStyleArray());

        $style = array(
            'lineCap'    => 'round',
            'lineJoin'   => 'bevel',
            'lineColor'  => 'transparent',
            'fillColor'  => 'cmyk(67,33,0,25)',
        );
        $res = $testObj->add($style, true);
        $exp3 = '3.000000 w'."\n"
            .'1 J'."\n"
            .'2 j'."\n"
            .'11.000000 M'."\n"
            .'[5.000000 7.000000] 1.000000 d'."\n"
            .'0.670000 0.330000 0.000000 0.250000 k'."\n";
        $this->assertEquals($exp3, $res);

        $style = array('lineWidth'  => 7.123);
        $res = $testObj->add($style, false);
        $exp4 = '7.123000 w'."\n";
        $this->assertEquals($exp4, $res);

        $res = $testObj->pop();
        $this->assertEquals($exp4, $res);

        $res = $testObj->pop();
        $this->assertEquals($exp3, $res);

        $res = $testObj->pop();
        $this->assertEquals($exp2, $res);

        $res = $testObj->pop();
        $this->assertEquals($exp1, $res);
    }

    public function testStyleEx()
    {
        $this->bcExpectException('\Com\Tecnick\Pdf\Graph\Exception');
        $testObj = $this->getTestObject();
        $testObj->pop();
    }

    public function testSaveRestoreStyle()
    {
        $testObj = $this->getTestObject();
        $testObj->add(array('lineWidth' => 1), false);
        $testObj->add(array('lineWidth' => 2), false);
        $testObj->add(array('lineWidth' => 3), false);
        $testObj->saveStyleStaus();
        $testObj->add(array('lineWidth' => 4), false);
        $testObj->add(array('lineWidth' => 5), false);
        $testObj->add(array('lineWidth' => 6), false);
        $this->assertEquals(array('lineWidth' => 6), $testObj->getCurrentStyleArray());
        $testObj->restoreStyleStaus();
        $this->assertEquals(array('lineWidth' => 3), $testObj->getCurrentStyleArray());
    }

    public function testStyleItem()
    {
        $testObj = $this->getTestObject();
        $res = $testObj->getCurrentStyleItem('lineCap');
        $this->assertEquals('butt', $res);
    }

    public function testStyleItemEx()
    {
        $this->bcExpectException('\Com\Tecnick\Pdf\Graph\Exception');
        $testObj = $this->getTestObject();
        $testObj->getCurrentStyleItem('wrongField');
    }

    public function testGetLastStyleProperty()
    {
        $testObj = $this->getTestObject();
        $testObj->add(array('lineWidth' => 1), false);
        $testObj->add(array('lineWidth' => 2), false);
        $testObj->add(array('lineWidth' => 3), false);
        $this->assertEquals(3, $testObj->getLastStyleProperty('lineWidth', 0));
        $testObj->add(array('lineWidth' => 4), false);
        $this->assertEquals(4, $testObj->getLastStyleProperty('lineWidth', 0));
        $this->assertEquals(7, $testObj->getLastStyleProperty('unknown', 7));
    }

    public function testGetPathPaintOp()
    {
        $testObj = $this->getTestObject();
        $res = $testObj->getPathPaintOp('', '');
        $this->assertEquals('', $res);
    
        $res = $testObj->getPathPaintOp('');
        $this->assertEquals('S'."\n", $res);
    
        $res = $testObj->getPathPaintOp('', 'df');
        $this->assertEquals('b'."\n", $res);
    
        $res = $testObj->getPathPaintOp('CEO');
        $this->assertEquals('W* n'."\n", $res);
    
        $res = $testObj->getPathPaintOp('F*D');
        $this->assertEquals('B*'."\n", $res);
    }

    public function testIsFillingMode()
    {
        $testObj = $this->getTestObject();
        $this->assertTrue($testObj->isFillingMode('f'));
        $this->assertTrue($testObj->isFillingMode('f*'));
        $this->assertTrue($testObj->isFillingMode('B'));
        $this->assertTrue($testObj->isFillingMode('B*'));
        $this->assertTrue($testObj->isFillingMode('b'));
        $this->assertTrue($testObj->isFillingMode('b*'));
        $this->assertFalse($testObj->isFillingMode('S'));
        $this->assertFalse($testObj->isFillingMode('s'));
        $this->assertFalse($testObj->isFillingMode('n'));
        $this->assertFalse($testObj->isFillingMode(''));
    }

    public function testIsStrokingMode()
    {
        $testObj = $this->getTestObject();
        $this->assertTrue($testObj->isStrokingMode('S'));
        $this->assertTrue($testObj->isStrokingMode('s'));
        $this->assertTrue($testObj->isStrokingMode('B'));
        $this->assertTrue($testObj->isStrokingMode('B*'));
        $this->assertTrue($testObj->isStrokingMode('b'));
        $this->assertTrue($testObj->isStrokingMode('b*'));
        $this->assertFalse($testObj->isStrokingMode('f'));
        $this->assertFalse($testObj->isStrokingMode('f*'));
        $this->assertFalse($testObj->isStrokingMode('n'));
        $this->assertFalse($testObj->isStrokingMode(''));
    }

    public function testIsClosingMode()
    {
        $testObj = $this->getTestObject();
        $this->assertTrue($testObj->isClosingMode('s'));
        $this->assertTrue($testObj->isClosingMode('b'));
        $this->assertTrue($testObj->isClosingMode('b*'));
        $this->assertFalse($testObj->isClosingMode('f'));
        $this->assertFalse($testObj->isClosingMode('f*'));
        $this->assertFalse($testObj->isClosingMode('S'));
        $this->assertFalse($testObj->isClosingMode('B'));
        $this->assertFalse($testObj->isClosingMode('B*'));
        $this->assertFalse($testObj->isClosingMode('n'));
        $this->assertFalse($testObj->isClosingMode(''));
    }

    public function testGetModeWithoutClose()
    {
        $testObj = $this->getTestObject();
        $this->assertEquals('', $testObj->getModeWithoutClose(''));
        $this->assertEquals('S', $testObj->getModeWithoutClose('s'));
        $this->assertEquals('B', $testObj->getModeWithoutClose('b'));
        $this->assertEquals('B*', $testObj->getModeWithoutClose('b*'));
        $this->assertEquals('n', $testObj->getModeWithoutClose('n'));
    }

    public function testGetModeWithoutFill()
    {
        $testObj = $this->getTestObject();
        $this->assertEquals('', $testObj->getModeWithoutFill(''));
        $this->assertEquals('', $testObj->getModeWithoutFill('f'));
        $this->assertEquals('', $testObj->getModeWithoutFill('f*'));
        $this->assertEquals('S', $testObj->getModeWithoutFill('B'));
        $this->assertEquals('S', $testObj->getModeWithoutFill('B*'));
        $this->assertEquals('s', $testObj->getModeWithoutFill('b'));
        $this->assertEquals('s', $testObj->getModeWithoutFill('b*'));
        $this->assertEquals('n', $testObj->getModeWithoutFill('n'));
    }

    public function testGetModeWithoutStroke()
    {
        $testObj = $this->getTestObject();
        $this->assertEquals('', $testObj->getModeWithoutStroke(''));
        $this->assertEquals('', $testObj->getModeWithoutStroke('S'));
        $this->assertEquals('h', $testObj->getModeWithoutStroke('s'));
        $this->assertEquals('f', $testObj->getModeWithoutStroke('B'));
        $this->assertEquals('f*', $testObj->getModeWithoutStroke('B*'));
        $this->assertEquals('h f', $testObj->getModeWithoutStroke('b'));
        $this->assertEquals('h f*', $testObj->getModeWithoutStroke('b*'));
        $this->assertEquals('n', $testObj->getModeWithoutStroke('n'));
    }

    public function testGetExtGState()
    {
        $testObj = $this->getTestObject();
        $this->assertEquals(
            '/GS1 gs'."\n",
            $testObj->getExtGState(array('A' => 'B'))
        );
        $this->assertEquals(
            '/GS1 gs'."\n",
            $testObj->getExtGState(array('A' => 'B'))
        );
        $this->assertEquals(
            '/GS2 gs'."\n",
            $testObj->getExtGState(array('C' => 'D'))
        );
    }

    public function testGetExtGStatePdfa()
    {
        $obj = new \Com\Tecnick\Pdf\Graph\Draw(
            1,
            0,
            0,
            new \Com\Tecnick\Color\Pdf(),
            new \Com\Tecnick\Pdf\Encrypt\Encrypt(),
            true
        );
        $this->assertEquals(
            '',
            $obj->getExtGState(array('A' => 'B'))
        );
    }
}
