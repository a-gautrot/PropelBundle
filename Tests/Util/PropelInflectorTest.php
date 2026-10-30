<?php

/**
 * This file is part of the PropelBundle package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\Bundle\PropelBundle\Tests\Util;

use PHPUnit\Framework\Attributes\DataProvider;
use Propel\Bundle\PropelBundle\Tests\TestCase;
use Propel\Bundle\PropelBundle\Util\PropelInflector;

/**
 * # PropelInflectorTest
 * @author William Durand <william.durand1@gmail.com>
 */
class PropelInflectorTest extends TestCase
{
    /**
     * @return list<array{string|null, string|null}>
     */
    public static function dataProviderForTestCamelize(): array
    {
        return [
            ['', ''],
            [null, null],
            ['foo', 'foo'],
            ['Foo', 'foo'],
            ['fooBar', 'fooBar'],
            ['FooBar', 'fooBar'],
            ['Foo_bar', 'fooBar'],
            ['Foo_Bar', 'fooBar'],
            ['Foo Bar', 'fooBar'],
            ['Foo bar Baz', 'fooBarBaz'],
            ['Foo_Bar_Baz', 'fooBarBaz'],
            ['foo_bar_baz', 'fooBarBaz'],
        ];
    }

    /**
     * @param mixed $word
     * @param mixed $expected
     * @return void
     */
    #[DataProvider('dataProviderForTestCamelize')]
    public function testCamelize(mixed $word, mixed $expected): void
    {
        $this->assertEquals($expected, PropelInflector::camelize($word));
    }
}
