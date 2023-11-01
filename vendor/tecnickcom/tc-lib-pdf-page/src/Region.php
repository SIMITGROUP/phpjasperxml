<?php

/**
 * Region.php
 *
 * @since       2011-05-23
 * @category    Library
 * @package     PdfPage
 * @author      Nicola Asuni <info@tecnick.com>
 * @copyright   2011-2023 Nicola Asuni - Tecnick.com LTD
 * @license     http://www.gnu.org/copyleft/lesser.html GNU-LGPL v3 (see LICENSE.TXT)
 * @link        https://github.com/tecnickcom/tc-lib-pdf-page
 *
 * This file is part of tc-lib-pdf-page software library.
 */

namespace Com\Tecnick\Pdf\Page;

use Com\Tecnick\Color\Pdf as Color;
use Com\Tecnick\Pdf\Page\Exception as PageException;

/**
 * Com\Tecnick\Pdf\Page\Region
 *
 * @since       2011-05-23
 * @category    Library
 * @package     PdfPage
 * @author      Nicola Asuni <info@tecnick.com>
 * @copyright   2011-2023 Nicola Asuni - Tecnick.com LTD
 * @license     http://www.gnu.org/copyleft/lesser.html GNU-LGPL v3 (see LICENSE.TXT)
 * @link        https://github.com/tecnickcom/tc-lib-pdf-page
 *
 * A page region defines the writable area of the page.
 */
abstract class Region extends \Com\Tecnick\Pdf\Page\Settings
{
    /**
     * Add a new page.
     *
     * @param array $data Page data:
     *     time        : UTC page modification time in seconds;
     *     group       : page group number;
     *     num         : if set overwrites the page number;
     *     content     : string containing the raw page content;
     *     annotrefs   : array containing the annotation object references;
     *     format      : page format name, or alternatively you can set width and height as below;
     *     width       : page width;
     *     height      : page height;
     *     orientation : page orientation ('P' or 'L');
     *     rotation    : the number of degrees by which the page shall be rotated clockwise when displayed or printed;
     *     box         : array containing page box boundaries and settings (@see setBox);
     *     transition  : array containing page transition data (@see getPageTransition);
     *     zoom        : preferred zoom (magnification) factor;
     *     margin      : page margins:
     *                   PL : page left margin measured from the left page edge
     *                   PR : page right margin measured from the right page edge
     *                   PT : page top or header top measured distance from the top page edge
     *                   HB : header bottom measured from the top page edge
     *                   CT : content top measured from the top page edge
     *                   CB : content bottom (page breaking point) measured from the top page edge
     *                   FT : footer top measured from the bottom page edge
     *                   PB : page bottom (footer bottom) measured from the bottom page edge
     *     columns     : number of equal vertical columns, if set it will automatically populate the region array
     *     region      : array containing the ordered list of rectangular areas where it is allowed to write,
     *                   each region is defined by:
     *                   RX : horizontal coordinate of top-left corner
     *                   RY : vertical coordinate of top-left corner
     *                   RW : region width
     *                   RH : region height
     *     autobreak   : true to automatically add a page when the content reaches the breaking point.
     *
     * NOTE: if $data is empty, then the last page format will be cloned.
     *
     * @return array Page data with additional Page ID property 'pid'.
     */
    public function add(array $data = array())
    {
        if (empty($data) && ($this->pmaxid >= 0)) {
            // clone last page data
            $data = $this->page[$this->pmaxid];
            unset($data['time'], $data['content'], $data['annotrefs'], $data['pagenum']);
        } else {
            $this->sanitizeGroup($data);
            $this->sanitizeRotation($data);
            $this->sanitizeZoom($data);
            $this->sanitizePageFormat($data);
            $this->sanitizeBoxData($data);
            $this->sanitizeTransitions($data);
            $this->sanitizeMargins($data);
            $this->sanitizeRegions($data);
        }
        $this->sanitizeTime($data);
        $this->sanitizeContent($data);
        $this->sanitizeAnnotRefs($data);
        $this->sanitizePageNumber($data);
        $data['content_mark'] = array(0);
        $data['currentRegion'] = 0;
        $data['pid'] = ++$this->pmaxid;
        $this->pid = $data['pid'];
        $this->page[$this->pid] = $data;
        if (isset($this->group[$data['group']])) {
            $this->group[$data['group']] += 1;
        } else {
            $this->group[$data['group']] = 1;
        }
        return $this->page[$this->pid];
    }

