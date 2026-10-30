<?php

/**
 * This file is part of the PropelBundle package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\Bundle\PropelBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\ConstraintDefinitionException;
use Symfony\Component\Validator\Exception\InvalidOptionsException;
use Symfony\Component\Validator\Exception\MissingOptionsException;

/**
 * # UniqueObject
 * Constraint for the Unique Object validator
 *
 * @author Maxime AILLOUD <maxime.ailloud@gmail.com>
 * @author Marek Kalnik <marekk@theodo.fr>
 * @api
 * @psalm-suppress PropertyNotSetInConstructor Symfony initializes groups lazily through Constraint::__get().
 */
class UniqueObject extends Constraint
{
    /**
     * @var string
     */
    public string $message = 'A {{ object_class }} object already exists with {{ fields }}';

    /**
     * @var string Used to merge multiple fields in the message
     */
    public string $messageFieldSeparator = ' and ';

    /**
     * @var array<array-key, mixed>|string
     */
    public $fields = [];

    /**
     * @var string|null Used to set the path where the error will be attached, default is global.
     */
    public ?string $errorPath = null;

    /**
     * @param mixed $options
     * @throws ConstraintDefinitionException
     * @throws InvalidOptionsException
     * @throws MissingOptionsException
     */
    public function __construct($options = null)
    {
        parent::__construct($options);

        if (empty($this->fields)) {
            throw new ConstraintDefinitionException("At least one field must be specified.");
        }
    }

    /**
     * @return string[]
     * @deprecated since Symfony 7.4
     * @see __construct()
     */
    public function getRequiredOptions(): array
    {
        return ['fields'];
    }

    /**
     * @return string
     */
    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
