<?php

/**
 * This file is part of the PropelBundle package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\Bundle\PropelBundle\Form;

use Propel\Generator\Model\ForeignKey;
use Propel\Generator\Model\Table;
use RuntimeException;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;

/**
 * # FormBuilder
 * @author Moritz Schroeder <moritz.schroeder@molabs.de>
 * @api
 */
class FormBuilder
{
    /**
     * Build a form based on the given table.
     *
     * @param BundleInterface $bundle
     * @param Table $table
     * @param string $formTypeNamespace
     *
     * @return string
     * @throws RuntimeException
     */
    public function buildFormType(BundleInterface $bundle, Table $table, string $formTypeNamespace): string
    {
        $modelName = $table->getPhpName();
        $formTypeContent = file_get_contents(__DIR__ . '/../Resources/skeleton/FormType.php');
        if ($formTypeContent === false) {
            throw new RuntimeException('Cannot read the form type skeleton.');
        }

        $formTypeContent = str_replace(
            '##NAMESPACE##',
            $bundle->getNamespace() . str_replace('/', '\\', $formTypeNamespace),
            $formTypeContent
        );
        $formTypeContent = str_replace('##CLASS##', "{$modelName}Type", $formTypeContent);
        $formTypeContent = str_replace('##FQCN##', "{$table->getNamespace()}\\$modelName", $formTypeContent);
        $formTypeContent = str_replace('##TYPE_NAME##', strtolower($modelName), $formTypeContent);
        return str_replace('##BUILD_CODE##', $this->buildFormFields($table), $formTypeContent);
    }

    /**
     * Build the fields in the FormType.
     *
     * @param Table $table Table from which the fields will be extracted.
     *
     * @return string The FormType code.
     */
    protected function buildFormFields(Table $table): string
    {
        $buildCode = '';
        foreach ($table->getColumns() as $column) {
            if ($column->isPrimaryKey()) {
                continue;
            }
            $name = $column->getPhpName();

            // Use foreignKey table name, so the TypeGuesser gets it right
            if ($column->isForeignKey()) {
                /** @var ForeignKey $foreignKey */
                $foreignKey = current($column->getForeignKeys());
                $foreignTable = $foreignKey->getForeignTable();
                if ($foreignTable === null) {
                    throw new RuntimeException('The foreign table is not defined.');
                }
                $name = $foreignTable->getPhpName();
            }
            $fieldName = lcfirst($name);
            $buildCode .= "\n        \$builder->add('$fieldName');";
        }

        return $buildCode;
    }
}
