<?php

/**
 * Box.php
 *
 * @since     2011-05-23
 * @category  Library
 * @package   PdfPage
 * @author    Nicola Asuni <info@tecnick.com>
 * @copyright 2011-2024 Nicola Asuni - Tecnick.com LTD
 * @license   http://www.gnu.org/copyleft/lesser.html GNU-LGPL v3 (see LICENSE.TXT)
 * @link      https://github.com/tecnickcom/tc-lib-pdf-page
 *
 * This file is part of tc-lib-pdf-page software library.
 */

namespace Com\Tecnick\Pdf\Page;

use Com\Tecnick\Color\Pdf as Color;
use Com\Tecnick\Pdf\Page\Exception as PageException;

/**
 * Com\Tecnick\Pdf\Page\Box
 *
 * @since     2011-05-23
 * @category  Library
 * @package   PdfPage
 * @author    Nicola Asuni <info@tecnick.com>
 * @copyright 2011-2024 Nicola Asuni - Tecnick.com LTD
 * @license   http://www.gnu.org/copyleft/lesser.html GNU-LGPL v3 (see LICENSE.TXT)
 * @link      https://github.com/tecnickcom/tc-lib-pdf-page
 *
 * @phpstan-type PageBci array{
 *            'color': string,
 *            'width': float,
 *            'style': string,
 *            'dash': array<int>,
 *          }
 *
 * @phpstan-type PageBox array{
 *            'llx': float,
 *            'lly': float,
 *            'urx': float,
 *            'ury': float,
 *            'bci'?: PageBci,
 *          }
 *
 * @phpstan-type MarginData array{
 *            'CB': float,
 *            'CT': float,
 *            'FT': float,
 *            'HB': float,
 *            'PB': float,
 *            'PL': float,
 *            'PR': float,
 *            'PT': float,
 *        }
 *
 * @phpstan-type RegionData array{
 *            'RB': float,
 *            'RH': float,
 *            'RL': float,
 *            'RR': float,
 *            'RT': float,
 *            'RW': float,
 *            'RX': float,
 *            'RY': float,
 *            'x' : float,
 *            'y' : float,
 *        }
 *

 * @phpstan-type TransitionData array{
 *            'B': bool,
 *            'D': int,
 *            'Di': string|int,
 *            'Dm': string,
 *            'Dur': float,
 *            'M': string,
 *            'S': string,
 *            'SS': float,
 *        }
 *
 * @phpstan-type PageData array{
 *        'annotrefs': array<int,int>,
 *        'autobreak': bool,
 *        'box': array<string, array{
 *            'llx': float,
 *            'lly': float,
 *            'urx': float,
 *            'ury': float,
 *            'bci': PageBci,
 *        }>,
 *        'columns': int,
 *        'content': array<string>,
 *        'content_mark': array<int>,
 *        'ContentHeight': float,
 *        'ContentWidth': float,
 *        'FooterHeight': float,
 *        'HeaderHeight': float,
 *        'currentRegion': int,
 *        'format': string,
 *        'group': int,
 *        'height': float,
 *        'margin': MarginData,
 *        'n': int,
 *        'num': int,
 *        'orientation': string,
 *        'pagenum': int,
 *        'pheight': float,
 *        'pid': int,
 *        'pwidth': float,
 *        'region': array<int, RegionData>,
 *        'rotation': int,
 *        'time': int,
 *        'transition': TransitionData,
 *        'width': float,
 *        'zoom': float,
 *    }
 */
abstract class Box extends \Com\Tecnick\Pdf\Page\Mode
{
    /**
     * Unit of measure conversion ratio.
     */
    protected float $kunit = 1.0;

    /**
     * Color object.
     */
    protected Color $col;

    /**
     * Page box names.
     *
     * @var array<string>
     */
    public const BOX = [
        'MediaBox',
        'CropBox',
        'BleedBox',
        'TrimBox',
        'ArtBox',
    ];

    /**
     * Swap X and Y coordinates of page boxes (change page boxes orientation).
     *
     * @param array<string, PageBox> $dims Array of page dimensions.
     *
     * @return array<string, PageBox> Page dimensions.
     */
    public function swapCoordinates(array $dims): array
    {
        foreach (self::BOX as $type) {
            // swap X and Y coordinates
            if (isset($dims[$type])) {
                $tmp = $dims[$type]['llx'];
                $dims[$type]['llx'] = $dims[$type]['lly'];
                $dims[$type]['lly'] = $tmp;
                $tmp = $dims[$type]['urx'];
                $dims[$type]['urx'] = $dims[$type]['ury'];
                $dims[$type]['ury'] = $tmp;
            }
        }

        return $dims;
    }