    /**
     * Set the current page number (move to the specified page).
     *
     * @param int $pid page index. Omit or set it to -1 for the current page ID.
     */
    public function setCurrentPage($pid = -1)
    {
        $pid = $this->sanitizePageID($pid);
        $this->pid = $pid;
        return $this->page[$this->pid];
    }

    /**
     * Returns the specified page data.
     *
     * @param int $pid page index. Omit or set it to -1 for the current page ID.
     *
     * return array
     */
    public function getPage($pid = -1)
    {
        $pid = $this->sanitizePageID($pid);
        return $this->page[$pid];
    }

    /**
     * Check if the specified page ID exist.
     *
     * @param int $pid page index. Omit or set it to -1 for the current page ID.
     *
     * @return int Page ID.
     */
    protected function sanitizePageID($pid = -1)
    {
        if ($pid < 0) {
            $pid = $this->pid;
        }
        if (!isset($this->page[$pid])) {
            throw new PageException('The page with index ' . $pid . ' do not exist.');
        }
        return $pid;
    }

    /**
     * Select the specified page region.
     *
     * @param int $idr ID of the region.
     * @param int $pid page index. Omit or set it to -1 for the current page ID.
     *
     * @return array Selected region data.
     */
    public function selectRegion($idr, $pid = -1)
    {
        $pid = $this->sanitizePageID($pid);
        $this->page[$pid]['currentRegion'] = min(max(0, intval($idr)), $this->page[$pid]['columns']);
        return $this->getRegion();
    }

    /**
     * Returns the current region data.
     *
     * @param int $pid page index. Omit or set it to -1 for the current page ID.
     *
     * @return array Region.
     */
    public function getRegion($pid = -1)
    {
        $pid = $this->sanitizePageID($pid);
        return $this->page[$pid]['region'][$this->page[$pid]['currentRegion']];
    }

    /**
     * Returns the next page data.
     * Creates a new page if required and page break is enabled.
     *
     * @param int $pid page index. Omit or set it to -1 for the current page ID.
     *
     * @return array Page data.
     */
    public function getNextPage($pid = -1)
    {
        $pid = $this->sanitizePageID($pid);
        if ($pid < $this->pmaxid) {
            $this->pid = ++$pid;
            return $this->page[$this->pid];
        }
        if (!$this->isAutoPageBreakEnabled()) {
            return $this->setCurrentPage($pid);
        }
        return $this->add();
    }

    /**
     * Returns the page data with the next selected region.
     * If there are no more regions available, then the first region on the next page is selected.
     * A new page is added if required.
     *
     * @param int $pid page index. Omit or set it to -1 for the current page ID.
     *
     * @return array Current page data.
     */
    public function getNextRegion($pid = -1)
    {
        $pid = $this->sanitizePageID($pid);
        $nextid = ($this->page[$pid]['currentRegion'] + 1);
        if (isset($this->page[$pid]['region'][$nextid])) {
            $this->page[$pid]['currentRegion'] = $nextid;
            return $this->page[$pid];
        }
        return $this->getNextPage($pid);
    }

    /**
     * Move to the next page region if required.
     *
     * @param float $height Height of the block to add.
     * @param float $ypos   Starting Y position or NULL for current position.
     * @param int   $pid    Page index. Omit or set it to -1 for the current page ID.
     *
     * @return array Page data.
     */
    public function checkRegionBreak($height = 0, $ypos = null, $pid = -1)
    {
        if ($this->isYOutRegion($ypos, $height, $pid)) {
            return $this->getNextRegion($pid);
        }
        return $this->getPage($pid);
    }

