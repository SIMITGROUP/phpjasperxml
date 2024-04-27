<?php

/**
 * Style.php
 *
 * @since     2011-05-23
 * @category  Library
 * @package   PdfGraph
 * @author    Nicola Asuni <info@tecnick.com>
 * @copyright 2011-2024 Nicola Asuni - Tecnick.com LTD
 * @license   http://www.gnu.org/copyleft/lesser.html GNU-LGPL v3 (see LICENSE.TXT)
 * @link      https://github.com/tecnickcom/tc-lib-pdf-graph
 *
 * This file is part of tc-lib-pdf-graph software library.
 */

namespace Com\Tecnick\Pdf\Graph;

use Com\Tecnick\Pdf\Graph\Exception as GraphException;

/**
 * Com\Tecnick\Pdf\Graph\Style
 *
 * @since     2011-05-23
 * @category  Library
 * @package   PdfGraph
 * @author    Nicola Asuni <info@tecnick.com>
 * @copyright 2011-2024 Nicola Asuni - Tecnick.com LTD
 * @license   http://www.gnu.org/copyleft/lesser.html GNU-LGPL v3 (see LICENSE.TXT)
 * @link      https://github.com/tecnickcom/tc-lib-pdf-graph
 *
 * @phpstan-import-type StyleDataOpt from \Com\Tecnick\Pdf\Graph\Base
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
abstract class Style extends \Com\Tecnick\Pdf\Graph\Base
{
    /**
     * Array of restore points (style ID).
     *
     * @var array<int>
     */
    protected array $stylemark = [0];

    /**
     * Map values for lineCap.
     *
     * @var array<int|string, int>
     */
    protected const LINECAPMAP = [
        0 => 0,
        1 => 1,
        2 => 2,
        'butt' => 0,
        'round' => 1,
        'square' => 2,
    ];

    /**
     * Map values for lineJoin.
     *
     * @var array<int|string, int>
     */
    protected const LINEJOINMAP = [
        0 => 0,
        1 => 1,
        2 => 2,
        'miter' => 0,
        'round' => 1,
        'bevel' => 2,
    ];

    /**
     * Map path paint operators.
     *
     * @var array<string, string>
     */
    protected const PPOPMAP = [
        'S' => 'S',
        'D' => 'S',
        's' => 's',
        'h S' => 's',
        'd' => 's',
        'f' => 'f',
        'F' => 'f',
        'h f' => 'h f',
        'f*' => 'f*',
        'F*' => 'f*',
        'h f*' => 'h f*',
        'B' => 'B',
        'FD' => 'B',
        'DF' => 'B',
        'B*' => 'B*',
        'F*D' => 'B*',
        'DF*' => 'B*',
        'b' => 'b',
        'h B' => 'b',
        'fd' => 'b',
        'df' => 'b',
        'b*' => 'b*',
        'h B*' => 'b*',
        'f*d' => 'b*',
        'df*' => 'b*',
        'W n' => 'W n',
        'CNZ' => 'W n',
        'W* n' => 'W* n',
        'CEO' => 'W* n',
        'h' => 'h',
        'n' => 'n',
    ];

    /**
     * Filling modes.
     *
     * @var array<string, bool>
     */
    protected const MODEFILLING = [
        'f' => true,
        'f*' => true,
        'B' => true,
        'B*' => true,
        'b' => true,
        'b*' => true,
    ];

    /**
     * Stroking Modes.
     *
     * @var array<string, bool>
     */
    protected const MODESTROKING = [
        'S' => true,
        's' => true,
        'B' => true,
        'B*' => true,
        'b' => true,
        'b*' => true,
    ];

    /**
     * Closing Modes.
     *
     * @var array<string, bool>
     */
    protected const MODECLOSING = [
        'b' => true,
        'b*' => true,
        's' => true,
    ];

    /**
     * Clipping Modes.
     *
     * @var array<string, bool>
     */
    protected const MODECLIPPING = [
        'CEO' => true,
        'CNZ' => true,
        'W n' => true,
        'W* n' => true,
    ];

    /**
     * Map of equivalent modes without close.
     *
     * @var array<string, string>
     */
    protected const MODETONOCLOSE = [
        's' => 'S',
        'b' => 'B',
        'b*' => 'B*',
    ];

    /**
     * Map of equivalent modes without fill.
     *
     * @var array<string, string>
     */
    protected const MODETONOFILL = [
        'f' => '',
        'f*' => '',
        'B' => 'S',
        'B*' => 'S',
        'b' => 's',
        'b*' => 's',
    ];

    /**
     * Map of equivalent modes without STROKE.
     *
     * @var array<string, string>
     */
    protected const MODETONOSTROKE = [
        'S' => '',
        's' => 'h',
        'B' => 'f',
        'B*' => 'f*',
        'b' => 'h f',
        'b*' => 'h f*',
    ];

    /**
     * Add a new style
     *
     * @param StyleDataOpt $style       Style to add.
     * @param bool         $inheritlast If true inherit missing values from the last style.
     *
     * @return string PDF style string
     */
    public function add(array $style = [], bool $inheritlast = false): string
    {
        if ($inheritlast) {
            $style = array_merge($this->style[$this->styleid], $style);
        }

        $this->style[++$this->styleid] = $style;
        return $this->getStyle();
    }

    /**
     * Remove and return last style.
     *
     * @return string PDF style string.
     */
    public function pop(): string
    {
        if ($this->styleid <= 0) {
            throw new GraphException('The style stack is empty');
        }

        $style = $this->getStyle();
        unset($this->style[$this->styleid]);
        --$this->styleid;
        return $style;
    }

    /**
     * Save the current style ID to be restored later.
     */
    public function saveStyleStatus(): void
    {
        $this->stylemark[] = $this->styleid;
    }

    /**
     * Restore the saved style status.
     */
    public function restoreStyleStatus(): void
    {
        $styleid = array_pop($this->stylemark);
        if ($styleid === null) {
            $styleid = 0;
        }

        $this->styleid = $styleid;

        $this->style = array_slice($this->style, 0, ($this->styleid + 1), true);
    }

    /**
     * Returns the last style array.
     *
     * @return StyleDataOpt
     */
    public function getCurrentStyleArray(): array
    {
        return $this->style[$this->styleid];
    }

    /**
     * Returns the last set value of the specified property.
     *
     * @param string $property Property to search.
     * @param mixed  $default  Default value to return in case the property is not found.
     *
     * @return mixed Property value or $default in case the property is not found.
     */
    public function getLastStyleProperty(string $property, mixed $default = null): mixed
    {
        for ($idx = $this->styleid; $idx >= 0; --$idx) {
            if (isset($this->style[$idx][$property])) {
                return $this->style[$idx][$property];
            }
        }

        return $default;
    }

    /**
     * Returns the value of th especified item from the last inserted style.
     *
     * @param string $item Item to search.
     */
    public function getCurrentStyleItem(string $item): mixed
    {
        if (! isset($this->style[$this->styleid][$item])) {
            throw new GraphException('The ' . $item . ' value is not set in the current style');
        }

        return $this->style[$this->styleid][$item];
    }

    /**
     * Returns the PDF string of the last style added.
     */
    public function getStyle(): string
    {
        return $this->getStyleCmd($this->style[$this->styleid]);
    }

    /**
     * Returns the PDF string of the specified style.
     *
     * @param StyleDataOpt $style Style to represent.
     */
    public function getStyleCmd(array $style = []): string
    {
        $out = '';
        if (isset($style['lineWidth'])) {
            $out .= sprintf('%F w' . "\n", ($style['lineWidth'] * $this->kunit));
        }

        $out .= $this->getLineModeCmd($style);

        if (isset($style['lineColor'])) {
            $out .= $this->pdfColor->getPdfColor($style['lineColor'], true);
        }

        if (isset($style['fillColor'])) {
            $out .= $this->pdfColor->getPdfColor($style['fillColor'], false);
        }

        return $out;
    }

    /**
     * Returns the PDF string of the specified line style.
     *
     * @param StyleDataOpt $style Style to represent.
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function getLineModeCmd(array $style = []): string
    {
        $out = '';

        if (isset($style['lineCap']) && isset(self::LINECAPMAP[$style['lineCap']])) {
            $out .= self::LINECAPMAP[$style['lineCap']] . ' J' . "\n";
        }

        if (isset($style['lineJoin']) && isset(self::LINEJOINMAP[$style['lineJoin']])) {
            $out .= self::LINEJOINMAP[$style['lineJoin']] . ' j' . "\n";
        }

        if (isset($style['miterLimit'])) {
            $out .= sprintf('%F M' . "\n", ($style['miterLimit'] * $this->kunit));
        }

        if (isset($style['dashArray'])) {
            $dash = [];
            foreach ($style['dashArray'] as $val) {
                $dash[] = sprintf('%F', ((float) $val * $this->kunit));
            }

            if (! isset($style['dashPhase'])) {
                $style['dashPhase'] = 0;
            }

            return $out .= sprintf('[%s] %F d' . "\n", implode(' ', $dash), $style['dashPhase']);
        }

        return $out;
    }

    /**
     * Get the Path-Painting Operators.
     *
     * @param string $mode    Mode of rendering. Possible values are:
     *                        - S or D: Stroke the path. - s or d:
     *                        Close and stroke the path. - f or F:
     *                        Fill the path, using the nonzero
     *                        winding number rule to determine the
     *                        region to fill. - f* or F*: Fill the
     *                        path, using the even-odd rule to
     *                        determine the region to fill. - B or FD
     *                        or DF: Fill and then stroke the path,
     *                        using the nonzero winding number rule
     *                        to determine the region to fill. - B*
     *                        or F*D or DF*: Fill and then stroke the
     *                        path, using the even-odd rule to
     *                        determine the region to fill. - b or fd
     *                        or df: Close, fill, and then stroke the
     *                        path, using the nonzero winding number
     *                        rule to determine the region to fill. -
     *                        b or f*d or df*: Close, fill, and then
     *                        stroke the path, using the even-odd
     *                        rule to determine the region to fill. -
     *                        CNZ: Clipping mode using the even-odd
     *                        rule to determine which regions lie
     *                        inside the clipping path. - CEO:
     *                        Clipping mode using the nonzero winding
     *                        number rule to determine which regions
     *                        lie inside the clipping path - n: End
     *                        the path object without filling or
     *                        stroking it.
     * @param string $default Default style
     */
    public function getPathPaintOp(string $mode, string $default = 'S'): string
    {
        if (! isset(self::PPOPMAP[$mode])) {
            $mode = $default;
        }

        if (! isset(self::PPOPMAP[$mode])) {
            return '';
        }

        return self::PPOPMAP[$mode] . "\n";
    }

    /**
     * Returns true if the specified path paint operator includes the filling option.
     *
     * @param string $mode Path paint operator (mode of rendering).
     */
    public function isFillingMode(string $mode): bool
    {
        return (isset(self::PPOPMAP[$mode]) && self::PPOPMAP[$mode] !== ''
            && (isset(self::MODEFILLING[self::PPOPMAP[$mode]])
            || $this->isClippingMode($mode))
        );
    }

    /**
     * Returns true if the specified mode includes the stroking option.
     *
     * @param string $mode Path paint operator (mode of rendering).
     */
    public function isStrokingMode(string $mode): bool
    {
        return (isset(self::PPOPMAP[$mode]) && self::PPOPMAP[$mode] !== ''
            && isset(self::MODESTROKING[self::PPOPMAP[$mode]])
        );
    }

    /**
     * Returns true if the specified mode includes "closing the path" option.
     *
     * @param string $mode Path paint operator (mode of rendering).
     */
    public function isClosingMode(string $mode): bool
    {
        return (isset(self::PPOPMAP[$mode]) && self::PPOPMAP[$mode] !== ''
            && (isset(self::MODECLOSING[self::PPOPMAP[$mode]])
            || $this->isClippingMode($mode))
        );
    }

    /**
     * Returns true if the specified mode is of clippping type.
     *
     * @param string $mode Path paint operator (mode of rendering).
     */
    public function isClippingMode(string $mode): bool
    {
        return (isset(self::PPOPMAP[$mode]) && self::PPOPMAP[$mode] !== ''
            && isset(self::MODECLIPPING[self::PPOPMAP[$mode]])
        );
    }

    /**
     * Remove the Close option from the specified Path paint operator.
     *
     * @param string $mode Path paint operator (mode of rendering).
     */
    public function getModeWithoutClose(string $mode): string
    {
        if (
            isset(self::PPOPMAP[$mode])
            && (self::PPOPMAP[$mode] !== '')
            && isset(self::MODETONOCLOSE[self::PPOPMAP[$mode]])
        ) {
            return self::MODETONOCLOSE[self::PPOPMAP[$mode]];
        }

        return $mode;
    }

    /**
     * Remove the Fill option from the specified Path paint operator.
     *
     * @param string $mode Path paint operator (mode of rendering).
     */
    public function getModeWithoutFill(string $mode): string
    {
        if (
            isset(self::PPOPMAP[$mode])
            && (self::PPOPMAP[$mode] !== '')
            && isset(self::MODETONOFILL[self::PPOPMAP[$mode]])
        ) {
            return self::MODETONOFILL[self::PPOPMAP[$mode]];
        }

        return $mode;
    }

    /**
     * Remove the Stroke option from the specified Path paint operator.
     *
     * @param string $mode Path paint operator (mode of rendering).
     */
    public function getModeWithoutStroke(string $mode): string
    {
        if (
            isset(self::PPOPMAP[$mode])
            && (self::PPOPMAP[$mode] !== '')
            && isset(self::MODETONOSTROKE[self::PPOPMAP[$mode]])
        ) {
            return self::MODETONOSTROKE[self::PPOPMAP[$mode]];
        }

        return $mode;
    }

    /**
     * Add transparency parameters to the current extgstate.
     *
     * @param array<string, mixed> $parms parameters.
     *
     * @return string PDF command.
     */
    public function getExtGState(array $parms): string
    {
        if ($this->pdfa) {
            return '';
        }

        $gsx = (count($this->extgstates) + 1);
        // check if this ExtGState already exist
        foreach ($this->extgstates as $idx => $ext) {
            if ($ext['parms'] == $parms) {
                $gsx = $idx;
                break;
            }
        }

        if (empty($this->extgstates[$gsx])) {
            $this->extgstates[$gsx] = [
                'n' => 0,
                'name' => '',
                'parms' => $parms,
            ];
        }

        return '/GS' . $gsx . ' gs' . "\n";
    }
}
