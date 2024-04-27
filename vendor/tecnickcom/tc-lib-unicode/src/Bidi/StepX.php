<?php

/**
 * StepX.php
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
use Com\Tecnick\Unicode\Data\Type as UniType;

/**
 * Com\Tecnick\Unicode\Bidi\StepX
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
 *
 * @phpstan-type DssData array{
 *          'ord': int,
 *          'cel': int,
 *          'dos': string,
 *          'dis': bool,
 *      }
 */
class StepX
{
    /**
     * Maximum embedding level
     */
    public const MAX_DEPTH = 125;

    /**
     * Directional Status Stack
     *
     * @var array<int, DssData>
     */
    protected array $dss = [];

    /**
     * Overflow Isolate Count
     */
    protected int $oic = 0;

    /**
     * Overflow Embedding Count
     */
    protected int $oec = 0;

    /**
     * Valid Isolate Count
     */
    protected int $vic = 0;

    /**
     * Array of characters data to return
     *
     * @var array<int, CharData>
     */
    protected array $chardata = [];

    /**
     * X Steps for Bidirectional algorithm
     * Explicit Levels and Directions
     *
     * @param array<int> $ordarr Array of UTF-8 codepoints
     * @param int   $pel    Paragraph embedding level
     */
    public function __construct(
        /**
         * Array of UTF-8 codepoints
         */
        protected array $ordarr,
        int $pel
    ) {
        //     - Push onto the stack an entry consisting of the paragraph embedding level,
        //       a neutral directional override status, and a false directional isolate status.
        $this->dss[] = [
            'ord' => -1, // dummy value, not used
            'cel' => $pel,
            'dos' => 'NI',
            'dis' => false,
        ];
        //     - Process each character iteratively, applying rules X2 through X8.
        //       Only embedding levels from 0 through max_depth are valid in this phase.
        //       (Note that in the resolution of levels in rules I1 and I2,
        //       the maximum embedding level of max_depth+1 can be reached.)
        $this->processX();
    }

    /**
     * Returns the processed array
     *
     * @return array<int, CharData>
     */
    public function getChrData(): array
    {
        return $this->chardata;
    }

    /**
     * Calculate the Least Even
     *
     * @param int $num Number to process
     */
    protected function getLEven(int $num): int
    {
        return (2 + $num - ($num % 2));
    }

    /**
     * Calculate the Least Odd
     *
     * @param int $num Number to process
     */
    protected function getLOdd(int $num): int
    {
        return (1 + $num + ($num % 2));
    }

    /**
     * Process X1
     */
    protected function processX(): void
    {
        foreach ($this->ordarr as $key => $ord) {
            $this->processXcase($key, $ord);
        }
    }

