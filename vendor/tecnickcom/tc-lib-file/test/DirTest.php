<?php

/**
 * DirTest.php
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
class DirTest extends TestUtil
{
    protected function getTestObject(): \Com\Tecnick\File\Dir
    {
        return new \Com\Tecnick\File\Dir();
    }

    /**
     * @dataProvider getAltFilePathsDataProvider
     */
    public function testGetAltFilePaths(string $name, string $expected): void
    {
        $testObj = $this->getTestObject();
        $dir = $testObj->findParentDir($name);
        $this->bcAssertMatchesRegularExpression('#' . $expected . '#', $dir);
    }

    /**
     * @return array<array{string, string}>
     */
    public static function getAltFilePathsDataProvider(): array
    {
        return [['', '/src/'], ['missing', '/'], ['src', '/src/']];
    }
}
