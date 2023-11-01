<?php

/**
 * SeedTest.php
 *
 * @since       2011-05-23
 * @category    Library
 * @package     PdfEncrypt
 * @author      Nicola Asuni <info@tecnick.com>
 * @copyright   2011-2023 Nicola Asuni - Tecnick.com LTD
 * @license     http://www.gnu.org/copyleft/lesser.html GNU-LGPL v3 (see LICENSE.TXT)
 * @link        https://github.com/tecnickcom/tc-lib-pdf-encrypt
 *
 * This file is part of tc-lib-pdf-encrypt software library.
 */

namespace Test;

use PHPUnit\Framework\TestCase;
use Test\TestUtil;

/**
 * Seed Test
 *
 * @since       2011-05-23
 * @category    Library
 * @package     PdfEncrypt
 * @author      Nicola Asuni <info@tecnick.com>
 * @copyright   2011-2023 Nicola Asuni - Tecnick.com LTD
 * @license     http://www.gnu.org/copyleft/lesser.html GNU-LGPL v3 (see LICENSE.TXT)
 * @link        https://github.com/tecnickcom/tc-lib-pdf-encrypt
 */
class SeedTest extends TestUtil
{
    protected function getTestObject()
    {
        return new \Com\Tecnick\Pdf\Encrypt\Type\Seed();
    }

    public function testEncrypt()
    {
        $testObj = $this->getTestObject();
        $result = $testObj->encrypt('hello', 'world');
        $this->assertNotEmpty($result);
    }

    public function testEncryptRaw()
    {
        $testObj = $this->getTestObject();
        $result = $testObj->encrypt('hello', 'world', 'raw');
        $this->assertNotEmpty($result);
    }
}
