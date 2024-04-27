<?php

/**
 * Gradient.php
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
 * Com\Tecnick\Pdf\Graph\Gradient
 *
 * @since     2011-05-23
 * @category  Library
 * @package   PdfGraph
 * @author    Nicola Asuni <info@tecnick.com>
 * @copyright 2011-2024 Nicola Asuni - Tecnick.com LTD
 * @license   http://www.gnu.org/copyleft/lesser.html GNU-LGPL v3 (see LICENSE.TXT)
 * @link      https://github.com/tecnickcom/tc-lib-pdf-graph
 *
 * @phpstan-import-type GradientData from \Com\Tecnick\Pdf\Graph\Base
 * @phpstan-import-type StyleDataOpt from \Com\Tecnick\Pdf\Graph\Base
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
abstract class Gradient extends \Com\Tecnick\Pdf\Graph\Raw
{
    /**
     * Blend mode.
     *
     * @var array<string, bool>
     */
    protected const BLENDMODE = [
        'Color' => true,
        'ColorBurn' => true,
        'ColorDodge' => true,
        'Darken' => true,
        'Difference' => true,
        'Exclusion' => true,
        'HardLight' => true,
        'Hue' => true,
        'Lighten' => true,
        'Luminosity' => true,
        'Multiply' => true,
        'Normal' => true,
        'Overlay' => true,
        'Saturation' => true,
        'Screen' => true,
        'SoftLight' => true,
    ];

    /**
     * Blend mode.
     *
     * @var array<string, string>
     */
    protected const COLSPACE = [
        'CMYK' => 'DeviceCMYK',
        'RGB' => 'DeviceRGB',
        'GRAY' => 'DeviceGray',
    ];

    /**
     * Returns the gradients array
     *
     * @return array<int, GradientData>
     */
    public function getGradientsArray(): array
    {
        return $this->gradients;
    }

    /**
     * Draws a basic rectangle
     *
     * @param float        $posx   Abscissa of upper-left corner.
     * @param float        $posy   Ordinate of upper-left corner.
     * @param float        $width  Width.
     * @param float        $height Height.
     * @param string       $mode   Mode of rendering. @see getPathPaintOp()
     * @param StyleDataOpt $style  Style.
     *
     * @return string PDF command
     */
    public function getBasicRect(
        float $posx,
        float $posy,
        float $width,
        float $height,
        string $mode = 'S',
        array $style = []
    ): string {
        return $this->getStyleCmd($style)
            . $this->getRawRect($posx, $posy, $width, $height)
            . $this->getPathPaintOp($mode);
    }

    /**
     * Get a linear colour gradient command.
     *
     * @param float        $posx       Abscissa of the top left corner of the rectangle.
     * @param float        $posy       Ordinate of the top left corner of the rectangle.
     * @param float        $width      Width of the rectangle.
     * @param float        $height     Height of the rectangle.
     * @param string       $colorstart Starting color.
     * @param string       $colorend   Ending color.
     * @param array<float> $coords     Gradient vector (x1, y1, x2, y2).
     *
     * @return string PDF command
     */
    public function getLinearGradient(
        float $posx,
        float $posy,
        float $width,
        float $height,
        string $colorstart,
        string $colorend,
        array $coords = [0, 0, 1, 0]
    ): string {
        return $this->getStartTransform()
        . $this->getClippingRect($posx, $posy, $width, $height)
        . $this->getGradientTransform($posx, $posy, $width, $height)
        . $this->getGradient(
            2,
            $coords,
            [
                [
                    'color' => $colorstart,
                    'exponent' => 1.0,
                    'offset' => 0.0,
                    'opacity' => 1.0,
                ],
                [
                    'color' => $colorend,
                    'exponent' => 1.0,
                    'offset' => 1.0,
                    'opacity' => 1.0,
                ],
            ],
            '',
            false
        )
        . $this->getStopTransform();
    }

    /**
     * Get a radial colour gradient command.
     *
     * @param float        $posx       Abscissa of the top left corner of the rectangle.
     * @param float        $posy       Ordinate of the top left corner of the rectangle.
     * @param float        $width      Width of the rectangle.
     * @param float        $height     Height of the rectangle.
     * @param string       $colorstart Starting color.
     * @param string       $colorend   Ending color.
     * @param array<float> $coords     Array of the form (fx, fy, cx, cy, r) where
     *                                 (fx, fy) is the starting point of the
     *                                 gradient with $colorstart (be inside the
     *                                 circle), (cx, cy) is the center of the
     *                                 circle with $colorend, and r is the radius
     *                                 of the circle.
     *
     * @return string PDF command
     */
    public function getRadialGradient(
        float $posx,
        float $posy,
        float $width,
        float $height,
        string $colorstart,
        string $colorend,
        array $coords = [0.5, 0.5, 0.5, 0.5, 1]
    ): string {
        return $this->getStartTransform()
        . $this->getClippingRect($posx, $posy, $width, $height)
        . $this->getGradientTransform($posx, $posy, $width, $height)
        . $this->getGradient(
            3,
            $coords,
            [
                [
                    'color' => $colorstart,
                    'exponent' => 1.0,
                    'offset' => 0.0,
                    'opacity' => 1.0,
                ],
                [
                    'color' => $colorend,
                    'exponent' => 1.0,
                    'offset' => 1.0,
                    'opacity' => 1.0,
                ],
            ],
            '',
            false
        )
        . $this->getStopTransform();
    }

    /**
     * Rectangular clipping area.
     *
     * @param float $posx   Abscissa of the top left corner of the rectangle.
     * @param float $posy   Ordinate of the top left corner of the rectangle.
     * @param float $width  Width of the rectangle.
     * @param float $height Height of the rectangle.
     */
    public function getClippingRect(float $posx, float $posy, float $width, float $height): string
    {
        return sprintf(
            '%F %F %F %F re W n' . "\n",
            ($posx * $this->kunit),
            (($this->pageh - $posy) * $this->kunit),
            ($width * $this->kunit),
            (-$height * $this->kunit)
        );
    }

    /**
     * Rectangular clipping area.
     *
     * @param float $posx   Abscissa of the top left corner of the rectangle.
     * @param float $posy   Ordinate of the top left corner of the rectangle.
     * @param float $width  Width of the rectangle.
     * @param float $height Height of the rectangle.
     */
    public function getGradientTransform(float $posx, float $posy, float $width, float $height): string
    {
        $ctm = [
            ($width * $this->kunit),
            0,
            0,
            ($height * $this->kunit),
            ($posx * $this->kunit),
            (($this->pageh - ($posy + $height)) * $this->kunit),
        ];
        return $this->getTransformation($ctm);
    }

    /**
     * Get a color gradient command.
     *
     * @param int               $type      Type of gradient (Not all types are currently supported):
     *                                     1 = Function-based shading; 2 = Axial shading; 3 = Radial
     *                                     shading; 4 = Free-form Gouraud-shaded triangle mesh; 5 =
     *                                     Lattice-form Gouraud-shaded triangle mesh; 6 = Coons
     *                                     patch mesh; 7 Tensor-product patch mesh
     * @param array<float>      $coords    Array of coordinates.
     * @param array<int, array{
     *            'color': string,
     *            'exponent'?: float,
     *            'opacity'?: float,
     *            'offset'?: float,
     *        }>  $stops     Array gradient color components:
     *                          color = color; offset = (0 to 1)
     *                          represents a location along the
     *                          gradient vector; exponent =
     *                          exponent of the exponential
     *                          interpolation function (default
     *                          = 1).
     * @param string            $bgcolor   Background color
     * @param bool              $antialias Flag indicating whether to filter the
     *                                     shading function to prevent aliasing artifacts.
     *
     * @return string PDF command
     */
    public function getGradient(
        int $type,
        array $coords,
        array $stops,
        string $bgcolor,
        bool $antialias = false
    ): string {
        if ($this->pdfa) {
            return '';
        }

        $model = $this->pdfColor->getColorObject($stops[0]['color']);
        if (! $model instanceof \Com\Tecnick\Color\Model) {
            throw new GraphException('Invalid color');
        }

        $ngr = (1 + count($this->gradients));
        $this->gradients[$ngr] = $this->getGradientStops(
            [
                'antialias' => $antialias,
                'background' => $this->pdfColor->getColorObject($bgcolor),
                'colors' => [],
                'colspace' => self::COLSPACE[$model->getType()],
                'coords' => $coords,
                'id' => 0,
                'pattern' => 0,
                'stream' => '',
                'transparency' => false,
                'type' => $type,
            ],
            $stops
        );

        $out = '';
        if ($this->gradients[$ngr]['transparency']) {
            // paint luminosity gradient
            $out .= '/TGS' . $ngr . ' gs' . "\n";
        }

        // paint the gradient
        $out .= '/Sh' . $ngr . ' sh' . "\n";

        return $out;
    }

    /**
     * Process the gradient stops.
     *
     * @param GradientData      $grad Array containing gradient info
     * @param array<int, array{
     *                'color': string,
     *                'exponent'?: float,
     *                'opacity'?: float,
     *                'offset'?: float,
     *             }> $stops Array gradient color components:
     *         color = color;
     *         offset = (0 to 1) represents a location along the gradient vector;
     *         exponent = exponent of the exponential interpolation function (default = 1).
     *
     * @return GradientData Gradient array.
     */
    protected function getGradientStops(array $grad, array $stops): array
    {
        $num_stops = count($stops);
        $last_stop_id = ($num_stops - 1);

        foreach ($stops as $key => $stop) {
            $grad['colors'][$key] = [];
            $grad['colors'][$key]['color'] = $stop['color'];
            $grad['colors'][$key]['exponent'] = 1;
            if (isset($stop['exponent'])) {
                // exponent for the interpolation function
                $grad['colors'][$key]['exponent'] = $stop['exponent'];
            }

            $grad['colors'][$key]['opacity'] = 1;
            if (isset($stop['opacity'])) {
                $grad['colors'][$key]['opacity'] = $stop['opacity'];
                $grad['transparency'] = ($grad['transparency'] || ($stop['opacity'] < 1));
            }

            // offset represents a location along the gradient vector
            if (isset($stop['offset'])) {
                $grad['colors'][$key]['offset'] = $stop['offset'];
            } elseif ($key == 0) {
                $grad['colors'][$key]['offset'] = 0;
            } elseif ($key == $last_stop_id) {
                $grad['colors'][$key]['offset'] = 1;
            } elseif (isset($grad['colors'][($key - 1)]['offset'])) {
                $offsetstep = ((1.0 - $grad['colors'][($key - 1)]['offset']) / ($num_stops - $key));
                $grad['colors'][$key]['offset'] = ($grad['colors'][($key - 1)]['offset'] + $offsetstep);
            }
        }

        return $grad;
    }

    /**
     * Paints a coons patch mesh.
     *
     * @param float        $posx       Abscissa of the top left corner of the rectangle.
     * @param float        $posy       Ordinate of the top left corner of the rectangle.
     * @param float        $width      Width of the rectangle.
     * @param float        $height     Height of the rectangle.
     * @param string       $colll      Lower-Left corner color.
     * @param string       $collr      Lower-Right corner color.
     * @param string       $colur      Upper-Right corner color.
     * @param string       $colul      Upper-Left corner color.
     * @param array<float> $coords     Coordinates
     * @param float        $coords_min Minimum value used by the coordinates.
     *                                 If a coordinate's value is smaller
     *                                 than this it will be cut to
     *                                 coords_min.
     * @param float        $coords_max Maximum value used by the coordinates.
     *                                 If a coordinate's value is greater
     *                                 than this it will be cut to
     *                                 coords_max.
     * @param bool         $antialias  Flag indicating whether to filter the
     *                                 shading function to prevent aliasing artifacts.
     *
     * @return string PDF command
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function getCoonsPatchMeshWithCoords(
        float $posx,
        float $posy,
        float $width,
        float $height,
        string $colll = 'yellow',
        string $collr = 'blue',
        string $colur = 'green',
        string $colul = 'red',
        array $coords = [
            0.00,
            0.00,
            0.33,
            0.00,
            0.67,
            0.00,
            1.00,
            0.00,
            1.00,
            0.33,
            1.00,
            0.67,
            1.00,
            1.00,
            0.67,
            1.00,
            0.33,
            1.00,
            0.00,
            1.00,
            0.00,
            0.67,
            0.00,
            0.33,
        ],
        float $coords_min = 0.0,
        float $coords_max = 1.0,
        bool $antialias = false
    ): string {
        if ($this->pdfa) {
            return '';
        }

        // simple array -> convert to multi patch array

        $patch_array = [
            0 => [
                'f' => 0,
                'points' => $coords,
                'colors' => [
                    0 => [
                        'red' => 1,
                        'green' => 1,
                        'blue' => 0,
                        'alpha' => 1,
                    ],
                    1 => [
                        'red' => 0,
                        'green' => 0,
                        'blue' => 1,
                        'alpha' => 1,
                    ],
                    2 => [
                        'red' => 0,
                        'green' => 1,
                        'blue' => 0,
                        'alpha' => 1,
                    ],
                    3 => [
                        'red' => 1,
                        'green' => 0,
                        'blue' => 0,
                        'alpha' => 1,
                    ],
                ],
            ],
        ];

        $colllobj = $this->pdfColor->getColorObject($colll);
        if (! $colllobj instanceof \Com\Tecnick\Color\Model) {
            throw new GraphException('Invalid Lower-Left corner color');
        }

        $patch_array[0]['colors'][0] = $colllobj->toRgbArray();

        $collrobj = $this->pdfColor->getColorObject($collr);
        if (! $collrobj instanceof \Com\Tecnick\Color\Model) {
            throw new GraphException('Invalid Lower-Right corner color');
        }

        $patch_array[0]['colors'][1] = $collrobj->toRgbArray();

        $colurobj = $this->pdfColor->getColorObject($colur);
        if (! $colurobj instanceof \Com\Tecnick\Color\Model) {
            throw new GraphException('Invalid Upper-Right corner color');
        }

        $patch_array[0]['colors'][2] = $colurobj->toRgbArray();

        $colulobj = $this->pdfColor->getColorObject($colul);
        if (! $colulobj instanceof \Com\Tecnick\Color\Model) {
            throw new GraphException('Invalid Upper-Left corner color');
        }

        $patch_array[0]['colors'][3] = $colulobj->toRgbArray();

        return $this->getCoonsPatchMesh(
            $posx,
            $posy,
            $width,
            $height,
            $patch_array,
            $coords_min,
            $coords_max,
            $antialias,
        );
    }

    /**
     * Paints a coons patch mesh.
     *
     * @param float        $posx       Abscissa of the top left corner of the rectangle.
     * @param float        $posy       Ordinate of the top left corner of the rectangle.
     * @param float        $width      Width of the rectangle.
     * @param float        $height     Height of the rectangle.
     * @param array<array{
     *            'f': int,
     *            'points': array<float>,
     *            'colors': array<int, array<string, float>>,
     *        }> $patch_array     For one patch mesh:
     *                                 array(float x1,
     *                                 float y1, ....
     *                                 float x12, float
     *                                 y12): 12 pairs of
     *                                 coordinates
     *                                 (normally from 0 to
     *                                 1) which specify
     *                                 the Bezier control
     *                                 points that define
     *                                 the patch. First
     *                                 pair is the lower
     *                                 left edge point,
     *                                 next is its right
     *                                 control point
     *                                 (control point 2).
     *                                 Then the other
     *                                 points are defined
     *                                 in the order:
     *                                 control point 1,
     *                                 edge point, control
     *                                 point 2 going
     *                                 counter-clockwise
     *                                 around the patch.
     *                                 Last (x12, y12) is
     *                                 the first edge
     *                                 point's left
     *                                 control point
     *                                 (control point 1).
     *                                 For two or more
     *                                 patch meshes:
     *                                 array[number of
     *                                 patches] - arrays
     *                                 with the following
     *                                 keys for each
     *                                 patch: f: where to
     *                                 put that patch (0 =
     *                                 first patch, 1, 2,
     *                                 3 = right, top and
     *                                 left) points: 12
     *                                 pairs of
     *                                 coordinates of the
     *                                 Bezier control
     *                                 points as above for
     *                                 the first patch, 8
     *                                 pairs of
     *                                 coordinates for the
     *                                 following patches,
     *                                 ignoring the
     *                                 coordinates already
     *                                 defined by the
     *                                 precedent patch
     *                                 colors: must be 4
     *                                 colors for the
     *                                 first patch, 2
     *                                 colors for the
     *                                 following patches
     * @param float        $coords_min Minimum value used by the coordinates.
     *                                 If a coordinate's value is smaller
     *                                 than this it will be cut to
     *                                 coords_min.
     * @param float        $coords_max Maximum value used by the coordinates.
     *                                 If a coordinate's value is greater
     *                                 than this it will be cut to
     *                                 coords_max.
     * @param bool         $antialias  Flag indicating whether to filter the
     *                                 shading function to prevent aliasing artifacts.
     *
     * @return string PDF command
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function getCoonsPatchMesh(
        float $posx,
        float $posy,
        float $width,
        float $height,
        array $patch_array = [],
        float $coords_min = 0.0,
        float $coords_max = 1.0,
        bool $antialias = false
    ): string {
        if ($this->pdfa) {
            return '';
        }

        $ngr = (1 + count($this->gradients));
        $this->gradients[$ngr] = [
            'antialias' => $antialias,
            'colors' => [],
            'background' => null,
            'colspace' => 'DeviceRGB',
            'coords' => [],
            'id' => 0,
            'pattern' => 0,
            'stream' => '',
            'transparency' => false,
            'type' => 6, //coons patch mesh
        ];

        $bpcd = 65535; // 16 bits per coordinate

        foreach ($patch_array as $par) {
            $this->gradients[$ngr]['stream'] .= chr($par['f']); // start with the edge flag as 8 bit
            foreach ($par['points'] as $point) {
                // each point as 16 bit
                $point = floor(
                    max(
                        0,
                        min(
                            $bpcd,
                            ((($point - $coords_min) / ($coords_max - $coords_min)) * $bpcd)
                        )
                    )
                );
                $this->gradients[$ngr]['stream'] .= chr((int) floor($point / 256)) . chr((int) floor($point % 256));
            }

            foreach ($par['colors'] as $color) {
                // each color component as 8 bit
                $this->gradients[$ngr]['stream'] .= chr((int) floor($color['red'] * 255))
                . chr((int) floor($color['green'] * 255))
                . chr((int) floor($color['blue'] * 255));
            }
        }

        return $this->getStartTransform()
            . $this->getClippingRect($posx, $posy, $width, $height)
            . $this->getGradientTransform($posx, $posy, $width, $height)
            . '/Sh' . $ngr . ' sh' . "\n"
            . $this->getStopTransform();
    }

    /**
     * Paints registration bars with color transtions
     *
     * @param float                     $posx     Abscissa of the top left corner of the rectangle.
     * @param float                     $posy     Ordinate of the top left corner of the rectangle.
     * @param float                     $width    Width of the rectangle.
     * @param float                     $height   Height of the rectangle.
     * @param bool                      $vertical If true prints bar vertically, otherwise horizontally.
     * @param array<int, array<string>> $colors   Array of colors to print,
     *                                            each entry is a color
     *                                            string or an array of two
     *                                            transition colors;
     *
     * @return string PDF command
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function getColorRegistrationBar(
        float $posx,
        float $posy,
        float $width,
        float $height,
        bool $vertical = false,
        array $colors = [
            // GRAY : black   to white
            ['g(0%)', 'g(100%)'],
            // RGB  : red     to white
            ['rgb(100%,0%,0%)', 'rgb(100%,100%,100%)'],
            // RGB  : green   to white
            ['rgb(0%,100%,0%)', 'rgb(100%,100%,100%)'],
            // RGB  : blue    to white
            ['rgb(0%,0%,100%)', 'rgb(100%,100%,100%)'],
            // CMYK : cyan    to white
            ['cmyk(100%,0%,0,0%)', 'cmyk(0%,0%,0,0%)'],
            // CMYK : magenta to white
            ['cmyk(0%,100%,0,0%)', 'cmyk(0%,0%,0,0%)'],
            // CMYK : yellow  to white
            ['cmyk(0%,0%,100,0%)', 'cmyk(0%,0%,0,0%)'],
            // CMYK : black   to white
            ['cmyk(0%,0%,0,100%)', 'cmyk(0%,0%,0,0%)'],
        ]
    ): string {
        $numbars = count($colors);
        if ($numbars <= 0) {
            return '';
        }

        // set bar measures
        if ($vertical) {
            $coords = [0, 1, 0, 0]; // coordinates for gradient transition
            $wbr = ($width / $numbars);  // bar width
            $hbr = $height;              // bar height
            $xdt = $wbr;                 // delta x
            $ydt = 0;                    // delta y
        } else {
            $coords = [0, 0, 1, 0];
            $wbr = $width;
            $hbr = ($height / $numbars);
            $xdt = 0;
            $ydt = $hbr;
        }

        $xbr = $posx;
        $ybr = $posy;

        $out = '';
        foreach ($colors as $color) {
            if (! empty($color) && ! empty($color[0])) {
                if (! isset($color[1])) {
                    $color[1] = $color[0];
                }

                if (($color[0] !== $color[1]) && (! $this->pdfa)) {
                    // color gradient
                    $out .= $this->getLinearGradient($xbr, $ybr, $wbr, $hbr, $color[0], $color[1], $coords);
                } else {
                    // colored rectangle
                    $out .= $this->getStartTransform();

                    $colobj = $this->pdfColor->getColorObject($color[0]);
                    if ($colobj instanceof \Com\Tecnick\Color\Model) {
                        $out .= $colobj->getPdfColor();
                    }

                    $out .= $this->getBasicRect($xbr, $ybr, $wbr, $hbr, 'F')
                        . $this->getStopTransform();
                }
            }

            $xbr += $xdt;
            $ybr += $ydt;
        }

        return $out;
    }

    /**
     * Get a crop-mark.
     *
     * @param float        $posx   Abscissa of the crop-mark center.
     * @param float        $posy   Ordinate of the crop-mark center.
     * @param float        $width  Width of the crop-mark.
     * @param float        $height Height of the crop-mark.
     * @param string       $type   Type of crop mark - one symbol per type:
     *                             T = TOP, B = BOTTOM, L = LEFT, R = RIGHT
     * @param StyleDataOpt $style  Line style to apply.
     *
     * @return string PDF command
     */
    public function getCropMark(
        float $posx,
        float $posy,
        float $width,
        float $height,
        string $type = 'TBLR',
        array $style = []
    ): string {
        $crops = array_unique(str_split(strtoupper($type), 1));
        $space_ratio = 4;
        $dhw = ($width / $space_ratio);  // horizontal space to leave before the intersection point
        $dvh = ($height / $space_ratio); // vertical space to leave before the intersection point

        $out = '';
        foreach ($crops as $crop) {
            switch ($crop) {
                case 'T':
                    $posx1 = $posx;
                    $posy1 = ($posy - $height);
                    $posx2 = $posx;
                    $posy2 = ($posy - $dvh);
                    break;
                case 'B':
                    $posx1 = $posx;
                    $posy1 = ($posy + $dvh);
                    $posx2 = $posx;
                    $posy2 = ($posy + $height);
                    break;
                case 'L':
                    $posx1 = ($posx - $width);
                    $posy1 = $posy;
                    $posx2 = ($posx - $dhw);
                    $posy2 = $posy;
                    break;
                case 'R':
                    $posx1 = ($posx + $dhw);
                    $posy1 = $posy;
                    $posx2 = ($posx + $width);
                    $posy2 = $posy;
                    break;
                default:
                    continue 2;
            }

            $out .= $this->getRawPoint($posx1, $posy1)
                . $this->getRawLine($posx2, $posy2)
                . $this->getPathPaintOp('S');
        }

        if ($out === '') {
            return '';
        }

        return $this->getStartTransform()
            . $this->getStyleCmd($style)
            . $out
            . $this->getStopTransform();
    }

    /**
     * Get overprint mode for stroking (OP) and non-stroking (op) painting operations.
     * (Check the "Entries in a Graphics State Parameter Dictionary" on PDF 32000-1:2008).
     *
     * @param bool $stroking    If true apply overprint for stroking operations.
     * @param bool $nonstroking If true apply overprint for painting operations other than stroking.
     * @param int  $mode        Overprint mode:
     *                          0 = each source
     *                          colour
     *                          component value
     *                          replaces the
     *                          value
     *                          previously
     *                          painted for the
     *                          corresponding
     *                          device
     *                          colorant; 1 = a
     *                          tint value of
     *                          0.0 for a
     *                          source colour
     *                          component shall
     *                          leave the
     *                          corresponding
     *                          component of
     *                          the previously
     *                          painted colour
     *                          unchanged.
     *
     * @return string PDF command
     */
    public function getOverprint(
        bool $stroking = true,
        bool $nonstroking = null,
        int $mode = 0
    ): string {
        if ($nonstroking === null) {
            $nonstroking = $stroking;
        }

        return $this->getExtGState(
            [
                'OP' => $stroking,
                'op' => $nonstroking,
                'OPM' => max(0, min(1, $mode)),
            ]
        );
    }

    /**
     * Set alpha for stroking (CA) and non-stroking (ca) operations.
     *
     * @param float        $stroking    Alpha value for stroking operations:
     *                                  real value from 0 (transparent) to 1 (opaque).
     * @param string       $bmv         Blend mode, one of the following:
     *                                  Normal, Multiply, Screen,
     *                                  Overlay, Darken, Lighten,
     *                                  ColorDodge, ColorBurn, HardLight,
     *                                  SoftLight, Difference, Exclusion,
     *                                  Hue, Saturation, Color,
     *                                  Luminosity.
     * @param float|string $nonstroking Alpha value for non-stroking operations:
     *                                  real value from 0 (transparent) to 1
     *                                  (opaque).
     *
     * @return string PDF command
     */
    public function getAlpha(
        float $stroking = 1,
        string $bmv = 'Normal',
        float|string $nonstroking = '',
        bool $ais = false
    ): string {
        if ($nonstroking == '') {
            $nonstroking = $stroking;
        }

        if ($bmv[0] == '/') {
            // remove trailing slash
            $bmv = substr($bmv, 1);
        }

        if (! isset(self::BLENDMODE[$bmv])) {
            $bmv = 'Normal';
        }

        return $this->getExtGState(
            [
                'CA' => $stroking,
                'ca' => (float) $nonstroking,
                'BM' => '/' . $bmv,
                'AIS' => $ais,
            ]
        );
    }
}
