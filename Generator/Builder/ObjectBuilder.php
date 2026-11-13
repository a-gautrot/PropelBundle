<?php

namespace Propel\Bundle\PropelBundle\Generator\Builder;

use Override;
use Propel\Bundle\PropelBundle\Generator\Platform\OraclePlatform;
use Propel\Generator\Builder\Om\ObjectBuilder as BaseObjectBuilder;
use Propel\Generator\Model\IdMethod;
use Propel\Generator\Model\PropelTypes;
use Propel\Runtime\Exception\PropelException;

/**
 * # ObjectBuilder
 * Use the runtime adapter for Oracle inserts so it can handle streamed OBJECT columns.
 */
class ObjectBuilder extends BaseObjectBuilder
{
    /**
     * @return string
     */
    #[Override]
    public function build(): string
    {
        if (!$this->getPlatform() instanceof OraclePlatform
            || !$this->getTable()->containsColumnsOfType(PropelTypes::OBJECT)) {
            // Some Propel behaviors compare the builder's exact class instead of using instanceof.
            $builder = new BaseObjectBuilder($this->getTable());
            $config = $this->getGeneratorConfig();
            if ($config !== null) {
                $builder->setGeneratorConfig($config);
            }

            return $builder->build();
        }

        return parent::build();
    }

    /**
     * @return string
     * @throws PropelException
     */
    #[Override]
    protected function addDoInsertBodyRaw(): string
    {
        if ($this->getPlatform() instanceof OraclePlatform
            && $this->getTable()->containsColumnsOfType(PropelTypes::OBJECT)) {
            $table = $this->getTable();
            $column = $table->getFirstPrimaryKeyColumn();
            $script = '';
            if ($column !== null && $table->getIdMethod() === IdMethod::NATIVE) {
                $getter = '$this->' . $column->getLowercasedName();
                if ($column->isAutoIncrement() && !$table->isAllowPkInsert()) {
                    $script .= "\n        if (null !== $getter) {\n"
                        . "            throw new PropelException(\n"
                        . "                'Cannot insert a value for auto-increment primary key.'\n"
                        . "            );\n"
                        . "        }\n";
                }
                $parameters = $table->getIdMethodParameters();
                $sequence = $parameters ? $parameters[0]->getValue() : $this->getPlatform()->getSequenceName($table);
                $script .= "\n        if (null === $getter) {";
                $script .= $this->getPlatform()->getIdentifierPhp(
                    $getter,
                    '$con',
                    $sequence,
                    '            ',
                    $column->getPhpCastType()
                );
                $script .= "\n" . '            $this->modifiedColumns['
                    . $this->getColumnConstant($column) . "] = true;\n        }\n";
            }

            return $script . "\n" . '        $criteria = $this->buildCriteria();'
                . $this->addDoInsertBodyStandard();
        }

        return parent::addDoInsertBodyRaw();
    }
}
