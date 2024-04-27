<?php

/**
 * Settings.php
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

use Com\Tecnick\Pdf\Encrypt\Encrypt;

/**
 * Com\Tecnick\Pdf\Page\Settings
 *
 * @since     2011-05-23
 * @category  Library
 * @package   PdfPage
 * @author    Nicola Asuni <info@tecnick.com>
 * @copyright 2011-2024 Nicola Asuni - Tecnick.com LTD
 * @license   http://www.gnu.org/copyleft/lesser.html GNU-LGPL v3 (see LICENSE.TXT)
 * @link      https://github.com/tecnickcom/tc-lib-pdf-page
 *
 * @phpstan-import-type PageBci from \Com\Tecnick\Pdf\Page\Box
 * @phpstan-import-type PageBox from \Com\Tecnick\Pdf\Page\Box
 * @phpstan-import-type MarginData from \Com\Tecnick\Pdf\Page\Box
 * @phpstan-import-type RegionData from \Com\Tecnick\Pdf\Page\Box
 * @phpstan-import-type TransitionData from \Com\Tecnick\Pdf\Page\Box
 * @phpstan-import-type PageData from \Com\Tecnick\Pdf\Page\Box
 *
 * @SuppressWarnings(PHPMD)
 */
abstract class Settings extends \Com\Tecnick\Pdf\Page\Box
{
    /**
     * Epsilon precision used to compare floating point values.
     */
    public const EPS = 0.0001;

    /**
     * Alias for total number of pages in a group.
     *
     * @var string
     */
    public const PAGE_TOT = '~#PT';

    /**
     * Alias for page number.
     *
     * @var string
     */
    public const PAGE_NUM = '~#PN';

    /**
     * Array of pages (stack).
     *
     * @var array<int, PageData>
     */
    protected array $page = [];

    /**
     * Current page ID.
     */
    protected int $pid = -1;

    /**
     * Maximum page ID.
     */
    protected int $pmaxid = -1;

    /**
     * Count pages in each group.
     *
     * @var array<int, int>
     */
    protected array $group = [
        0 => 0,
    ];

    /**
     * Encrypt object.
     */
    protected Encrypt $enc;

    /**
     * True if we are in PDF/A mode.
     */
    protected bool $pdfa = false;

    /**
     * Enable stream compression.
     */
    protected bool $compress = true;

    /**
     * True if the signature approval is enabled (for incremental updates).
     */
    protected bool $sigapp = false;

    /**
     * Reserved Object ID for the resource dictionary.
     */
    protected int $rdoid = 1;

    /**
     * Root object ID.
     */
    protected int $rootoid = 0;

    /**
     * Sanitize or set the page modification time.
     *
     * @param array{
     *        'num': int,
     *    } $data Page data.
     */
    public function sanitizePageNumber(array &$data): void
    {
        if (! empty($data['num'])) {
            $data['num'] = max(0, (int) $data['num']);
        }
    }

    /**
     * Sanitize or set the page modification time.
     *
     * @param array{
     *        'time': int,
     *    } $data Page data.
     */
    public function sanitizeTime(array &$data): void
    {
        $data['time'] = empty($data['time']) ? time() : max(0, (int) $data['time']);
    }

    /**
     * Sanitize or set the page group.
     *
     * @param array{
     *        'group': int,
     *    } $data Page data.
     */
    public function sanitizeGroup(array &$data): void
    {
        $data['group'] = empty($data['group']) ? 0 : max(0, $data['group']);
    }

    /**
     * Sanitize or set the page content.
     *
     * @param array{
     *        'content': array<string>,
     *    } $data Page data.
     */
    public function sanitizeContent(array &$data): void
    {
        if (empty($data['content'])) {
            $data['content'] = [''];
            return;
        }

        if (is_string($data['content'])) {
            $data['content'] = [(string) $data['content']];
        }
    }

    /**
     * Sanitize or set the annotation references.
     *
     * @param array{
     *        'annotrefs': array<int,int>,
     *    } $data Page data.
     */
    public function sanitizeAnnotRefs(array &$data): void
    {
        if (empty($data['annotrefs'])) {
            $data['annotrefs'] = [];
        }
    }

