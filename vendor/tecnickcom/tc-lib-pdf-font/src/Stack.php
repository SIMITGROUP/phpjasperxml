<?php

/**
 * Stack.php
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

use Com\Tecnick\Unicode\Data\Type as UnicodeType;
use Com\Tecnick\Pdf\Font\Exception as FontException;

/**
 * Com\Tecnick\Pdf\Font\Stack
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
 * @phpstan-type TTextSplit array{
 *     'pos': int,
 *     'ord': int,
 *     'spaces': int,
 *     'septype': string,
 *     'wordwidth': float,
 *     'totwidth': float,
 *     'totspacewidth': float,
 * }
 *
 * @phpstan-type TTextDims array{
 *     'chars': int,
 *     'spaces': int,
 *     'words': int,
 *     'totwidth': float,
 *     'totspacewidth': float,
 *     'split': array<int, TTextSplit>,
 * }
 *
 * @phpstan-type TBBox array{float, float, float, float}
 *
 * @phpstan-type TStackItem array{
 *        'key': string,
 *        'style': string,
 *        'size': float,
 *        'spacing': float,
 *        'stretching': float,
 *    }
 *
 * @phpstan-type TFontMetric array{
 *     'ascent': float,
 *     'avgwidth': float,
 *     'capheight': float,
 *     'cbbox': array<int, TBBox>,
 *     'cratio': float,
 *     'cw': array<int, float>,
 *     'descent': float,
 *     'dw': float,
 *     'fbbox': array<int, float>,
 *     'height': float,
 *     'key': string,
 *     'maxwidth': float,
 *     'midpoint': float,
 *     'missingwidth': float,
 *     'out': string,
 *     'outraw': string,
 *     'size': float,
 *     'spacing': float,
 *     'stretching': float,
 *     'type': string,
 *     'up': float,
 *     'usize': float,
 *     'ut': float,
 *     'xheight': float,
 * }
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class Stack extends \Com\Tecnick\Pdf\Font\Buffer
{
    /**
     * Default font size in points
     */
    public const DEFAULT_SIZE = 10;

    /**
     * Array (stack) containing fonts in order of insertion.
     * The last item is the current font.
     *
     * @var array<int, TStackItem>
     */
    protected array $stack = [];

    /**
     * Current font index
     */
    protected int $index = -1;

    /**
     * Array containing font metrics for each fontkey-size combination.
     *
     * @var array<string, TFontMetric>
     */
    protected array $metric = [];

    /**
     * Insert a font into the stack
     *
     * The definition file (and the font file itself when embedding) must be present either in the current directory
     * or in the one indicated by K_PATH_FONTS if the constant is defined.
     *
     * @param int    $objnum     Current PDF object number
     * @param string $font       Font family, or comma separated list of font families
     *                           If it is a standard family name, it will override the corresponding font.
     * @param string $style      Font style.
     *                           Possible values are (case insensitive):
     *                           regular (default)
     *                           B: bold
     *                           I: italic
     *                           U: underline
     *                           D: strikeout (linethrough)
     *                           O: overline
     * @param ?int   $size       Font size in points (set to null to inherit the last font size).
     * @param ?float $spacing    Extra spacing between characters.
     * @param ?float $stretching Horizontal character stretching ratio.
     * @param string $ifile      The font definition file (or empty for autodetect).
     *                           By default, the name is built from the family and style, in lower case with no spaces.
     * @param ?bool  $subset     If true embedd only a subset of the font
     *                           (stores only the information related to
     *                           the used characters); If false embedd
     *                           full font; This option is valid only for
     *                           TrueTypeUnicode fonts and it is disabled
     *                           for PDF/A. If you want to enable users
     *                           to modify the document, set this
     *                           parameter to false. If you subset the
     *                           font, the person who receives your PDF
     *                           would need to have your same font in
     *                           order to make changes to your PDF. The
     *                           file size of the PDF would also be
     *                           smaller because you are embedding only a
     *                           subset. Set this to null to use the
     *                           default value. NOTE: This option is
     *                           computational and memory intensive.
     *
     * @return TFontMetric Font data
     *
     * @throws FontException in case of error
     */
    public function insert(
        int &$objnum,
        string $font,
        string $style = '',
        ?int $size = null,
        ?float $spacing = null,
        ?float $stretching = null,
        string $ifile = '',
        ?bool $subset = null
    ): array {
        if ($subset === null) {
            $subset = $this->subset;
        }

        $size = $this->getInputSize($size);
        $spacing = $this->getInputSpacing($spacing);
        $stretching = $this->getInputStretching($stretching);

        // try to load the corresponding imported font
        $err = null;
        $keys = $this->getNormalizedFontKeys($font);
        $fontkey = '';
        foreach ($keys as $key) {
            try {
                $fontkey = $this->add($objnum, $key, $style, $ifile, $subset);
                $err = null;
                break;
            } catch (FontException $exc) {
                $err = $exc;
            }
        }

        if ($err instanceof \Com\Tecnick\Pdf\Font\Exception) {
            throw new FontException($err->getMessage());
        }

        // add this font in the stack
        $data = $this->getFont($fontkey);

        $this->stack[++$this->index] = [
            'key' => $fontkey,
            'style' => $data['style'],
            'size' => $size,
            'spacing' => $spacing,
            'stretching' => $stretching,
        ];

        return $this->getFontMetric($this->stack[$this->index]);
    }

    /**
     * Returns the current font data array
     *
     * @return TFontMetric
     */
    public function getCurrentFont(): array
    {
        return $this->getFontMetric($this->stack[$this->index]);
    }

    /**
     * Returns the current font type (i.e.: Core, TrueType, TrueTypeUnicode, Type1).
     */
    public function getCurrentFontType(): string
    {
        return $this->getFont($this->stack[$this->index]['key'])['type'];
    }

    /**
     * Returns the PDF code to use the current font.
     */
    public function getOutCurrentFont(): string
    {
        return $this->getFontMetric($this->stack[$this->index])['out'];
    }

    /**
     * Returns true if the current font type is Core, TrueType or Type1.
     */
    public function isCurrentByteFont(): bool
    {
        $currentFontType = $this->getCurrentFontType();
        return (($currentFontType == 'Core') || ($currentFontType == 'TrueType') || ($currentFontType == 'Type1'));
    }

    /**
     * Returns true if the current font type is TrueTypeUnicode or cidfont0.
     */
    public function isCurrentUnicodeFont(): bool
    {
        $currentFontType = $this->getCurrentFontType();
        return (($currentFontType == 'TrueTypeUnicode') || ($currentFontType == 'cidfont0'));
    }

    /**
     * Remove and return the last inserted font
     *
     * @return TFontMetric
     */
    public function popLastFont(): array
    {
        if (($this->index < 0) || ($this->stack === [])) {
            throw new FontException('The font stack is empty');
        }

        $font = array_pop($this->stack);
        --$this->index;
        return $this->getFontMetric($font);
    }

    /**
     * Replace missing characters with selected substitutions
     *
     * @param array<int, int>        $uniarr Array of character codepoints.
     * @param array<int, array<int>> $subs   Array of possible character substitutions.
     *                                       The key is the character to check (integer value),
     *                                       the value is an array of possible substitutes.
     *
     * @return array<int, int> Array of character codepoints.
     */
    public function replaceMissingChars(array $uniarr, array $subs = []): array
    {
        $font = $this->getFontMetric($this->stack[$this->index]);
        foreach ($uniarr as $pos => $uni) {
            if (isset($font['cw'][$uni])) {
                continue;
            }

            if (! isset($subs[$uni])) {
                continue;
            }

            foreach ($subs[$uni] as $alt) {
                if (isset($font['cw'][$alt])) {
                    $uniarr[$pos] = $alt;
                    break;
                }
            }
        }

        return $uniarr;
    }

    /**
     * Returns true if the specified unicode value is defined in the current font
     *
     * @param int $ord Unicode character value to convert
     */
    public function isCharDefined(int $ord): bool
    {
        $font = $this->getFontMetric($this->stack[$this->index]);
        return isset($font['cw'][$ord]);
    }

    /**
     * Returns the width of the specified character
     *
     * @param int $ord Unicode character value.
     */
    public function getCharWidth(int $ord): float
    {
        if (($ord == 173) || ($ord == 8203)) {
            // 173 = SHY character is not printed, as it is used for text hyphenation
            // 8203 = ZWSP character
            return 0;
        }

        $font = $this->getFontMetric($this->stack[$this->index]);
        return $font['cw'][$ord] ?? $font['dw'];
    }

    /**
     * Returns the lenght of the string specified using an array of codepoints.
     *
     * @param array<int, int> $uniarr Array of character codepoints.
     */
    public function getOrdArrWidth(array $uniarr): float
    {
        return $this->getOrdArrDims($uniarr)['totwidth'];
    }

    /**
     * Returns various dimensions of the string specified using an array of codepoints.
     *
     * @param array<int, int> $uniarr Array of character codepoints.
     *
     * @return TTextDims
     */
    public function getOrdArrDims(array $uniarr): array
    {
        $chars = count($uniarr); // total number of chars
        $spaces = 0; // total number of spaces
        $totwidth = 0; // total string width
        $totspacewidth = 0; // total space width
        $words = 0; // total number of words
        $fact = ($this->stack[$this->index]['spacing'] * $this->stack[$this->index]['stretching']);
        $uniarr[] = 8203; // add null at the end to ensure that the last word is processed
        $split = [];
        foreach ($uniarr as $idx => $ord) {
            $unitype = UnicodeType::UNI[$ord];
            $chrwidth = $this->getCharWidth($ord);
            // 'B' Paragraph Separator
            // 'S' Segment Separator
            // 'WS' Whitespace
            // 'BN' Boundary Neutral
            if (($unitype == 'B') || ($unitype == 'S') || ($unitype == 'WS') || ($unitype == 'BN')) {
                $split[$words] = [
                    'pos' => $idx,
                    'ord' => $ord,
                    'spaces' => $spaces,
                    'septype' => $unitype,
                    'wordwidth' => 0,
                    'totwidth' => ($totwidth + ($fact * ($idx - 1))),
                    'totspacewidth' => ($totspacewidth + ($fact * ($spaces - 1))),
                ];
                if ($words > 0) {
                    $split[$words]['wordwidth'] = ($split[$words]['totwidth'] - $split[($words - 1)]['totwidth']);
                }
                $words++;
                if ($unitype == 'WS') {
                    ++$spaces;
                    $totspacewidth += $chrwidth;
                }
            }
            $totwidth += $chrwidth;
        }
        $totwidth += ($fact * ($chars - 1));
        $totspacewidth += ($fact * ($spaces - 1));
        return [
            'chars' => $chars,
            'spaces' => $spaces,
            'words' => $words,
            'totwidth' => $totwidth,
            'totspacewidth' => $totspacewidth,
            'split' => $split,
        ];
    }

    /**
     * Returns the glyph bounding box of the specified character in the current font in user units.
     *
     * @param int $ord Unicode character value.
     *
     * @return TBBox (xMin, yMin, xMax, yMax)
     */
    public function getCharBBox(int $ord): array
    {
        $font = $this->getFontMetric($this->stack[$this->index]);
        return $font['cbbox'][$ord] ?? [0.0, 0.0, 0.0, 0.0]; // glyph without outline
    }

    /**
     * Replace a char if it is defined on the current font.
     *
     * @param int $oldchar Integer code (unicode) of the character to replace.
     * @param int $newchar Integer code (unicode) of the new character.
     *
     * @return int the replaced char or the old char in case the new char i not defined
     */
    public function replaceChar(int $oldchar, int $newchar): int
    {
        if ($this->isCharDefined($newchar)) {
            // add the new char on the subset list
            $this->addSubsetChar($this->stack[$this->index]['key'], $newchar);
            // return the new character
            return $newchar;
        }

        // return the old char
        return $oldchar;
    }

    /**
     * Returns the font metrics associated to the input key.
     *
     * @param TStackItem $font Stack item
     *
     * @return TFontMetric
     */
    protected function getFontMetric(array $font): array
    {
        $mkey = md5(serialize($font));
        if (! empty($this->metric[$mkey])) {
            return $this->metric[$mkey];
        }

        $size = ($font['size']);
        $usize = ((float) $size / $this->kunit);
        $cratio = ((float) $size / 1000);
        $wratio = ($cratio * $font['stretching']); // horizontal ratio
        $data = $this->getFont($font['key']);
        $outfont = sprintf('/F%d %F Tf', $data['i'], $font['size']); // PDF output string
        // add this font in the stack wit metrics in internal units
        $this->metric[$mkey] = [
            'ascent' => ((float) $data['desc']['Ascent'] * $cratio),
            'avgwidth' => ((float) $data['desc']['AvgWidth'] * $cratio * $font['stretching']),
            'capheight' => ((float) $data['desc']['CapHeight'] * $cratio),
            'cbbox' => [],
            'cratio' => $cratio,
            'cw' => [],
            'descent' => ((float) $data['desc']['Descent'] * $cratio),
            'dw' => ((float) $data['dw'] * $cratio * $font['stretching']),
            'fbbox' => [],
            'height' => ((float) ($data['desc']['Ascent'] - $data['desc']['Descent']) * $cratio),
            'key' => $font['key'],
            'maxwidth' => ((float) $data['desc']['MaxWidth'] * $cratio * $font['stretching']),
            'midpoint' => ((float) ($data['desc']['Ascent'] + $data['desc']['Descent']) * $cratio / 2),
            'missingwidth' => ((float) $data['desc']['MissingWidth'] * $cratio * $font['stretching']),
            'out' => 'BT ' . $outfont . ' ET' . "\r",
            'outraw' => $outfont,
            'size' => $size,
            'spacing' => $font['spacing'],
            'stretching' => $font['stretching'],
            'type' => $data['type'],
            'up' => ((float) $data['up'] * $cratio),
            'usize' => $usize,
            'ut' => ((float) $data['ut'] * $cratio),
            'xheight' => ((float) $data['desc']['XHeight'] * $cratio),
        ];
        $tbox = explode(' ', substr($data['desc']['FontBBox'], 1, -1));
        $this->metric[$mkey]['fbbox'] = [
            // left
            ((float) $tbox[0] * $wratio),
            // bottom
            ((float) $tbox[1] * $cratio),
            // right
            ((float) $tbox[2] * $wratio),
            // top
            ((float) $tbox[3] * $cratio),
        ];
        //left, bottom, right, and top edges
        foreach ($data['cw'] as $cid => $width) {
            $this->metric[$mkey]['cw'][(int) $cid] = ((float) $width * $wratio);
        }

        if (is_array($data['cbbox'])) {
            foreach ($data['cbbox'] as $cid => $val) {
                if (! is_array($val)) {
                    continue;
                }

                if (count($val) != 4) {
                    continue;
                }

                $this->metric[$mkey]['cbbox'][(int) $cid] = [
                    // left
                    ((float) $val[0] * $wratio),
                    // bottom
                    ((float) $val[1] * $cratio),
                    // right
                    ((float) $val[2] * $wratio),
                    // top
                    ((float) $val[3] * $cratio),
                ];
            }
        }

        return $this->metric[$mkey];
    }

    /**
     * Normalize the input size
     *
     * @param ?int $size Font size in points (set to null to inherit the last font size).
     *
     *                   return float
     */
    protected function getInputSize(?int $size = null): float
    {
        if (($size === null) || ($size < 0)) {
            if ($this->index >= 0) {
                // inherit the size of the last inserted font
                return $this->stack[$this->index]['size'];
            }

            return self::DEFAULT_SIZE;
        }

        return max(0, (float) $size);
    }

    /**
     * Normalize the input spacing
     *
     * @param ?float $spacing Extra spacing between characters.
     *                        return float
     */
    protected function getInputSpacing(?float $spacing = null): float
    {
        if ($spacing === null) {
            if ($this->index >= 0) {
                // inherit the size of the last inserted font
                return $this->stack[$this->index]['spacing'];
            }

            return 0;
        }

        return ($spacing);
    }

    /**
     * Normalize the input stretching
     *
     * @param ?float $stretching Horizontal character stretching ratio.
     *                           return float
     */
    protected function getInputStretching(?float $stretching = null): float
    {
        if ($stretching === null) {
            if ($this->index >= 0) {
                // inherit the size of the last inserted font
                return $this->stack[$this->index]['stretching'];
            }

            return 1;
        }

        return ($stretching);
    }

    /**
     * Return normalized font keys
     *
     * @param string $fontfamily Property string containing comma-separated font family names
     *
     * @return array<string>
     */
    protected function getNormalizedFontKeys(string $fontfamily): array
    {
        if ($fontfamily === '') {
            throw new FontException('Empty font family name');
        }

        $keys = [];
        // remove spaces and symbols
        $fontfamily = preg_replace('/[^a-z0-9_\,]/', '', strtolower($fontfamily));
        if (($fontfamily === null) || (! is_string($fontfamily))) {
            throw new FontException('Invalid font family name: ' . $fontfamily);
        }

        // extract all font names
        $fontslist = preg_split('/[,]/', $fontfamily);
        if ($fontslist === false) {
            throw new FontException('Invalid font family name: ' . $fontfamily);
        }

        // replacement patterns

        $fontpattern = ['/regular$/', '/italic$/', '/oblique$/', '/bold([I]?)$/'];
        $fontreplacement = ['', 'I', 'I', 'B\\1'];

        $keypattern = ['/^serif|^cursive|^fantasy|^timesnewroman/', '/^sansserif/', '/^monospace/'];
        $keyreplacement = ['times', 'helvetica', 'courier'];

        // find first valid font name
        foreach ($fontslist as $font) {
            $font = preg_replace($fontpattern, $fontreplacement, $font);
            if ($font === null) {
                throw new FontException('Invalid font family name: ' . $fontfamily);
            }

            // replace common family names and core fonts
            $fontkey = preg_replace($keypattern, $keyreplacement, $font);
            if ($fontkey === null) {
                throw new FontException('Invalid font family name: ' . $fontfamily);
            }

            $keys[] = $fontkey;
        }

        return $keys;
    }
}
