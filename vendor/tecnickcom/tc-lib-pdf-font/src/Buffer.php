<?php

/**
 * Buffer.php
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

use Com\Tecnick\Pdf\Font\Exception as FontException;

/**
 * Com\Tecnick\Pdf\Font\Buffer
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
abstract class Buffer
{
    /**
     * Array containing all fonts data
     *
     * @var array<string, TFontData>
     */
    protected array $font = [];

    /**
     * Font counter
     */
    protected int $numfonts = 0;

    /**
     * Array containing encoding differences
     *
     * @var array<int, string>
     */
    protected array $encdiff = [];

    /**
     * Index for Encoding differences
     */
    protected int $numdiffs = 0;

    /**
     * Array containing font definitions grouped by file
     *
     * @var array<string, array{
     *          'dir': string,
     *          'keys': array<string>,
     *          'length1': int,
     *          'length2': int,
     *          'subset': bool,
     *      }>
     */
    protected array $file = [];

    /**
     * Initialize fonts buffer
     *
     * @param float $kunit   Unit of measure conversion ratio.
     * @param bool  $subset  If true embedd only a subset of the fonts
     *                       (stores only the information related to
     *                       the used characters); If false embedd
     *                       full font; This option is valid only for
     *                       TrueTypeUnicode fonts and it is disabled
     *                       for PDF/A. If you want to enable users to
     *                       modify the document, set this parameter
     *                       to false. If you subset the font, the
     *                       person who receives your PDF would need
     *                       to have your same font in order to make
     *                       changes to your PDF. The file size of the
     *                       PDF would also be smaller because you are
     *                       embedding only a subset. NOTE: This
     *                       option is computational and memory
     *                       intensive.
     * @param bool  $unicode True if we are in Unicode mode, False otherwhise.
     * @param bool  $pdfa    True if we are in PDF/A mode.
     *
     * @return string Font key
     *
     * @throws FontException in case of error
     */
    public function __construct(
        protected float $kunit,
        protected bool $subset = false,
        protected bool $unicode = true,
        protected bool $pdfa = false
    ) {
    }

    /**
     * Get the default subset mode
     */
    public function isSubsetMode(): bool
    {
        return $this->subset;
    }

    /**
     * Returns the fonts buffer
     *
     * @return array<string, TFontData>
     */
    public function getFonts(): array
    {
        return $this->font;
    }

    /**
     * Returns the fonts buffer
     *
     * @return array<int, string>
     */
    public function getEncDiffs(): array
    {
        return $this->encdiff;
    }

    /**
     * Returns true if the specified font key exist on buffer
     *
     * @param string $key Font key
     */
    public function isValidKey(string $key): bool
    {
        return isset($this->font[$key]);
    }

    /**
     * Get font by key
     *
     * @param string $key Font key
     *
     * @return TFontData Returns the fonts array.
     *
     * @throws FontException in case of error
     */
    public function getFont(string $key): array
    {
        if (! isset($this->font[$key])) {
            throw new FontException('The font ' . $key . ' has not been loaded');
        }

        return $this->font[$key];
    }

    /**
     * Add a character to the subset list
     *
     * @param string $key  The font key
     * @param int    $char The Unicode character value to add
     */
    public function addSubsetChar(string $key, int $char): void
    {
        if (! isset($this->font[$key])) {
            throw new FontException('The font ' . $key . ' has not been loaded');
        }

        $this->font[$key]['subsetchars'][$char] = true;
    }

    /**
     * Add a new font to the fonts buffer
     *
     * The definition file (and the font file itself when embedding) must be present either in the current directory
     * or in the one indicated by K_PATH_FONTS if the constant is defined.
     *
     * @param int    $objnum Current PDF object number
     * @param string $font   Font family.
     *                       If it is a standard family name, it will override the corresponding font.
     * @param string $style  Font style.
     *                       Possible values are (case insensitive):
     *                       regular (default)
     *                       B: bold
     *                       I: italic
     *                       U: underline
     *                       D: strikeout (linethrough)
     *                       O: overline
     * @param string $ifile  The font definition file (or empty for autodetect).
     *                       By default, the name is built from the family and style, in lower case with no spaces.
     * @param ?bool  $subset If true embed only a subset of the font
     *                       (stores only the information related to
     *                       the used characters); If false embed
     *                       full font; This option is valid only
     *                       for TrueTypeUnicode fonts and it is
     *                       disabled for PDF/A. If you want to
     *                       enable users to modify the document,
     *                       set this parameter to false. If you
     *                       subset the font, the person who
     *                       receives your PDF would need to have
     *                       your same font in order to make changes
     *                       to your PDF. The file size of the PDF
     *                       would also be smaller because you are
     *                       embedding only a subset. Set this to
     *                       null to use the default value. NOTE:
     *                       This option is computational and memory
     *                       intensive.
     *
     * @return string Font key
     *
     * @throws FontException in case of error
     */
    public function add(
        int &$objnum,
        string $font,
        string $style = '',
        string $ifile = '',
        ?bool $subset = null
    ) {
        if ($subset === null) {
            $subset = $this->subset;
        }

        $fobj = new Font($font, $style, $ifile, $subset, $this->unicode, $this->pdfa);
        $key = $fobj->getFontkey();

        if (isset($this->font[$key])) {
            return $key;
        }

        $fobj->load();
        $this->font[$key] = $fobj->getFontData();

        $this->setFontFile($key);
        $this->setFontDiff($key);

        $this->font[$key]['i'] = ++$this->numfonts;
        $this->font[$key]['n'] = ++$objnum;

        return $key;
    }

    /**
     * Set font file and subset
     *
     * @param string $key Font key
     */
    protected function setFontFile(string $key): void
    {
        if (empty($this->font[$key]['file'])) {
            return;
        }

        $file = $this->font[$key]['file'];
        if (! isset($this->file[$file])) {
            $this->file[$file] = [
                'dir' => '',
                'keys' => [],
                'length1' => 0,
                'length2' => 0,
                'subset' => false,
            ];
        }

        if (! in_array($key, $this->file[$file]['keys'])) {
            $this->file[$file]['keys'][] = $key;
        }

        $this->file[$file]['dir'] = $this->font[$key]['dir'];
        $this->file[$file]['length1'] = $this->font[$key]['length1'];
        $this->file[$file]['length2'] = $this->font[$key]['length2'];
        $this->file[$file]['subset'] = ($this->file[$file]['subset'] && $this->font[$key]['subset']);
    }

    /**
     * Set font diff
     *
     * @param string $key Font key
     */
    protected function setFontDiff(string $key): void
    {
        if (empty($this->font[$key]['diff'])) {
            return;
        }

        $diffid = array_search($this->font[$key]['diff'], $this->encdiff, true);
        if ($diffid === false) {
            $diffid = ++$this->numdiffs;
            $this->encdiff[$diffid] = $this->font[$key]['diff'];
        }

        $this->font[$key]['diffid'] = $diffid;
    }
}
