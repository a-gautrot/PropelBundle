<?php

/**
 * This file is part of the PropelBundle package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\Bundle\PropelBundle\Twig\Extension;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * # SyntaxExtension
 *
 * @package PropelBundle
 * @subpackage Extension
 * @author William DURAND <william.durand1@gmail.com>
 */
class SyntaxExtension extends AbstractExtension
{
    /**
     * @return array
     */
    public function getFilters(): array
    {
        return [
            new TwigFilter('format_sql', [$this, 'formatSQL'], ['is_safe' => ['html']]),
            new TwigFilter('format_memory', [$this, 'formatMemory']),
        ];
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return 'propel_syntax_extension';
    }

    /**
     * Format a byte count into a human-readable representation.
     *
     * @param int|float $bytes Byte count to convert. Can be negative.
     * @param int $precision How many decimals to include.
     *
     * @return string
     */
    public function formatMemory(int|float $bytes, int $precision = 3): string
    {
        $absBytes = abs($bytes);
        $sign = ($bytes == $absBytes) ? 1 : -1;
        $suffix = ['B', 'kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
        $total = count($suffix);

        for ($i = 0; $absBytes > 1024 && $i < $total; $i++) {
            $absBytes /= 1024;
        }

        return self::toPrecision($sign * $absBytes, $precision) . ' ' . $suffix[$i];
    }

    /**
     * Rounding to significant digits (sort of like JavaScript's toPrecision()).
     *
     * @param int|float $number Value to round
     * @param int $significantFigures Number of significant figures
     *
     * @return string
     */
    public static function toPrecision(int|float $number, int $significantFigures = 3): string
    {
        if ($number === 0) {
            return '0';
        }

        $significantDecimals = (int)floor($significantFigures - log10(abs($number)));
        $magnitude = pow(10, $significantDecimals);
        $shifted = round($number * $magnitude);

        return number_format($shifted / $magnitude, $significantDecimals);
    }

    /**
     * @param string|string[] $sql
     *
     * @return string|string[]
     */
    public function formatSQL(string|array $sql): string|array
    {
        // list of keywords to prepend a newline in output
        $newlines = [
            'FROM',
            '(((FULL|LEFT|RIGHT)? ?(OUTER|INNER)?|CROSS|NATURAL)? JOIN)',
            'VALUES',
            'WHERE',
            'ORDER BY',
            'GROUP BY',
            'HAVING',
            'LIMIT',
        ];

        // list of keywords to highlight
        $keywords = array_merge($newlines, [
            // base
            'SELECT', 'UPDATE', 'DELETE', 'INSERT', 'REPLACE',
            'SET',
            'INTO',
            'AS',
            'DISTINCT',

            // most used methods
            'COUNT',
            'AVG',
            'MIN',
            'MAX',

            // joins
            'ON', 'USING',

            // where clause
            '(IS (NOT)?)?NULL',
            '(NOT )?IN',
            '(NOT )?I?LIKE',
            'AND', 'OR', 'XOR',
            'BETWEEN',

            // order, group, limit...
            'ASC',
            'DESC',
            'OFFSET',
        ]);

        return preg_replace([
            '/\b(' . implode('|', $newlines) . ')\b/',
            '/\b(' . implode('|', $keywords) . ')\b/',
            '/(\/\*.*\*\/)/',
            '/(`[^`.]*`)/',
            '/(([0-9a-zA-Z$_]+)\.([0-9a-zA-Z$_]+))/',
        ], [
            '<br />\\1',
            '<span class="SQLKeyword">\\1</span>',
            '<span class="SQLComment">\\1</span>',
            '<span class="SQLName">\\1</span>',
            '<span class="SQLName">\\1</span>',
        ], $sql);
    }
}
