<?php

/**
 * Subset.php
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

namespace Com\Tecnick\Pdf\Font;

use Com\Tecnick\File\Byte;
use Com\Tecnick\Pdf\Font\Exception as FontException;
use Com\Tecnick\Pdf\Font\Import\TrueType;

/**
 * Com\Tecnick\Pdf\Font\Subset
 *
 * @since     2011-05-23
 * @category  Library
 * @package   PdfFont
 * @author    Nicola Asuni <info@tecnick.com>
 * @copyright 2011-2024 Nicola Asuni - Tecnick.com LTD
 * @license   http://www.gnu.org/copyleft/lesser.html GNU-LGPL v3 (see LICENSE.TXT)
 * @link      https://github.com/tecnickcom/tc-lib-pdf-font
 *
 * @phpstan-import-type TFontData from Load
 */
class Subset
{
    /**
     * array of table names to preserve (loca and glyf tables will be added later)
     * the cmap table is not needed and shall not be present,
     * since the mapping from character codes to glyph descriptions is provided separately
     *
     * @var array<string, bool>
     */
    protected const TABLENAMES = [
        'head' => true,
        'hhea' => true,
        'hmtx' => true,
        'maxp' => true,
        'cvt ' => true,
        'fpgm' => true,
        'prep' => true,
        'glyf' => true,
        'loca' => true,
    ];

    /**
     * Content of the input font file
     */
    protected string $font = '';

    /**
     * Object used to read font bytes
     */
    protected Byte $fbyte;

    /**
     * Extracted font metrics
     *
     * @var TFontData
     */
    protected array $fdt = [
        'Ascender' => 0,
        'Ascent' => 0,
        'AvgWidth' => 0.0,
        'CapHeight' => 0,
        'CharacterSet' => '',
        'Descender' => 0,
        'Descent' => 0,
        'EncodingScheme' => '',
        'FamilyName' => '',
        'Flags' => 0,
        'FontBBox' => [],
        'FontName' => '',
        'FullName' => '',
        'IsFixedPitch' => false,
        'ItalicAngle' => 0,
        'Leading' => 0,
        'MaxWidth' => 0,
        'MissingWidth' => 0,
        'StdHW' => 0,
        'StdVW' => 0,
        'StemH' => 0,
        'StemV' => 0,
        'UnderlinePosition' => 0,
        'UnderlineThickness' => 0,
        'Version' => '',
        'Weight' => '',
        'XHeight' => 0,
        'bbox' => '',
        'cbbox' => [],
        'cidinfo' => [
            'Ordering' => '',
            'Registry' => '',
            'Supplement' => 0,
            'uni2cid' => [],
        ],
        'compress' => false,
        'ctg' => '',
        'ctgdata' => [],
        'cw' => [],
        'datafile' => '',
        'desc' => [
            'Ascent' => 0,
            'AvgWidth' => 0,
            'CapHeight' => 0,
            'Descent' => 0,
            'Flags' => 0,
            'FontBBox' => '',
            'ItalicAngle' => 0,
            'Leading' => 0,
            'MaxWidth' => 0,
            'MissingWidth' => 0,
            'StemH' => 0,
            'StemV' => 0,
            'XHeight' => 0,
        ],
        'diff' => '',
        'diff_n' => 0,
        'dir' => '',
        'dw' => 0,
        'enc' => '',
        'enc_map' => [],
        'encodingTables' => [],
        'encoding_id' => 0,
        'encrypted' => '',
        'fakestyle' => false,
        'family' => '',
        'file' => '',
        'file_n' => 0,
        'file_name' => '',
        'i' => 0,
        'ifile' => '',
        'indexToLoc' => [],
        'input_file' => '',
        'isUnicode' => false,
        'italicAngle' => 0,
        'key' => '',
        'lenIV' => 0,
        'length1' => 0,
        'length2' => 0,
        'linked' => false,
        'mode' => [
            'bold' => false,
            'italic' => false,
            'linethrough' => false,
            'overline' => false,
            'underline' => false,
        ],
        'n' => 0,
        'name' => '',
        'numGlyphs' => 0,
        'numHMetrics' => 0,
        'originalsize' => 0,
        'pdfa' => false,
        'platform_id' => 0,
        'settype' => '',
        'short_offset' => false,
        'size1' => 0,
        'size2' => 0,
        'style' => '',
        'subset' => false,
        'subsetchars' => [],
        'table' => [],
        'tot_num_glyphs' => 0,
        'type' => '',
        'underlinePosition' => 0,
        'underlineThickness' => 0,
        'unicode' => false,
        'unitsPerEm' => 0,
        'up' => 0,
        'urk' => 0.0,
        'ut' => 0,
        'weight' => '',
    ];

    /**
     * Array containing subset glyphs indexes of chars from cmap table
     *
     * @var array<int, bool>
     */
    protected array $subglyphs = [];

    /**
     * Subset font
     */
    protected string $subfont = '';

