<?php

/**
 * TrueType.php
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

use Com\Tecnick\File\Byte;
use Com\Tecnick\File\File;
use Com\Tecnick\Pdf\Font\Exception as FontException;
use Com\Tecnick\Unicode\Data\Encoding;

/**
 * Com\Tecnick\Pdf\Font\Import\TrueType
 *
 * @since     2011-05-23
 * @category  Library
 * @package   PdfFont
 * @author    Nicola Asuni <info@tecnick.com>
 * @copyright 2011-2024 Nicola Asuni - Tecnick.com LTD
 * @license   http://www.gnu.org/copyleft/lesser.html GNU-LGPL v3 (see LICENSE.TXT)
 * @link      https://github.com/tecnickcom/tc-lib-pdf-font
 *
 * @phpstan-import-type TFontData from \Com\Tecnick\Pdf\Font\Load
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
class TrueType
{
    /**
     * Array containing subset chars
     *
     * @var array<int, bool>
     */
    protected array $subchars = [];

    /**
     * Array containing subset glyphs indexes of chars from cmap table
     *
     * @var array<int, bool>
     */
    protected array $subglyphs = [
        0 => true,
    ];

    /**
     * Pointer position on the original font data
     */
    protected int $offset = 0;

    /**
     * Process TrueType font
     *
     * @param string           $font     Content of the input font file
     * @param TFontData         $fdt      Extracted font metrics
     * @param Byte             $fbyte    Object used to read font bytes
     * @param array<int, bool> $subchars Array containing subset chars
     *
     * @throws FontException in case of error
     */
    public function __construct(
        protected string $font,
        protected array $fdt,
        protected Byte $fbyte,
        array $subchars = []
    ) {
        ksort($subchars);
        $this->subchars = $subchars;
        $this->process();
    }

    /**
     * Get all the extracted font metrics
     *
     * @return TFontData
     */
    public function getFontMetrics(): array
    {
        return $this->fdt;
    }

    /**
     * Get glyphs in the subset
     *
     * @return array<int, bool>
     */
    public function getSubGlyphs(): array
    {
        return $this->subglyphs;
    }

    /**
     * Process TrueType font
     */
    protected function process(): void
    {
        $this->isValidType();
        $this->setFontFile();
        $this->getTables();
        $this->checkMagickNumber();
        $this->offset += 2; // skip flags
        $this->getBbox();
        $this->getIndexToLoc();
        $this->getEncodingTables();
        $this->getOS2Metrics();
        $this->getFontName();
        $this->getPostData();
        $this->getHheaData();
        $this->getMaxpData();
        $this->getCIDToGIDMap();
        $this->getHeights();
        $this->getWidths();
    }

    /**
     * Check if the font is a valid type
     *
     * @throws FontException if the font is invalid
     */
    protected function isValidType(): void
    {
        if ($this->fbyte->getULong($this->offset) != 0x10000) {
            throw new FontException('sfnt version must be 0x00010000 for TrueType version 1.0.');
        }

        $this->offset += 4;
    }

    /**
     * Copy or link the original font file
     */
    protected function setFontFile(): void
    {
        if (! empty($this->fdt['desc']['MaxWidth'])) {
            // subsetting mode
            $this->fdt['Flags'] = $this->fdt['desc']['Flags'];
            return;
        }

        if ($this->fdt['type'] == 'cidfont0') {
            return;
        }

        if ($this->fdt['linked']) {
            // creates a symbolic link to the existing font
            symlink($this->fdt['input_file'], $this->fdt['dir'] . $this->fdt['file_name']);
            return;
        }

        // store compressed font
        $this->fdt['file'] = $this->fdt['file_name'] . '.z';
        $file = new File();
        $fpt = $file->fopenLocal($this->fdt['dir'] . $this->fdt['file'], 'wb');

        $cmpr = gzcompress($this->font);
        if ($cmpr === false) {
            throw new FontException('Error compressing font file.');
        }

        fwrite($fpt, $cmpr);
        fclose($fpt);
    }

    /**
     * Get the font tables
     */
    protected function getTables(): void
    {
        // get number of tables
        $numTables = $this->fbyte->getUShort($this->offset);
        $this->offset += 2;
        // skip searchRange, entrySelector and rangeShift
        $this->offset += 6;
        // tables array
        $this->fdt['table'] = [];
        // ---------- get tables ----------
        for ($idx = 0; $idx < $numTables; ++$idx) {
            // get table info
            $tag = substr($this->font, $this->offset, 4);
            $this->offset += 4;
            $this->fdt['table'][$tag] = [
                'checkSum' => 0,
                'data' => '',
                'length' => 0,
                'offset' => 0,
            ];
            $this->fdt['table'][$tag]['checkSum'] = $this->fbyte->getULong($this->offset);
            $this->offset += 4;
            $this->fdt['table'][$tag]['offset'] = $this->fbyte->getULong($this->offset);
            $this->offset += 4;
            $this->fdt['table'][$tag]['length'] = $this->fbyte->getULong($this->offset);
            $this->offset += 4;
        }
    }

    /**
     * Check if the font is a valid type
     *
     * @throws FontException if the font is invalid
     */
    protected function checkMagickNumber(): void
    {
        $this->offset = ($this->fdt['table']['head']['offset'] + 12);
        if ($this->fbyte->getULong($this->offset) != 0x5F0F3CF5) {
            // magicNumber must be 0x5F0F3CF5
            throw new FontException('magicNumber must be 0x5F0F3CF5');
        }

        $this->offset += 4;
    }

    /**
     * Get BBox, units and flags
     */
    protected function getBbox(): void
    {
        $this->fdt['unitsPerEm'] = $this->fbyte->getUShort($this->offset);
        $this->offset += 2;
        // units ratio constant
        $this->fdt['urk'] = (1000 / $this->fdt['unitsPerEm']);
        $this->offset += 16; // skip created, modified
        $xMin = (int) round($this->fbyte->getFWord($this->offset) * $this->fdt['urk']);
        $this->offset += 2;
        $yMin = (int) round($this->fbyte->getFWord($this->offset) * $this->fdt['urk']);
        $this->offset += 2;
        $xMax = (int) round($this->fbyte->getFWord($this->offset) * $this->fdt['urk']);
        $this->offset += 2;
        $yMax = (int) round($this->fbyte->getFWord($this->offset) * $this->fdt['urk']);
        $this->offset += 2;
        $this->fdt['bbox'] = $xMin . ' ' . $yMin . ' ' . $xMax . ' ' . $yMax;
        $macStyle = $this->fbyte->getUShort($this->offset);
        $this->offset += 2;
        // PDF font flags
        if (($macStyle & 2) == 2) {
            // italic flag
            $this->fdt['Flags'] |= 64;
        }
    }

    /**
     * Get index to loc map
     */
    protected function getIndexToLoc(): void
    {
        // get offset mode (indexToLocFormat : 0 = short, 1 = long)
        $this->offset = ($this->fdt['table']['head']['offset'] + 50);
        $this->fdt['short_offset'] = ($this->fbyte->getShort($this->offset) == 0);
        $this->offset += 2;
        // get the offsets to the locations of the glyphs in the font, relative to the beginning of the glyphData table
        $this->fdt['indexToLoc'] = [];
        $this->offset = $this->fdt['table']['loca']['offset'];
        if ($this->fdt['short_offset']) {
            // short version
            $this->fdt['tot_num_glyphs'] = (int) floor($this->fdt['table']['loca']['length'] / 2); // numGlyphs + 1
            for ($idx = 0; $idx < $this->fdt['tot_num_glyphs']; ++$idx) {
                $this->fdt['indexToLoc'][$idx] = $this->fbyte->getUShort($this->offset) * 2;
                if (
                    isset($this->fdt['indexToLoc'][($idx - 1)])
                    && ($this->fdt['indexToLoc'][$idx] === $this->fdt['indexToLoc'][($idx - 1)])
                ) {
                    // the last glyph didn't have an outline
                    unset($this->fdt['indexToLoc'][($idx - 1)]);
                }

                $this->offset += 2;
            }
        } else {
            // long version
            $this->fdt['tot_num_glyphs'] = (int) floor($this->fdt['table']['loca']['length'] / 4); // numGlyphs + 1
            for ($idx = 0; $idx < $this->fdt['tot_num_glyphs']; ++$idx) {
                $this->fdt['indexToLoc'][$idx] = $this->fbyte->getULong($this->offset);
                if (
                    isset($this->fdt['indexToLoc'][($idx - 1)])
                    && ($this->fdt['indexToLoc'][$idx] === $this->fdt['indexToLoc'][($idx - 1)])
                ) {
                    // the last glyph didn't have an outline
                    unset($this->fdt['indexToLoc'][($idx - 1)]);
                }

                $this->offset += 4;
            }
        }
    }

    protected function getEncodingTables(): void
    {
        // get glyphs indexes of chars from cmap table
        $this->offset = $this->fdt['table']['cmap']['offset'] + 2;
        $numEncodingTables = $this->fbyte->getUShort($this->offset);
        $this->offset += 2;
        $this->fdt['encodingTables'] = [];
        for ($idx = 0; $idx < $numEncodingTables; ++$idx) {
            $this->fdt['encodingTables'][$idx]['platformID'] = $this->fbyte->getUShort($this->offset);
            $this->offset += 2;
            $this->fdt['encodingTables'][$idx]['encodingID'] = $this->fbyte->getUShort($this->offset);
            $this->offset += 2;
            $this->fdt['encodingTables'][$idx]['offset'] = $this->fbyte->getULong($this->offset);
            $this->offset += 4;
        }
    }

    /**
     * Get encoding tables
     */
    protected function getOS2Metrics(): void
    {
        $this->offset = $this->fdt['table']['OS/2']['offset'];
        $this->offset += 2; // skip version
        // xAvgCharWidth
        $this->fdt['AvgWidth'] = (int) round($this->fbyte->getFWord($this->offset) * $this->fdt['urk']);
        $this->offset += 2;
        // usWeightClass
        $usWeightClass = round($this->fbyte->getUFWord($this->offset) * $this->fdt['urk']);
        // estimate StemV and StemH (400 = usWeightClass for Normal - Regular font)
        $this->fdt['StemV'] = (int) round((70 * $usWeightClass) / 400);
        $this->fdt['StemH'] = (int) round((30 * $usWeightClass) / 400);
        $this->offset += 2;
        $this->offset += 2; // usWidthClass
        $fsType = $this->fbyte->getShort($this->offset);
        $this->offset += 2;
        if ($fsType == 2) {
            throw new FontException(
                'This Font cannot be modified, embedded or exchanged in any manner'
                . ' without first obtaining permission of the legal owner.'
            );
        }
    }

    protected function getFontName(): void
    {
        $this->fdt['name'] = '';
        $this->offset = $this->fdt['table']['name']['offset'];
        $this->offset += 2; // skip Format selector (=0).
        // Number of NameRecords that follow n.
        $numNameRecords = $this->fbyte->getUShort($this->offset);
        $this->offset += 2;
        // Offset to start of string storage (from start of table).
        $stringStorageOffset = $this->fbyte->getUShort($this->offset);
        $this->offset += 2;
        for ($idx = 0; $idx < $numNameRecords; ++$idx) {
            $this->offset += 6; // skip Platform ID, Platform-specific encoding ID, Language ID.
            // Name ID.
            $nameID = $this->fbyte->getUShort($this->offset);
            $this->offset += 2;
            if ($nameID == 6) {
                // String length (in bytes).
                $stringLength = $this->fbyte->getUShort($this->offset);
                $this->offset += 2;
                // String offset from start of storage area (in bytes).
                $stringOffset = $this->fbyte->getUShort($this->offset);
                $this->offset += 2;
                $this->offset = ($this->fdt['table']['name']['offset'] + $stringStorageOffset + $stringOffset);
                $this->fdt['name'] = substr($this->font, $this->offset, $stringLength);
                $name = preg_replace('/[^a-zA-Z0-9_\-]/', '', $this->fdt['name']);
                if (($name === null) || ($name === '')) {
                    throw new FontException('Error getting font name.');
                }

                $this->fdt['name'] = $name;
                break;
            } else {
                $this->offset += 4; // skip String length, String offset
            }
        }
    }

    protected function getPostData(): void
    {
        $this->offset = $this->fdt['table']['post']['offset'];
        $this->offset += 4; // skip Format Type
        $this->fdt['italicAngle'] = $this->fbyte->getFixed($this->offset);
        $this->offset += 4;
        $this->fdt['underlinePosition'] = (int) round($this->fbyte->getFWord($this->offset) * $this->fdt['urk']);
        $this->offset += 2;
        $this->fdt['underlineThickness'] = (int) round($this->fbyte->getFWord($this->offset) * $this->fdt['urk']);
        $this->offset += 2;
        $isFixedPitch = ($this->fbyte->getULong($this->offset) != 0);
        $this->offset += 2;
        if ($isFixedPitch) {
            $this->fdt['Flags'] |= 1;
        }
    }

    protected function getHheaData(): void
    {
        // ---------- get hhea data ----------
        $this->offset = $this->fdt['table']['hhea']['offset'];
        $this->offset += 4; // skip Table version number
        // Ascender
        $this->fdt['Ascent'] = (int) round($this->fbyte->getFWord($this->offset) * $this->fdt['urk']);
        $this->offset += 2;
        // Descender
        $this->fdt['Descent'] = (int) round($this->fbyte->getFWord($this->offset) * $this->fdt['urk']);
        $this->offset += 2;
        // LineGap
        $this->fdt['Leading'] = (int) round($this->fbyte->getFWord($this->offset) * $this->fdt['urk']);
        $this->offset += 2;
        // advanceWidthMax
        $this->fdt['MaxWidth'] = (int) round($this->fbyte->getUFWord($this->offset) * $this->fdt['urk']);
        $this->offset += 2;
        $this->offset += 22; // skip some values
        // get the number of hMetric entries in hmtx table
        $this->fdt['numHMetrics'] = $this->fbyte->getUShort($this->offset);
    }

    protected function getMaxpData(): void
    {
        $this->offset = $this->fdt['table']['maxp']['offset'];
        $this->offset += 4; // skip Table version number
        // get the the number of glyphs in the font.
        $this->fdt['numGlyphs'] = $this->fbyte->getUShort($this->offset);
    }

    /**
     * Get font heights
     */
    protected function getHeights(): void
    {
        // get xHeight (height of x)
        $this->fdt['XHeight'] = ($this->fdt['Ascent'] + $this->fdt['Descent']);
        if (! empty($this->fdt['ctgdata'][120])) {
            $this->offset = (
                $this->fdt['table']['glyf']['offset']
                + $this->fdt['indexToLoc'][$this->fdt['ctgdata'][120]]
                + 4
            );
            $yMin = $this->fbyte->getFWord($this->offset);
            $this->offset += 4;
            $yMax = $this->fbyte->getFWord($this->offset);
            $this->offset += 2;
            $this->fdt['XHeight'] = (int) round(($yMax - $yMin) * $this->fdt['urk']);
        }

        // get CapHeight (height of H)
        $this->fdt['CapHeight'] = (int) $this->fdt['Ascent'];
        if (! empty($this->fdt['ctgdata'][72])) {
            $this->offset = (
                $this->fdt['table']['glyf']['offset']
                + $this->fdt['indexToLoc'][$this->fdt['ctgdata'][72]]
                + 4
            );
            $yMin = $this->fbyte->getFWord($this->offset);
            $this->offset += 4;
            $yMax = $this->fbyte->getFWord($this->offset);
            $this->offset += 2;
            $this->fdt['CapHeight'] = (int) round(($yMax - $yMin) * $this->fdt['urk']);
        }
    }

    /**
     * Get font widths
     */
    protected function getWidths(): void
    {
        // create widths array
        $chw = [];
        $this->offset = $this->fdt['table']['hmtx']['offset'];
        for ($i = 0; $i < $this->fdt['numHMetrics']; ++$i) {
            $chw[$i] = round($this->fbyte->getUFWord($this->offset) * $this->fdt['urk']);
            $this->offset += 4; // skip lsb
        }

        if ($this->fdt['numHMetrics'] < $this->fdt['numGlyphs']) {
            // fill missing widths with the last value
            $chw = array_pad($chw, $this->fdt['numGlyphs'], $chw[($this->fdt['numHMetrics'] - 1)]);
        }

        $this->fdt['MissingWidth'] = $chw[0];
        $this->fdt['cw'] = [];
        $this->fdt['cbbox'] = [];
        for ($cid = 0; $cid <= 65535; ++$cid) {
            if (isset($this->fdt['ctgdata'][$cid])) {
                if (isset($chw[$this->fdt['ctgdata'][$cid]])) {
                    $this->fdt['cw'][$cid] = $chw[$this->fdt['ctgdata'][$cid]];
                }

                if (isset($this->fdt['indexToLoc'][$this->fdt['ctgdata'][$cid]])) {
                    $this->offset = (
                        $this->fdt['table']['glyf']['offset']
                        + $this->fdt['indexToLoc'][$this->fdt['ctgdata'][$cid]]
                    );
                    $xMin = round($this->fbyte->getFWord($this->offset + 2) * $this->fdt['urk']);
                    $yMin = round($this->fbyte->getFWord($this->offset + 4) * $this->fdt['urk']);
                    $xMax = round($this->fbyte->getFWord($this->offset + 6) * $this->fdt['urk']);
                    $yMax = round($this->fbyte->getFWord($this->offset + 8) * $this->fdt['urk']);
                    $this->fdt['cbbox'][$cid] = [$xMin, $yMin, $xMax, $yMax];
                }
            }
        }
    }

    /**
     * Add CTG entry
     */
    protected function addCtgItem(int $cid, int $gid): void
    {
        $this->fdt['ctgdata'][$cid] = $gid;
        if (isset($this->subchars[$cid])) {
            $this->subglyphs[$gid] = true;
        }
    }

    /**
     * Process the  CID To GID Map.
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function getCIDToGIDMap(): void
    {
        $this->fdt['ctgdata'] = [];
        foreach ($this->fdt['encodingTables'] as $enctable) {
            // get only specified Platform ID and Encoding ID
            if (
                ($enctable['platformID'] == $this->fdt['platform_id'])
                && ($enctable['encodingID'] == $this->fdt['encoding_id'])
            ) {
                $this->offset = ($this->fdt['table']['cmap']['offset'] + $enctable['offset']);
                $format = $this->fbyte->getUShort($this->offset);
                $this->offset += 2;
                match ($format) {
                    0 => $this->processFormat0(),
                    2 => $this->processFormat2(),
                    4 => $this->processFormat4(),
                    6 => $this->processFormat6(),
                    8 => $this->processFormat8(),
                    10 => $this->processFormat10(),
                    12 => $this->processFormat12(),
                    13 => $this->processFormat13(),
                    14 => $this->processFormat14(),
                    default => throw new FontException('Unsupported cmap format: ' . $format),
                };
            }
        }

        if (! isset($this->fdt['ctgdata'][0])) {
            $this->fdt['ctgdata'][0] = 0;
        }

        if ($this->fdt['type'] != 'TrueTypeUnicode') {
            return;
        }

        if (count($this->fdt['ctgdata']) != 256) {
            return;
        }

        $this->fdt['type'] = 'TrueType';
    }

    /**
     * Process Format 0: Byte encoding table
     */
    protected function processFormat0(): void
    {
        $this->offset += 4; // skip length and version/language
        for ($chr = 0; $chr < 256; ++$chr) {
            $gid = $this->fbyte->getByte($this->offset);
            $this->addCtgItem($chr, $gid);
            ++$this->offset;
        }
    }

    /**
     * Process Format 2: High-byte mapping through table
     */
    protected function processFormat2(): void
    {
        $this->offset += 4; // skip length and version/language
        $numSubHeaders = 0;
        for ($chr = 0; $chr < 256; ++$chr) {
            // Array that maps high bytes to subHeaders: value is subHeader index * 8.
            $subHeaderKeys[$chr] = ($this->fbyte->getUShort($this->offset) / 8);
            $this->offset += 2;
            if ($numSubHeaders < $subHeaderKeys[$chr]) {
                $numSubHeaders = $subHeaderKeys[$chr];
            }
        }

        // the number of subHeaders is equal to the max of subHeaderKeys + 1
        ++$numSubHeaders;
        // read subHeader structures
        $subHeaders = [];
        $numGlyphIndexArray = 0;
        for ($ish = 0; $ish < $numSubHeaders; ++$ish) {
            $subHeaders[$ish]['firstCode'] = $this->fbyte->getUShort($this->offset);
            $this->offset += 2;
            $subHeaders[$ish]['entryCount'] = $this->fbyte->getUShort($this->offset);
            $this->offset += 2;
            $subHeaders[$ish]['idDelta'] = $this->fbyte->getUShort($this->offset);
            $this->offset += 2;
            $subHeaders[$ish]['idRangeOffset'] = $this->fbyte->getUShort($this->offset);
            $this->offset += 2;
            $subHeaders[$ish]['idRangeOffset'] -= (2 + (($numSubHeaders - $ish - 1) * 8));
            $subHeaders[$ish]['idRangeOffset'] /= 2;
            $numGlyphIndexArray += $subHeaders[$ish]['entryCount'];
        }

        $glyphIndexArray = [
            0 => 0,
        ];
        for ($gid = 0; $gid < $numGlyphIndexArray; ++$gid) {
            $glyphIndexArray[$gid] = $this->fbyte->getUShort($this->offset);
            $this->offset += 2;
        }

        for ($chr = 0; $chr < 256; ++$chr) {
            $shk = $subHeaderKeys[$chr];
            if ($shk == 0) {
                // one byte code
                $cdx = $chr;
                $gid = $glyphIndexArray[0];
                $this->addCtgItem($cdx, $gid);
            } else {
                // two bytes code
                $start_byte = $subHeaders[$shk]['firstCode'];
                $end_byte = $start_byte + $subHeaders[$shk]['entryCount'];
                for ($jdx = $start_byte; $jdx < $end_byte; ++$jdx) {
                    // combine high and low bytes
                    $cdx = (($chr << 8) + $jdx);
                    $idRangeOffset = ($subHeaders[$shk]['idRangeOffset'] + $jdx - $subHeaders[$shk]['firstCode']);
                    $gid = max(0, (($glyphIndexArray[$idRangeOffset] + $subHeaders[$shk]['idDelta']) % 65536));
                    $this->addCtgItem($cdx, $gid);
                }
            }
        }
    }

    /**
     * Process Format 4: Segment mapping to delta values
     */
    protected function processFormat4(): void
    {
        $length = $this->fbyte->getUShort($this->offset);
        $this->offset += 2;
        $this->offset += 2; // skip version/language
        $segCount = floor($this->fbyte->getUShort($this->offset) / 2);
        $this->offset += 2;
        $this->offset += 6; // skip searchRange, entrySelector, rangeShift
        $endCount = []; // array of end character codes for each segment
        for ($kdx = 0; $kdx < $segCount; ++$kdx) {
            $endCount[$kdx] = $this->fbyte->getUShort($this->offset);
            $this->offset += 2;
        }

        $this->offset += 2; // skip reservedPad
        $startCount = []; // array of start character codes for each segment
        for ($kdx = 0; $kdx < $segCount; ++$kdx) {
            $startCount[$kdx] = $this->fbyte->getUShort($this->offset);
            $this->offset += 2;
        }

        $idDelta = []; // delta for all character codes in segment
        for ($kdx = 0; $kdx < $segCount; ++$kdx) {
            $idDelta[$kdx] = $this->fbyte->getUShort($this->offset);
            $this->offset += 2;
        }

        $idRangeOffset = []; // Offsets into glyphIdArray or 0
        for ($kdx = 0; $kdx < $segCount; ++$kdx) {
            $idRangeOffset[$kdx] = $this->fbyte->getUShort($this->offset);
            $this->offset += 2;
        }

        $gidlen = (floor($length / 2) - 8 - (4 * $segCount));
        $glyphIdArray = []; // glyph index array
        for ($kdx = 0; $kdx < $gidlen; ++$kdx) {
            $glyphIdArray[$kdx] = $this->fbyte->getUShort($this->offset);
            $this->offset += 2;
        }

        for ($kdx = 0; $kdx < $segCount; ++$kdx) {
            for ($chr = $startCount[$kdx]; $chr <= $endCount[$kdx]; ++$chr) {
                if ($idRangeOffset[$kdx] == 0) {
                    $gid = max(0, (($idDelta[$kdx] + $chr) % 65536));
                } else {
                    $gid = (floor($idRangeOffset[$kdx] / 2) + ($chr - $startCount[$kdx]) - ($segCount - $kdx));
                    $gid = max(0, (($glyphIdArray[$gid] + $idDelta[$kdx]) % 65536));
                }

                $this->addCtgItem($chr, $gid);
            }
        }
    }

    /**
     * Process Format 6: Trimmed table mapping
     */
    protected function processFormat6(): void
    {
        $this->offset += 4; // skip length and version/language
        $firstCode = $this->fbyte->getUShort($this->offset);
        $this->offset += 2;
        $entryCount = $this->fbyte->getUShort($this->offset);
        $this->offset += 2;
        for ($kdx = 0; $kdx < $entryCount; ++$kdx) {
            $chr = ($kdx + $firstCode);
            $gid = $this->fbyte->getUShort($this->offset);
            $this->offset += 2;
            $this->addCtgItem($chr, $gid);
        }
    }

    /**
     * Process Format 8: Mixed 16-bit and 32-bit coverage
     */
    protected function processFormat8(): void
    {
        $this->offset += 10; // skip reserved, length and version/language
        for ($kdx = 0; $kdx < 8192; ++$kdx) {
            $is32[$kdx] = $this->fbyte->getByte($this->offset);
            ++$this->offset;
        }

        $nGroups = $this->fbyte->getULong($this->offset);
        $this->offset += 4;
        for ($idx = 0; $idx < $nGroups; ++$idx) {
            $startCharCode = $this->fbyte->getULong($this->offset);
            $this->offset += 4;
            $endCharCode = $this->fbyte->getULong($this->offset);
            $this->offset += 4;
            $startGlyphID = $this->fbyte->getULong($this->offset);
            $this->offset += 4;
            for ($cpw = $startCharCode; $cpw <= $endCharCode; ++$cpw) {
                $is32idx = floor($cpw / 8);
                if ((isset($is32[$is32idx])) && (($is32[$is32idx] & (1 << (7 - ($cpw % 8)))) == 0)) {
                    $chr = $cpw;
                } else {
                    // 32 bit format
                    // convert to decimal (http://www.unicode.org/faq//utf_bom.html#utf16-4)
                    //LEAD_OFFSET = (0xD800 - (0x10000 >> 10)) = 55232
                    //SURROGATE_OFFSET = (0x10000 - (0xD800 << 10) - 0xDC00) = -56613888
                    $chr = (((55232 + ($cpw >> 10)) << 10) + (0xDC00 + ($cpw & 0x3FF)) - 56_613_888);
                }

                $this->addCtgItem($chr, $startGlyphID);
                $this->fdt['ctgdata'][$chr] = 0; // overwrite
                ++$startGlyphID;
            }
        }
    }

    /**
     * Process Format 10: Trimmed array
     */
    protected function processFormat10(): void
    {
        $this->offset += 10; // skip reserved, length and version/language
        $startCharCode = $this->fbyte->getULong($this->offset);
        $this->offset += 4;
        $numChars = $this->fbyte->getULong($this->offset);
        $this->offset += 4;
        for ($kdx = 0; $kdx < $numChars; ++$kdx) {
            $chr = ($kdx + $startCharCode);
            $gid = $this->fbyte->getUShort($this->offset);
            $this->addCtgItem($chr, $gid);
            $this->offset += 2;
        }
    }

    /**
     * Process Format 12: Segmented coverage
     */
    protected function processFormat12(): void
    {
        $this->offset += 10; // skip length and version/language
        $nGroups = $this->fbyte->getULong($this->offset);
        $this->offset += 4;
        for ($kdx = 0; $kdx < $nGroups; ++$kdx) {
            $startCharCode = $this->fbyte->getULong($this->offset);
            $this->offset += 4;
            $endCharCode = $this->fbyte->getULong($this->offset);
            $this->offset += 4;
            $startGlyphCode = $this->fbyte->getULong($this->offset);
            $this->offset += 4;
            for ($chr = $startCharCode; $chr <= $endCharCode; ++$chr) {
                $this->addCtgItem($chr, $startGlyphCode);
                ++$startGlyphCode;
            }
        }
    }

    /**
     * Process Format 13: Many-to-one range mappings
     *
     * @TODO: TO BE IMPLEMENTED
     */
    protected function processFormat13(): void
    {
    }

    /**
     * Process Format 14: Unicode Variation Sequences
     *
     * @TODO: TO BE IMPLEMENTED
     */
    protected function processFormat14(): void
    {
    }
}
