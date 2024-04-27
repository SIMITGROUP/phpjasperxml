<?php

/**
 * Arabic.php
 *
 * @since     2011-05-23
 * @category  Library
 * @package   Unicode
 * @author    Nicola Asuni <info@tecnick.com>
 * @copyright 2011-2024 Nicola Asuni - Tecnick.com LTD
 * @license   http://www.gnu.org/copyleft/lesser.html GNU-LGPL v3 (see LICENSE.TXT)
 * @link      https://github.com/tecnickcom/tc-lib-unicode
 *
 * This file is part of tc-lib-unicode software library.
 */

namespace Com\Tecnick\Unicode\Bidi\Shaping;

use Com\Tecnick\Unicode\Data\Arabic as UniArabic;

/**
 * Com\Tecnick\Unicode\Bidi\Shaping\Arabic
 *
 * @since     2015-07-13
 * @category  Library
 * @package   Unicode
 * @author    Nicola Asuni <info@tecnick.com>
 * @copyright 2011-2024 Nicola Asuni - Tecnick.com LTD
 * @license   http://www.gnu.org/copyleft/lesser.html GNU-LGPL v3 (see LICENSE.TXT)
 * @link      https://github.com/tecnickcom/tc-lib-unicode
 *
 * @phpstan-type CharData array{
 *     'char': int,
 *     'i': int,
 *     'level': int,
 *     'otype': string,
 *     'pdimatch': int,
 *     'pos': int,
 *     'type': string,
 *     'x': int,
 * }
 *
 * @phpstan-type SeqData array{
 *     'e': int,
 *     'edir': string,
 *     'end': int,
 *     'eos': string,
 *     'length': int,
 *     'maxlevel': int,
 *     'sos': string,
 *     'start': int,
 *     'item': array<int, CharData>,
 * }
 */
abstract class Arabic
{
    /**
     * Sequence to process and return
     *
     * @var SeqData
     */
    protected array $seq = [
        'e' => 0,
        'edir' => '',
        'end' => 0,
        'eos' => '',
        'length' => 0,
        'maxlevel' => 0,
        'sos' => '',
        'start' => 0,
        'item' => [],
    ];

    /**
     * Array of processed chars
     *
     * @var array<int, CharData>
     */
    protected array $newchardata = [];

    /**
     * Array of AL characters
     *
     * @var array<int, CharData>
     */
    protected array $alchars = [];

    /**
     * Number of AL characters
     */
    protected int $numalchars = 0;

    /**
     * Check if it is a LAA LETTER
     *
     * @param ?CharData $prevchar Previous char
     * @param CharData  $thischar Current char
     */
    protected function isLaaLetter(?array $prevchar, array $thischar): bool
    {
        return ($prevchar !== null)
            && ($prevchar['char'] == UniArabic::LAM)
            && (isset(UniArabic::LAA[$thischar['char']]));
    }

    /**
     * Check next char
     *
     * @param CharData  $thischar Current char
     * @param ?CharData $nextchar Next char
     */
    protected function hasNextChar(array $thischar, ?array $nextchar): bool
    {
        return (($nextchar !== null)
            && (($nextchar['otype'] == 'AL') || ($nextchar['otype'] == 'NSM'))
            && ($nextchar['type'] == $thischar['type'])
            && ($nextchar['char'] != UniArabic::QUESTION_MARK)
        );
    }

    /**
     * Check previous char
     *
     * @param ?CharData $prevchar Previous char
     * @param CharData  $thischar Current char
     */
    protected function hasPrevChar(?array $prevchar, array $thischar): bool
    {
        return ((($prevchar !== null)
            && (($prevchar['otype'] == 'AL') || ($prevchar['otype'] == 'NSM'))
            && ($prevchar['type'] == $thischar['type']))
        );
    }

    /**
     * Check if it is a middle character
     *
     * @param ?CharData $prevchar Previous char
     * @param CharData  $thischar Current char
     * @param ?CharData $nextchar Next char
     */
    protected function isMiddleChar(?array $prevchar, array $thischar, ?array $nextchar): bool
    {
        return ($this->hasPrevChar($prevchar, $thischar) && $this->hasNextChar($thischar, $nextchar));
    }

    /**
     * Check if it is a final character
     *
     * @param ?CharData $prevchar Previous char
     * @param CharData  $thischar Current char
     * @param ?CharData $nextchar Next char
     */
    protected function isFinalChar(?array $prevchar, array $thischar, ?array $nextchar): bool
    {
        if ($this->hasPrevChar($prevchar, $thischar)) {
            return true;
        }

        return (($nextchar !== null) && ($nextchar['char'] == UniArabic::QUESTION_MARK));
    }

