<?php

/**
 * Page.php
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
use Com\Tecnick\Pdf\Encrypt\Encrypt;
use Com\Tecnick\Pdf\Page\Exception as PageException;

/**
 * Com\Tecnick\Pdf\Page\Page
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
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class Page extends \Com\Tecnick\Pdf\Page\Region
{
    /**
     * Initialize page data.
     *
     * @param string  $unit     Unit of measure ('pt', 'mm', 'cm', 'in').
     * @param Color   $color    Color object.
     * @param Encrypt $encrypt  Encrypt object.
     * @param bool    $pdfa     True if we are in PDF/A mode.
     * @param bool    $compress Set to false to disable stream compression.
     * @param bool    $sigapp   True if the signature approval is enabled (for incremental updates).
     */
    public function __construct(
        string $unit,
        Color $color,
        Encrypt $encrypt,
        bool $pdfa = false,
        bool $compress = true,
        bool $sigapp = false
    ) {
        $this->kunit = $this->getUnitRatio($unit);
        $this->col = $color;
        $this->enc = $encrypt;
        $this->pdfa = $pdfa;
        $this->compress = $compress;
        $this->sigapp = $sigapp;
    }

    /**
     * Get the unit ratio.
     *
     * @return float Unit Ratio.
     */
    public function getKUnit(): float
    {
        return $this->kunit;
    }

    /**
     * Enable Signature Approval.
     *
     * @param bool $sigapp True if the signature approval is enabled (for incremental updates).
     */
    public function enableSignatureApproval(bool $sigapp): static
    {
        $this->sigapp = $sigapp;
        return $this;
    }

    /**
     * Remove the specified page.
     *
     * @param int $pid page index. Omit or set it to -1 for the current page ID.
     *
     * @return PageData Removed page.
     */
    public function delete(int $pid = -1): array
    {
        $pid = $this->sanitizePageID($pid);
        $page = $this->page[$pid];
        --$this->group[$this->page[$pid]['group']];
        unset($this->page[$pid]);
        $this->page = array_values($this->page); // reindex array
        --$this->pmaxid;
        return $page;
    }

    /**
     * Remove and return last page.
     *
     * @return PageData Removed page.
     */
    public function pop(): array
    {
        return $this->delete($this->pmaxid);
    }

    /**
     * Move a page to a previous position.
     *
     * @param int $from Index of the page to move.
     * @param int $new  Destination index.
     */
    public function move(int $from, int $new): void
    {
        if (($from <= $new) || ($from > $this->pmaxid)) {
            throw new PageException('The new position must be lower than the starting position');
        }

        $this->page = array_values(
            [
                ...array_slice($this->page, 0, $new),
                $this->page[$from],
                ...array_slice($this->page, $new, ($from - $new)),
                ...array_slice($this->page, ($from + 1)),
            ]
        );
    }

    /**
     * Returns the array (stack) containing all pages data.
     *
     * @return array<int, PageData> Pages.
     */
    public function getPages(): array
    {
        return $this->page;
    }

    /**
     * Add Annotation references.
     *
     * @param int $oid Annotation object IDs.
     * @param int $pid page index. Omit or set it to -1 for the current page ID.
     */
    public function addAnnotRef(int $oid, int $pid = -1): void
    {
        $pid = $this->sanitizePageID($pid);
        $this->page[$pid]['annotrefs'][] = $oid;
    }

    /**
     * Add page content.
     *
     * @param string $content Page content.
     * @param int    $pid     Page index. Omit or set it to -1 for the current page ID.
     */
    public function addContent(string $content, int $pid = -1): void
    {
        $pid = $this->sanitizePageID($pid);
        $this->page[$pid]['content'][] = $content;
    }

    /**
     * Remove and return last page content.
     *
     * @param int $pid page index. Omit or set it to -1 for the current page ID.
     */
    public function popContent(int $pid = -1): string
    {
        $pid = $this->sanitizePageID($pid);
        $page = array_pop($this->page[$pid]['content']);
        if ($page === null) {
            throw new PageException('Page content is empty');
        }

        return $page;
    }

    /**
     * Add page content mark.
     *
     * @param int $pid page index. Omit or set it to -1 for the current page ID.
     */
    public function addContentMark(int $pid = -1): void
    {
        $pid = $this->sanitizePageID($pid);
        $this->page[$pid]['content_mark'][] = count($this->page[$pid]['content']);
    }

    /**
     * Remove the last marked page content.
     *
     * @param int $pid page index. Omit or set it to -1 for the current page ID.
     */
    public function popContentToLastMark(int $pid = -1): void
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
    public function getPdfPages(int &$pon): string
    {
        $out = $this->getPageRootObj($pon);
        foreach ($this->page as $num => $page) {
            if (! isset($page['num'])) {
                if ($num > 0) {
                    $page['num'] = (
                        $page['group'] == $this->page[($num - 1)]['group']
                    ) ? (1 + $this->page[($num - 1)]['num']) : 1;
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
            if (! $this->pdfa) {
                $out .= '/Group << /Type /Group /S /Transparency /CS /DeviceRGB >>' . "\n";
            }

            if (! $this->sigapp) {
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
     * @return int Resource dictionary Object ID.
     */
    public function getResourceDictObjID(): int
    {
        return $this->rdoid;
    }

    /**
     * Returns the root object ID.
     *
     * @return int Root Object ID.
     */
    public function getRootObjID(): int
    {
        return $this->rootoid;
    }

    /**
     * Returns the PDF command to output the page content.
     *
     * @param PageData $page Page data.
     *
     * @return string PDF command.
     */
    protected function getPageTransition(array $page): string
    {
        if (empty($page['transition'])) {
            return '';
        }

        $entries = ['B', 'D', 'Di', 'Dm', 'M', 'S', 'SS'];
        $out = '';
        $out .= '/Dur ' . sprintf('%F', $page['transition']['Dur']) . "\n";

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

        return $out . ('>>' . "\n");
    }

    /**
     * Get references to page annotations.
     *
     * @param PageData $page Page data.
     *
     * @return string PDF command.
     */
    protected function getAnnotationRef(array $page): string
    {
        if (empty($page['annotrefs'])) {
            return '';
        }

        $out = '/Annots [ ';
        foreach ($page['annotrefs'] as $val) {
            $out .= (int) $val . ' 0 R ';
        }

        return $out . (']' . "\n");
    }

    /**
     * Returns the PDF command to output the page content.
     *
     * @param int    $pon     Current PDF object number.
     * @param string $content Page content.
     *
     * @return string PDF command.
     */
    protected function getPageContentObj(int &$pon, string $content = ''): string
    {
        $out = ++$pon . ' 0 obj' . "\n"
            . '<<';
        if ($this->compress) {
            $out .= ' /Filter /FlateDecode';
            $cmpr = gzcompress($content);
            if ($cmpr !== false) {
                $content = $cmpr;
            }
        }

        $stream = $this->enc->encryptString($content, $pon);
        return $out . (' /Length ' . strlen($stream)
            . ' >>' . "\n"
            . 'stream' . "\n"
            . $stream . "\n"
            . 'endstream' . "\n"
            . 'endobj' . "\n");
    }

    /**
     * Returns the PDF command to output the page root object.
     *
     * @param int $pon Current PDF object number.
     *
     * @return string PDF command.
     */
    protected function getPageRootObj(int &$pon): string
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

        return $out . ('] /Count ' . $numpages . ' >>' . "\n"
            . 'endobj' . "\n");
    }

    /**
     * Replace page templates and numbers.
     *
     * @param PageData $data Page data.
     */
    protected function replacePageTemplates(array $data): string
    {
        return implode(
            "\n",
            str_replace(
                [self::PAGE_TOT, self::PAGE_NUM],
                [$this->group[$data['group']], $data['num']],
                $data['content']
            )
        );
    }
}
