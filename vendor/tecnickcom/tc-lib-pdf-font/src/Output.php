<?php

/**
 * Output.php
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

use Com\Tecnick\Pdf\Encrypt\Encrypt;
use Com\Tecnick\Pdf\Font\Exception as FontException;

/**
 * Com\Tecnick\Pdf\Font\Output
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
class Output extends \Com\Tecnick\Pdf\Font\OutFont
{
    /**
     * Array of character subsets for each font file
     *
     * @var array<string, array<int, bool>>
     */
    protected array $subchars = [];

    /**
     * PDF string block with the fonts definitions
     */
    protected string $out = '';

    /**
     * Initialize font data
     *
     * @param array<string, TFontData> $fonts   Array of imported fonts data
     * @param int                     $pon     Current PDF Object Number
     * @param Encrypt                 $encrypt Encrypt object
     */
    public function __construct(
        protected array $fonts,
        int $pon,
        Encrypt $encrypt
    ) {
        $this->pon = $pon;
        $this->enc = $encrypt;

        $this->out = $this->getEncodingDiffs();
        $this->out .= $this->getFontFiles();
        $this->out .= $this->getFontDefinitions();
    }

    /**
     * Returns current PDF object number
     */
    public function getObjectNumber(): int
    {
        return $this->pon;
    }

    /**
     * Returns the PDF fonts block
     */
    public function getFontsBlock(): string
    {
        return $this->out;
    }

    /**
     * Get the PDF output string for font encoding diffs
     *
     * return string
     */
    protected function getEncodingDiffs(): string
    {
        $out = '';
        $done = []; // store processed items to avoid duplication
        foreach ($this->fonts as $fkey => $font) {
            if (! empty($font['diff'])) {
                $dkey = md5($font['diff']);
                if (! isset($done[$dkey])) {
                    $out .= (++$this->pon) . ' 0 obj' . "\n"
                        . '<< /Type /Encoding /BaseEncoding /WinAnsiEncoding /Differences ['
                        . $font['diff'] . '] >>' . "\n"
                        . 'endobj' . "\n";
                    $done[$dkey] = $this->pon;
                }

                $this->fonts[$fkey]['diff_n'] = $done[$dkey];
            }

            // extract the character subset
            if (! empty($font['file'])) {
                $file_key = md5($font['file']);
                if (empty($this->subchars[$file_key])) {
                    $this->subchars[$file_key] = $font['subsetchars'];
                } else {
                    $this->subchars[$file_key] = array_merge($this->subchars[$file_key], $font['subsetchars']);
                }
            }
        }

        return $out;
    }

    /**
     * Get the PDF output string for font files
     *
     * return string
     */
    protected function getFontFiles(): string
    {
        $out = '';
        $done = []; // store processed items to avoid duplication
        foreach ($this->fonts as $fkey => $font) {
            if (! empty($font['file'])) {
                $dkey = md5($font['file']);
                if (! isset($done[$dkey])) {
                    $fontfile = $this->getFontFullPath($font['dir'], $font['file']);
                    $font_data = file_get_contents($fontfile);
                    if ($font_data === false) {
                        throw new FontException('Unable to read font file: ' . $fontfile);
                    }

                    if ($font['subset']) {
                        $font_data = gzuncompress($font_data);
                        if ($font_data === false) {
                            throw new FontException('Unable to uncompress font file: ' . $fontfile);
                        }

                        $sub = new Subset($font_data, $font, $this->subchars[md5($font['file'])]);
                        $font_data = $sub->getSubsetFont();
                        $font['length1'] = strlen($font_data);
                        $font_data = gzcompress($font_data);
                        if ($font_data === false) {
                            throw new FontException('Unable to compress font file: ' . $fontfile);
                        }
                    }

                    ++$this->pon;
                    $stream = $this->enc->encryptString($font_data, $this->pon);
                    $out .= $this->pon . ' 0 obj' . "\n"
                        . '<<'
                        . ' /Filter /FlateDecode'
                        . ' /Length ' . strlen($stream)
                        . ' /Length1 ' . $font['length1'];
                    $out .= ' /Length2 ' . $font['length2']
                        . ' /Length3 0';

                    $out .= ' >> stream' . "\n"
                        . $stream . "\n"
                        . 'endstream' . "\n"
                        . 'endobj' . "\n";
                    $done[$dkey] = $this->pon;
                }

                $this->fonts[$fkey]['file_n'] = $done[$dkey];
            }
        }

        return $out;
    }

    /**
     * Get the PDF output string for fonts
     *
     * return string
     */
    protected function getFontDefinitions(): string
    {
        $out = '';
        foreach ($this->fonts as $font) {
            $out .= match (strtolower($font['type'])) {
                'core' => $this->getCore($font),
                'cidfont0' => $this->getCid0($font),
                'type1' => $this->getTrueType($font),
                'truetype' => $this->getTrueType($font),
                'truetypeunicode' => $this->getTrueTypeUnicode($font),
                default => throw new FontException('Unsupported font type: ' . $font['type']),
            };
        }

        return $out;
    }
}
