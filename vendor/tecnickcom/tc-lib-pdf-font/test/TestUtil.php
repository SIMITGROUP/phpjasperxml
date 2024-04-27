<?php

/**
 * TestUtil.php
 *
 * @since     2020-12-19
 * @category  Library
 * @package   PdfFont
 * @author    Nicola Asuni <info@tecnick.com>
 * @copyright 2015-2024 Nicola Asuni - Tecnick.com LTD
 * @license   http://www.gnu.org/copyleft/lesser.html GNU-LGPL v3 (see LICENSE.TXT)
 * @link      https://github.com/tecnickcom/tc-lib-pdf-font
 *
 * This file is part of tc-lib-color software library.
 */

namespace Test;

use PHPUnit\Framework\TestCase;

/**
 * Web Color class test
 *
 * @since     2020-12-19
 * @category  Library
 * @package   PdfFont
 * @author    Nicola Asuni <info@tecnick.com>
 * @copyright 2015-2024 Nicola Asuni - Tecnick.com LTD
 * @license   http://www.gnu.org/copyleft/lesser.html GNU-LGPL v3 (see LICENSE.TXT)
 * @link      https://github.com/tecnickcom/tc-lib-pdf-font
 *
 * @preserveGlobalState         disabled
 * @runTestsInSeparateProcesses
 */
class TestUtil extends TestCase
{
    protected function setupTest(): void
    {
        if (! defined('K_PATH_FONTS')) {
            define('K_PATH_FONTS', dirname(__DIR__) . '/target/tmptest/');
        }

        system('rm -rf ' . K_PATH_FONTS . ' && mkdir -p ' . K_PATH_FONTS);
    }

    protected function getFontPath(): string
    {
        if (defined('K_PATH_FONTS')) {
            return K_PATH_FONTS;
        }

        return '';
    }

    public function bcAssertEqualsWithDelta(
        mixed $expected,
        mixed $actual,
        float $delta = 0.01,
        string $message = ''
    ): void {
        parent::assertEqualsWithDelta($expected, $actual, $delta, $message);
    }

    /**
     * @param class-string<\Throwable> $exception
     */
    public function bcExpectException($exception): void
    {
        parent::expectException($exception);
    }
}