    /**
     * Sanitize or set the page rotation.
     * The number of degrees by which the page shall be rotated clockwise when displayed or printed.
     * The value shall be a multiple of 90.
     *
     * @param array{
     *        'rotation': int,
     *    } $data Page data.
     */
    public function sanitizeRotation(array &$data): void
    {
        $data['rotation'] = empty($data['rotation']) || ($data['rotation'] % 90 != 0) ? 0 : (int) $data['rotation'];
    }

    /**
     * Sanitize or set the page preferred zoom (magnification) factor.
     *
     * @param array{
     *        'zoom': float,
     *    } $data Page data.
     */
    public function sanitizeZoom(array &$data): void
    {
        $data['zoom'] = empty($data['zoom']) ? 1 : $data['zoom'];
    }

    /**
     * Sanitize or set the page transitions.
     *
     * @param array{
     *        'transition': TransitionData,
     *    } $data Page data.
     */
    public function sanitizeTransitions(array &$data): void
    {
        if (empty($data['transition'])) {
            return;
        }

        // display duration before advancing page
        if (empty($data['transition']['Dur'])) {
            unset($data['transition']['Dur']);
        } else {
            $data['transition']['Dur'] = max(0, $data['transition']['Dur']);
        }

        // transition style
        $styles = [
            'Split',
            'Blinds',
            'Box',
            'Wipe',
            'Dissolve',
            'Glitter',
            'R',
            'Fly',
            'Push',
            'Cover',
            'Uncover',
            'Fade',
        ];
        if (empty($data['transition']['S']) || ! in_array($data['transition']['S'], $styles)) {
            $data['transition']['S'] = 'R';
        }

        // duration of the transition effect, in seconds
        $data['transition']['D'] ??= 1;

        // dimension in which the specified transition effect shall occur
        if (
            empty($data['transition']['Dm'])
            || ! in_array($data['transition']['S'], ['Split', 'Blinds'])
            || ! in_array($data['transition']['Dm'], ['H', 'V'])
        ) {
            unset($data['transition']['Dm']);
        }

        // direction of motion for the specified transition effect
        if (
            empty($data['transition']['M'])
            || ! in_array($data['transition']['S'], ['Split', 'Box', 'Fly'])
            || ! in_array($data['transition']['M'], ['I', 'O'])
        ) {
            unset($data['transition']['M']);
        }

        // direction in which the specified transition effect shall moves
        if (
            empty($data['transition']['Di'])
            || ! in_array($data['transition']['S'], ['Wipe', 'Glitter', 'Fly', 'Cover', 'Uncover', 'Push'])
            || ! in_array($data['transition']['Di'], ['None', 0, 90, 180, 270, 315])
            || (in_array($data['transition']['Di'], [90, 180]) && ($data['transition']['S'] != 'Wipe'))
            || (($data['transition']['Di'] == 315) && ($data['transition']['S'] != 'Glitter'))
            || (($data['transition']['Di'] == 'None') && ($data['transition']['S'] != 'Fly'))
        ) {
            unset($data['transition']['Di']);
        }

        // If true, the area that shall be flown in is rectangular and opaque
        $data['transition']['B'] = ! empty($data['transition']['B']);
    }

    /**
     * Sanitize or set the page margins.
     *
     * @param array{
     *        'ContentHeight': float,
     *        'ContentWidth': float,
     *        'FooterHeight': float,
     *        'HeaderHeight': float,
     *        'height': float,
     *        'margin'?: MarginData,
     *        'orientation': string,
     *        'pheight': float,
     *        'pwidth': float,
     *        'width': float,
     *    } $data Page data.
     */
    public function sanitizeMargins(array &$data): void
    {
        if (empty($data['margin'])) {
            $data['margin'] = [];
            if (empty($data['width']) || empty($data['height'])) {
                [$data['width'], $data['height'], $data['orientation']] = $this->getPageFormatSize('A4', 'P');
                $data['width'] /= $this->kunit;
                $data['height'] /= $this->kunit;
            }
        }

        $margins = [
            'PL' => $data['width'],
            'PR' => $data['width'],
            'PT' => $data['height'],
            'HB' => $data['height'],
            'CT' => $data['height'],
            'CB' => $data['height'],
            'FT' => $data['height'],
            'PB' => $data['height'],
        ];
        foreach ($margins as $type => $max) {
            $data['margin'][$type] = (
                empty($data['margin'][$type])
            ) ? 0 : min(max(0, $data['margin'][$type]), $max);
        }

        $data['margin']['PR'] = min($data['margin']['PR'], ($data['width'] - $data['margin']['PL']));
        $data['margin']['HB'] = max($data['margin']['HB'], $data['margin']['PT']);
        $data['margin']['CT'] = max($data['margin']['CT'], $data['margin']['HB']);
        $data['margin']['CB'] = min($data['margin']['CB'], ($data['height'] - $data['margin']['CT']));
        $data['margin']['FT'] = min($data['margin']['FT'], $data['margin']['CB']);
        $data['margin']['PB'] = min($data['margin']['PB'], $data['margin']['FT']);

        $data['ContentWidth'] = ($data['width'] - $data['margin']['PL'] - $data['margin']['PR']);
        $data['ContentHeight'] = ($data['height'] - $data['margin']['CT'] - $data['margin']['CB']);
        $data['HeaderHeight'] = ($data['margin']['HB'] - $data['margin']['PT']);
        $data['FooterHeight'] = ($data['margin']['FT'] - $data['margin']['PB']);
    }

