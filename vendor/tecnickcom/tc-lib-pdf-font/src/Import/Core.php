<?php

/**
 * Core.php
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

/**
 * Com\Tecnick\Pdf\Font\Import\Core
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
 */
class Core
{
    /**
     * @param string   $font Content of the input font file
     * @param TFontData $fdt  Extracted font metrics
     *
     * @throws FontException in case of error
     */
    public function __construct(
        protected string $font,
        protected array $fdt
    ) {
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

    protected function setFlags(): void
    {
        if (($this->fdt['FontName'] == 'Symbol') || ($this->fdt['FontName'] == 'ZapfDingbats')) {
            $this->fdt['Flags'] |= 4;
        } else {
            $this->fdt['Flags'] |= 32;
        }

        if ($this->fdt['IsFixedPitch']) {
            $this->fdt['Flags'] = ((int) $this->fdt['Flags']) | 1;
        }

        if ((int) $this->fdt['ItalicAngle'] != 0) {
            $this->fdt['Flags'] = ((int) $this->fdt['Flags']) | 64;
        }
    }

    /**
     * Set Char widths
     *
     * @param array<int, int> $cwidths Extracted widths
     */
    protected function setCharWidths(array $cwidths): void
    {
        $this->fdt['MissingWidth'] = 600;
        if (! empty($cwidths[32])) {
            $this->fdt['MissingWidth'] = $cwidths[32];
        }

        $this->fdt['MaxWidth'] = (int) $this->fdt['MissingWidth'];
        $this->fdt['AvgWidth'] = 0;
        $this->fdt['cw'] = [];
        for ($cid = 0; $cid <= 255; ++$cid) {
            if (isset($cwidths[$cid])) {
                if ($cwidths[$cid] > $this->fdt['MaxWidth']) {
                    $this->fdt['MaxWidth'] = $cwidths[$cid];
                }

                $this->fdt['AvgWidth'] += $cwidths[$cid];
                $this->fdt['cw'][$cid] = $cwidths[$cid];
            } else {
                $this->fdt['cw'][$cid] = (int) $this->fdt['MissingWidth'];
            }
        }

        $this->fdt['AvgWidth'] = (int) round($this->fdt['AvgWidth'] / count($cwidths));
    }

    /**
     * Extract Metrics
     */
    protected function extractMetrics(): void
    {
        $cwd = [];
        $this->fdt['cbbox'] = [];
        $lines = explode("\n", str_replace("\r", '', $this->font));
        // process each row
        foreach ($lines as $line) {
            $col = explode(' ', rtrim($line));
            if (count($col) > 1) {
                $this->processMetricRow($col, $cwd);
            }
        }

        $this->fdt['Leading'] = 0;
        $this->setCharWidths($cwd);
    }

    /**
     * Extract Metrics
     *
     * @param array<int, string> $col Array containing row elements to process
     * @param array<int, int>    $cwd Array contianing cid widths
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function processMetricRow(array $col, array &$cwd): void
    {
        switch ($col[0]) {
            case 'IsFixedPitch':
                $this->fdt['IsFixedPitch'] = ($col[1] == 'true');
                break;
            case 'FontBBox':
                $this->fdt['FontBBox'] = [(int) $col[1], (int) $col[2], (int) $col[3], (int) $col[4]];
                break;
            case 'C':
                $cid = (int) $col[1];
                if ($cid >= 0) {
                    $cwd[$cid] = (int) $col[4];
                    if (! empty($col[14])) {
                        $this->fdt['cbbox'][$cid] = [(int) $col[10], (int) $col[11], (int) $col[12], (int) $col[13]];
                    }
                }

                break;
            case 'FontName':
            case 'FullName':
            case 'FamilyName':
            case 'Weight':
            case 'CharacterSet':
            case 'Version':
            case 'EncodingScheme':
                $this->fdt[$col[0]] = $col[1];
                break;
            case 'ItalicAngle':
            case 'UnderlinePosition':
            case 'UnderlineThickness':
            case 'CapHeight':
            case 'XHeight':
            case 'Ascender':
            case 'Descender':
            case 'StdHW':
            case 'StdVW':
                $this->fdt[$col[0]] = (int) $col[1];
                break;
        }
    }

    /**
     * Map values to the correct key name
     */
    protected function remapValues(): void
    {
        // rename properties
        $this->fdt['name'] = $this->fdt['FullName'];
        $this->fdt['underlinePosition'] = $this->fdt['UnderlinePosition'];
        $this->fdt['underlineThickness'] = $this->fdt['UnderlineThickness'];
        $this->fdt['italicAngle'] = $this->fdt['ItalicAngle'];
        $this->fdt['Ascent'] = $this->fdt['Ascender'];
        $this->fdt['Descent'] = $this->fdt['Descender'];
        $this->fdt['StemV'] = $this->fdt['StdVW'];
        $this->fdt['StemH'] = $this->fdt['StdHW'];

        $name = preg_replace('/[^a-zA-Z0-9_\-]/', '', $this->fdt['name']);
        if ($name === null) {
            throw new FontException('Invalid font name');
        }

        $this->fdt['name'] = $name;
        $this->fdt['bbox'] = implode(' ', $this->fdt['FontBBox']);

        if (empty($this->fdt['XHeight'])) {
            $this->fdt['XHeight'] = 0;
        }
    }

    protected function setMissingValues(): void
    {
        $this->fdt['Descender'] = $this->fdt['FontBBox'][1];

        $this->fdt['Ascender'] = $this->fdt['FontBBox'][3];

        if (empty($this->fdt['CapHeight'])) {
            $this->fdt['CapHeight'] = $this->fdt['Ascender'];
        }
    }

    /**
     * Process Core font
     */
    protected function process(): void
    {
        $this->extractMetrics();
        $this->setFlags();
        $this->setMissingValues();
        $this->remapValues();
    }
}
