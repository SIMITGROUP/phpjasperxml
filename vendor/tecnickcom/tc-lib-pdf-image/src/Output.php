<?php

/**
 * Output.php
 *
 * @since     2011-05-23
 * @category  Library
 * @package   PdfImage
 * @author    Nicola Asuni <info@tecnick.com>
 * @copyright 2011-2024 Nicola Asuni - Tecnick.com LTD
 * @license   http://www.gnu.org/copyleft/lesser.html GNU-LGPL v3 (see LICENSE.TXT)
 * @link      https://github.com/tecnickcom/tc-lib-pdf-image
 *
 * This file is part of tc-lib-pdf-image software library.
 */

namespace Com\Tecnick\Pdf\Image;

use Com\Tecnick\Pdf\Encrypt\Encrypt;
use Com\Tecnick\Pdf\Image\Exception as ImageException;

/**
 * Com\Tecnick\Pdf\Image\Output
 *
 * @since     2011-05-23
 * @category  Library
 * @package   PdfImage
 * @author    Nicola Asuni <info@tecnick.com>
 * @copyright 2011-2024 Nicola Asuni - Tecnick.com LTD
 * @license   http://www.gnu.org/copyleft/lesser.html GNU-LGPL v3 (see LICENSE.TXT)
 * @link      https://github.com/tecnickcom/tc-lib-pdf-image
 *
 * @phpstan-import-type ImageBaseData from \Com\Tecnick\Pdf\Image\Import
 * @phpstan-import-type ImageRawData from \Com\Tecnick\Pdf\Image\Import
 */
abstract class Output
{
    /**
     * Current PDF object number.
     */
    protected int $pon;

    /**
     * Store image object IDs for the XObject Dictionary.
     *
     * @var array<string, int>
     */
    protected array $xobjdict = [];

    /**
     * Stack of added images.
     *
     * @var array<int, array{
     *          'iid': int,
     *          'key': string,
     *          'width': int,
     *          'height': int,
     *          'defprint': bool,
     *          'altimgs'?: array<int, int>,
     *      }>
     */
    protected array $image = [];

    /**
     * Cache used to store imported image data.
     * The same image data can be reused multiple times.
     *
     * @var array<string, ImageRawData>
     */
    protected array $cache = [];

    /**
     * Initialize images data.
     *
     * @param float   $kunit    Unit of measure conversion ratio.
     * @param Encrypt $encrypt Encrypt object.
     * @param bool    $pdfa     True if we are in PDF/A mode.
     * @param bool    $compress Set to false to disable stream compression.
     */
    public function __construct(
        protected float $kunit,
        /**
         * Encrypt object.
         */
        protected Encrypt $encrypt,
        protected bool $pdfa = false,
        protected bool $compress = true
    ) {
    }

    /**
     * Returns current PDF object number.
     */
    public function getObjectNumber(): int
    {
        return $this->pon;
    }

    /**
     * Get the PDF output string to print the specified image ID.
     *
     * @param int   $iid        Image ID.
     * @param float $xpos       Abscissa (X coordinate) of the upper-left Image corner in user units.
     * @param float $ypos       Ordinate (Y coordinate) of the upper-left Image corner in user units.
     * @param float $width      Image width in user units.
     * @param float $height     Image height in user units.
     * @param float $pageheight Page height in user units.
     *
     * @return string Image PDF page content.
     */
    public function getSetImage(
        int $iid,
        float $xpos,
        float $ypos,
        float $width,
        float $height,
        float $pageheight
    ): string {
        if (empty($this->image[$iid])) {
            throw new ImageException('Unknown image ID: ' . $iid);
        }

        $out = 'q';
        $out .= sprintf(
            ' %F 0 0 %F %F %F cm',
            ($width * $this->kunit),
            ($height * $this->kunit),
            ($xpos * $this->kunit),
            (($pageheight - $ypos - $height) * $this->kunit) // reverse coordinate
        );
        if (! empty($this->cache[$this->image[$iid]['key']]['mask'])) {
            $out .= ' /IMGmask' . $iid . ' Do';
            if (! empty($this->cache[$this->image[$iid]['key']]['plain'])) {
                $out .= ' /IMGplain' . $iid . ' Do';
            }
        } else {
            $out .= ' /IMG' . $iid . ' Do';
        }

        return $out . ' Q';
    }