    /**
     * Sanitize or set the page regions (columns).
     *
     * @param array{
     *        'autobreak'?: bool,
     *        'columns'?: int,
     *        'ContentHeight': float,
     *        'ContentWidth': float,
     *        'height': float,
     *        'margin': MarginData,
     *        'region'?: array<int, RegionData>,
     *        'width': float,
     *    } $data Page data.
     */
    public function sanitizeRegions(array &$data): void
    {
        if (! empty($data['columns'])) {
            // set eaual columns
            $data['region'] = [];
            $width = ($data['ContentWidth'] / $data['columns']);
            for ($idx = 0; $idx < $data['columns']; ++$idx) {
                $data['region'][] = [
                    'RX' => ($data['margin']['PL'] + ($idx * $width)),
                    'RY' => $data['margin']['CT'],
                    'RW' => $width,
                    'RH' => $data['ContentHeight'],
                ];
            }
        }

        if (empty($data['region'])) {
            // default single region
            $data['region'] = [[
                'RX' => $data['margin']['PL'],
                'RY' => $data['margin']['CT'],
                'RW' => $data['ContentWidth'],
                'RH' => $data['ContentHeight'],
            ]];
        }

        $data['columns'] = 0; // count the number of regions
        foreach ($data['region'] as $key => $val) {
            // region width
            $data['region'][$key]['RW'] = min(max(0, $val['RW']), $data['ContentWidth']);
            // horizontal coordinate of the top-left corner
            $data['region'][$key]['RX'] = min(
                max(0, $val['RX']),
                ($data['width'] - $data['margin']['PR'] - $val['RW'])
            );
            // distance of the region right side from the left page edge
            $data['region'][$key]['RL'] = ($val['RX'] + $val['RW']);
            // distance of the region right side from the right page edge
            $data['region'][$key]['RR'] = ($data['width'] - $val['RX'] - $val['RW']);
            // region height
            $data['region'][$key]['RH'] = min(max(0, $val['RH']), $data['ContentHeight']);
            // vertical coordinate of the top-left corner
            $data['region'][$key]['RY'] = min(
                max(0, $val['RY']),
                ($data['height'] - $data['margin']['CB'] - $val['RH'])
            );
            // distance of the region bottom side from the top page edge
            $data['region'][$key]['RT'] = ($val['RY'] + $val['RH']);
            // distance of the region bottom side from the bottom page edge
            $data['region'][$key]['RB'] = ($data['height'] - $val['RY'] - $val['RH']);

            // initialize cursor position inside the region
            $data['region'][$key]['x'] = $data['region'][$key]['RX'];
            $data['region'][$key]['y'] = $data['region'][$key]['RY'];

            ++$data['columns'];
        }

        if (! isset($data['autobreak'])) {
            $data['autobreak'] = true;
        }
    }