    /**
     * Return the auto-page-break status.
     *
     * @param int $pid page index. Omit or set it to -1 for the current page ID.
     *
     * @return bool True if the auto page break is enabled, false otherwise.
     */
    public function isAutoPageBreakEnabled($pid = -1)
    {
        $pid = $this->sanitizePageID($pid);
        return $this->page[$pid]['autobreak'];
    }

    /**
     * Enable or disable automatic page break.
     *
     * @param bool $isenabled Set this to true to enable automatic page break.
     * @param int $pid page index. Omit or set it to -1 for the current page ID.
     */
    public function enableAutoPageBreak($isenabled = true, $pid = -1)
    {
        $pid = $this->sanitizePageID($pid);
        $this->page[$pid]['autobreak'] = (bool) $isenabled;
    }

    /**
     * Check if the specified position is outside the region.
     *
     * @param float  $pos Position.
     * @param string $min ID of the min region value to check.
     * @param string $max ID of the max region value to check.
     * @param int $pid page index. Omit or set it to -1 for the current page ID.
     *
     * @return boolean
     */
    private function isOutRegion($pos, $min, $max, $pid = -1)
    {
        $region = $this->getRegion($pid);
        return (($pos < ($region[$min] - self::EPS)) || ($pos > ($region[$max] + self::EPS)));
    }

    /**
     * Check if the specified vertical position is outside the region.
     *
     * @param float $posy   Y position or NULL for current position.
     * @param float $height Additional height to add.
     * @param int $pid page index. Omit or set it to -1 for the current page ID.
     *
     * @return boolean
     */
    public function isYOutRegion($posy = null, $height = 0, $pid = -1)
    {
        if ($posy === null) {
            $posy = $this->getY();
        }
        return $this->isOutRegion(floatval($posy + $height), 'RY', 'RT', $pid);
    }

    /**
     * Check if the specified horizontal position is outside the region.
     *
     * @param float $posx  X position or NULL for current position.
     * @param float $width Additional width to add.
     * @param int $pid page index. Omit or set it to -1 for the current page ID.
     *
     * @return boolean
     */
    public function isXOutRegion($posx = null, $width = 0, $pid = -1)
    {
        if ($posx === null) {
            $posx = $this->getX();
        }
        return $this->isOutRegion(floatval($posx + $width), 'RX', 'RL', $pid);
    }

    /**
     * Return the absolute horizontal cursor position for the current region.
     *
     * @param int $pid page index. Omit or set it to -1 for the current page ID.
     *
     * @return float
     */
    public function getX($pid = -1)
    {
        $pid = $this->sanitizePageID($pid);
        return $this->page[$pid]['region'][$this->page[$pid]['currentRegion']]['x'];
    }

    /**
     * Return the absolute vertical cursor position for the current region.
     *
     * @param int $pid page index. Omit or set it to -1 for the current page ID.
     *
     * @return float
     */
    public function getY($pid = -1)
    {
        $pid = $this->sanitizePageID($pid);
        return $this->page[$pid]['region'][$this->page[$pid]['currentRegion']]['y'];
    }

    /**
     * Set the absolute horizontal cursor position for the current region.
     *
     * @param float $xpos X position relative to the page coordinates.
     * @param int   $pid page index. Omit or set it to -1 for the current page ID.
     */
    public function setX($xpos, $pid = -1)
    {
        $pid = $this->sanitizePageID($pid);
        $this->page[$pid]['region'][$this->page[$pid]['currentRegion']]['x'] = floatval($xpos);
        return $this;
    }

    /**
     * Set the absolute vertical cursor position for the current region.
     *
     * @param float $ypos Y position relative to the page coordinates.
     * @param int   $pid page index. Omit or set it to -1 for the current page ID.
     */
    public function setY($ypos, $pid = -1)
    {
        $pid = $this->sanitizePageID($pid);
        $this->page[$pid]['region'][$this->page[$pid]['currentRegion']]['y'] = floatval($ypos);
        return $this;
    }
}