    /**
     * Set initial or middle char
     *
     * @param int                    $idx       Current index
     * @param ?CharData              $prevchar  Previous char
     * @param CharData               $thischar  Current char
     * @param array<int, array<int>> $arabicarr Substitution array
     */
    protected function setMiddleChar(int $idx, ?array $prevchar, array $thischar, array $arabicarr): void
    {
        if (($prevchar != null) && in_array($prevchar['char'], UniArabic::END)) {
            if (isset($arabicarr[$thischar['char']][2])) {
                // initial
                $this->newchardata[$idx]['char'] = $arabicarr[$thischar['char']][2];
            }
        } elseif (isset($arabicarr[$thischar['char']][3])) {
            // medial
            $this->newchardata[$idx]['char'] = $arabicarr[$thischar['char']][3];
        }
    }

    /**
     * Set initial char
     *
     * @param int                    $idx       Current index
     * @param CharData               $thischar  Current char
     * @param array<int, array<int>> $arabicarr Substitution array
     */
    protected function setInitialChar(int $idx, array $thischar, array $arabicarr): void
    {
        if (isset($arabicarr[$this->seq['item'][$idx]['char']][2])) {
            $this->newchardata[$idx]['char'] = $arabicarr[$thischar['char']][2];
        }
    }

    /**
     * Set final char
     *
     * @param int                    $idx       Current index
     * @param ?CharData              $prevchar  Previous char
     * @param CharData               $thischar  Current char
     * @param array<int, array<int>> $arabicarr Substitution array
     */
    protected function setFinalChar(int $idx, ?array $prevchar, array $thischar, array $arabicarr): void
    {
        if (
            ($idx > 1)
            && ($thischar['char'] == UniArabic::HEH)
            && ($this->seq['item'][($idx - 1)]['char'] == UniArabic::LAM)
            && ($this->seq['item'][($idx - 2)]['char'] == UniArabic::LAM)
        ) {
            // Allah Word
            $this->newchardata[($idx - 2)]['char'] = -1;
            $this->newchardata[($idx - 1)]['char'] = -1;
            $this->newchardata[$idx]['char'] = UniArabic::LIGATURE_ALLAH_ISOLATED_FORM;
        } elseif (($prevchar !== null) && in_array($prevchar['char'], UniArabic::END)) {
            if (isset($arabicarr[$thischar['char']][0])) {
                // isolated
                $this->newchardata[$idx]['char'] = $arabicarr[$thischar['char']][0];
            }
        } elseif (isset($arabicarr[$thischar['char']][1])) {
            // final
            $this->newchardata[$idx]['char'] = $arabicarr[$thischar['char']][1];
        }
    }

    /**
     * Process AL character
     *
     * @param int       $idx      Current index
     * @param int       $pos      Current char position
     * @param ?CharData $prevchar Previous char
     * @param CharData  $thischar Current char
     * @param ?CharData $nextchar Next char
     */
    protected function processAlChar(int $idx, int $pos, ?array $prevchar, array $thischar, ?array $nextchar): void
    {
        $laaletter = $this->isLaaLetter($prevchar, $thischar);
        if ($laaletter) {
            $arabicarr = UniArabic::LAA;
            $prevchar = (($pos > 1) ? $this->alchars[($pos - 2)] : null);
        } else {
            $arabicarr = UniArabic::SUBSTITUTE;
        }

        if ($this->isMiddleChar($prevchar, $thischar, $nextchar)) {
            $this->setMiddleChar($idx, $prevchar, $thischar, $arabicarr);
        } elseif ($this->hasNextChar($thischar, $nextchar)) {
            $this->setInitialChar($idx, $thischar, $arabicarr);
        } elseif ($this->isFinalChar($prevchar, $thischar, $nextchar)) {
            // final
            $this->setFinalChar($idx, $prevchar, $thischar, $arabicarr);
        } elseif (isset($arabicarr[$thischar['char']][0])) {
            // isolated
            $this->newchardata[$idx]['char'] = $arabicarr[$thischar['char']][0];
        }

        // if laa letter
        if ($laaletter) {
            // mark characters to delete
            $this->newchardata[($this->alchars[($pos - 1)]['i'])]['char'] = -1;
        }
    }
}
