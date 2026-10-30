<?php

/**
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Bundle\PropelBundle\Form\DataTransformer;

use Propel\Runtime\Collection\ObjectCollection;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

/**
 * # CollectionToArrayTransformer
 *
 * @author William Durand <william.durand1@gmail.com>
 * @author Pierre-Yves Lebecq <py.lebecq@gmail.com>
 *
 * @implements DataTransformerInterface<ObjectCollection, array>
 * @api
 */
class CollectionToArrayTransformer implements DataTransformerInterface
{
    /**
     * @param mixed $value
     *
     * @return array<array-key, mixed>
     * @throws TransformationFailedException
     */
    public function transform($value): array
    {
        if ($value === null) {
            return [];
        }

        if (!$value instanceof ObjectCollection) {
            throw new TransformationFailedException('Expected a \Propel\Runtime\Collection\ObjectCollection.');
        }

        return $value->getData();
    }

    /**
     * @param mixed $value
     *
     * @return ObjectCollection
     * @throws TransformationFailedException
     */
    public function reverseTransform($value): ObjectCollection
    {
        $collection = new ObjectCollection();

        if ($value === '' || $value === null) {
            return $collection;
        }

        if (!is_array($value)) {
            throw new TransformationFailedException('Expected an array.');
        }

        $collection->setData($value);

        return $collection;
    }
}
