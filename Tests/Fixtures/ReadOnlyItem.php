<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Bundle\PropelBundle\Tests\Fixtures;

use Propel\Runtime\ActiveRecord\ActiveRecordInterface;

/**
 * # ReadOnlyItem
 */
class ReadOnlyItem implements ActiveRecordInterface
{
    /**
     * @return string
     */
    public function getName(): string
    {
        return 'Marvin';
    }

    /**
     * @return bool
     */
    public function isPrimaryKeyNull(): bool
    {
        return $this->getPrimaryKey() === null;
    }

    /**
     * @return int
     */
    public function getPrimaryKey(): int
    {
        return 42;
    }
}