    /**
     * Process X1 case
     *
     * @param int $pos Original character position in the input string
     * @param int $ord Char code
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function processXcase(int $pos, int $ord): void
    {
        $edss = end($this->dss);
        if ($edss === false) {
            return;
        }

        switch ($ord) {
            case UniConstant::RLE:
                // X2
                $this->setDss($this->getLOdd($edss['cel']), UniConstant::RLE, 'NI');
                break;
            case UniConstant::LRE:
                // X3
                $this->setDss($this->getLEven($edss['cel']), UniConstant::LRE, 'NI');
                break;
            case UniConstant::RLO:
                // X4
                $this->setDss($this->getLOdd($edss['cel']), UniConstant::RLO, 'R');
                break;
            case UniConstant::LRO:
                // X5
                $this->setDss($this->getLEven($edss['cel']), UniConstant::LRO, 'L');
                break;
            case UniConstant::RLI:
                // X5a
                $this->processChar($pos, $ord, $edss);
                $this->setDss($this->getLOdd($edss['cel']), UniConstant::RLI, 'NI', true, true, 1);
                break;
            case UniConstant::LRI:
                // X5b
                $this->processChar($pos, $ord, $edss);
                $this->setDss($this->getLEven($edss['cel']), UniConstant::LRI, 'NI', true, true, 1);
                break;
            case UniConstant::FSI:
                // X5c
                $this->processChar($pos, $ord, $edss);
                $this->processFsiCase($pos, $edss);
                break;
            case UniConstant::PDI:
                // X6a
                $this->processPdiCase($pos, $ord, $edss);
                break;
            case UniConstant::PDF:
                // X7
                $this->processPdfCase($edss);
                break;
            default:
                // X6
                $this->processChar($pos, $ord, $edss);
                break;
        }
    }

    /**
     * Set temporary data (X2 to X5)
     *
     * @param int         $cel     Embedding Level
     * @param int         $ord     Char code
     * @param string      $dos     Directional override status
     * @param bool        $dis     Directional isolate status
     * @param bool $isolate True if Isolate initiator
     * @param int         $ivic    increment for the valid isolate count
     */
    protected function setDss(
        int $cel,
        int $ord,
        string $dos,
        bool $dis = false,
        bool $isolate = false,
        int $ivic = 0
    ): void {
        // X2 to X5
        //     - Compute the least odd|even embedding level greater than the embedding level of the last entry
        //       on the directional status stack.
        //     - If this new level would be valid, and the overflow isolate count and overflow embedding
        //       count are both zero, then this RLE is valid. Push an entry consisting of the new embedding
        //       level, neutral|left|right directional override status, and false directional isolate status onto the
        //       directional status stack.
        //     - Otherwise, this is an overflow RLE. If the overflow isolate count is zero, increment the
        //       overflow embedding|isolate count by one. Leave all other variables unchanged.
        if (($cel >= self::MAX_DEPTH) || ($this->oic != 0) || ($this->oec != 0)) {
            if ($isolate) {
                ++$this->oic;
            } elseif ($this->oic == 0) {
                ++$this->oec;
            }

            return;
        }

        $this->vic += $ivic;
        $this->dss[] = [
            'ord' => $ord,
            'cel' => $cel,
            'dos' => $dos,
            'dis' => $dis,
        ];
    }

    /**
     * Push a char on the stack
     *
     * @param int   $pos  Original character position in the input string
     * @param int   $ord  Char code
     * @param DssData $edss Last entry in the Directional Status Stack
     */
    protected function pushChar(int $pos, int $ord, array $edss): void
    {
        $unitype = (UniType::UNI[$ord] ?? $edss['dos']);
        $this->chardata[] = [
            'char' => $ord,
            'i' => -1,
            'level' => $edss['cel'],
            'otype' => $unitype,
            'pdimatch' => -1,
            'pos' => $pos,
            'type' => (($edss['dos'] !== 'NI') ? $edss['dos'] : $unitype),
            'x' => -1,
        ];
    }

    /**
     * Process normal char (X6)
     *
     * @param int   $pos  Original character position in the input string
     * @param int   $ord  Char code
     * @param DssData $edss Last entry in the Directional Status Stack
     */
    protected function processChar(int $pos, int $ord, array $edss): void
    {
        // X6. For all types besides B, BN, RLE, LRE, RLO, LRO, PDF, RLI, LRI, FSI, and PDI:
        //     - Set the current character’s embedding level to the embedding level
        //       of the last entry on the directional status stack.
        //     - Whenever the directional override status of the last entry on the directional status stack
        //       is not neutral, reset the current character type according to the directional override
        //       status of the last entry on the directional status stack.
        if (isset(UniType::UNI[$ord]) && ((UniType::UNI[$ord] == 'B') || (UniType::UNI[$ord] == 'BN'))) {
            return;
        }

        $this->pushChar($pos, $ord, $edss);
    }

    /**
     * Process the PDF type character
     *
     * @param DssData $edss Last entry in the Directional Status Stack
     */
    protected function processPdfCase(array $edss): void
    {
        // X7. With each PDF, perform the following steps:
        //     - If the overflow isolate count is greater than zero, do nothing. (This PDF is within the
        //       scope of an overflow isolate initiator. It either matches and terminates the scope of an
        //       overflow embedding initiator within that overflow isolate, or does not match any
        //       embedding initiator.)
        if ($this->oic > 0) {
            return;
        }

        //     - Otherwise, if the overflow embedding count is greater than zero, decrement it by one.
        //       (This PDF matches and terminates the scope of an overflow embedding initiator that is not
        //       within the scope of an overflow isolate initiator.)
        if ($this->oec > 0) {
            --$this->oec;
            return;
        }

        //     - Otherwise, if the directional isolate status of the last entry on the directional status
        //       stack is false, and the directional status stack contains at least two entries, pop the
        //       last entry from the directional status stack. (This PDF matches and terminates the scope
        //       of a valid embedding initiator. Since the stack has at least two entries, this pop does
        //       not leave the stack empty.)
        if (($edss['dis'] === false) && (count($this->dss) > 1)) {
            array_pop($this->dss);
        }

        //     - Otherwise, do nothing. (This PDF does not match any embedding initiator.)
    }

