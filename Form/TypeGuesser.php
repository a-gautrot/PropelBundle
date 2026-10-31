<?php

/**
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Bundle\PropelBundle\Form;

use Propel\Bundle\PropelBundle\Form\Type\ModelType;
use Propel\Generator\Model\PropelTypes;
use Propel\Runtime\Map\ColumnMap;
use Propel\Runtime\Map\Exception\ColumnNotFoundException;
use Propel\Runtime\Map\RelationMap;
use Propel\Runtime\Map\TableMap;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\FormTypeGuesserInterface;
use Symfony\Component\Form\Guess\Guess;
use Symfony\Component\Form\Guess\TypeGuess;
use Symfony\Component\Form\Guess\ValueGuess;

/**
 * # TypeGuesser
 * Propel Type guesser.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 * @api
 */
class TypeGuesser implements FormTypeGuesserInterface
{
    /**
     * @var array<string, TableMap|ColumnMap>
     */
    private array $cache = [];

    /**
     * @param string $class
     * @param string $property
     * @return TypeGuess|null
     * @throws ColumnNotFoundException
     */
    #[\Override]
    public function guessType(string $class, string $property): ?TypeGuess
    {
        if (!$table = $this->getTable($class)) {
            return new TypeGuess(TextType::class, [], Guess::LOW_CONFIDENCE);
        }

        foreach ($table->getRelations() as $relation) {
            if ($relation->getType() === RelationMap::MANY_TO_ONE) {
                if (strtolower($property) === strtolower($relation->getName())) {
                    return new TypeGuess(ModelType::class, [
                        'class' => $relation->getForeignTable()->getClassName(),
                        'multiple' => false,
                    ], Guess::HIGH_CONFIDENCE);
                }
            } elseif ($relation->getType() === RelationMap::ONE_TO_MANY) {
                if (strtolower($property) === strtolower($relation->getPluralName())) {
                    return new TypeGuess(ModelType::class, [
                        'class' => $relation->getForeignTable()->getClassName(),
                        'multiple' => true,
                    ], Guess::HIGH_CONFIDENCE);
                }
            } elseif ($relation->getType() === RelationMap::MANY_TO_MANY) {
                if (strtolower($property) == strtolower($relation->getPluralName())) {
                    return new TypeGuess(ModelType::class, [
                        'class' => $relation->getLocalTable()->getClassName(),
                        'multiple' => true,
                    ], Guess::HIGH_CONFIDENCE);
                }
            }
        }

        if (!$column = $this->getColumn($class, $property)) {
            return new TypeGuess(TextType::class, [], Guess::LOW_CONFIDENCE);
        }

        switch ($column->getType()) {
            case PropelTypes::BOOLEAN:
            case PropelTypes::BOOLEAN_EMU:
                return new TypeGuess(CheckboxType::class, [], Guess::HIGH_CONFIDENCE);
            case PropelTypes::TIMESTAMP:
            case PropelTypes::BU_TIMESTAMP:
                return new TypeGuess(DateTimeType::class, [], Guess::HIGH_CONFIDENCE);
            case PropelTypes::DATE:
            case PropelTypes::BU_DATE:
                return new TypeGuess(DateType::class, [], Guess::HIGH_CONFIDENCE);
            case PropelTypes::TIME:
                return new TypeGuess(TimeType::class, [], Guess::HIGH_CONFIDENCE);
            case PropelTypes::FLOAT:
            case PropelTypes::REAL:
            case PropelTypes::DOUBLE:
            case PropelTypes::DECIMAL:
                return new TypeGuess(NumberType::class, [], Guess::MEDIUM_CONFIDENCE);
            case PropelTypes::TINYINT:
            case PropelTypes::SMALLINT:
            case PropelTypes::INTEGER:
            case PropelTypes::BIGINT:
            case PropelTypes::NUMERIC:
                return new TypeGuess(IntegerType::class, [], Guess::MEDIUM_CONFIDENCE);
            case PropelTypes::ENUM:
            case PropelTypes::CHAR:
                if ($column->getValueSet()) {
                    //check if this is mysql enum
                    $choices = $column->getValueSet();
                    $labels = array_map('ucfirst', $choices);

                    return new TypeGuess(
                        ChoiceType::class,
                        ['choices' => array_combine($choices, $labels)],
                        Guess::MEDIUM_CONFIDENCE
                    );
                }
                return new TypeGuess(TextType::class, [], Guess::MEDIUM_CONFIDENCE);
            case PropelTypes::VARCHAR:
                return new TypeGuess(TextType::class, [], Guess::MEDIUM_CONFIDENCE);
            case PropelTypes::LONGVARCHAR:
            case PropelTypes::BLOB:
            case PropelTypes::CLOB:
            case PropelTypes::CLOB_EMU:
                return new TypeGuess(TextareaType::class, [], Guess::MEDIUM_CONFIDENCE);
            default:
                return new TypeGuess(TextType::class, [], Guess::LOW_CONFIDENCE);
        }
    }

    /**
     * @param string $class
     *
     * @return TableMap|null
     */
    protected function getTable(string $class): ?TableMap
    {
        if (isset($this->cache[$class]) && $this->cache[$class] instanceof TableMap) {
            return $this->cache[$class];
        }

        if (class_exists($queryClass = "{$class}Query")) {
            $query = new $queryClass();

            return $this->cache[$class] = $query->getTableMap();
        }

        return null;
    }

    /**
     * @param string $class
     * @param string $property
     *
     * @return ColumnMap|null
     * @throws ColumnNotFoundException
     */
    protected function getColumn(string $class, string $property): ?ColumnMap
    {
        $cached = $this->cache["$class::$property"] ?? null;
        if ($cached instanceof ColumnMap) {
            return $cached;
        }

        $table = $this->getTable($class);

        if ($table && $table->hasColumn($property)) {
            return $this->cache["$class::$property"] = $table->getColumn($property);
        }

        return null;
    }

    /**
     * @param string $class
     * @param string $property
     * @return ValueGuess|null
     * @throws ColumnNotFoundException
     */
    #[\Override]
    public function guessRequired(string $class, string $property): ?ValueGuess
    {
        if ($column = $this->getColumn($class, $property)) {
            return new ValueGuess($column->isNotNull(), Guess::HIGH_CONFIDENCE);
        }

        return null;
    }

    /**
     * @param string $class
     * @param string $property
     * @return ValueGuess|null
     * @throws ColumnNotFoundException
     */
    #[\Override]
    public function guessMaxLength(string $class, string $property): ?ValueGuess
    {
        if ($column = $this->getColumn($class, $property)) {
            if ($column->isText()) {
                return new ValueGuess($column->getSize(), Guess::HIGH_CONFIDENCE);
            }
            switch ($column->getType()) {
                case PropelTypes::FLOAT:
                case PropelTypes::REAL:
                case PropelTypes::DOUBLE:
                case PropelTypes::DECIMAL:
                    return new ValueGuess(null, Guess::MEDIUM_CONFIDENCE);
            }
        }

        return null;
    }

    /**
     * @param string $class
     * @param string $property
     * @return ValueGuess|null
     * @throws ColumnNotFoundException
     */
    #[\Override]
    public function guessPattern(string $class, string $property): ?ValueGuess
    {
        if ($column = $this->getColumn($class, $property)) {
            switch ($column->getType()) {
                case PropelTypes::FLOAT:
                case PropelTypes::REAL:
                case PropelTypes::DOUBLE:
                case PropelTypes::DECIMAL:
                    return new ValueGuess(null, Guess::MEDIUM_CONFIDENCE);
            }
        }

        return null;
    }
}