    /**
     * Pointer position on the original font data
     */
    protected int $offset = 0;

    /**
     * Process TrueType font
     *
     * @param string           $font     Content of the input font file
     * @param TFontData         $fdt      Extracted font metrics
     * @param array<int, bool> $subchars Array containing subset chars
     *
     * @throws FontException in case of error
     */
    public function __construct(string $font, array $fdt, array $subchars = [])
    {
        $this->fbyte = new Byte($font);
        $trueType = new TrueType($font, $fdt, $this->fbyte, $subchars);
        $this->fdt = $trueType->getFontMetrics();
        $this->subglyphs = $trueType->getSubGlyphs();
        $this->addCompositeGlyphs();
        $this->addProcessedTables();
        $this->removeUnusedTables();
        $this->buildSubsetFont();
    }

    /**
     * Get all the extracted font metrics
     */
    public function getSubsetFont(): string
    {
        return $this->subfont;
    }

    /**
     * Returs the checksum of a TTF table.
     *
     * @param string $table  Table to check
     * @param int    $length Length of table in bytes
     *
     * @return int checksum
     */
    protected function getTableChecksum(string $table, int $length): int
    {
        $sum = 0;
        $tlen = ($length / 4);
        $offset = 0;
        for ($idx = 0; $idx < $tlen; ++$idx) {
            $val = unpack('Ni', substr($table, $offset, 4));
            if ($val === false) {
                throw new FontException('Unable to unpack table data');
            }

            $sum += $val['i'];
            $offset += 4;
        }

        $sum = unpack('Ni', pack('N', $sum));
        if ($sum === false) {
            throw new FontException('Unable to unpack checksum');
        }

        return $sum['i'];
    }

    /**
     * Add composite glyphs
     */
    protected function addCompositeGlyphs(): void
    {
        $new_sga = $this->subglyphs;
        while ($new_sga !== []) {
            $sga = array_keys($new_sga);
            $new_sga = [];
            foreach ($sga as $key) {
                $new_sga = $this->findCompositeGlyphs($new_sga, $key);
            }

            $this->subglyphs = [...$this->subglyphs, ...$new_sga];
        }

        // sort glyphs by key (and remove duplicates)
        ksort($this->subglyphs);
    }

    /**
     * Add composite glyphs
     *
     * @param array<int, bool> $new_sga
     *
     * @return array<int, bool>
     */
    protected function findCompositeGlyphs(array $new_sga, int $key): array
    {
        if (isset($this->fdt['indexToLoc'][$key])) {
            $this->offset = ($this->fdt['table']['glyf']['offset'] + $this->fdt['indexToLoc'][$key]);
            $numberOfContours = $this->fbyte->getShort($this->offset);
            $this->offset += 2;
            if ($numberOfContours < 0) { // composite glyph
                $this->offset += 8; // skip xMin, yMin, xMax, yMax
                do {
                    $flags = $this->fbyte->getUShort($this->offset);
                    $this->offset += 2;
                    $glyphIndex = $this->fbyte->getUShort($this->offset);
                    $this->offset += 2;
                    if (! isset($this->subglyphs[$glyphIndex])) {
                        // add missing glyphs
                        $new_sga[$glyphIndex] = true;
                    }

                    // skip some bytes by case
                    if (($flags & 1) !== 0) {
                        $this->offset += 4;
                    } else {
                        $this->offset += 2;
                    }

                    if (($flags & 8) !== 0) {
                        $this->offset += 2;
                    } elseif (($flags & 64) !== 0) {
                        $this->offset += 4;
                    } elseif (($flags & 128) !== 0) {
                        $this->offset += 8;
                    }
                } while ($flags & 32);
            }
        }

        return $new_sga;
    }

    /**
     * Remove unused tables
     */
    protected function removeUnusedTables(): void
    {
        // get the tables to preserve
        $this->offset = 12;
        $tabname = array_keys($this->fdt['table']);
        foreach ($tabname as $tag) {
            if (! isset(self::TABLENAMES[$tag])) {
                // remove the table
                unset($this->fdt['table'][$tag]);
                continue;
            }

            if (empty($this->fdt['table'][$tag])) {
                $this->fdt['table'][$tag] = [
                    'checkSum' => 0,
                    'data' => '',
                    'length' => 0,
                    'offset' => 0,
                ];
            }

            $this->fdt['table'][$tag]['data'] = substr(
                $this->font,
                $this->fdt['table'][$tag]['offset'],
                $this->fdt['table'][$tag]['length']
            );
            if ($tag == 'head') {
                // set the checkSumAdjustment to 0
                $this->fdt['table'][$tag]['data'] = substr($this->fdt['table'][$tag]['data'], 0, 8)
                    . "\x0\x0\x0\x0" . substr($this->fdt['table'][$tag]['data'], 12);
            }

            $pad = 4 - ((int) $this->fdt['table'][$tag]['length'] % 4);
            if ($pad != 4) {
                // the length of a table must be a multiple of four bytes
                $this->fdt['table'][$tag]['length'] += (int) $pad;
                $this->fdt['table'][$tag]['data'] .= str_repeat("\x0", $pad);
            }

            $this->fdt['table'][$tag]['offset'] = $this->offset;
            $this->offset += $this->fdt['table'][$tag]['length'];
            // check sum is not changed
        }
    }

