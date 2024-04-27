<?php

/**
 * EncryptTest.php
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
 * Encrypt Test
 *
 * @since     2011-05-23
 * @category  Library
 * @package   PdfEncrypt
 * @author    Nicola Asuni <info@tecnick.com>
 * @copyright 2011-2024 Nicola Asuni - Tecnick.com LTD
 * @license   http://www.gnu.org/copyleft/lesser.html GNU-LGPL v3 (see LICENSE.TXT)
 * @link      https://github.com/tecnickcom/tc-lib-pdf-encrypt
 */
class EncryptTest extends TestUtil
{
    public function testEncryptException(): void
    {
        $this->bcExpectException('\\' . \Com\Tecnick\Pdf\Encrypt\Exception::class);
        $encrypt = new \Com\Tecnick\Pdf\Encrypt\Encrypt(true, md5('file_id'));
        $encrypt->encrypt('WRONG');
    }

    public function testEncryptModeException(): void
    {
        $this->bcExpectException('\\' . \Com\Tecnick\Pdf\Encrypt\Exception::class);
        new \Com\Tecnick\Pdf\Encrypt\Encrypt(true, md5('file_id'), 4);
    }

    public function testEncryptThree(): void
    {
        $encrypt = new \Com\Tecnick\Pdf\Encrypt\Encrypt(
            true,
            md5('file_id'),
            3,
            ['print'],
            'alpha',
            'beta'
        );
        $result = $encrypt->encrypt(3, 'alpha');
        $this->assertEquals(32, strlen($result));
    }

    public function testEncryptPubThree(): void
    {
        $pubkeys = [[
            'c' => __DIR__ . '/data/cert.pem',
            'p' => ['print'],
        ]];
        $encrypt = new \Com\Tecnick\Pdf\Encrypt\Encrypt(
            true,
            md5('file_id'),
            3,
            ['print'],
            'alpha',
            'beta',
            $pubkeys
        );
        $result = $encrypt->encrypt(3, 'alpha');
        $this->assertEquals(32, strlen($result));
    }

    public function testEncryptPubNoP(): void
    {
        $pubkeys = [[
            'c' => __DIR__ . '/data/cert.pem',
            'p' => ['print'],
        ]];
        $encrypt = new \Com\Tecnick\Pdf\Encrypt\Encrypt(
            true,
            md5('file_id'),
            3,
            ['print'],
            'alpha',
            'beta',
            $pubkeys
        );
        $result = $encrypt->encrypt(3, 'alpha');
        $this->assertEquals(32, strlen($result));
    }

    public function testEncryptPubException(): void
    {
        $this->bcExpectException('\\' . \Com\Tecnick\Pdf\Encrypt\Exception::class);
        new \Com\Tecnick\Pdf\Encrypt\Encrypt(
            true,
            md5('file_id'),
            3,
            ['print'],
            'alpha',
            'beta',
            [[
                'c' => __FILE__,
                'p' => ['print'],
            ]]
        );
    }

    public function testEncryptModZeroPub(): void
    {
        error_reporting(E_ALL); // DEBUG
        $pubkeys = [[
            'c' => __DIR__ . '/data/cert.pem',
            'p' => ['print'],
        ]];
        $encrypt = new \Com\Tecnick\Pdf\Encrypt\Encrypt(
            true,
            md5('file_id'),
            0,
            ['print'],
            'alpha',
            'beta',
            $pubkeys
        );
        $result = $encrypt->encrypt(1, 'alpha');
        // Check for "error:0308010C:digital envelope routines::unsupported" when using OpenSSL 3.
        // var_dump(openssl_error_string());
        $this->assertEquals(5, strlen($result));
    }

    public function testGetEncryptionData(): void
    {
        $permissions = ['print'];
        $encrypt = new \Com\Tecnick\Pdf\Encrypt\Encrypt(true, md5('file_id'), 0, $permissions, 'alpha', 'beta');
        $result = $encrypt->getEncryptionData();
        $this->assertEquals('fc93f6b8ab2f4ffb06cf6676f570fc26', md5(serialize($result)));
        $this->assertEquals(2_147_422_008, $result['protection']);
        $this->assertEquals(1, $result['V']);
        $this->assertEquals(40, $result['Length']);
        $this->assertEquals('V2', $result['CF']['CFM']);
    }

    public function testGetObjectKey(): void
    {
        $permissions = ['print', 'modify', 'copy', 'annot-forms', 'fill-forms', 'extract', 'assemble', 'print-high'];

        $encrypt = new \Com\Tecnick\Pdf\Encrypt\Encrypt(true, md5('file_id'), 2, $permissions, 'alpha', 'beta');
        $result = $encrypt->getObjectKey(123);
        $this->assertEquals('93879594941619c98047c404192b977d', bin2hex($result));
    }

    public function testGetUserPermissionCode(): void
    {
        $permissions = [
            'owner',
            'print',
            'modify',
            'copy',
            'annot-forms',
            'fill-forms',
            'extract',
            'assemble',
            'print-high',
        ];

        $encrypt = new \Com\Tecnick\Pdf\Encrypt\Encrypt();
        $result = $encrypt->getUserPermissionCode($permissions, 0);
        $this->assertEquals(2_147_421_954, $result);
    }

