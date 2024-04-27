<?php

/**
 * AESTest.php
 *
 * @since     2011-05-23
 * @category  Library
 * @package   PdfEncrypt
 * @author    Nicola Asuni <info@tecnick.com>
 * @copyright 2011-2024 Nicola Asuni - Tecnick.com LTD
 * @license   http://www.gnu.org/copyleft/lesser.html GNU-LGPL v3 (see LICENSE.TXT)
 * @link      https://github.com/tecnickcom/tc-lib-pdf-encrypt
 *
 * This file is part of tc-lib-pdf-encrypt software library.
 */

namespace Test;

/**
 * AES encryption Test
 *
 * @since     2011-05-23
 * @category  Library
 * @package   PdfEncrypt
 * @author    Nicola Asuni <info@tecnick.com>
 * @copyright 2011-2024 Nicola Asuni - Tecnick.com LTD
 * @license   http://www.gnu.org/copyleft/lesser.html GNU-LGPL v3 (see LICENSE.TXT)
 * @link      https://github.com/tecnickcom/tc-lib-pdf-encrypt
 */
class AESTest extends TestUtil
{
    protected function getTestObject(): \Com\Tecnick\Pdf\Encrypt\Type\AES
    {
        return new \Com\Tecnick\Pdf\Encrypt\Type\AES();
    }

    public function testEncrypt128(): void
    {
        $aes = $this->getTestObject();
        $data = 'alpha';
        $key = '0123456789abcdef'; // 16 bytes = 128 bit KEY

        $enc_a = $aes->encrypt($data, $key);
        $enc_b = $aes->encrypt($data, $key, 'aes-128-cbc');
        $this->assertEquals(strlen($enc_a), strlen($enc_b));

        $aesSixteen = new \Com\Tecnick\Pdf\Encrypt\Type\AESSixteen();
        $enc_c = $aesSixteen->encrypt($data, $key);
        $this->assertEquals(strlen($enc_a), strlen($enc_c));
    }

    public function testEncrypt256(): void
    {
        $aes = $this->getTestObject();
        $data = 'alpha';
        $key = '0123456789abcdef0123456789abcdef'; // 32 bytes = 256 bit KEY

        $enc_a = $aes->encrypt($data, $key, '');
        $enc_b = $aes->encrypt($data, $key, 'aes-256-cbc');
        $this->assertEquals(strlen($enc_a), strlen($enc_b));

        $aesThirtytwo = new \Com\Tecnick\Pdf\Encrypt\Type\AESThirtytwo();
        $enc_c = $aesThirtytwo->encrypt($data, $key);
        $this->assertEquals(strlen($enc_a), strlen($enc_c));
    }

    public function testEncryptException(): void
    {
        $this->bcExpectException('\\' . \Com\Tecnick\Pdf\Encrypt\Exception::class);
        $aes = $this->getTestObject();
        $aes->encrypt('alpha', '12345', 'ERROR');
    }
}
