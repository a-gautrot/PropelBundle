<?php

namespace Propel\Bundle\PropelBundle\Tests\Twig\Extension;

use PHPUnit\Framework\TestCase;
use Propel\Bundle\PropelBundle\Twig\Extension\SyntaxExtension;

/**
 * # SyntaxExtensionTest
 */
class SyntaxExtensionTest extends TestCase
{
    /**
     * @return void
     */
    public function testZeroBytesCanBeFormatted(): void
    {
        self::assertSame('0 B', new SyntaxExtension()->formatMemory(0.0));
    }

    /**
     * @return void
     */
    public function testLargeByteCountsKeepTheLargestUnit(): void
    {
        self::assertSame('1,050,000 YB', new SyntaxExtension()->formatMemory(1024.0 ** 10));
    }
}