    public function testConvertHexStringToString(): void
    {
        $encrypt = new \Com\Tecnick\Pdf\Encrypt\Encrypt();

        $result = $encrypt->convertHexStringToString('');
        $this->assertEquals('', $result);

        $result = $encrypt->convertHexStringToString('68656c6c6f20776f726c64');
        $this->assertEquals('hello world', $result);

        $result = $encrypt->convertHexStringToString('68656c6c6f20776f726c642');
        $this->assertEquals('hello world ', $result);
    }

    public function testConvertStringToHexString(): void
    {
        $encrypt = new \Com\Tecnick\Pdf\Encrypt\Encrypt();

        $result = $encrypt->convertStringToHexString('');
        $this->assertEquals('', $result);

        $result = $encrypt->convertStringToHexString('hello world');
        $this->assertEquals('68656c6c6f20776f726c64', $result);
    }

    public function testEncodeNameObject(): void
    {
        $encrypt = new \Com\Tecnick\Pdf\Encrypt\Encrypt();

        $result = $encrypt->encodeNameObject('');
        $this->assertEquals('', $result);

        $result = $encrypt->encodeNameObject('059akzAKZ#_=-');
        $this->assertEquals('059akzAKZ#_=-', $result);

        $result = $encrypt->encodeNameObject('059[]{}+~*akzAKZ#_=-');
        $this->assertEquals('059#5B#5D#7B#7D#2B#7E#2AakzAKZ#_=-', $result);
    }

    public function testEscapeString(): void
    {
        $encrypt = new \Com\Tecnick\Pdf\Encrypt\Encrypt();

        $result = $encrypt->escapeString('');
        $this->assertEquals('', $result);

        $result = $encrypt->escapeString('hello world');
        $this->assertEquals('hello world', $result);

        $result = $encrypt->escapeString('(hello world) slash \\' . chr(13));
        $this->assertEquals('\\(hello world\\) slash \\\\\r', $result);
    }

    public function testEncryptStringDisabled(): void
    {
        $encrypt = new \Com\Tecnick\Pdf\Encrypt\Encrypt();

        $result = $encrypt->encryptString('');
        $this->assertEquals('', $result);

        $result = $encrypt->encryptString('hello world');
        $this->assertEquals('hello world', $result);

        $result = $encrypt->encryptString('(hello world) slash \\' . chr(13) . chr(250));
        $this->assertEquals('(hello world) slash \\' . chr(13) . chr(250), $result);
    }

    public function testEncryptStringEnabled(): void
    {
        $permissions = ['print', 'modify', 'copy', 'annot-forms', 'fill-forms', 'extract', 'assemble', 'print-high'];

        $enc = new \Com\Tecnick\Pdf\Encrypt\Encrypt(true, md5('file_id'), 0, $permissions, 'alpha');
        $result = $enc->encryptString('(hello world) slash \\' . chr(13));
        $this->assertEquals('728cc693be1e4c1fb6b7e7b2a34644ad', md5($result));

        $enc = new \Com\Tecnick\Pdf\Encrypt\Encrypt(true, md5('file_id'), 1, $permissions, 'alpha', 'beta');
        $result = $enc->encryptString('(hello world) slash \\' . chr(13));
        $this->assertEquals('258ad774ddeec21b3b439a720df18e0d', md5($result));
    }

    public function testEscapeDataStringDisabled(): void
    {
        $encrypt = new \Com\Tecnick\Pdf\Encrypt\Encrypt();

        $result = $encrypt->escapeDataString('');
        $this->assertEquals('()', $result);

        $result = $encrypt->escapeDataString('hello world');
        $this->assertEquals('(hello world)', $result);

        $result = $encrypt->escapeDataString('(hello world) slash \\' . chr(13));
        $this->assertEquals('(\\(hello world\\) slash \\\\\r)', $result);
    }

    public function testEscapeDataStringEnabled(): void
    {
        $permissions = ['print', 'modify', 'copy', 'annot-forms', 'fill-forms', 'extract', 'assemble', 'print-high'];

        $enc = new \Com\Tecnick\Pdf\Encrypt\Encrypt(true, md5('file_id'), 0, $permissions, 'alpha');
        $result = $enc->escapeDataString('(hello world) slash \\' . chr(13));
        $this->assertEquals('24f60765c1c07a44fc3c9b44d2f55dbc', md5($result));

        $enc = new \Com\Tecnick\Pdf\Encrypt\Encrypt(true, md5('file_id'), 1, $permissions, 'alpha', 'beta');
        $result = $enc->escapeDataString('(hello world) slash \\' . chr(13));
        $this->assertEquals('ebc28272f4aff661fa0b7764d791fb79', md5($result));
    }

    public function testGetFormattedDate(): void
    {
        $permissions = ['print', 'modify', 'copy', 'annot-forms', 'fill-forms', 'extract', 'assemble', 'print-high'];

        $enc = new \Com\Tecnick\Pdf\Encrypt\Encrypt(false);
        $result = $enc->getFormattedDate();
        $this->assertEquals('(D:', substr($result, 0, 3));
        $this->assertEquals("+00'00')", substr($result, -8));

        $enc = new \Com\Tecnick\Pdf\Encrypt\Encrypt(true, md5('file_id'), 0, $permissions, 'alpha');
        $result = $enc->getFormattedDate();
        $this->assertNotEmpty($result);
    }
}
