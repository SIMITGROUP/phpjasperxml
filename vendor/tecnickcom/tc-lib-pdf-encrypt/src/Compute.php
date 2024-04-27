<?php

/**
 * Compute.php
 *
 * @since     2008-01-02
 * @category  Library
 * @package   PdfEncrypt
 * @author    Nicola Asuni <info@tecnick.com>
 * @copyright 2011-2024 Nicola Asuni - Tecnick.com LTD
 * @license   http://www.gnu.org/copyleft/lesser.html GNU-LGPL v3 (see LICENSE.TXT)
 * @link      https://github.com/tecnickcom/tc-lib-pdf-encrypt
 *
 * This file is part of tc-lib-pdf-encrypt software library.
 */

namespace Com\Tecnick\Pdf\Encrypt;

use Com\Tecnick\Pdf\Encrypt\Exception as EncException;

/**
 * Com\Tecnick\Pdf\Encrypt\Compute
 *
 * PHP class to generate encryption data
 *
 * @since     2008-01-02
 * @category  Library
 * @package   PdfEncrypt
 * @author    Nicola Asuni <info@tecnick.com>
 * @copyright 2011-2024 Nicola Asuni - Tecnick.com LTD
 * @license   http://www.gnu.org/copyleft/lesser.html GNU-LGPL v3 (see LICENSE.TXT)
 * @link      https://github.com/tecnickcom/tc-lib-pdf-encrypt
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
abstract class Compute extends \Com\Tecnick\Pdf\Encrypt\Data
{
    /**
     * Encrypt data using the specified encrypt type.
     *
     * @param int|string|false $type   Encrypt type.
     * @param string     $data   Data string to encrypt.
     * @param string     $key    Encryption key.
     * @param int        $objnum Object number.
     */
    public function encrypt(
        int|string|false $type,
        string $data = '',
        string $key = '',
        int $objnum = 0,
    ): string {
        if (empty($this->encryptdata['encrypted']) || ($type === false)) {
            return $data;
        }

        if (! isset(self::ENCMAP[$type])) {
            throw new EncException('unknown encryption type: ' . $type);
        }

        if (($key == '') && ($type == $this->encryptdata['mode'])) {
            if ($this->encryptdata['mode'] < 3) {
                $key = $this->getObjectKey($objnum);
            } elseif ($this->encryptdata['mode'] == 3) {
                $key = $this->encryptdata['key'];
            }
        }

        $class = '\\Com\\Tecnick\\Pdf\\Encrypt\\Type\\' . self::ENCMAP[$type];
        $obj = new $class();
        return $obj->encrypt($data, $key);
    }

    /**
     * Compute encryption key depending on object number where the encrypted data is stored.
     * This is used for all strings and streams without crypt filter specifier.
     *
     * @param int $objnum Object number.
     */
    public function getObjectKey(int $objnum): string
    {
        $objkey = $this->encryptdata['key'] . pack('VXxx', $objnum);
        if ($this->encryptdata['mode'] == 2) {
            // AES-128 padding
            $objkey .= "\x73\x41\x6C\x54"; // sAlT
        }

        $objkey = substr($this->encrypt('MD5-16', $objkey, 'H*'), 0, (($this->encryptdata['Length'] / 8) + 5));
        return substr($objkey, 0, 16);
    }

    /**
     * Convert encryption P value to a string of bytes, low-order byte first.
     *
     * @param int $protection 32bit encryption permission value (P value).
     */
    public function getEncPermissionsString(int $protection): string
    {
        $binprot = sprintf('%032b', $protection);
        return chr((int) bindec(substr($binprot, 24, 8)))
            . chr((int) bindec(substr($binprot, 16, 8)))
            . chr((int) bindec(substr($binprot, 8, 8)))
            . chr((int) bindec(substr($binprot, 0, 8)));
    }

    /**
     * Return the permission code used on encryption (P value).
     *
     * @param array<string> $permissions The set of permissions (specify the ones you want to block).
     * @param int   $mode        Encryption strength: 0 = RC4 40 bit; 1 = RC4 128 bit; 2 = AES 128 bit; 3 = AES 256 bit.
     */
    public function getUserPermissionCode(
        array $permissions,
        int $mode = 0,
    ): int {
        $protection = 2_147_422_012; // 32 bit: (01111111 11111111 00001111 00111100)
        foreach ($permissions as $permission) {
            if (! isset(self::PERMBITS[$permission])) {
                continue;
            }

            if ($mode <= 0 && self::PERMBITS[$permission] > 32) {
                continue;
            }

            // set only valid permissions
            if (self::PERMBITS[$permission] == 2) {
                // the logic for bit 2 is inverted (cleared by default)
                $protection += self::PERMBITS[$permission];
            } else {
                $protection -= self::PERMBITS[$permission];
            }
        }

        return $protection;
    }

    /**
     * Compute UE value
     */
    protected function getUEValue(): string
    {
        $hashkey = hash('sha256', $this->encryptdata['user_password'] . $this->encryptdata['UKS'], true);
        return $this->encrypt('AESnopad', $this->encryptdata['key'], $hashkey);
    }

    /**
     * Compute OE value
     */
    protected function getOEValue(): string
    {
        $hashkey = hash(
            'sha256',
            $this->encryptdata['owner_password'] . $this->encryptdata['OKS'] . $this->encryptdata['U'],
            true
        );
        return $this->encrypt('AESnopad', $this->encryptdata['key'], $hashkey);
    }

    /**
     * Compute U value
     */
    protected function getUvalue(): string
    {
        if ($this->encryptdata['mode'] == 0) { // RC4-40
            return $this->encrypt('RC4', self::ENCPAD, $this->encryptdata['key']);
        }

        if ($this->encryptdata['mode'] < 3) { // RC4-128, AES-128
            $tmp = $this->encrypt('MD5-16', self::ENCPAD . $this->encryptdata['fileid'], 'H*');
            $enc = $this->encrypt('RC4', $tmp, $this->encryptdata['key']);
            $len = strlen($tmp);
            for ($idx = 1; $idx <= 19; ++$idx) {
                $ekey = '';
                for ($jdx = 0; $jdx < $len; ++$jdx) {
                    $ekey .= chr(ord($this->encryptdata['key'][$jdx]) ^ $idx);
                }

                $enc = $this->encrypt('RC4', $enc, $ekey);
            }

            $enc .= str_repeat("\x00", 16);
            return substr($enc, 0, 32);
        }

        // AES-256 ($this->encryptdata['mode'] = 3)
        $seed = $this->encrypt('MD5-16', $this->encrypt('seed'), 'H*');
        // User Validation Salt
        $this->encryptdata['UVS'] = substr($seed, 0, 8);
        // User Key Salt
        $this->encryptdata['UKS'] = substr($seed, 8, 16);
        return hash('sha256', $this->encryptdata['user_password'] . $this->encryptdata['UVS'], true)
            . $this->encryptdata['UVS'] . $this->encryptdata['UKS'];
    }

    /**
     * Compute O value
     */
    protected function getOValue(): string
    {
        if ($this->encryptdata['mode'] < 3) { // RC4-40, RC4-128, AES-128
            $tmp = $this->encrypt('MD5-16', $this->encryptdata['owner_password'], 'H*');
            if ($this->encryptdata['mode'] > 0) {
                for ($idx = 0; $idx < 50; ++$idx) {
                    $tmp = $this->encrypt('MD5-16', $tmp, 'H*');
                }
            }

            $owner_key = substr($tmp, 0, ($this->encryptdata['Length'] / 8));
            $enc = $this->encrypt('RC4', $this->encryptdata['user_password'], $owner_key);
            if ($this->encryptdata['mode'] > 0) {
                $len = strlen($owner_key);
                for ($idx = 1; $idx <= 19; ++$idx) {
                    $ekey = '';
                    for ($jdx = 0; $jdx < $len; ++$jdx) {
                        $ekey .= chr(ord($owner_key[$jdx]) ^ $idx);
                    }

                    $enc = $this->encrypt('RC4', $enc, $ekey);
                }
            }

            return $enc;
        }

        // AES-256 ($this->encryptdata['mode'] = 3)
        $seed = $this->encrypt('MD5-16', $this->encrypt('seed'), 'H*');
        // Owner Validation Salt
        $this->encryptdata['OVS'] = substr($seed, 0, 8);
        // Owner Key Salt
        $this->encryptdata['OKS'] = substr($seed, 8, 16);
        return hash(
            'sha256',
            $this->encryptdata['owner_password'] . $this->encryptdata['OVS'] . $this->encryptdata['U'],
            true
        ) . $this->encryptdata['OVS'] . $this->encryptdata['OKS'];
    }

    /**
     * Compute encryption key
     */
    protected function generateEncryptionKey(): void
    {
        if ($this->encryptdata['pubkey']) {
            $this->generatePublicEncryptionKey();
        } else { // standard mode
            $this->generateStandardEncryptionKey();
        }
    }

    /**
     * Compute standard encryption key
     */
    protected function generateStandardEncryptionKey(): void
    {
        $keybytelen = ($this->encryptdata['Length'] / 8);
        if ($this->encryptdata['mode'] == 3) { // AES-256
            // generate 256 bit random key
            $this->encryptdata['key'] = substr(hash('sha256', $this->encrypt('seed'), true), 0, $keybytelen);
            // truncate passwords
            $this->encryptdata['user_password'] = substr($this->encryptdata['user_password'], 0, 127);
            $this->encryptdata['owner_password'] = substr($this->encryptdata['owner_password'], 0, 127);
            $this->encryptdata['U'] = $this->getUValue();
            $this->encryptdata['UE'] = $this->getUEValue();
            $this->encryptdata['O'] = $this->getOValue();
            $this->encryptdata['OE'] = $this->getOEValue();
            $this->encryptdata['P'] = $this->encryptdata['protection'];
            // Computing the encryption dictionary's Perms (permissions) value
            $perms = $this->getEncPermissionsString($this->encryptdata['protection']); // bytes 0-3
            $perms .= chr(255) . chr(255) . chr(255) . chr(255); // bytes 4-7
            $perms .= 'T'; // $this->encryptdata['CF']['EncryptMetadata'] is never set, so we always encrypt
            $perms .= 'adb'; // bytes 9-11
            $perms .= 'nick'; // bytes 12-15
            $this->encryptdata['perms'] = $this->encrypt('AESnopad', $perms, $this->encryptdata['key']);
        } else { // RC4-40, RC4-128, AES-128
            // Pad passwords
            $this->encryptdata['user_password'] = substr($this->encryptdata['user_password'] . self::ENCPAD, 0, 32);
            $this->encryptdata['owner_password'] = substr($this->encryptdata['owner_password'] . self::ENCPAD, 0, 32);
            $this->encryptdata['O'] = $this->getOValue();
            // get default permissions (reverse byte order)
            $permissions = $this->getEncPermissionsString($this->encryptdata['protection']);
            // Compute encryption key
            $tmp = $this->encrypt(
                'MD5-16',
                $this->encryptdata['user_password']
                . $this->encryptdata['O']
                . $permissions
                . $this->encryptdata['fileid'],
                'H*'
            );
            if ($this->encryptdata['mode'] > 0) {
                for ($idx = 0; $idx < 50; ++$idx) {
                    $tmp = $this->encrypt('MD5-16', substr($tmp, 0, $keybytelen), 'H*');
                }
            }

            $this->encryptdata['key'] = substr($tmp, 0, $keybytelen);
            $this->encryptdata['U'] = $this->getUValue();
            $this->encryptdata['P'] = $this->encryptdata['protection'];
        }
    }

    /**
     * Compute public encryption key
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function generatePublicEncryptionKey(): void
    {
        $keybytelen = ($this->encryptdata['Length'] / 8);
        // random 20-byte seed
        $seed = sha1($this->encrypt('seed'), true);
        $recipient_bytes = '';
        $pubkeys = $this->encryptdata['pubkeys'] ?? throw new EncException('Missing pubkeys');
        foreach ($pubkeys as $pubkey) {
            // for each public certificate
            if (isset($pubkey['p'])) {
                $pkprotection = $this->getUserPermissionCode($pubkey['p'], $this->encryptdata['mode']);
            } else {
                $pkprotection = $this->encryptdata['protection'];
            }

            // get default permissions (reverse byte order)
            $pkpermissions = $this->getEncPermissionsString($pkprotection);
            // envelope data
            $envelope = $seed . $pkpermissions;

            // write the envelope data to a temporary file
            $tempkeyfile = tempnam(
                sys_get_temp_dir(),
                '__tcpdf_key_' . md5($this->encryptdata['fileid'] . $envelope) . '_'
            );
            if ($tempkeyfile === false) {
                throw new EncException('Unable to generate temporary key file name');
            }

            if (file_put_contents($tempkeyfile, $envelope) === false) {
                throw new EncException('Unable to create temporary key file: ' . $tempkeyfile);
            }

            $tempencfile = tempnam(
                sys_get_temp_dir(),
                '__tcpdf_enc_' . md5($this->encryptdata['fileid'] . $envelope) . '_'
            );
            if ($tempencfile === false) {
                throw new EncException('Unable to generate temporary key file name');
            }

            if (! function_exists('openssl_pkcs7_encrypt')) {
                throw new EncException(
                    'Unable to encrypt the file: ' . $tempkeyfile . "\n"
                    . 'Public-Key Security requires openssl_pkcs7_encrypt.'
                );
            }

            $pkey = file_get_contents($pubkey['c']);
            if ($pkey === false) {
                throw new EncException('Unable to read public key file: ' . $pubkey['c']);
            }

            if (
                ! openssl_pkcs7_encrypt(
                    $tempkeyfile,
                    $tempencfile,
                    $pkey,
                    [],
                    PKCS7_BINARY
                )
            ) {
                throw new EncException(
                    'Unable to encrypt the file: ' . $tempkeyfile . "\n"
                    . 'OpenSSL error: ' . openssl_error_string()
                );
            }

            // read encryption signature
            $signature = file_get_contents($tempencfile);
            if ($signature === false) {
                throw new EncException('Unable to read signature file: ' . $tempencfile);
            }

            $sigcontentpos = strpos($signature, 'Content-Disposition');
            if ($sigcontentpos === false) {
                throw new EncException('Unable to find signature content');
            }

            // extract signature
            $signature = substr($signature, $sigcontentpos);

            $tmparr = explode("\n\n", $signature);
            $signature = trim($tmparr[1]);
            unset($tmparr);
            // decode signature
            $signature = base64_decode($signature);
            if ($signature === false) {
                throw new EncException('Unable to decode signature: ' . $tempencfile);
            }

            $sigarr = unpack('H*', $signature);
            if ($sigarr === false) {
                throw new EncException('Unable to unpack signature: ' . $tempencfile);
            }

            // convert signature to hex
            $hexsignature = current($sigarr);

            // store signature on recipients array
            $this->encryptdata['Recipients'][] = $hexsignature;

            // The bytes of each item in the Recipients array of PKCS#7 objects
            // in the order in which they appear in the array
            $recipient_bytes .= $signature;
        }

        // calculate encryption key
        if ($this->encryptdata['mode'] == 3) { // AES-256
            $this->encryptdata['key'] = substr(hash('sha256', $seed . $recipient_bytes, true), 0, $keybytelen);
        } else { // RC4-40, RC4-128, AES-128
            $this->encryptdata['key'] = substr(sha1($seed . $recipient_bytes, true), 0, $keybytelen);
        }
    }
}
