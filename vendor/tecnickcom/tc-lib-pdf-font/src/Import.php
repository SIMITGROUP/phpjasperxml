<?php

/**
 * Import.php
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
use Com\Tecnick\File\Dir;
use Com\Tecnick\File\File;
use Com\Tecnick\Pdf\Font\Exception as FontException;
use Com\Tecnick\Pdf\Font\Import\Core;
use Com\Tecnick\Pdf\Font\Import\TrueType;
use Com\Tecnick\Pdf\Font\Import\TypeOne;
use Com\Tecnick\Unicode\Data\Encoding;

/**
 * Com\Tecnick\Pdf\Font\Import
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
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class Import
{
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
     * Import the specified font and create output files.
     *
     * @param string $file        Font file to process
     * @param string $output_path Output path for generated font files (must be writeable by the web server).
     *                            Leave null for default font folder.
     * @param string $type        Font type. Leave empty for autodetect mode. Valid values are:
     *                            Core (AFM - Adobe Font Metrics) TrueTypeUnicode TrueType
     *                            Type1 CID0JP (CID-0 Japanese) CID0KR (CID-0 Korean) CID0CS
     *                            (CID-0 Chinese Simplified) CID0CT (CID-0 Chinese Traditional)
     * @param string $encoding    Name of the encoding table to use. Leave empty for default mode.
     *                            Omit this parameter for TrueType Unicode and symbolic fonts like
     *                            Symbol or ZapfDingBats.
     * @param int    $flags       Unsigned 32-bit integer containing flags specifying various characteristics
     *                            of the font as described in "PDF32000:2008 - 9.8.2 Font Descriptor Flags":
     *                            +1 for fixed width font +4 for symbol or +32 for non-symbol +64 for italic
     *                            Note: Fixed and Italic mode are generally autodetected, so you have to set
     *                            it to 32 = non-symbolic font (default) or 4 = symbolic font.
     * @param int    $platform_id Platform ID for CMAP table to extract.
     *                            For a Unicode font for Windows this
     *                            value should be 3, for Macintosh
     *                            should be 1.
     * @param int    $encoding_id Encoding ID for CMAP table to extract.
     *                            For a Unicode font for Windows this
     *                            value should be 1, for Macintosh
     *                            should be 0. When Platform ID is 3,
     *                            legal values for Encoding ID are: 0 =
     *                            Symbol, 1 = Unicode, 2 = ShiftJIS, 3 =
     *                            PRC, 4 = Big5, 5 = Wansung, 6 = Johab,
     *                            7 = Reserved, 8 = Reserved, 9 =
     *                            Reserved, 10 = UCS-4.
     * @param bool   $linked      If true, links the font file to system font instead of copying the font data
     *                            (not transportable). Note: this option do not work with Type1 fonts.
     *
     * @throws FontException in case of error
     */
    public function __construct(
        string $file,
        string $output_path = '',
        string $type = '',
        string $encoding = '',
        int $flags = 32,
        int $platform_id = 3,
        int $encoding_id = 1,
        bool $linked = false
    ) {
        $this->fdt['input_file'] = $file;
        $this->fdt['file_name'] = $this->makeFontName($file);
        if (empty($this->fdt['file_name'])) {
            throw new FontException('the font name is empty');
        }

        $this->fdt['dir'] = $this->findOutputPath($output_path);
        $this->fdt['datafile'] = $this->fdt['dir'] . $this->fdt['file_name'] . '.json';
        if (@file_exists($this->fdt['datafile'])) {
            throw new FontException('this font has been already imported: ' . $this->fdt['datafile']);
        }

        // get font data
        if (! is_file($file)) {
            throw new FontException('invalid font file: ' . $file);
        }

        if (($font = @file_get_contents($file)) === false) {
            throw new FontException('unable to read the input font file: ' . $file);
        }

        $this->font = $font;

        $this->fbyte = new Byte($this->font);

        $this->fdt['settype'] = $type;
        $this->fdt['type'] = $this->getFontType($type);
        $this->fdt['isUnicode'] = (($this->fdt['type'] == 'TrueTypeUnicode') || ($this->fdt['type'] == 'cidfont0'));
        $this->fdt['Flags'] = $flags;
        $this->initFlags();
        $this->fdt['enc'] = $this->getEncodingTable($encoding);
        $this->fdt['diff'] = $this->getEncodingDiff();
        $this->fdt['originalsize'] = strlen($this->font);
        $this->fdt['ctg'] = $this->fdt['file_name'] . '.ctg.z';
        $this->fdt['platform_id'] = $platform_id;
        $this->fdt['encoding_id'] = $encoding_id;
        $this->fdt['linked'] = $linked;

        $processor = match ($this->fdt['type']) {
            'Core' => new Core($this->font, $this->fdt),
            'Type1' => new TypeOne($this->font, $this->fdt),
            default => new TrueType($this->font, $this->fdt, $this->fbyte),
        };
        ;

        $this->fdt = $processor->getFontMetrics();

        $this->saveFontData();
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
     * Get the output font name
     */
    public function getFontName(): string
    {
        return $this->fdt['file_name'];
    }

    /**
     * Initialize font flags from font name
     */
    protected function initFlags(): void
    {
        $filename = strtolower(basename($this->fdt['input_file']));

        if (
            (str_contains($filename, 'mono'))
            || (str_contains($filename, 'courier'))
            || (str_contains($filename, 'fixed'))
        ) {
            $this->fdt['Flags'] |= 1;
        }

        if (
            (str_contains($filename, 'symbol'))
            || (str_contains($filename, 'zapfdingbats'))
        ) {
            $this->fdt['Flags'] |= 4;
        }

        if (
            (str_contains($filename, 'italic'))
            || (str_contains($filename, 'oblique'))
        ) {
            $this->fdt['Flags'] |= 64;
        }
    }

    /**
     * Save the eported metadata font file
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    protected function saveFontData(): void
    {
        $pfile = '{"type":"' . $this->fdt['type'] . '"'
            . ',"name":"' . $this->fdt['name'] . '"'
            . ',"up":' . $this->fdt['underlinePosition']
            . ',"ut":' . $this->fdt['underlineThickness']
            . ',"dw":' . (($this->fdt['MissingWidth'] > 0) ? $this->fdt['MissingWidth'] : $this->fdt['AvgWidth'])
            . ',"diff":"' . $this->fdt['diff'] . '"'
            . ',"platform_id":' . $this->fdt['platform_id']
            . ',"encoding_id":' . $this->fdt['encoding_id'];

        if ($this->fdt['type'] == 'Core') {
            // Core
            $pfile .= ',"enc":""';
        } elseif ($this->fdt['type'] == 'Type1') {
            // Type 1
            $pfile .= ',"enc":"' . $this->fdt['enc'] . '"'
                . ',"file":"' . $this->fdt['file'] . '"'
                . ',"size1":' . $this->fdt['size1']
                . ',"size2":' . $this->fdt['size2'];
        } else {
            $pfile .= ',"originalsize":' . $this->fdt['originalsize'];
            if ($this->fdt['type'] == 'cidfont0') {
                $pfile .= ',' . UniToCid::TYPE[$this->fdt['settype']];
            } else {
                // TrueType
                $pfile .= ',"enc":"' . $this->fdt['enc'] . '"'
                    . ',"file":"' . $this->fdt['file'] . '"'
                    . ',"ctg":"' . $this->fdt['ctg'] . '"';
                // create CIDToGIDMap
                $cidtogidmap = str_pad('', 131072, "\x00"); // (256 * 256 * 2) = 131072
                foreach ($this->fdt['ctgdata'] as $cid => $gid) {
                    $cidtogidmap = $this->updateCIDtoGIDmap($cidtogidmap, (int) $cid, (int) $gid);
                }

                // store compressed CIDToGIDMap
                $file = new File();
                $fpt = $file->fopenLocal($this->fdt['dir'] . $this->fdt['ctg'], 'wb');

                $cmpr = gzcompress($cidtogidmap);
                if ($cmpr === false) {
                    throw new FontException('unable to compress CIDToGIDMap');
                }

                fwrite($fpt, $cmpr);
                fclose($fpt);
            }
        }

        if ($this->fdt['isUnicode']) {
            $pfile .= ',"isUnicode":true';
        } else {
            $pfile .= ',"isUnicode":false';
        }

        $pfile .= ',"desc":{"Flags":' . $this->fdt['Flags']
            . ',"FontBBox":"[' . $this->fdt['bbox'] . ']"'
            . ',"ItalicAngle":' . $this->fdt['italicAngle']
            . ',"Ascent":' . $this->fdt['Ascent']
            . ',"Descent":' . $this->fdt['Descent']
            . ',"Leading":' . $this->fdt['Leading']
            . ',"CapHeight":' . $this->fdt['CapHeight']
            . ',"XHeight":' . $this->fdt['XHeight']
            . ',"StemV":' . $this->fdt['StemV']
            . ',"StemH":' . $this->fdt['StemH']
            . ',"AvgWidth":' . $this->fdt['AvgWidth']
            . ',"MaxWidth":' . $this->fdt['MaxWidth']
            . ',"MissingWidth":' . $this->fdt['MissingWidth']
            . '}';
        if (! empty($this->fdt['cbbox'])) {
            $ccboxstr = '';
            foreach ($this->fdt['cbbox'] as $cid => $bbox) {
                $ccboxstr .= ',"' . $cid . '":[' . $bbox[0] . ',' . $bbox[1] . ',' . $bbox[2] . ',' . $bbox[3] . ']';
            }

            $pfile .= ',"cbbox":{' . substr($ccboxstr, 1) . '}';
        }

        if (! empty($this->fdt['cw'])) {
            $cwstr = '';
            foreach ($this->fdt['cw'] as $cid => $width) {
                $cwstr .= ',"' . $cid . '":' . $width;
            }

            $pfile .= ',"cw":{' . substr($cwstr, 1) . '}';
        }

        $pfile .= '}' . "\n";

        // store file
        $file = new File();
        $fpt = $file->fopenLocal($this->fdt['datafile'], 'wb');
        fwrite($fpt, $pfile);
        fclose($fpt);
    }

    /**
     * Make the output font name
     *
     * @param string $font_file Input font file
     */
    protected function makeFontName(string $font_file): string
    {
        $font_path_parts = pathinfo($font_file);
        if (empty($font_path_parts['filename'])) {
            throw new FontException('Invalid font file name: ' . $font_file);
        }

        $fname = preg_replace('/[^a-z0-9_]/', '', strtolower($font_path_parts['filename']));
        if ($fname === null) {
            throw new FontException('Invalid font file name: ' . $font_file);
        }

        return str_replace(
            ['bold', 'oblique', 'italic', 'regular'],
            ['b', 'i', 'i', ''],
            $fname
        );
    }

    /**
     * Find the path where to store the processed font.
     *
     * @param string $output_path Output path for generated font files (must be writeable by the web server).
     *                            Leave null for default font folder (K_PATH_FONTS).
     */
    protected function findOutputPath(string $output_path = ''): string
    {
        if ($output_path !== '' && is_writable($output_path)) {
            return $output_path;
        }

        if (defined('K_PATH_FONTS') && is_writable(K_PATH_FONTS)) {
            return K_PATH_FONTS;
        }

        $dirobj = new Dir();
        $dir = $dirobj->findParentDir('fonts', __DIR__);
        if ($dir == '/') {
            $dir = sys_get_temp_dir();
        }

        if (! str_ends_with($dir, '/')) {
            $dir .= '/';
        }

        return $dir;
    }

    /**
     * Get the font type
     *
     * @param string $font_type Font type. Leave empty for autodetect mode.
     */
    protected function getFontType(string $font_type): string
    {
        // autodetect font type
        if ($font_type === '') {
            if (str_starts_with($this->font, 'StartFontMetrics')) {
                // AFM type - we use this type only for the 14 Core fonts
                return 'Core';
            }

            if (str_starts_with($this->font, 'OTTO')) {
                throw new FontException('Unsupported font format: OpenType with CFF data');
            }

            if ($this->fbyte->getULong(0) == 0x10000) {
                return 'TrueTypeUnicode';
            }

            return 'Type1';
        }

        if (str_starts_with($font_type, 'CID0')) {
            return 'cidfont0';
        }

        if (in_array($font_type, ['Core', 'Type1', 'TrueType', 'TrueTypeUnicode'])) {
            return $font_type;
        }

        throw new FontException('unknown or unsupported font type: ' . $font_type);
    }

    /**
     * Get the encoding table
     *
     * @param string $encoding Name of the encoding table to use. Leave empty for default mode.
     *                         Omit this parameter for TrueType Unicode and symbolic fonts like
     *                         Symbol or ZapfDingBats.
     */
    protected function getEncodingTable(string $encoding = ''): string
    {
        if ($encoding === '') {
            if (($this->fdt['type'] == 'Type1') && (($this->fdt['Flags'] & 4) == 0)) {
                return 'cp1252';
            }

            return '';
        }

        $enc = preg_replace('/[^A-Za-z0-9_\-]/', '', $encoding);
        if ($enc === null) {
            throw new FontException('Invalid encoding name: ' . $encoding);
        }

        return $enc;
    }

    /**
     * If required, get differences between the reference encoding (cp1252) and the current encoding
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function getEncodingDiff(): string
    {
        $diff = '';
        if (
            (($this->fdt['type'] == 'TrueType') || ($this->fdt['type'] == 'Type1'))
            && (! empty($this->fdt['enc'])
            && (is_string($this->fdt['enc']))
            && ($this->fdt['enc'] != 'cp1252')
            && isset(Encoding::MAP[$this->fdt['enc']]))
        ) {
            // build differences from reference encoding
            $enc_ref = Encoding::MAP['cp1252'];
            $enc_target = Encoding::MAP[$this->fdt['enc']];
            $last = 0;
            for ($idx = 32; $idx <= 255; ++$idx) {
                if ($enc_target[$idx] != $enc_ref[$idx]) {
                    if ($idx != $last + 1) {
                        $diff .= $idx . ' ';
                    }

                    $last = $idx;
                    $diff .= '/' . $enc_target[$idx] . ' ';
                }
            }
        }

        return $diff;
    }

    /**
     * Update the CIDToGIDMap string with a new value
     *
     * @param string $map CIDToGIDMap.
     * @param int    $cid CID value.
     * @param int    $gid GID value.
     */
    protected function updateCIDtoGIDmap(string $map, int $cid, int $gid): string
    {
        if (($cid >= 0) && ($cid <= 0xFFFF) && ($gid >= 0)) {
            if ($gid > 0xFFFF) {
                $gid -= 0x10000;
            }

            $map[($cid * 2)] = chr($gid >> 8);
            $map[(($cid * 2) + 1)] = chr($gid & 0xFF);
        }

        return $map;
    }
}
