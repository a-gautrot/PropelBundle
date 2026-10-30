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
     * @var mixed
     */
    private $name;

    /**
     * @var mixed
     */
    private $type;

    /**
     * @param mixed $name
     * @param mixed $type
     */
    public function __construct($name, $type)
    {
        $this->name = $name;
        $this->type = $type;
    }

    /**
     * @return mixed
     */
    public function getType()
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

        switch ($this->type) {
            case PropelTypes::CHAR:
            case PropelTypes::VARCHAR:
            case PropelTypes::LONGVARCHAR:
            case PropelTypes::BLOB:
            case PropelTypes::CLOB:
            case PropelTypes::CLOB_EMU:
                return true;
            default:
                return false;
        }

    }

    /**
     * @return bool
     */
    public function isNotNull(): bool
    {
        return $this->name === 'id';
    }
}