    /**
     * Add glyf and loca tables
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    protected function addProcessedTables(): void
    {
        // build new glyf and loca tables
        $glyf = '';
        $loca = '';
        $this->offset = 0;
        $glyf_offset = $this->fdt['table']['glyf']['offset'];
        for ($i = 0; $i < $this->fdt['tot_num_glyphs']; ++$i) {
            if (
                isset($this->subglyphs[$i])
                && isset($this->fdt['indexToLoc'][$i])
                && isset($this->fdt['indexToLoc'][($i + 1)])
            ) {
                $length = ($this->fdt['indexToLoc'][($i + 1)] - $this->fdt['indexToLoc'][$i]);
                $glyf .= substr($this->font, ($glyf_offset + $this->fdt['indexToLoc'][$i]), $length);
            } else {
                $length = 0;
            }

            if ($this->fdt['short_offset']) {
                $loca .= pack('n', floor($this->offset / 2));
            } else {
                $loca .= pack('N', $this->offset);
            }

            $this->offset += $length;
        }

        // add loca
        if (empty($this->fdt['table']['loca'])) {
            $this->fdt['table']['loca'] = [
                'checkSum' => 0,
                'data' => '',
                'length' => 0,
                'offset' => 0,
            ];
        }

        $this->fdt['table']['loca']['data'] = $loca;
        $this->fdt['table']['loca']['length'] = strlen($loca);
        $this->fdt['table']['loca']['offset'] = $this->offset;
        $pad = 4 - ($this->fdt['table']['loca']['length'] % 4);
        if ($pad != 4) {
            // the length of a table must be a multiple of four bytes
            $this->fdt['table']['loca']['length'] += $pad;
            $this->fdt['table']['loca']['data'] .= str_repeat("\x0", $pad);
        }

        $this->fdt['table']['loca']['checkSum'] = $this->getTableChecksum(
            $this->fdt['table']['loca']['data'],
            $this->fdt['table']['loca']['length']
        );

        $this->offset += $this->fdt['table']['loca']['length'];

        // add glyf
        if (empty($this->fdt['table']['glyf'])) {
            $this->fdt['table']['glyf'] = [
                'checkSum' => 0,
                'data' => '',
                'length' => 0,
                'offset' => 0,
            ];
        }

        $this->fdt['table']['glyf']['data'] = $glyf;
        $this->fdt['table']['glyf']['length'] = strlen($glyf);
        $this->fdt['table']['glyf']['offset'] = $this->offset;
        $pad = 4 - ($this->fdt['table']['glyf']['length'] % 4);
        if ($pad != 4) {
            // the length of a table must be a multiple of four bytes
            $this->fdt['table']['glyf']['length'] += $pad;
            $this->fdt['table']['glyf']['data'] .= str_repeat("\x0", $pad);
        }

        $this->fdt['table']['glyf']['checkSum'] = $this->getTableChecksum(
            $this->fdt['table']['glyf']['data'],
            $this->fdt['table']['glyf']['length']
        );
    }

    /**
     * build new subset font
     */
    protected function buildSubsetFont(): void
    {
        $this->subfont = '';
        $this->subfont .= pack('N', 0x10000); // sfnt version
        $numTables = count($this->fdt['table']);
        $this->subfont .= pack('n', $numTables); // numTables
        $entrySelector = floor(log($numTables, 2));
        $searchRange = 2 ** $entrySelector * 16;
        $rangeShift = ($numTables * 16) - $searchRange;
        $this->subfont .= pack('n', $searchRange); // searchRange
        $this->subfont .= pack('n', $entrySelector); // entrySelector
        $this->subfont .= pack('n', $rangeShift); // rangeShift
        $this->offset = ($numTables * 16);
        foreach ($this->fdt['table'] as $tag => $data) {
            $this->subfont .= $tag; // tag
            $this->subfont .= pack('N', $data['checkSum']); // checkSum
            $this->subfont .= pack('N', ($data['offset'] + $this->offset)); // offset
            $this->subfont .= pack('N', $data['length']); // length
        }

        foreach ($this->fdt['table'] as $data) {
            $this->subfont .= $data['data'];
        }

        // set checkSumAdjustment on head table
        $checkSumAdjustment = (0xB1B0AFBA - $this->getTableChecksum($this->subfont, strlen($this->subfont)));
        $this->subfont = substr($this->subfont, 0, $this->fdt['table']['head']['offset'] + 8)
            . pack('N', $checkSumAdjustment)
            . substr($this->subfont, $this->fdt['table']['head']['offset'] + 12);
    }
}
