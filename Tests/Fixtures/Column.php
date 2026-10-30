<?php

/**
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Bundle\PropelBundle\Tests\Fixtures;

use Propel\Generator\Model\PropelTypes;

/**
 * # Column
 */
class Column
{
    /**
     * @param mixed $name
     * @param mixed $type
     */
    public function __construct(private readonly mixed $name, private readonly mixed $type)
    {
    }

    /**
     * @return mixed
     */
    public function getType(): mixed
    {
        return $this->type;
    }

    /**
     * @return int
     */
    public function getSize(): int
    {
        return $this->isText() ? 255 : 0;
    }

    /**
     * @return bool
     */
    public function isText(): bool
    {
        if (!$this->type) {
            return false;
        }

        return match ($this->type) {
            PropelTypes::CHAR, PropelTypes::VARCHAR, PropelTypes::LONGVARCHAR, PropelTypes::BLOB, PropelTypes::CLOB, PropelTypes::CLOB_EMU => true,
            default => false,
        };

    }

    /**
     * @return bool
     */
    public function isNotNull(): bool
    {
        return $this->name === 'id';
    }
}