    /**
     * Process the PDI type character
     *
     * @param int   $pos  Original character position in the input string
     * @param int   $ord  Char code
     * @param DssData $edss Last entry in the Directional Status Stack
     */
    protected function processPdiCase(int $pos, int $ord, array $edss): void
    {
        // X6a. With each PDI, perform the following steps:
        //      - If the overflow isolate count is greater than zero, this PDI matches an overflow isolate
        //        initiator. Decrement the overflow isolate count by one.
        if ($this->oic > 0) {
            --$this->oic;
            return;
        }

        //      - Otherwise, if the valid isolate count is zero, this PDI does not match any isolate
        //        initiator, valid or overflow. Do nothing.
        if ($this->vic == 0) {
            return;
        }

        //      - Otherwise, this PDI matches a valid isolate initiator. Perform the following steps:
        //        - Reset the overflow embedding count to zero. (This terminates the scope of those overflow
        //          embedding initiators within the scope of the matched isolate initiator whose scopes have
        //          not been terminated by a matching PDF, and which thus lack a matching PDF.)
        $this->oec = 0;
        //        - While the directional isolate status of the last entry on the stack is false, pop the
        //          last entry from the directional status stack. (This terminates the scope of those valid
        //          embedding initiators within the scope of the matched isolate initiator whose scopes have
        //          not been terminated by a matching PDF, and which thus lack a matching PDF. Given that the
        //          valid isolate count is non-zero, the directional status stack before this step is
        //          executed must contain an entry with directional isolate status true, and thus after this
        //          step is executed the last entry on the stack will indeed have a true directional isolate
        //          status, i.e. represent the scope of the matched isolate initiator. This cannot be the
        //          stack's first entry, which always belongs to the paragraph level and has a false
        //          directional status, so there is at least one more entry below it on the stack.)
        $count_dss = count($this->dss);
        while (($edss['dis'] === false) && ($count_dss > 1)) {
            array_pop($this->dss);
            --$count_dss;
            $edss = end($this->dss);
            if ($edss === false) {
                break;
            }
        }

        //        - Pop the last entry from the directional status stack and decrement the valid isolate
        //          count by one. (This terminates the scope of the matched isolate initiator. Since the
        //          preceding step left the stack with at least two entries, this pop does not leave the
        //          stack empty.)
        array_pop($this->dss);
        --$this->vic;

        $edss = end($this->dss);
        if ($edss === false) {
            return;
        }

        //      - In all cases, look up the last entry on the directional status stack left after the
        //        steps above and:
        //        - Set the PDI’s level to the entry's embedding level.
        //        - If the entry's directional override status is not neutral, reset the current character type
        //          from PDI to L if the override status is left-to-right, and to R if the override status is
        //          right-to-left.
        $this->pushChar($pos, $ord, $edss);
    }

    /**
     * Process the PDF type character
     *
     * @param int   $pos  Original character position in the input string
     * @param DssData $edss Last entry in the Directional Status Stack
     */
    protected function processFsiCase(int $pos, array $edss): void
    {
        // X5c. With each FSI, apply rules P2 and P3 to the sequence of characters between the FSI and its
        //      matching PDI, or if there is no matching PDI, the end of the paragraph, as if this sequence
        //      of characters were a paragraph. If these rules decide on paragraph embedding level 1, treat
        //      the FSI as an RLI in rule X5a. Otherwise, treat it as an LRI in rule X5b.
        $stepp = new StepP(array_slice($this->ordarr, $pos));
        if ($stepp->getPel() == 0) {
            $this->setDss($this->getLEven($edss['cel']), UniConstant::LRI, 'NI', true, true, 1);
        } else {
            $this->setDss($this->getLOdd($edss['cel']), UniConstant::RLI, 'NI', true, true, 1);
        }
    }
}
