<?php

/**
 * Page.php
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
use Com\Tecnick\Pdf\Encrypt\Encrypt;
use Com\Tecnick\Pdf\Page\Exception as PageException;

/**
 * Com\Tecnick\Pdf\Page\Page
 *
 * @since       2011-05-23
 * @category    Library
 * @package     PdfPage
 * @author      Nicola Asuni <info@tecnick.com>
 * @copyright   2011-2023 Nicola Asuni - Tecnick.com LTD
 * @license     http://www.gnu.org/copyleft/lesser.html GNU-LGPL v3 (see LICENSE.TXT)
 * @link        https://github.com/tecnickcom/tc-lib-pdf-page
 *
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class Page extends \Com\Tecnick\Pdf\Page\Region
{
    /**
     * Initialize page data.
     *
     * @param string  $unit     Unit of measure ('pt', 'mm', 'cm', 'in').
     * @param Color   $col      Color object.
     * @param Encrypt $enc      Encrypt object.
     * @param bool    $pdfa     True if we are in PDF/A mode.
     * @param bool    $compress Set to false to disable stream compression.
     * @param bool    $sigapp   True if the signature approval is enabled (for incremental updates).
     */
    public function __construct(
        $unit,
        Color $col,
        Encrypt $enc,
        $pdfa = false,
        $compress = true,
        $sigapp = false
    ) {
        $this->kunit = $this->getUnitRatio($unit);
        $this->col = $col;
        $this->enc = $enc;
        $this->pdfa = (bool) $pdfa;
        $this->compress = (bool) $compress;
        $this->sigapp = (bool) $sigapp;
    }

    /**
     * Get the unit ratio.
     *
     * @return float Unit Ratio.
     */
    public function getKUnit()
    {
        return $this->kunit;
    }

    /**
     * Enable Signature Approval.
     *
     * @param bool $sigapp True if the signature approval is enabled (for incremental updates).
     */
    public function enableSignatureApproval($sigapp)
    {
        $this->sigapp = (bool) $sigapp;
        return $this;
    }

    /**
     * Remove the specified page.
     *
     * @param int $pid page index. Omit or set it to -1 for the current page ID.
     *
     * @return array Removed page.
     */
    public function delete($pid = -1)
    {
        $pid = $this->sanitizePageID($pid);
        $page = $this->page[$pid];
        $this->group[$this->page[$pid]['group']] -= 1;
        unset($this->page[$pid]);
        $this->page = array_values($this->page); // reindex array
        --$this->pmaxid;
        return $page;
    }

    /**
     * Remove and return last page.
     *
     * @return array Removed page.
     */
    public function pop()
    {
        return $this->delete($this->pmaxid);
    }

    /**
     * Move a page to a previous position.
     *
     * @param int $from Index of the page to move.
     * @param int $new  Destination index.
     */
    public function move($from, $new)
    {
        if (($from <= $new) || ($from > $this->pmaxid)) {
            throw new PageException('The new position must be lower than the starting position');
        }
        $this->page = array_values(
            array_merge(
                array_slice($this->page, 0, $new),
                array($this->page[$from]),
                array_slice($this->page, $new, ($from - $new)),
                array_slice($this->page, ($from + 1))
            )
        );
    }

    /**
     * Returns the array (stack) containing all pages data.
     *
     * return array Pages.
     */
    public function getPages()
    {
        return $this->page;
    }

    /**
     * Add Annotation references.
     *
     * @param int $oid Annotation object IDs.
     * @param int $pid page index. Omit or set it to -1 for the current page ID.
     */
    public function addAnnotRef($oid, $pid = -1)
    {
        $pid = $this->sanitizePageID($pid);
        $this->page[$pid]['annotrefs'][] = (int) $oid;
    }

    /**
     * Add page content.
     *
     * @param string $content Page content.
     * @param int    $pid     Page index. Omit or set it to -1 for the current page ID.
     */
    public function addContent($content, $pid = -1)
    {
        $pid = $this->sanitizePageID($pid);
        $this->page[$pid]['content'][] = (string) $content;
    }

    /**
     * Remove and return last page content.
     *
     * @param int $pid page index. Omit or set it to -1 for the current page ID.
     *
     * @return string
     */
    public function popContent($pid = -1)
    {
        $pid = $this->sanitizePageID($pid);
        return array_pop($this->page[$pid]['content']);
    }

    /**
     * Add page content mark.
     *
     * @param int $pid page index. Omit or set it to -1 for the current page ID.
     */
    public function addContentMark($pid = -1)
    {
        $pid = $this->sanitizePageID($pid);
        $this->page[$pid]['content_mark'][] = count($this->page[$pid]['content']);
    }

    /**
     * Remove the last marked page content.
     *
     * @param int $pid page index. Omit or set it to -1 for the current page ID.
     */
    public function popContentToLastMark($pid = -1)
    {
        $pid = $this->sanitizePageID($pid);
        $mark = array_pop($this->page[$pid]['content_mark']);
        $this->page[$pid]['content'] = array_slice($this->page[$pid]['content'], 0, $mark, true);
    }

    /**
     * Returns the PDF command to output all page sections.
     *
     * @param int $pon Current PDF object number.
     *
     * @return string PDF command.
     */
    public function getPdfPages(&$pon)
    {
        $out = $this->getPageRootObj($pon);
        foreach ($this->page as $num => $page) {
            if (!isset($page['num'])) {
                if ($num > 0) {
                    if ($page['group'] == $this->page[($num - 1)]['group']) {
                        $page['num'] = (1 + $this->page[($num - 1)]['num']);
                    } else {
                        // new page group
                        $page['num'] = 1;
                    }
                } else {
                    $page['num'] = (1 + $num);
                }
            }
            $this->page[$num]['num'] = $page['num'];

            $content = $this->replacePageTemplates($page);
            $out .= $this->getPageContentObj($pon, $content);
            $contentobjid = $pon;

            $out .= $page['n'] . ' 0 obj' . "\n"
                . '<<' . "\n"
                . '/Type /Page' . "\n"
                . '/Parent ' . $this->rootoid . ' 0 R' . "\n";
            if (!$this->pdfa) {
                $out .= '/Group << /Type /Group /S /Transparency /CS /DeviceRGB >>' . "\n";
            }
            if (!$this->sigapp) {
                $out .= '/LastModified ' . $this->enc->getFormattedDate($page['time'], $pon) . "\n";
            }
            $out .= '/Resources ' . $this->rdoid . ' 0 R' . "\n"
                . $this->getBox($page['box'])
                . $this->getBoxColorInfo($page['box'])
                . '/Contents ' . $contentobjid . ' 0 R' . "\n"
                . '/Rotate ' . $page['rotation'] . "\n"
                . '/PZ ' . sprintf('%F', $page['zoom']) . "\n"
                . $this->getPageTransition($page)
                . $this->getAnnotationRef($page)
                . '>>' . "\n"
                . 'endobj' . "\n";
        }
        return $out;
    }

    /**
     * Returns the reserved Object ID for the Resource dictionary.
     *
     * return int Resource dictionary Object ID.
     */
    public function getResourceDictObjID()
    {
        return $this->rdoid;
    }

    /**
     * Returns the root object ID.
     *
     * return int Root Object ID.
     */
    public function getRootObjID()
    {
        return $this->rootoid;
    }

    /**
     * Returns the PDF command to output the page content.
     *
     * @param array $page Page data.
     *
     * @return string PDF command.
     */
    protected function getPageTransition($page)
    {
        if (empty($page['transition'])) {
            return '';
        }
        $entries = array('S', 'D', 'Dm', 'M', 'Di', 'SS', 'B');
        $out = '';
        if (isset($page['transition']['Dur'])) {
            $out .= '/Dur ' . sprintf('%F', $page['transition']['Dur']) . "\n";
        }
        $out .= '/Trans <<' . "\n"
            . '/Type /Trans' . "\n";
        foreach ($page['transition'] as $key => $val) {
            if (in_array($key, $entries)) {
                if (is_float($val)) {
                    $val = sprintf('%F', $val);
                }
                $out .= '/' . $key . ' /' . $val . "\n";
            }
        }
        $out .= '>>' . "\n";
        return $out;
    }

    /**
     * Get references to page annotations.
     *
     * @param array $page Page data.
     *
     * @return string PDF command.
     */
    protected function getAnnotationRef($page)
    {
        if (empty($page['annotrefs'])) {
            return '';
        }
        $out = '/Annots [ ';
        foreach ($page['annotrefs'] as $val) {
            $out .= intval($val) . ' 0 R ';
        }
        $out .= ']' . "\n";
        return $out;
    }

    /**
     * Returns the PDF command to output the page content.
     *
     * @param int    $pon     Current PDF object number.
     * @param string $content Page content.
     *
     * @return string PDF command.
     */
    protected function getPageContentObj(&$pon, $content = '')
    {
        $out = ++$pon . ' 0 obj' . "\n"
            . '<<';
        if ($this->compress) {
            $out .= ' /Filter /FlateDecode';
            $content = gzcompress($content);
        }
        $stream = $this->enc->encryptString($content, $pon);
        $out .= ' /Length ' . strlen($stream)
            . ' >>' . "\n"
            . 'stream' . "\n"
            . $stream . "\n"
            . 'endstream' . "\n"
            . 'endobj' . "\n";
        return $out;
    }

    /**
     * Returns the PDF command to output the page root object.
     *
     * @param int $pon Current PDF object number.
     *
     * @return string PDF command.
     */
    protected function getPageRootObj(&$pon)
    {
        $this->rdoid = ++$pon; // reserve object ID for the resource dictionary
        $this->rootoid = ++$pon;
        $out = $this->rootoid . ' 0 obj' . "\n";
        $out .= '<< /Type /Pages /Kids [ ';
        $numpages = count($this->page);
        for ($pid = 0; $pid < $numpages; ++$pid) {
            $this->page[$pid]['n'] = ++$pon;
            $out .= $this->page[$pid]['n'] . ' 0 R ';
        }
        $out .= '] /Count ' . $numpages . ' >>' . "\n"
            . 'endobj' . "\n";
        return $out;
    }

    /**
     * Replace page templates and numbers.
     *
     * @param array $data Page data.
     */
    protected function replacePageTemplates(array $data)
    {
        return implode(
            "\n",
            str_replace(
                array(self::PAGE_TOT, self::PAGE_NUM),
                array($this->group[$data['group']], $data['num']),
                $data['content']
            )
        );
    }
}
