<?php

/**
 * StepXten.php
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

namespace Com\Tecnick\Unicode\Bidi;

use Com\Tecnick\Unicode\Data\Constant as UniConstant;

/**
 * Com\Tecnick\Unicode\Bidi\StepXten
 *
 * @since     2015-07-13
 * @category  Library
 * @package   Unicode
 * @author    Nicola Asuni <info@tecnick.com>
 * @copyright 2011-2024 Nicola Asuni - Tecnick.com LTD
 * @license   http://www.gnu.org/copyleft/lesser.html GNU-LGPL v3 (see LICENSE.TXT)
 * @link      https://github.com/tecnickcom/tc-lib-unicode
 *
 * @phpstan-import-type SeqData from \Com\Tecnick\Unicode\Bidi\Shaping\Arabic
 * @phpstan-import-type CharData from \Com\Tecnick\Unicode\Bidi\Shaping\Arabic
 */
class StepXten
{
    /**
     * Number of characters
     */
    protected int $numchars;

    /**
     * Array of Level Run sequences
     *
     * @var array<int, array{'start': int, 'end': int, 'e': int}>
     */
    protected array $runseq = [];

    /**
     * Number of Level Run sequences
     */
    protected int $numrunseq = 0;

    /**
     * Array of Isolated Level Run sequences
     *
     * @var array<int, SeqData>
     */
    protected array $ilrs = [];

    /**
     * X Steps for Bidirectional algorithm
     *
     * @param array<int, CharData> $chardata Array of UTF-8 codepoints
     * @param int   $pel      Paragraph Embedding Level
     */
    public function __construct(
        /**
         * Array of characters data to return
         */
        protected array $chardata,
        /**
         * Paragraph Embedding Level
         */
        protected int $pel
    ) {
        $this->numchars = count($chardata);
        $this->setIsolatedLevelRunSequences();
    }

    /**
     * Get the Isolated Run Sequences
     *
     * @return array<int, SeqData>
     */
    public function getIsolatedLevelRunSequences(): array
    {
        return $this->ilrs;
    }

    /**
     * Get the embedded direction (L or R)
     */
    protected function getEmbeddedDirection(int $level): string
    {
        return ((($level % 2) == 0) ? 'L' : 'R');
    }

    protected function setLevelRunSequences(): void
    {
        $start = 0;
        while ($start < $this->numchars) {
            $end = ($start + 1);
            while (($end < $this->numchars) && ($this->chardata[$end]['level'] == $this->chardata[$start]['level'])) {
                ++$end;
            }

            --$end;
            $this->runseq[] = [
                'start' => $start,
                'end' => $end,
                'e' => $this->chardata[$start]['level'],
            ];
            ++$this->numrunseq;
            $start = ($end + 1);
        }
    }

    /**
     * returns true if the input char is an Isolate Initiator
     */
    protected function isIsolateInitiator(int $ord): bool
    {
        return (($ord == UniConstant::RLI) || ($ord == UniConstant::LRI) || ($ord == UniConstant::FSI));
    }

    /**
     * Set level Isolated Level Run Sequences
     */
    protected function setIsolatedLevelRunSequences(): void
    {
        $this->setLevelRunSequences();
        $numiso = 0;
        foreach ($this->runseq as $idx => $seq) {
            // Create a new level run sequence, and initialize it to contain just that level run
            $isorun = [
                'e' => $seq['e'],
                'edir' => $this->getEmbeddedDirection($seq['e']), // embedded direction
                'start' => $seq['start'], // position of the first char
                'end' => $seq['end'],  // position of the last char
                'length' => ($seq['end'] - $seq['start'] + 1),
                'sos' => '',  // start-of-sequence
                'eos' => '',  // end-of-sequence
                'maxlevel' => 0,
                'item' => [],
            ];
            for ($jdx = 0; $jdx < $isorun['length']; ++$jdx) {
                $isorun['item'][$jdx] = $this->chardata[($seq['start'] + $jdx)];
            }

            $endchar = $isorun['item'][($jdx - 1)]['char'];

            // While the level run currently last in the sequence ends with an isolate initiator that has a
            // matching PDI, append the level run containing the matching PDI to the sequence.
            // (Note that this matching PDI must be the first character of its level run.)
            $pdimatch = -1;
            if ($this->isIsolateInitiator($endchar)) {
                // find the next sequence with the same level that starts with a PDI
                for ($kdx = ($idx + 1); $kdx < $this->numrunseq; ++$kdx) {
                    if (
                        ($this->runseq[$kdx]['e'] == $isorun['e'])
                        && ($this->chardata[$this->runseq[$kdx]['start']]['char'] == UniConstant::PDI)
                    ) {
                        $pdimatch = $this->runseq[$kdx]['start'];
                        $this->chardata[$pdimatch]['pdimatch'] = $numiso;
                        break;
                    }
                }
            }

            // For each level run in the paragraph whose first character is not a PDI,
            // or is a PDI that does not match any isolate initiator
            if ($this->chardata[$seq['start']]['pdimatch'] >= 0) {
                $parent = $this->chardata[$seq['start']]['pdimatch'];

                $this->ilrs[$parent]['item'] = array_merge(
                    $this->ilrs[$parent]['item'],
                    $isorun['item']
                );

                $this->ilrs[$parent]['length'] += $isorun['length'];
                $this->ilrs[$parent]['end'] += $isorun['end'];
                if ($pdimatch >= 0) {
                    $this->chardata[$pdimatch]['pdimatch'] = $parent;
                }
            } else {
                $this->ilrs[$numiso] = $isorun;
                ++$numiso;
            }
        }

        $this->setStartEndOfSequence();
    }

    /**
     * Determine the start-of-sequence (sos) and end-of-sequence (eos) types, either L or R,
     * for each isolating run sequence.
     */
    protected function setStartEndOfSequence(): void
    {
        foreach ($this->ilrs as $key => $seq) {
            // For sos, compare the level of the first character in the sequence with the level of the character
            // preceding it in the paragraph (not counting characters removed by X9), and if there is none,
            // with the paragraph embedding level.
            $lev = $seq['item'][0]['level'];
            if ($seq['start'] == 0) {
                $prev = $this->pel;
            } else {
                $lastchr = $this->chardata[($seq['start'] - 1)];
                $prev = $lastchr['level'];
            }

            $this->ilrs[$key]['sos'] = $this->getEmbeddedDirection(max($prev, $lev));

            // For eos, compare the level of the last character in the sequence with the level of the character
            // following it in the paragraph (not counting characters removed by X9), and if there is none or the
            // last character of the sequence is an isolate initiator (lacking a matching PDI), with the paragraph
            // embedding level.
            $lastchr = end($seq['item']);
            if ($lastchr === false) {
                return;
            }

            $lev = $lastchr['level'];
            if ((! isset($this->chardata[($seq['end'] + 1)]['level'])) || $this->isIsolateInitiator($lastchr['char'])) {
                $next = $this->pel;
            } else {
                $next = $this->chardata[($seq['end'] + 1)]['level'];
            }

            $this->ilrs[$key]['eos'] = $this->getEmbeddedDirection(max($next, $lev));

            // If the higher level is odd, the sos or eos is R; otherwise, it is L.
        }
    }
}