    /**
     * Sanitize or set the page boxes containing the page boundaries.
     *
     * @param array{
     *        'box'?: array<string, PageBox>,
     *        'format'?: string,
     *        'height': float,
     *        'orientation': string,
     *        'pheight'?: float,
     *        'pwidth'?: float,
     *        'width': float,
     *    } $data Page data.
     */
    public function sanitizeBoxData(array &$data): void
    {
        if (empty($data['box'])) {
            if (empty($data['pwidth']) || empty($data['pheight'])) {
                [$data['pwidth'], $data['pheight'], $data['orientation']] = $this->getPageFormatSize('A4', 'P');
            }

            $data['box'] = $this->setPageBoxes($data['pwidth'], $data['pheight']);
        } else {
            if (isset($data['format']) && $data['format'] !== '' && ($data['format'] == 'MediaBox')) {
                $data['format'] = '';
                $data['width'] = abs($data['box']['MediaBox']['urx'] - $data['box']['MediaBox']['llx']) / $this->kunit;
                $data['height'] = abs($data['box']['MediaBox']['ury'] - $data['box']['MediaBox']['lly']) / $this->kunit;
                $this->sanitizePageFormat($data);
            }

            if (empty($data['box']['MediaBox'])) {
                $data['box'] = $this->setBox($data['box'], 'MediaBox', 0, 0, $data['pwidth'], $data['pheight']);
            }

            if (empty($data['box']['CropBox'])) {
                $data['box'] = $this->setBox(
                    $data['box'],
                    'CropBox',
                    $data['box']['MediaBox']['llx'],
                    $data['box']['MediaBox']['lly'],
                    $data['box']['MediaBox']['urx'],
                    $data['box']['MediaBox']['ury']
                );
            }

            if (empty($data['box']['BleedBox'])) {
                $data['box'] = $this->setBox(
                    $data['box'],
                    'BleedBox',
                    $data['box']['CropBox']['llx'],
                    $data['box']['CropBox']['lly'],
                    $data['box']['CropBox']['urx'],
                    $data['box']['CropBox']['ury']
                );
            }

            if (empty($data['box']['TrimBox'])) {
                $data['box'] = $this->setBox(
                    $data['box'],
                    'TrimBox',
                    $data['box']['CropBox']['llx'],
                    $data['box']['CropBox']['lly'],
                    $data['box']['CropBox']['urx'],
                    $data['box']['CropBox']['ury']
                );
            }

            if (empty($data['box']['ArtBox'])) {
                $data['box'] = $this->setBox(
                    $data['box'],
                    'ArtBox',
                    $data['box']['CropBox']['llx'],
                    $data['box']['CropBox']['lly'],
                    $data['box']['CropBox']['urx'],
                    $data['box']['CropBox']['ury']
                );
            }
        }

        $orientation = $this->getPageOrientation(
            abs($data['box']['MediaBox']['urx'] - $data['box']['MediaBox']['llx']),
            abs($data['box']['MediaBox']['ury'] - $data['box']['MediaBox']['lly'])
        );
        if (empty($data['orientation'])) {
            $data['orientation'] = $orientation;
        } elseif ($data['orientation'] != $orientation) {
            $data['box'] = $this->swapCoordinates($data['box']);
        }
    }

    /**
     * Sanitize or set the page format.
     *
     * @param array{
     *        'format': string,
     *        'height': float,
     *        'orientation': string,
     *        'pheight': float,
     *        'pwidth': float,
     *        'width': float,
     *    } $data Page data.
     */
    public function sanitizePageFormat(array &$data): void
    {
        if (empty($data['orientation'])) {
            $data['orientation'] = '';
        }

        if (! empty($data['format'])) {
            [$data['pwidth'], $data['pheight'], $data['orientation']] = $this->getPageFormatSize(
                $data['format'],
                $data['orientation']
            );
            $data['width'] = ($data['pwidth'] / $this->kunit);
            $data['height'] = ($data['pheight'] / $this->kunit);
        } else {
            $data['format'] = 'CUSTOM';
            if (empty($data['width']) || empty($data['height'])) {
                // default page format
                $data['format'] = 'A4';
                $data['orientation'] = 'P';
                $this->sanitizePageFormat($data);
                return;
            }

            [$data['width'], $data['height'], $data['orientation']] = $this->getPageOrientedSize(
                $data['width'],
                $data['height'],
                $data['orientation']
            );
        }

        // convert values in points
        $data['pwidth'] = ($data['width'] * $this->kunit);
        $data['pheight'] = ($data['height'] * $this->kunit);
    }
}