    /**
     * Set page boundaries.
     *
     * @param array<string, PageBox> $dims Array of page dimensions to modify.
     * @param string                 $type Box type: MediaBox, CropBox, BleedBox, TrimBox, ArtBox.
     * @param float                  $llx  Lower-left x coordinate in user units.
     * @param float                  $lly  Lower-left y coordinate in user units.
     * @param float                  $urx  Upper-right x coordinate in user units.
     * @param float                  $ury  Upper-right y coordinate in user units.
     * @param PageBci                $bci  BoxColorInfo: guideline style (color, width, style, dash).
     *
     * @return array<string, PageBox> Page dimensions.
     */
    public function setBox(
        array $dims,
        string $type,
        float $llx,
        float $lly,
        float $urx,
        float $ury,
        ?array $bci = null,
    ): array {
        if ($dims === []) {
            // initialize array
            $dims = [];
        }

        if (! in_array($type, self::BOX)) {
            throw new PageException('unknown page box type: ' . $type);
        }

        $dims[$type]['llx'] = $llx;
        $dims[$type]['lly'] = $lly;
        $dims[$type]['urx'] = $urx;
        $dims[$type]['ury'] = $ury;

        if ($bci === null) {
            // set default values
            $bci = [
                'color' => '#000000',
                'width' => (1.0 / $this->kunit),
                'style' => 'S', // S = solid; D = dash
                'dash' => [3],
            ];
        }

        $dims[$type]['bci'] = $bci;

        return $dims;
    }

    /**
     * Initialize page boxes.
     *
     * @param float $width  Page width in points.
     * @param float $height Page height in points.
     *
     * @return array<string, PageBox> Page boxes.
     */
    public function setPageBoxes(float $width, float $height): array
    {
        $dims = [];
        foreach (self::BOX as $type) {
            $dims = $this->setBox($dims, $type, 0, 0, $width, $height);
        }

        return $dims;
    }

    /**
     * Returns the PDF command to output the specified page boxes.
     *
     * @param array<string, array{
     *            'llx': float,
     *            'lly': float,
     *            'urx': float,
     *            'ury': float,
     *          }> $dims Array of page dimensions.
     */
    protected function getBox(array $dims): string
    {
        $out = '';
        foreach (self::BOX as $box) {
            if (empty($dims[$box])) {
                // @codeCoverageIgnoreStart
                continue;
                // @codeCoverageIgnoreEnd
            }

            $out .= '/' . $box . ' [' . sprintf(
                '%F %F %F %F',
                $dims[$box]['llx'],
                $dims[$box]['lly'],
                $dims[$box]['urx'],
                $dims[$box]['ury']
            ) . ']' . "\n";
        }

        return $out;
    }

    /**
     * Returns the PDF command to output the specified page BoxColorInfo.
     *
     * @param array<string, array{
     *            'bci': PageBci,
     *          }> $dims Array of page dimensions.
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function getBoxColorInfo(array $dims): string
    {
        $out = '/BoxColorInfo <<' . "\n";
        foreach (self::BOX as $box) {
            if (empty($dims[$box])) {
                continue;
            }

            $out .= '/' . $box . ' <<' . "\n";
            if (! empty($dims[$box]['bci']['color'])) {
                $out .= '/C [' . $this->col->getPdfRgbComponents($dims[$box]['bci']['color']) . ']' . "\n";
            }

            if (! empty($dims[$box]['bci']['width'])) {
                $out .= '/W ' . sprintf('%F', ($dims[$box]['bci']['width'] * $this->kunit)) . "\n";
            }

            if (! empty($dims[$box]['bci']['style'])) {
                $mode = strtoupper($dims[$box]['bci']['style'][0]);
                if ($mode !== 'D') {
                    $mode = 'S';
                }

                $out .= '/S /' . $mode . "\n";
            }

            if (! empty($dims[$box]['bci']['dash'])) {
                $out .= '/D [';
                foreach ($dims[$box]['bci']['dash'] as $dash) {
                    $out .= sprintf(' %F', ((float) $dash * $this->kunit));
                }

                $out .= ' ]' . "\n";
            }

            $out .= '>>' . "\n";
        }

        return $out . ('>>' . "\n");
    }
}
