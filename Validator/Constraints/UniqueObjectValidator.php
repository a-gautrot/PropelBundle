<?php

/**
 * This file is part of the PropelBundle package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\Bundle\PropelBundle\Validator\Constraints;

use Propel\Runtime\Map\TableMap;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\ConstraintDefinitionException;
use function count;

/**
 * # UniqueObjectValidator
 * Unique Object Validator checks if one or a set of fields contain unique values.
 *
 * @author Maxime AILLOUD <maxime.ailloud@gmail.com>
 * @author Marek Kalnik <marekk@theodo.fr>
 */
class UniqueObjectValidator extends ConstraintValidator
{
    /**
     * @param mixed $value
     * @param Constraint $constraint
     * @return void
     */
    public function validate($value, Constraint $constraint): void
    {
        /** @var UniqueObject $constraint */
        $fields = (array)$constraint->fields;
        $class = get_class($value);
        $queryClass = "{$class}Query";
        $tableMapClass = $class::TABLE_MAP;
        $classFields = $tableMapClass::getFieldnames(TableMap::TYPE_FIELDNAME);

        // ensure at least one field is selected
        if (empty($fields)) {
            throw new ConstraintDefinitionException('At least one field has to be specified.');
        }

        foreach ($fields as $fieldName) {
            if (!in_array($fieldName, $classFields)) {
                throw new ConstraintDefinitionException("The field \"$fieldName\" doesn't exist in the \"$class\" class.");
            }
        }

        $bddUsersQuery = $queryClass::create();
        foreach ($fields as $fieldName) {
            $bddUsersQuery->filterBy(
                $tableMapClass::translateFieldname($fieldName, TableMap::TYPE_FIELDNAME, TableMap::TYPE_PHPNAME),
                $value->getByName($fieldName, TableMap::TYPE_FIELDNAME)
            );
        }

        $bddUsers = $bddUsersQuery->find();
        $countUser = count($bddUsers);

        if ($countUser > 1 || ($countUser === 1 && !$value->equals($bddUsers[0]))) {
            $fieldParts = [];

            foreach ($fields as $fieldName) {
                $fieldPhpName = $tableMapClass::translateFieldname($fieldName, TableMap::TYPE_FIELDNAME, TableMap::TYPE_PHPNAME);
                $fieldParts[] = "$fieldPhpName \"{$value->getByName($fieldName, TableMap::TYPE_FIELDNAME)}\"";
            }

            $this->context->buildViolation($constraint->message)
                // if no path is selected select first field
                ->atPath($constraint->errorPath ?? $fields[0])
                ->setParameters([
                    '{{ object_class }}' => $class,
                    '{{ fields }}' => implode($constraint->messageFieldSeparator, $fieldParts)
                ])
                ->addViolation();

        }
    }
}
