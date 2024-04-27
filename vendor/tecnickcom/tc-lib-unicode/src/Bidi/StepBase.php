<?php

/**
 * StepBase.php
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

/**
 * Com\Tecnick\Unicode\Bidi\StepBase
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
 */
abstract class StepBase
{
    /**
     * Initialize Sequence to process
     *
     * @param SeqData $seq     Isolated Sequence array
     * @param bool  $process If false disable automatic processing (this is a testing flag)
     */
    public function __construct(
        /**
         * Sequence to process and return
         */
        protected array $seq,
        $process = true
    ) {
        if ($process) {
            $this->process();
        }
    }

    /**
     * Returns the processed array
     *
     * @return SeqData
     */
    public function getSequence(): array
    {
        return $this->seq;
    }

    /**
     * Process the current step
     */
    abstract protected function process(): void;

    /**
     * Generic step
     *
     * @param string $method Processing methos
     */
    public function processStep($method): void
    {
        for ($idx = 0; $idx < $this->seq['length']; ++$idx) {
            $this->$method($idx);
        }
    }
}
