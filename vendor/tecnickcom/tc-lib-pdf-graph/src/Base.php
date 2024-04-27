<?php

/**
 * Base.php
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

use Com\Tecnick\Color\Pdf as PdfColor;
use Com\Tecnick\Pdf\Encrypt\Encrypt;

/**
 * Com\Tecnick\Pdf\Graph\Base
 *
 * @since     2011-05-23
 * @category  Library
 * @package   PdfGraph
 * @author    Nicola Asuni <info@tecnick.com>
 * @copyright 2011-2024 Nicola Asuni - Tecnick.com LTD
 * @license   http://www.gnu.org/copyleft/lesser.html GNU-LGPL v3 (see LICENSE.TXT)
 * @link      https://github.com/tecnickcom/tc-lib-pdf-graph
 *
 * @phpstan-type StyleData array{
 *          'lineWidth': float,
 *          'lineCap': string,
 *          'lineJoin': string,
 *          'miterLimit': float,
 *          'dashArray': array<int>,
 *          'dashPhase': float,
 *          'lineColor': string,
 *          'fillColor': string,
 *      }
 *
 * @phpstan-type StyleDataOpt array{
 *          'lineWidth'?: float,
 *          'lineCap'?: string,
 *          'lineJoin'?: string,
 *          'miterLimit'?: float,
 *          'dashArray'?: array<int>,
 *          'dashPhase'?: float,
 *          'lineColor'?: string,
 *          'fillColor'?: string,
 *      }
 *
 * @phpstan-type GradientData array{
 *          'antialias': bool,
 *          'background': ?\Com\Tecnick\Color\Model,
 *          'colors': array<int, array{
 *              'color': string,
 *              'exponent'?: float,
 *              'opacity'?: float,
 *              'offset'?: float,
 *          }>,
 *          'colspace': string,
 *          'coords': array<float>,
 *          'id': int,
 *          'pattern': int,
 *          'stream': string,
 *          'transparency': bool,
 *          'type': int,
 *      }
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
abstract class Base
{
    /**
     * Pi constant
     * We use this instead of M_PI because HHVM has a different value.
     *
     * @var float
     */
    public const MPI = 3.14159265358979323846264338327950288419716939937510;

    /**
     * Current PDF object number
     */
    protected int $pon = 0;

    /**
     * Current page height
     */
    protected float $pageh = 0;

    /**
     * Current page width
     */
    protected float $pagew = 0;

    /**
     * Unit of measure conversion ratio
     */
    protected float $kunit = 1.0;

    /**
     * Stack index.
     */
    protected int $styleid = -1;

    /**
     * Stack containing style data.
     *
     * @var array<StyleDataOpt>
     */
    protected array $style = [];

    /**
     * Array of transparency objects and parameters.
     *
     * @var array<int, array{
     *          'n': int,
     *          'name': string,
     *          'parms': array<string, mixed>,
     *      }>
     */
    protected array $extgstates = [];

    /**
     * Array of gradients
     *
     * @var array<int, GradientData>
     */
    protected array $gradients = [];

    /**
     * Initialize
     *
     * @param float    $kunit    Unit of measure conversion ratio.
     * @param float    $pagew    Page width.
     * @param float    $pageh    Page height.
     * @param PdfColor $pdfColor Color object.
     * @param bool     $pdfa     True if we are in PDF/A mode.
     * @param bool     $compress Set to false to disable stream compression.
     */
    public function __construct(
        float $kunit,
        float $pagew,
        float $pageh,
        /**
         * Color object
         */
        protected PdfColor $pdfColor,
        /**
         * Encrypt object
         */
        protected Encrypt $encrypt,
        protected bool $pdfa = false,
        protected bool $compress = true
    ) {
        $this->setKUnit($kunit);
        $this->setPageWidth($pagew);
        $this->setPageHeight($pageh);
        $this->initStyle();
    }

    /**
     * Initialize default style
     */
    public function initStyle(): void
    {
        $this->style[++$this->styleid] = $this->getDefaultStyle();
    }

    /**
     * Returns the default style.
     *
     * @param StyleDataOpt $style Style parameters to merge to the default ones.
     *
     * @return StyleData
     */
    public function getDefaultStyle(array $style = []): array
    {
        $def = [
            // line thickness in user units
            'lineWidth' => (1.0 / $this->kunit),
            // shape of the endpoints for any open path that is stroked
            'lineCap' => 'butt',
            // shape of joints between connected segments of a stroked path
            'lineJoin' => 'miter',
            // maximum length of mitered line joins for stroked paths
            'miterLimit' => (10.0 / $this->kunit),
            // lengths of alternating dashes and gaps
            'dashArray' => [],
            // distance  at which to start the dash
            'dashPhase' => 0,
            // line (drawing) color
            'lineColor' => 'black',
            // background (filling) color
            'fillColor' => 'black',
        ];

        return array_merge($def, $style);
    }

    /**
     * Returns current PDF object number
     */
    public function getObjectNumber(): int
    {
        return $this->pon;
    }

    /**
     * Set page height
     *
     * @param float $pageh Page height
     */
    public function setPageHeight(float $pageh): static
    {
        $this->pageh = $pageh;
        return $this;
    }

    /**
     * Set page width
     *
     * @param float $pagew Page width
     */
    public function setPageWidth(float $pagew): static
    {
        $this->pagew = $pagew;
        return $this;
    }

    /**
     * Set unit of measure conversion ratio.
     *
     * @param float $kunit Unit of measure conversion ratio.
     */
    public function setKUnit(float $kunit): static
    {
        $this->kunit = $kunit;
        return $this;
    }

    /**
     * Get the PDF output string for ExtGState
     *
     * @param int $pon Current PDF Object Number
     *
     * @return string PDF command
     */
    public function getOutExtGState(int $pon): string
    {
        $this->pon = $pon;
        $out = '';
        foreach ($this->extgstates as $idx => $ext) {
            $this->extgstates[$idx]['n'] = ++$this->pon;
            $out .= $this->pon . ' 0 obj' . "\n"
                . '<< /Type /ExtGState';
            foreach ($ext['parms'] as $key => $val) {
                if (is_numeric($val)) {
                    $val = sprintf('%F', $val);
                } elseif ($val === true) {
                    $val = 'true';
                } elseif ($val === false) {
                    $val = 'false';
                }

                $out .= ' /' . $key . ' ' . $val;
            }

            $out .= ' >>' . "\n"
            . 'endobj' . "\n";
        }

        return $out;
    }

    /**
     * Get the PDF output string for ExtGState Resource Dictionary
     *
     * @return string PDF command
     */
    public function getOutExtGStateResources(): string
    {
        if ($this->pdfa || $this->extgstates === []) {
            return '';
        }

        $out = ' /ExtGState <<';
        foreach ($this->extgstates as $key => $ext) {
            if (! empty($ext['name'])) {
                $out .= ' /' . $ext['name'];
            } else {
                $out .= ' /GS' . $key;
            }

            $out .= ' ' . $ext['n'] . ' 0 R';
        }

        return $out . (' >>' . "\n");
    }

    /**
     * Get the PDF output string for Gradients Resource Dictionary
     *
     * @return string PDF command
     */
    public function getOutGradientResources(): string
    {
        if ($this->pdfa || $this->gradients === []) {
            return '';
        }

        $grp = '';
        $grs = '';
        foreach ($this->gradients as $idx => $grad) {
            // gradient patterns
            $grp .= ' /p' . $idx . ' ' . $grad['pattern'] . ' 0 R';
            // gradient shadings
            $grs .= ' /Sh' . $idx . ' ' . $grad['id'] . ' 0 R';
        }

        return ' /Pattern <<' . $grp . ' >>' . "\n"
            . ' /Shading <<' . $grs . ' >>' . "\n";
    }

    /**
     * Get the PDF output string for gradient colors and transparency
     *
     * @param GradientData $grad Array of gradient colors
     * @param string       $type Type of output: 'color' or 'opacity'
     *
     * @return string PDF command
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function getOutGradientCols(array $grad, string $type): string
    {
        if (($type == 'opacity') && ! $grad['transparency']) {
            return '';
        }

        $out = '';
        if (($grad['type'] == 2) || ($grad['type'] == 3)) {
            $num_cols = count($grad['colors']);
            $lastcols = ($num_cols - 1);
            $funct = []; // color and transparency objects
            $bounds = [];
            $encode = [];

            for ($idx = 1; $idx < $num_cols; ++$idx) {
                $col0 = $grad['colors'][($idx - 1)][$type];
                $col1 = $grad['colors'][$idx][$type];
                if ($type == 'color') {
                    $col0 = $this->pdfColor->getColorObject($grad['colors'][($idx - 1)][$type]);
                    $col1 = $this->pdfColor->getColorObject($grad['colors'][$idx][$type]);
                    if (! $col0 instanceof \Com\Tecnick\Color\Model) {
                        continue;
                    }

                    if (! $col1 instanceof \Com\Tecnick\Color\Model) {
                        continue;
                    }

                    $col0 = $col0->getComponentsString();
                    $col1 = $col1->getComponentsString();
                }

                $encode[] = '0 1';
                if ($idx < $lastcols && isset($grad['colors'][$idx]['offset'])) {
                    $bounds[] = sprintf('%F ', $grad['colors'][$idx]['offset']);
                }

                $out .= ++$this->pon . ' 0 obj' . "\n"
                . '<<'
                . ' /FunctionType 2'
                . ' /Domain [0 1]'
                . ' /C0 [' . $col0 . ']'
                . ' /C1 [' . $col1 . ']';
                if (isset($grad['colors'][$idx]['exponent'])) {
                    $out .= ' /N ' . $grad['colors'][$idx]['exponent'];
                }

                $out .= ' >>' . "\n"
                . 'endobj' . "\n";
                $funct[] = $this->pon . ' 0 R';
            }

            $out .= ++$this->pon . ' 0 obj' . "\n"
                . '<<'
                . ' /FunctionType 3'
                . ' /Domain [0 1]'
                . ' /Functions [' . implode(' ', $funct) . ']'
                . ' /Bounds [' . implode(' ', $bounds) . ']'
                . ' /Encode [' . implode(' ', $encode) . ']'
                . ' >>' . "\n"
                . 'endobj' . "\n";
        }

        return $out . $this->getOutPatternObj($grad, $this->pon);
    }

    /**
     * Get the PDF output string for the pattern and shading object
     *
     * @param GradientData $grad   Array of gradient colors
     * @param int          $objref Refrence object number
     *
     * @return string PDF command
     */
    protected function getOutPatternObj(array $grad, int $objref): string
    {
        // set shading object
        if ($grad['transparency']) {
            $grad['colspace'] = 'DeviceGray';
        }

        $oid = ++$this->pon;
        $out = $oid . ' 0 obj' . "\n"
            . '<<'
            . ' /ShadingType ' . $grad['type']
            . ' /ColorSpace /' . $grad['colspace'];
        if (! empty($grad['background'])) {
            $out .= ' /Background [' . $grad['background']->getComponentsString() . ']';
        }

        if ($grad['antialias']) {
            $out .= ' /AntiAlias true';
        }

        if ($grad['type'] == 2) {
            $out .= ' ' . sprintf(
                '/Coords [%F %F %F %F]',
                $grad['coords'][0],
                $grad['coords'][1],
                $grad['coords'][2],
                $grad['coords'][3]
            )
                . ' /Domain [0 1]'
                . ' /Function ' . $objref . ' 0 R'
                . ' /Extend [true true]'
                . ' >>' . "\n";
        } elseif ($grad['type'] == 3) {
            // x0, y0, r0, x1, y1, r1
            // the  radius of the inner circle is 0
            $out .= ' ' . sprintf(
                '/Coords [%F %F 0 %F %F %F]',
                $grad['coords'][0],
                $grad['coords'][1],
                $grad['coords'][2],
                $grad['coords'][3],
                $grad['coords'][4]
            )
                . ' /Domain [0 1]'
                . ' /Function ' . $objref . ' 0 R'
                . ' /Extend [true true]'
                . ' >>' . "\n";
        } elseif ($grad['type'] == 6) {
            $stream = $this->encrypt->encryptString($grad['stream'], $this->pon);
            $out .= ' /BitsPerCoordinate 16 /BitsPerComponent 8/Decode[0 1 0 1 0 1 0 1 0 1] /BitsPerFlag 8 /Length '
                . strlen($stream)
                . ' >>' . "\n"
                . ' stream' . "\n"
                . $stream . "\n"
                . 'endstream' . "\n";
        }

        $out .= 'endobj' . "\n";

        // pattern object
        $out .= ++$this->pon . ' 0 obj' . "\n"
            . '<<'
            . ' /Type /Pattern'
            . ' /PatternType 2'
            . ' /Shading ' . $oid . ' 0 R'
            . ' >>' . "\n"
            . 'endobj'
            . "\n";

        return $out;
    }

    /**
     * Get the PDF output string for gradient shaders
     *
     * @param int $pon Current PDF Object Number
     *
     * @return string PDF command
     */
    public function getOutGradientShaders(int $pon): string
    {
        $this->pon = $pon;

        if ($this->pdfa || $this->gradients === []) {
            return '';
        }

        $idt = count($this->gradients); // index for transparency gradients
        $out = '';
        foreach ($this->gradients as $idx => $grad) {
            $gcol = $this->getOutGradientCols($grad, 'color');
            if ($gcol !== '') {
                $out .= $gcol;
                $this->gradients[$idx]['id'] = ($this->pon - 1);
                $this->gradients[$idx]['pattern'] = $this->pon;
            }

            $gopa = $this->getOutGradientCols($grad, 'opacity');
            $idgs = ($idx + $idt);

            if ($gopa !== '') {
                $out .= $gopa;
                $this->gradients[$idgs]['id'] = ($this->pon - 1);
                $this->gradients[$idgs]['pattern'] = $this->pon;
            }

            if ($grad['transparency']) {
                $oid = ++$this->pon;
                $pwidth = ($this->pagew * $this->kunit);
                $pheight = ($this->pageh * $this->kunit);
                $rect = sprintf('%F %F', $pwidth, $pheight);

                $out .= $oid . ' 0 obj' . "\n"
                    . '<<'
                    . ' /Type /XObject'
                    . ' /Subtype /Form'
                    . ' /FormType 1';
                $stream = 'q /a0 gs /Pattern cs /p' . $idgs . ' scn 0 0 ' . $pwidth . ' ' . $pheight . ' re f Q';
                if ($this->compress) {
                    $cmpstream = gzcompress($stream);
                    if ($cmpstream !== false) {
                        $stream = $cmpstream;
                        $out .= ' /Filter /FlateDecode';
                    }
                }

                $stream = $this->encrypt->encryptString($stream, $oid);
                $out .= ' /Length ' . strlen($stream)
                    . ' /BBox [0 0 ' . $rect . ']'
                    . ' /Group << /Type /Group /S /Transparency /CS /DeviceGray >>'
                    . ' /Resources <<'
                    . ' /ExtGState << /a0 << /ca 1 /CA 1 >>  >>'
                    . ' /Pattern << /p' . $idgs . ' ' . $this->gradients[$idgs]['pattern'] . ' 0 R >>'
                    . ' >>'
                    . ' >>' . "\n"
                    . ' stream' . "\n"
                    . $stream . "\n"
                    . 'endstream' . "\n"
                    . 'endobj' . "\n";

                // SMask
                $objsm = ++$this->pon;
                $out .= $objsm . ' 0 obj' . "\n"
                    . '<<'
                    . ' /Type /Mask'
                    . ' /S /Luminosity'
                    . ' /G ' . $oid . ' 0 R'
                    . ' >>' . "\n"
                    . 'endobj' . "\n";

                // ExtGState
                $objext = ++$this->pon;
                $out .= ++$objext . ' 0 obj' . "\n"
                    . '<<'
                    . ' /Type /ExtGState'
                    . ' /SMask ' . $objsm . ' 0 R'
                    . ' /AIS false'
                    . ' >>' . "\n"
                    . 'endobj' . "\n";
                $this->extgstates[] = [
                    'n' => $objext,
                    'name' => 'TGS' . $idx,
                    'parms' => [],
                ];
            }
        }

        return $out;
    }
}
