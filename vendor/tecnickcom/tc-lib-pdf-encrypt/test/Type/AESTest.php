<?php

/**
 * AESTest.php
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
 * AES encryption Test
 *
 * @since       2011-05-23
 * @category    Library
 * @package     PdfEncrypt
 * @author      Nicola Asuni <info@tecnick.com>
 * @copyright   2011-2023 Nicola Asuni - Tecnick.com LTD
 * @license     http://www.gnu.org/copyleft/lesser.html GNU-LGPL v3 (see LICENSE.TXT)
 * @link        https://github.com/tecnickcom/tc-lib-pdf-encrypt
 */
class AESTest extends TestUtil
{
    protected function getTestObject()
    {
        return new \Com\Tecnick\Pdf\Encrypt\Type\AES();
    }

    public function testEncrypt128()
    {
        $testObj = $this->getTestObject();
        $data = 'alpha';
        $key = '0123456789abcdef'; // 16 bytes = 128 bit KEY

        $enc_a = $testObj->encrypt($data, $key);
        $enc_b = $testObj->encrypt($data, $key, 'aes-128-cbc');
        $this->assertEquals(strlen($enc_a), strlen($enc_b));

        $eobj = new \Com\Tecnick\Pdf\Encrypt\Type\AESSixteen();
        $enc_c = $eobj->encrypt($data, $key);
        $this->assertEquals(strlen($enc_a), strlen($enc_c));
    }

    public function testEncrypt256()
    {
        $testObj = $this->getTestObject();
        $data = 'alpha';
        $key = '0123456789abcdef0123456789abcdef'; // 32 bytes = 256 bit KEY

        $enc_a = $testObj->encrypt($data, $key, '');
        $enc_b = $testObj->encrypt($data, $key, 'aes-256-cbc');
        $this->assertEquals(strlen($enc_a), strlen($enc_b));

        $eobj = new \Com\Tecnick\Pdf\Encrypt\Type\AESThirtytwo();
        $enc_c = $eobj->encrypt($data, $key);
        $this->assertEquals(strlen($enc_a), strlen($enc_c));
    }

    public function testEncryptException()
    {
        $this->bcExpectException('\Com\Tecnick\Pdf\Encrypt\Exception');
        $testObj = $this->getTestObject();
        $testObj->encrypt('alpha', '12345', 'ERROR');
    }
}