    /**
     * Get the PDF output string for Images.
     *
     * @param int $pon Current PDF Object Number.
     *
     * @return string PDF code for the images block.
     */
    public function getOutImagesBlock(int $pon): string
    {
        $this->pon = $pon;
        $out = '';
        foreach ($this->image as $iid => $img) {
            if (empty($this->cache[$img['key']]['out'])) {
                if (! empty($this->cache[$img['key']]['mask'])) {
                    $out .= $this->getOutImage($img, $this->cache[$img['key']]['mask'], 'mask');
                    if (! empty($this->cache[$img['key']]['plain'])) {
                        $out .= $this->getOutImage($img, $this->cache[$img['key']]['plain'], 'plain');
                    }
                } else {
                    $out .= $this->getOutImage($img, $this->cache[$img['key']]);
                }

                $this->image[$iid] = $img;
            }

            if (! empty($this->cache[$img['key']]['mask']['obj'])) {
                // the mask image must be omitted
                // $this->xobjdict['IMGmask'.$img['iid']] = $this->cache[$img['key']]['mask']['obj'];
                if (! empty($this->cache[$img['key']]['plain']['obj'])) {
                    $this->xobjdict['IMGplain' . $img['iid']] = $this->cache[$img['key']]['plain']['obj'];
                }
            } else {
                $this->xobjdict['IMG' . $img['iid']] = $this->cache[$img['key']]['obj'];
            }
        }

        return $out;
    }

    /**
     * Get the PDF output string for Image object.
     *
     * @param array{
     *          'iid': int,
     *          'key': string,
     *          'width': int,
     *          'height': int,
     *          'defprint': bool,
     *          'altimgs'?: array<int, int>,
     *      }  $img  Image reference.
     * @param ImageBaseData  $data Image raw data.
     * @param string $sub  Sub image ('mask', 'plain' or empty string).
     *
     * @return string PDF Image object.
     */
    protected function getOutImage(
        array &$img,
        array &$data,
        string $sub = '',
    ): string {
        $out = $this->getOutIcc($data)
                . $this->getOutPalette($data)
                . $this->getOutAltImages($img, $data, $sub);

        $data['obj'] = ++$this->pon;

        $out .= $data['obj'] . ' 0 obj' . "\n"
            . '<</Type /XObject'
            . ' /Subtype /Image'
            . ' /Width ' . $data['width']
            . ' /Height ' . $data['height']
            . $this->getOutColorInfo($data);

        if (! empty($data['exturl'])) {
            // external stream
            $out .= ' /Length 0 /F << /FS /URL /F '
            . $this->encrypt->escapeDataString($data['exturl'], $this->pon) . ' >>';
            if (! empty($data['filter'])) {
                $out .= ' /FFilter /' . $data['filter'];
            }

            $out .= ' >> stream' . "\n"
                . 'endstream' . "\n";
        } else {
            if (! empty($data['filter'])) {
                $out .= ' /Filter /' . $data['filter'];
            }

            if (! empty($data['parms'])) {
                $out .= ' ' . $data['parms'];
            }

            // Colour Key Masking
            if (! empty($data['trns'])) {
                $trns = $this->getOutTransparency($data);
                if ($trns !== '') {
                    $out .= ' /Mask [ ' . $trns . ']';
                }
            }

            $stream = $this->encrypt->encryptString($data['data'], $this->pon);
            $out .= ' /Length ' . strlen($stream)
                . '>> stream' . "\n"
                . $stream . "\n"
                . 'endstream' . "\n";
        }

        $out .= 'endobj' . "\n";

        $this->cache[$img['key']]['out'] = true; // mark this as done

        return $out;
    }

    /**
     * Return XObjects Dictionary portion for the images.
     */
    public function getXobjectDict(): string
    {
        $out = '';
        foreach ($this->xobjdict as $iid => $objid) {
            $out .= ' /' . $iid . ' ' . $objid . ' 0 R';
        }

        return $out;
    }

    /**
     * Get the PDF output string for ICC object.
     *
     * @param array{
     *          'channels': int,
     *          'colspace': string,
     *          'icc': string,
     *          'obj_icc': int,
     *        } $data Image raw data.
     */
    protected function getOutIcc(array &$data): string
    {
        if (empty($data['icc'])) {
            return '';
        }

        $data['obj_icc'] = ++$this->pon;
        $out = $data['obj_icc'] . ' 0 obj' . "\n"
            . '<<'
            . ' /N ' . $data['channels']
            . ' /Alternate /' . $data['colspace'];
        $icc = $data['icc'];
        if ($this->compress) {
            $out .= ' /Filter /FlateDecode';
            $cicc = gzcompress($icc);
            if ($cicc !== false) {
                $icc = $cicc;
            }
        }

        $stream = $this->encrypt->encryptString($icc, $this->pon);
        return $out . (' /Length ' . strlen($stream)
            . ' >>'
            . ' stream' . "\n"
            . $stream . "\n"
            . 'endstream' . "\n"
            . 'endobj' . "\n");
    }

