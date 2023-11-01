<?php

/**
 * TestUtil.php
 *
 * @since       2020-12-19
 * @category    Library
 * @package     PdfImage
 * @author      Nicola Asuni <info@tecnick.com>
 * @copyright   2015-2023 Nicola Asuni - Tecnick.com LTD
 * @license     http://www.gnu.org/copyleft/lesser.html GNU-LGPL v3 (see LICENSE.TXT)
 * @link        https://github.com/tecnickcom/tc-lib-pdf-image
 *
 * This file is part of tc-lib-color software library.
 */

namespace Test;

use PHPUnit\Framework\TestCase;

/**
 * Web Color class test
 *
 * @since      2020-12-19
 * @category    Library
 * @package     PdfImage
 * @author      Nicola Asuni <info@tecnick.com>
 * @copyright   2015-2023 Nicola Asuni - Tecnick.com LTD
 * @license     http://www.gnu.org/copyleft/lesser.html GNU-LGPL v3 (see LICENSE.TXT)
 * @link        https://github.com/tecnickcom/tc-lib-pdf-image
 */
class TestUtil extends TestCase
{
    public function bcExpectException($exception)
    {
        if (\is_callable([self::class, 'expectException'])) {
            parent::expectException($exception);
            return;
        }
        /* @phpstan-ignore-next-line */
        parent::setExpectedException($exception);
    }
}
