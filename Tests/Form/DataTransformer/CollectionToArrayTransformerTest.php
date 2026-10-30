<?php

/**
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Bundle\PropelBundle\Tests\Form\Form\DataTransformer;

use Propel\Bundle\PropelBundle\Form\DataTransformer\CollectionToArrayTransformer;
use Propel\Bundle\PropelBundle\Tests\TestCase;
use Propel\Runtime\Collection\ObjectCollection;
use stdClass;
use Symfony\Component\Form\Exception\TransformationFailedException;

/**
 * # CollectionToArrayTransformerTest
 */
class CollectionToArrayTransformerTest extends TestCase
{
    /**
     * @var CollectionToArrayTransformer
     */
    private CollectionToArrayTransformer $transformer;

    /**
     * @return void
     */
    public function testTransform(): void
    {
        $result = $this->transformer->transform(new ObjectCollection());

//        $this->assertTrue(is_array($result));
        $this->assertCount(0, $result);
    }

    /**
     * @return void
     */
    public function testTransformWithNull(): void
    {
        $result = $this->transformer->transform(null);

//        $this->assertTrue(is_array($result));
        $this->assertCount(0, $result);
    }

    /**
     * @return void
     */
    public function testTransformThrowsExceptionIfNotObjectCollection(): void
    {
        $this->expectException(TransformationFailedException::class);

        $this->transformer->transform(new DummyObject());
    }

    /**
     * @return void
     */
    public function testTransformWithData(): void
    {
        $coll = new ObjectCollection();
        $coll->setData([$a = new stdClass, $b = new stdClass]);

        $result = $this->transformer->transform($coll);

//        $this->assertTrue(is_array($result));
        $this->assertCount(2, $result);
        $this->assertSame($a, $result[0]);
        $this->assertSame($b, $result[1]);
    }

    /**
     * @return void
     */
    public function testReverseTransformWithNull(): void
    {
        $result = $this->transformer->reverseTransform(null);

//        $this->assertInstanceOf('\Propel\Runtime\Collection\ObjectCollection', $result);
        $this->assertCount(0, $result->getData());
    }

    /**
     * @return void
     */
    public function testReverseTransformWithEmptyString(): void
    {
        $result = $this->transformer->reverseTransform('');

//        $this->assertInstanceOf('\Propel\Runtime\Collection\ObjectCollection', $result);
        $this->assertCount(0, $result->getData());
    }

    /**
     * @return void
     */
    public function testReverseTransformThrowsExceptionIfNotArray(): void
    {
        $this->expectException(TransformationFailedException::class);

        $this->transformer->reverseTransform(new DummyObject());
    }

    /**
     * @return void
     */
    public function testReverseTransformWithData(): void
    {
        $inputData = [$a = new stdClass, $b = new stdClass];

        $result = $this->transformer->reverseTransform($inputData);
        $data = $result->getData();

//        $this->assertInstanceOf('\Propel\Runtime\Collection\ObjectCollection', $result);

//        $this->assertTrue(is_array($data));
        $this->assertCount(2, $data);
        $this->assertSame($a, $data[0]);
        $this->assertSame($b, $data[1]);
        $this->assertsame($inputData, $data);
    }

    /**
     * @return void
     */
    protected function setUp(): void
    {
        if (!class_exists('Symfony\Component\Form\Form')) {
            $this->markTestSkipped('The "Form" component is not available');
        }

        parent::setUp();

        $this->transformer = new CollectionToArrayTransformer();
    }
}

/**
 *
 */
class DummyObject
{
}
