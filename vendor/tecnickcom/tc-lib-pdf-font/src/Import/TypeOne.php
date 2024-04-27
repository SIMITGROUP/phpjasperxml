<?php

/**
 * TypeOne.php
 *
 * @since     2011-05-23
 * @category  Library
 * @package   PdfFont
 * @author    Nicola Asuni <info@tecnick.com>
 * @copyright 2011-2024 Nicola Asuni - Tecnick.com LTD
 * @license   http://www.gnu.org/copyleft/lesser.html GNU-LGPL v3 (see LICENSE.TXT)
 * @link      https://github.com/tecnickcom/tc-lib-pdf-font
 *
 * This file is part of tc-lib-pdf-font software library.
 */

namespace Com\Tecnick\Pdf\Font\Import;

use Com\Tecnick\File\File;
use Com\Tecnick\Pdf\Font\Exception as FontException;
use Com\Tecnick\Unicode\Data\Encoding;

/**
 * Com\Tecnick\Pdf\Font\Import\TypeOne
 *
 * @since     2011-05-23
 * @category  Library
 * @package   PdfFont
 * @author    Nicola Asuni <info@tecnick.com>
 * @copyright 2011-2024 Nicola Asuni - Tecnick.com LTD
 * @license   http://www.gnu.org/copyleft/lesser.html GNU-LGPL v3 (see LICENSE.TXT)
 * @link      https://github.com/tecnickcom/tc-lib-pdf-font
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class TypeOne extends \Com\Tecnick\Pdf\Font\Import\Core
{
    /**
     * Store font data
     */
    protected function storeFontData(): void
    {
        // read first segment
        $dat = unpack('Cmarker/Ctype/Vsize', substr($this->font, 0, 6));
        if (($dat === false) || ($dat['marker'] != 128)) {
            throw new FontException('Font file is not a valid binary Type1');
        }

        $this->fdt['size1'] = $dat['size'];
        $data = substr($this->font, 6, $this->fdt['size1']);
        // read second segment
        $dat = unpack('Cmarker/Ctype/Vsize', substr($this->font, (6 + $this->fdt['size1']), 6));
        if (($dat === false) || ($dat['marker'] != 128)) {
            throw new FontException('Font file is not a valid binary Type1');
        }

        $this->fdt['size2'] = $dat['size'];
        $this->fdt['encrypted'] = substr($this->font, (12 + $this->fdt['size1']), $this->fdt['size2']);
        $data .= $this->fdt['encrypted'];
        // store compressed font
        $this->fdt['file'] = $this->fdt['file_name'] . '.z';
        $file = new File();
        $fpt = $file->fopenLocal($this->fdt['dir'] . $this->fdt['file'], 'wb');

        $cmpr = gzcompress($data);
        if ($cmpr === false) {
            throw new FontException('Unable to compress font data');
        }

        fwrite($fpt, $cmpr);
        fclose($fpt);
    }

    /**
     * Extract Font information
     */
    protected function extractFontInfo(): void
    {
        if (preg_match('#/FontName[\s]*\/([^\s]*)#', $this->font, $matches) !== 1) {
            preg_match('#/FullName[\s]*\(([^\)]*)#', $this->font, $matches);
        }

        $name = preg_replace('/[^a-zA-Z0-9_\-]/', '', $matches[1]);
        if ($name === null) {
            throw new FontException('Unable to extract font name');
        }

        $this->fdt['name'] = $name;
        preg_match('#/FontBBox[\s]*{([^}]*)#', $this->font, $matches);
        $this->fdt['bbox'] = trim($matches[1]);
        $bvl = explode(' ', $this->fdt['bbox']);
        $this->fdt['Ascent'] = (int) $bvl[3];
        $this->fdt['Descent'] = (int) $bvl[1];
        preg_match('#/ItalicAngle[\s]*([0-9\+\-]*)#', $this->font, $matches);
        $this->fdt['italicAngle'] = (int) $matches[1];

        if ($this->fdt['italicAngle'] != 0) {
            $this->fdt['Flags'] |= 64;
        }

        preg_match('#/UnderlinePosition[\s]*([0-9\+\-]*)#', $this->font, $matches);
        $this->fdt['underlinePosition'] = (int) $matches[1];
        preg_match('#/UnderlineThickness[\s]*([0-9\+\-]*)#', $this->font, $matches);
        $this->fdt['underlineThickness'] = (int) $matches[1];
        preg_match('#/isFixedPitch[\s]*([^\s]*)#', $this->font, $matches);
        if ($matches[1] == 'true') {
            $this->fdt['Flags'] = (((int) $this->fdt['Flags']) | 1);
        }

        preg_match('#/Weight[\s]*\(([^\)]*)#', $this->font, $matches);
        if (! empty($matches[1])) {
            $this->fdt['weight'] = strtolower($matches[1]);
        }

        $this->fdt['weight'] = 'Book';
        $this->fdt['Leading'] = 0;
    }

    /**
     * Extract Font information
     *
     * @return array<string, int>
     */
    protected function getInternalMap(): array
    {
        $imap = [];
        if (preg_match_all('#dup[\s]([0-9]+)[\s]*/([^\s]*)[\s]put#sU', $this->font, $fmap, PREG_SET_ORDER) > 0) {
            foreach ($fmap as $val) {
                $imap[$val[2]] = (int) $val[1];
            }
        }

        return $imap;
    }

    /**
     * Decrypt eexec encrypted part
     */
    protected function getEplain(): string
    {
        $csr = 55665; // eexec encryption constant
        $cc1 = 52845;
        $cc2 = 22719;
        $elen = strlen($this->fdt['encrypted']);
        $eplain = '';
        for ($idx = 0; $idx < $elen; ++$idx) {
            $chr = ord($this->fdt['encrypted'][$idx]);
            $eplain .= chr($chr ^ ($csr >> 8));
            $csr = ((($chr + $csr) * $cc1 + $cc2) % 65536);
        }

        return $eplain;
    }

    /**
     * Extract eexec info
     *
     * @return array<int, array<int, string>>
     */
    protected function extractEplainInfo(): array
    {
        $eplain = $this->getEplain();
        if (preg_match('#/ForceBold[\s]*([^\s]*)#', $eplain, $matches) > 0 && $matches[1] == 'true') {
            $this->fdt['Flags'] |= 0x40000;
        }

        $this->extractStem($eplain);
        if (preg_match('#/BlueValues[\s]*\[([^\]]*)#', $eplain, $matches) > 0) {
            $bvl = explode(' ', $matches[1]);
            if (count($bvl) >= 6) {
                $vl1 = (int) $bvl[2];
                $vl2 = (int) $bvl[4];
                $this->fdt['XHeight'] = min($vl1, $vl2);
                $this->fdt['CapHeight'] = max($vl1, $vl2);
            }
        }

        $this->getRandomBytes($eplain);
        return $this->getCharstringData($eplain);
    }

    /**
     * Extract eexec info
     *
     * @param string $eplain Decoded eexec encrypted part
     */
    protected function extractStem(string $eplain): void
    {
        if (preg_match('#/StdVW[\s]*\[([^\]]*)#', $eplain, $matches) > 0) {
            $this->fdt['StemV'] = (int) $matches[1];
        } elseif (($this->fdt['weight'] == 'bold') || ($this->fdt['weight'] == 'black')) {
            $this->fdt['StemV'] = 123;
        } else {
            $this->fdt['StemV'] = 70;
        }

        $this->fdt['StemH'] = preg_match('#/StdHW[\s]*\[([^\]]*)#', $eplain, $matches) > 0 ? (int) $matches[1] : 30;

        if (preg_match('#/Cap[X]?Height[\s]*\[([^\]]*)#', $eplain, $matches) > 0) {
            $this->fdt['CapHeight'] = (int) $matches[1];
        } else {
            $this->fdt['CapHeight'] = (int) $this->fdt['Ascent'];
        }

        $this->fdt['XHeight'] = ((int) $this->fdt['Ascent'] + (int) $this->fdt['Descent']);
    }

    /**
     * Get the number of random bytes at the beginning of charstrings
     */
    protected function getRandomBytes(string $eplain): void
    {
        $this->fdt['lenIV'] = 4;
        if (preg_match('#/lenIV[\s]*(\d*)#', $eplain, $matches) > 0) {
            $this->fdt['lenIV'] = (int) $matches[1];
        }
    }

    /**
     * @return array<int, array<int, string>>
     */
    protected function getCharstringData(string $eplain): array
    {
        $this->fdt['enc_map'] = [];
        $eplain = substr($eplain, (strpos($eplain, '/CharStrings') + 1));
        preg_match_all('#/([A-Za-z0-9\.]*)[\s][0-9]+[\s]RD[\s](.*)[\s]ND#sU', $eplain, $matches, PREG_SET_ORDER);
        if ($this->fdt['enc'] === '') {
            return $matches;
        }

        if (! isset(Encoding::MAP[$this->fdt['enc']])) {
            return $matches;
        }

        $this->fdt['enc_map'] = Encoding::MAP[$this->fdt['enc']];
        return $matches;
    }

    /**
     * get CID
     *
     * @param array<string, int> $imap
     * @param array<int, string> $val
     */
    protected function getCid(array $imap, array $val): int
    {
        if (isset($imap[$val[1]])) {
            return $imap[$val[1]];
        }

        if ($this->fdt['enc_map'] === false) {
            return 0;
        }

        $cid = array_search($val[1], $this->fdt['enc_map'], true);
        if ($cid === false) {
            return 0;
        }

        if ($cid > 1000) {
            return 1000;
        }

        return (int) $cid;
    }

    /**
     * Decode number
     *
     * @param array<int, int> $ccom
     * @param array<int, int> $cdec
     * @param array<int, int> $cwidths
     */
    protected function decodeNumber(
        int $idx,
        int &$cck,
        int &$cid,
        array &$ccom,
        array &$cdec,
        array &$cwidths
    ): int {
        if ($ccom[$idx] == 255) {
            $sval = chr($ccom[($idx + 1)]) . chr($ccom[($idx + 2)]) . chr($ccom[($idx + 3)]) . chr($ccom[($idx + 4)]);
            $vsval = unpack('li', $sval);
            if ($vsval === false) {
                throw new FontException('Unable to unpack number');
            }

            $cdec[$cck] = $vsval['i'];
            return ($idx + 5);
        }

        if ($ccom[$idx] >= 251) {
            $cdec[$cck] = ((-($ccom[$idx] - 251) * 256) - $ccom[($idx + 1)] - 108);
            return ($idx + 2);
        }

        if ($ccom[$idx] >= 247) {
            $cdec[$cck] = ((($ccom[$idx] - 247) * 256) + $ccom[($idx + 1)] + 108);
            return ($idx + 2);
        }

        if ($ccom[$idx] >= 32) {
            $cdec[$cck] = ($ccom[$idx] - 139);
            return ++$idx;
        }

        $cdec[$cck] = $ccom[$idx];
        if ($cck <= 0) {
            return ++$idx;
        }

        if ($cdec[$cck] != 13) {
            return ++$idx;
        }

        // hsbw command: update width
        $cwidths[$cid] = $cdec[($cck - 1)];
        return ++$idx;
    }

    /**
     * Process Type1 font
     */
    protected function process(): void
    {
        $this->storeFontData();
        $this->extractFontInfo();
        $imap = $this->getInternalMap();
        $matches = $this->extractEplainInfo();
        $cwidths = [];
        $cc1 = 52845;
        $cc2 = 22719;
        foreach ($matches as $match) {
            $cid = $this->getCid($imap, $match);
            // decrypt charstring encrypted part
            $csr = 4330; // charstring encryption constant
            $ccd = $match[2];
            $clen = strlen($ccd);
            $ccom = [];
            for ($idx = 0; $idx < $clen; ++$idx) {
                $chr = ord($ccd[$idx]);
                $ccom[] = ($chr ^ ($csr >> 8));
                $csr = ((($chr + $csr) * $cc1 + $cc2) % 65536);
            }

            // decode numbers
            $cdec = [];
            $cck = 0;
            $idx = $this->fdt['lenIV'];
            while ($idx < $clen) {
                $idx = $this->decodeNumber($idx, $cck, $cid, $ccom, $cdec, $cwidths);
                ++$cck;
            }
        }

        $this->setCharWidths($cwidths);
    }
}
