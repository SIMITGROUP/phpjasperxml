<?php

/**
 * StepP.php
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
 * Com\Tecnick\Unicode\Bidi\StepP
 *
 * @since     2015-07-13
 * @category  Library
 * @package   Unicode
 * @author    Nicola Asuni <info@tecnick.com>
 * @copyright 2011-2024 Nicola Asuni - Tecnick.com LTD
 * @license   http://www.gnu.org/copyleft/lesser.html GNU-LGPL v3 (see LICENSE.TXT)
 * @link      https://github.com/tecnickcom/tc-lib-unicode
 */
class StepP
{
    /**
     * P Steps for Bidirectional algorithm
     *
     * @param array<int> $ordarr Array of UTF-8 codepoints
     */
    public function __construct(
        /**
         * Array of UTF-8 codepoints
         */
        protected array $ordarr
    ) {
    }

    /**
     * Get the Paragraph Embedding Level
     */
    public function getPel(): int
    {
        // P2. In each paragraph, find the first character of type L, AL, or R
        //     while skipping over any characters between an isolate initiator and its matching PDI or,
        //     if it has no matching PDI, the end of the paragraph.
        // P3. If a character is found in P2 and it is of type AL or R,
        //     then set the paragraph embedding level to one; otherwise, set it to zero.
        $isolate = 0;
        foreach ($this->ordarr as $ord) {
            $isolate = $this->getIsolateLevel($ord, $isolate);
            if (($isolate == 0) && isset(UniType::UNI[$ord])) {
                $type = UniType::UNI[$ord];
                if ($type === 'L') {
                    return 0;
                }

                if (($type === 'R') || ($type === 'AL')) {
                    return 1;
                }
            }
        }

        return 0;
    }

    /**
     * Update the level of explicit directional isolates
     */
    protected function getIsolateLevel(int $ord, int $isolate): int
    {
        if (($ord == UniConstant::LRI) || ($ord == UniConstant::RLI) || ($ord == UniConstant::FSI)) {
            ++$isolate;
        } elseif ($ord == UniConstant::PDI) {
            --$isolate;
        }

        return max(0, $isolate);
    }
}
