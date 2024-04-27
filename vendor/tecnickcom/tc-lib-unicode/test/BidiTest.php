<?php

/**
 * BidiTest.php
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

namespace Test;

use Com\Tecnick\Unicode\Bidi;

/**
 * Bidi Test
 *
 * @since     2011-05-23
 * @category  Library
 * @package   Unicode
 * @author    Nicola Asuni <info@tecnick.com>
 * @copyright 2011-2024 Nicola Asuni - Tecnick.com LTD
 * @license   http://www.gnu.org/copyleft/lesser.html GNU-LGPL v3 (see LICENSE.TXT)
 * @link      https://github.com/tecnickcom/tc-lib-unicode
 */
class BidiTest extends TestUtil
{
    public function testException(): void
    {
        $this->bcExpectException('\\' . \Com\Tecnick\Unicode\Exception::class);
        new \Com\Tecnick\Unicode\Bidi();
    }

    /**
     * @dataProvider inputDataProvider
     *
     * @param ?string $str      String to convert (if null it will be generated from $chrarr or $ordarr)
     * @param ?array<string>  $chrarr   Array of UTF-8 chars (if empty it will be generated from $str or $ordarr)
     * @param ?array<int>  $ordarr   Array of UTF-8 codepoints (if empty it will be generated from $str or $chrarr)
     * @param string $forcedir If 'R' forces RTL, if 'L' forces LTR
     * @param bool   $shaping  If true enable the shaping algorithm
     */
    public function testStr(
        ?string $str = null,
        ?array $chrarr = null,
        ?array $ordarr = null,
        string $forcedir = '',
        bool $shaping = true
    ): void {
        $bidi = new Bidi($str, $chrarr, $ordarr, $forcedir, $shaping);
        $this->assertEquals('test', $bidi->getString());
        $this->assertEquals(['t', 'e', 's', 't'], $bidi->getChrArray());
        $this->assertEquals([116, 101, 115, 116], $bidi->getOrdArray());
        $this->assertEquals([
            116 => true,
            101 => true,
            115 => true,
        ], $bidi->getCharKeys());
        $this->assertEquals(4, $bidi->getNumChars());
    }

    /**
     * @return array<int, array{?string, ?array<string>, ?array<int>, string, bool}>
     */
    public static function inputDataProvider(): array
    {
        return [
            ['test', null, null, '', true],
            [null, ['t', 'e', 's', 't'], null, '', true],
            [null, null, [116, 101, 115, 116], '', true],
            ['test', ['t', 'e', 's', 't'], null, '', true],
            ['test', null, [116, 101, 115, 116], '', true],
            [null, ['t', 'e', 's', 't'], [116, 101, 115, 116], '', true],
            ['test', ['t', 'e', 's', 't'], [116, 101, 115, 116], '', true],
            ['test', null, null, 'L', true],
            ['test', null, null, 'R', true],
        ];
    }

    /**
     * @dataProvider bidiStrDataProvider
     */
    public function testBidiStr(string $str, mixed $expected, string $forcedir = ''): void
    {
        $bidi = new Bidi($str, null, null, $forcedir, true);
        $this->assertEquals($expected, $bidi->getString());
    }

    /**
     * @return array<int, array{string, string, string}>
     */
    public static function bidiStrDataProvider(): array
    {
        return [
            [
                "\n\nABC\nEFG\n\nHIJ\n\n",
                "\n\nABC\nEFG\n\nHIJ\n\n",
                'L',
            ],
            [
                json_decode('"\u202EABC\u202C"'),
                'CBA',
                '',
            ],
            [
                'left to right',
                'right to left',
                'R',
            ],
            [
                'left to right ',
                ' right to left',
                'R',
            ],
            [
                json_decode('"smith (fabrikam \u0600\u0601\u0602) \u05de\u05d6\u05dc"'),
                json_decode('"\u05dc\u05d6\u05de (\u0602\u0601\u0600 fabrikam) smith"'),
                'R',
            ],
            [
                json_decode('"\u0600\u0601\u0602 book(s)"'),
                json_decode('"book(s) \u0602\u0601\u0600"'),
                'R',
            ],
            [
                json_decode('"\u0600\u0601(\u0602\u0603[&ef]!)gh"'),
                json_decode('"gh(![ef&]\u0603\u0602)\u0601\u0600"'),
                'R',
            ],
            [
                'تشكيل اختبار', 'ﺭﺎﺒﺘﺧﺍ ﻞﻴﻜﺸﺗ',
                '',
                '',
            ],
            [
                json_decode('"\u05de\u05d6\u05dc \u05d8\u05d5\u05d1"'),
                json_decode('"\u05d1\u05d5\u05d8 \u05dc\u05d6\u05de"'),
                '',
            ],
            [
                json_decode(
                    '"\u0644\u0644\u0647 \u0600\u0601\u0602 \uFB50'
                    . ' \u0651\u064c\u0651\u064d\u0651\u064e\u0651\u064f\u0651\u0650'
                    . ' \u0644\u0622"'
                ),
                json_decode('"\ufef5\ufedf \ufc62\ufc61\ufc60\ufc5f\ufc5e \ufb50 \u0602\u0601\u0600 \ufdf2"'),
                '',
            ],
            [
                json_decode('"A\u2067\u05d8\u2069B"'),
                json_decode('"A\u2067\u05d8\u2069B"'),
                '',
            ],
            [
                // RLI + PDI
                json_decode(
                    '"The words \"\u2067\u05de\u05d6\u05dc [mazel] \u05d8\u05d5\u05d1 [tov]\u2069\"'
                    . ' mean \"Congratulations!\""'
                ),
                'The words "⁧[tov] בוט [mazel] לזמ⁩" mean "Congratulations!"',
                '',
            ],
            [
                // RLE + PDF
                json_decode('"it is called \"\u202bAN INTRODUCTION TO java\u202c\" - $19.95 in hardcover."'),
                'it is called "java TO INTRODUCTION AN" - $19.95 in hardcover.',
                '',
            ],
            [
                // RLI + PDI
                json_decode('"it is called \"\u2067AN INTRODUCTION TO java\u2069\" - $19.95 in hardcover."'),
                'it is called "⁧java TO INTRODUCTION AN⁩" - $19.95 in hardcover.',
                '',
            ],
        ];
    }
}
