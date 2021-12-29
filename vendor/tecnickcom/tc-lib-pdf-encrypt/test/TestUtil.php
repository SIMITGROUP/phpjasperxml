<?php
/**
 * TestUtil.php
 *
 * @since       2020-12-19
 * @category    Library
 * @package     file
 * @author      Nicola Asuni <info@tecnick.com>
 * @copyright   2015-2021 Nicola Asuni - Tecnick.com LTD
 * @license     http://www.gnu.org/copyleft/lesser.html GNU-LGPL v3 (see LICENSE.TXT)
 * @link        https://github.com/tecnickcom/tc-lib-file
 *
 * This file is part of tc-lib-file software library.
 */

namespace Test;

use PHPUnit\Framework\TestCase;

/**
 * Test Util
 *
 * @since      2020-12-19
 * @category    Library
 * @package     file
 * @author      Nicola Asuni <info@tecnick.com>
 * @copyright   2015-2021 Nicola Asuni - Tecnick.com LTD
 * @license     http://www.gnu.org/copyleft/lesser.html GNU-LGPL v3 (see LICENSE.TXT)
 * @link        https://github.com/tecnickcom/tc-lib-file
 */
class TestUtil extends TestCase
{
    public function bcExpectException($exception)
    {
        if (\is_callable(['parent', 'expectException'])) {
            return parent::expectException($exception);
        }
        return parent::setExpectedException($exception);
    }
}