    /**
     * Get the PDF output string for Indexed palette object.
     *
     * @param array{
     *        'colspace': string,
     *        'obj_pal': int,
     *        'pal': string,
     * } $data Image raw data.
     */
    protected function getOutPalette(array &$data): string
    {
        if ($data['colspace'] != 'Indexed') {
            return '';
        }

        $data['obj_pal'] = ++$this->pon;
        $out = $data['obj_pal'] . ' 0 obj' . "\n"
            . '<<';
        $pal = $data['pal'];
        if ($this->compress) {
            $out .= '/Filter /FlateDecode';
            $cpal = gzcompress($pal);
            if ($cpal !== false) {
                $pal = $cpal;
            }
        }

        $stream = $this->encrypt->encryptString($pal, $this->pon);
        return $out . (' /Length ' . strlen($stream)
            . '>>'
            . ' stream' . "\n"
            . $stream . "\n"
            . 'endstream' . "\n"
            . 'endobj' . "\n");
    }

    /**
     * Get the PDF output string for color and mask information.
     *
     * @param array{
     *        'bits': int,
     *        'colspace': string,
     *        'ismask': bool,
     *        'key': string,
     *        'obj_alt': int,
     *        'obj_icc': int,
     *        'obj_pal': int,
     *        'pal': string,
     * } $data Image raw data.
     */
    protected function getOutColorInfo(array $data): string
    {
        $out = '';
        // set color space
        if (! empty($data['obj_icc'])) {
            // ICC Colour Space
            $out .= ' /ColorSpace [/ICCBased ' . $data['obj_icc'] . ' 0 R]';
        } elseif (! empty($data['obj_pal'])) {
            // Indexed Colour Space
            $out .= ' /ColorSpace [/Indexed /DeviceRGB '
                . ((strlen($data['pal']) / 3) - 1)
                . ' ' . $data['obj_pal'] . ' 0 R]';
        } else {
            // Device Colour Space
            $out .= ' /ColorSpace /' . $data['colspace'];
        }

        if ($data['colspace'] == 'DeviceCMYK') {
            $out .= ' /Decode [1 0 1 0 1 0 1 0]';
        }

        $out .= ' /BitsPerComponent ' . $data['bits'];

        if (! $data['ismask'] && ! empty($this->cache[$data['key']]['mask']['obj'])) {
            $out .= ' /SMask ' . $this->cache[$data['key']]['mask']['obj'] . ' 0 R';
        }

        if (! empty($data['obj_alt'])) {
            // reference to alternate images dictionary
            $out .= ' /Alternates ' . $data['obj_alt'] . ' 0 R';
        }

        return $out;
    }

    /**
     * Get the PDF output string for Alternate images object.
     *
     * @param array{
     *          'iid': int,
     *          'key': string,
     *          'width': int,
     *          'height': int,
     *          'defprint': bool,
     *          'altimgs'?: array<int, int>,
     *      } $img Image reference.
     * @param array{
     *            'obj_alt': int,
     *        } $data Image raw data.
     * @param string $sub Sub image ('mask', 'plain' or empty string).
     */
    protected function getOutAltImages(
        array $img,
        array &$data,
        string $sub = '',
    ): string {
        if ($this->pdfa || empty($img['altimgs']) || ($sub == 'mask')) {
            return '';
        }

        $data['obj_alt'] = ++$this->pon;

        $out = $this->pon . ' 0 obj' . "\n"
            . '[';
        foreach ($img['altimgs'] as $iid) {
            if (! empty($this->cache[$this->image[$iid]['key']]['obj'])) {
                $out .= ' << /Image ' . $this->cache[$this->image[$iid]['key']]['obj'] . ' 0 R'
                    . ' /DefaultForPrinting ' . (empty($this->image[$iid]['defprint']) ? 'false' : 'true')
                    . ' >>';
            }
        }

        return $out . (' ]' . "\n"
            . 'endobj' . "\n");
    }

    /**
     * Get the PDF output string for color and mask information.
     *
     * @param array{
     *          'trns': array<int, int>,
     *        } $data Image raw data.
     */
    protected function getOutTransparency(array $data): string
    {
        $trns = '';
        foreach ($data['trns'] as $idx => $val) {
            if ($val == 0) {
                $trns .= $idx . ' ' . $idx . ' ';
            }
        }

        return $trns;
    }
}
