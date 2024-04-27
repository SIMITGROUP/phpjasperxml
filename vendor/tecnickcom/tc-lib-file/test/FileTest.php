<?php

/**
 * FileTest.php
 *
 * @since     2015-07-28
 * @category  Library
 * @package   File
 * @author    Nicola Asuni <info@tecnick.com>
 * @copyright 2015-2024 Nicola Asuni - Tecnick.com LTD
 * @license   http://www.gnu.org/copyleft/lesser.html GNU-LGPL v3 (see LICENSE.TXT)
 * @link      https://github.com/tecnickcom/tc-lib-file
 *
 * This file is part of tc-lib-file software library.
 */

namespace Test;

/**
 * File Color class test
 *
 * @since     2015-07-28
 * @category  Library
 * @package   File
 * @author    Nicola Asuni <info@tecnick.com>
 * @copyright 2015-2024 Nicola Asuni - Tecnick.com LTD
 * @license   http://www.gnu.org/copyleft/lesser.html GNU-LGPL v3 (see LICENSE.TXT)
 * @link      https://github.com/tecnickcom/tc-lib-file
 */
class FileTest extends TestUtil
{
    protected function getTestObject(): \Com\Tecnick\File\File
    {
        return new \Com\Tecnick\File\File();
    }

    public function testFopenLocal(): void
    {
        $file = $this->getTestObject();
        $handle = $file->fopenLocal(__FILE__, 'r');
        $this->bcAssertIsResource($handle);
        fclose($handle);
    }

    public function testFopenLocalNonLocal(): void
    {
        $this->bcExpectException('\\' . \Com\Tecnick\File\Exception::class);
        $file = $this->getTestObject();
        $file->fopenLocal('http://www.example.com/test.txt', 'r');
    }

    public function testFopenLocalMissing(): void
    {
        $this->bcExpectException('\\' . \Com\Tecnick\File\Exception::class);
        $file = $this->getTestObject();
        $file->fopenLocal('/missing_error.txt', 'r');
    }

    public function testfReadInt(): void
    {
        $file = $this->getTestObject();
        $handle = fopen(__FILE__, 'r');
        $this->assertNotFalse($handle);
        $res = $file->fReadInt($handle);
        // '<?ph' = 60 63 112 104 = 00111100 00111111 01110000 01101000 = 1010790504
        $this->assertEquals(1_010_790_504, $res);
        fclose($handle);
    }

    public function testRfRead(): void
    {
        $file = $this->getTestObject();
        $handle = fopen(dirname(__DIR__) . '/src/File.php', 'rb');
        $this->assertNotFalse($handle);
        $res = $file->rfRead($handle, 2);
        $this->assertEquals('<?', $res);
        $res = $file->rfRead($handle, 3);
        $this->assertEquals('php', $res);
        fclose($handle);
    }

    public function testRfReadException(): void
    {
        $this->bcExpectException('\\' . \Com\Tecnick\File\Exception::class);
        $file = $this->getTestObject();
        $file->rfRead(null, 2);
    }

    /**
     * @param string $file     File path
     * @param array{string, array<int, string>}  $expected Expected result
     *
     * @dataProvider getAltFilePathsDataProvider
     */
    public function testGetAltFilePaths(string $file, array $expected): void
    {
        $testObj = $this->getTestObject();
        $_SERVER['DOCUMENT_ROOT'] = '/var/www';
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['HTTPS'] = 'on';
        $_SERVER['SCRIPT_URI'] = 'https://localhost/path/example.php';
        $alt = $testObj->getAltFilePaths($file);
        $this->assertEquals($expected, $alt);
    }

    /**
     * Data provider for testGetAltFilePaths
     *
     * @return array<array{string, array<int, string>}>
     */
    public static function getAltFilePathsDataProvider(): array
    {
        return [
            [
                'http://www.example.com/test.txt',
                [
                    0 => 'http://www.example.com/test.txt',
                ],
            ],
            [
                'https://localhost/path/test.txt',
                [
                    0 => 'https://localhost/path/test.txt',
                    3 => '/var/www/path/test.txt',
                ],
            ],
            [
                '//www.example.com/space test.txt',
                [
                    0 => '//www.example.com/space test.txt',
                    2 => 'https://www.example.com/space%20test.txt',
                ],
            ],
            [
                '/path/test.txt',
                [
                    0 => '/path/test.txt',
                    1 => '/var/www/path/test.txt',
                    4 => 'https://localhost/path/test.txt',
                ],
            ],
            [
                'https://localhost/path/test.php?a=0&b=1&amp;c=2;&amp;d="a+b%20c"',
                [
                    0 => 'https://localhost/path/test.php?a=0&b=1&amp;c=2;&amp;d="a+b%20c"',
                    2 => 'https://localhost/path/test.php?a=0&b=1&c=2;&d="a+b%20c"',
                ],
            ],
            [
                'path/test.txt',
                [
                    0 => 'path/test.txt',
                    4 => 'https://localhost/path/test.txt',
                ],
            ],
        ];
    }

    public function testFileGetContentsException(): void
    {
        $this->bcExpectException('\\' . \Com\Tecnick\File\Exception::class);
        $file = $this->getTestObject();
        $file->fileGetContents('missing.txt');
    }

    public function testFileGetContents(): void
    {
        $file = $this->getTestObject();
        $res = $file->fileGetContents(__FILE__);
        $this->assertEquals('<?php', substr($res, 0, 5));
    }

    public function testFileGetContentsCurl(): void
    {
        $this->bcExpectException('\\' . \Com\Tecnick\File\Exception::class);
        $file = $this->getTestObject();
        define('FORCE_CURL', true);
        $file->fileGetContents('http://www.example.com/test.txt');
    }
}
