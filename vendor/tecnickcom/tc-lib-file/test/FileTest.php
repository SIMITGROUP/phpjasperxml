<?php
/**
 * FileTest.php
 *
 * @since       2015-07-28
 * @category    Library
 * @package     File
 * @author      Nicola Asuni <info@tecnick.com>
 * @copyright   2015-2015 Nicola Asuni - Tecnick.com LTD
 * @license     http://www.gnu.org/copyleft/lesser.html GNU-LGPL v3 (see LICENSE.TXT)
 * @link        https://github.com/tecnickcom/tc-lib-file
 *
 * This file is part of tc-lib-file software library.
 */

namespace Test;

use PHPUnit\Framework\TestCase;

/**
 * File Color class test
 *
 * @since       2015-07-28
 * @category    Library
 * @package     File
 * @author      Nicola Asuni <info@tecnick.com>
 * @copyright   2015-2015 Nicola Asuni - Tecnick.com LTD
 * @license     http://www.gnu.org/copyleft/lesser.html GNU-LGPL v3 (see LICENSE.TXT)
 * @link        https://github.com/tecnickcom/tc-lib-file
 */
class FileTest extends TestUtil
{
    protected function getTestObject()
    {
        return new \Com\Tecnick\File\File();
    }

    public function testFopenLocal()
    {
        $testObj = $this->getTestObject();
        $handle = $testObj->fopenLocal(__FILE__, 'r');
        $this->bcAssertIsResource($handle);
        fclose($handle);
    }

    public function testFopenLocalNonLocal()
    {
        $this->bcExpectException('\Com\Tecnick\File\Exception');
        $testObj = $this->getTestObject();
        $testObj->fopenLocal('http://www.example.com/test.txt', 'r');
    }

    public function testFopenLocalMissing()
    {
        $this->bcExpectException('\Com\Tecnick\File\Exception');
        $testObj = $this->getTestObject();
        $testObj->fopenLocal('/missing_error.txt', 'r');
    }

    public function testfReadInt()
    {
        $testObj = $this->getTestObject();
        $handle = fopen(__FILE__, 'r');
        $res = $testObj->fReadInt($handle);
        // '<?ph' = 60 63 112 104 = 00111100 00111111 01110000 01101000 = 1010790504
        $this->assertEquals(1010790504, $res);
        fclose($handle);
    }

    public function testRfRead()
    {
        $testObj = $this->getTestObject();
        $handle = fopen(dirname(__DIR__).'/src/File.php', 'rb');
        $res = $testObj->rfRead($handle, 2);
        $this->assertEquals('<?', $res);
        $res = $testObj->rfRead($handle, 3);
        $this->assertEquals('php', $res);
        fclose($handle);
    }

    public function testRfReadException()
    {
        $this->bcExpectException('\Com\Tecnick\File\Exception');
        $testObj = $this->getTestObject();
        $testObj->rfRead(null, 2);
    }

    /**
     * @dataProvider getAltFilePathsDataProvider
     */
    public function testGetAltFilePaths($file, $expected)
    {
        $testObj = $this->getTestObject();
        $_SERVER['DOCUMENT_ROOT'] = '/var/www';
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['HTTPS'] = 'on';
        $_SERVER['SCRIPT_URI'] = 'https://localhost/path/example.php';
        $alt = $testObj->getAltFilePaths($file);
        $this->assertEquals($expected, $alt);
    }

    public function getAltFilePathsDataProvider()
    {
        return array(
            array(
                'http://www.example.com/test.txt',
                array(
                    0 => 'http://www.example.com/test.txt'
                )
            ),
            array(
                'https://localhost/path/test.txt',
                array(
                    0 => 'https://localhost/path/test.txt',
                    3 => '/var/www/path/test.txt'
                )
            ),
            array(
                '//www.example.com/space test.txt',
                array(
                    0 => '//www.example.com/space test.txt',
                    2 => 'https://www.example.com/space%20test.txt'
                )
            ),
            array(
                '/path/test.txt',
                array(
                    0 => '/path/test.txt',
                    1 => '/var/www/path/test.txt',
                    4 => 'https://localhost/path/test.txt'
                )
            ),
            array(
                'https://localhost/path/test.php?a=0&b=1&amp;c=2;&amp;d="a+b%20c"',
                array(
                      0 => 'https://localhost/path/test.php?a=0&b=1&amp;c=2;&amp;d="a+b%20c"',
                      2 => 'https://localhost/path/test.php?a=0&b=1&c=2;&d="a+b%20c"',
                )
            ),
            array(
                'path/test.txt',
                array(
                    0 => 'path/test.txt',
                    4 => 'https://localhost/path/test.txt'
                )
            ),
        );
    }

    public function testFileGetContentsException()
    {
        $this->bcExpectException('\Com\Tecnick\File\Exception');
        $testObj = $this->getTestObject();
        $testObj->fileGetContents('missing.txt');
    }

    public function testFileGetContents()
    {
        $testObj = $this->getTestObject();
        $res = $testObj->fileGetContents(__FILE__);
        $this->assertEquals('<?php', substr($res, 0, 5));
    }

    public function testFileGetContentsCurl()
    {
        $this->bcExpectException('\Com\Tecnick\File\Exception');
        $testObj = $this->getTestObject();
        define('FORCE_CURL', true);
        $testObj->fileGetContents('http://www.example.com/test.txt');
    }
}
