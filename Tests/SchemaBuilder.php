<?php

namespace Propel\Bundle\PropelBundle\Tests;

use Propel\Bundle\PropelBundle\Generator\Builder\ObjectBuilder;
use DOMDocument;
use Exception;
use InvalidArgumentException;
use PDO;
use Propel\Bundle\PropelBundle\Adapter\MssqlAdapter;
use Propel\Bundle\PropelBundle\Adapter\OracleAdapter;
use Propel\Bundle\PropelBundle\Adapter\SqlsrvAdapter;
use Propel\Bundle\PropelBundle\Generator\Platform\OraclePlatform;
use Propel\Generator\Model\Table;
use Propel\Generator\Config\QuickGeneratorConfig;
use Propel\Generator\Platform\MysqlPlatform;
use Propel\Generator\Platform\MssqlPlatform;
use Propel\Generator\Platform\PgsqlPlatform;
use Propel\Generator\Platform\SqlitePlatform;
use Propel\Generator\Platform\SqlsrvPlatform;
use Propel\Generator\Util\QuickBuilder;
use Propel\Runtime\Adapter\Pdo\MysqlAdapter;
use Propel\Runtime\Adapter\Pdo\PgsqlAdapter;
use Propel\Runtime\Adapter\Pdo\SqliteAdapter;
use Propel\Runtime\Connection\ConnectionWrapper;

/**
 * # SchemaBuilder
 * Builds isolated fixtures on the database selected by TEST_DSN.
 */
final class SchemaBuilder
{
    /**
     * @param string $schema
     * @param list<string>|null $classTargets
     * @return ConnectionWrapper
     * @throws Exception
     */
    public static function buildSchema(string $schema, ?array $classTargets = null): ConnectionWrapper
    {
        $dsn = getenv('TEST_DSN') ?: 'sqlite::memory:';
        switch (strtok($dsn, ':')) {
            case 'mysql':
                [$platform, $adapter] = [new MysqlPlatform(), new MysqlAdapter()];
                break;
            case 'pgsql':
                [$platform, $adapter] = [new PgsqlPlatform(), new PgsqlAdapter()];
                break;
            case 'sqlite':
                [$platform, $adapter] = [new SqlitePlatform(), new SqliteAdapter()];
                break;
            case 'sqlsrv':
                [$platform, $adapter] = [new class extends SqlsrvPlatform {
                    public function getDropTableDDL(Table $table): string
                    {
                        return '';
                    }
                }, new SqlsrvAdapter()];
                break;
            case 'dblib':
            case 'mssql':
                [$platform, $adapter] = [new class extends MssqlPlatform {
                    public function getDropTableDDL(Table $table): string
                    {
                        return '';
                    }
                }, new MssqlAdapter()];
                break;
            case 'oci':
            case 'oracle':
                [$platform, $adapter] = [new OraclePlatform(), new OracleAdapter()];
                break;
            default:
                throw new InvalidArgumentException(
                    'TEST_DSN must use mysql, pgsql, sqlite, sqlsrv, dblib, mssql, oci, or oracle.'
                );
        }
        // QuickBuilder creates PDO directly, bypassing OracleAdapter's DSN normalization.
        if (strncmp($dsn, 'oracle:', strlen('oracle:')) === 0) {
            $dsn = 'oci:' . substr($dsn, 7);
        } elseif (strncmp($dsn, 'mssql:', strlen('mssql:')) === 0) {
            $dsn = 'dblib:' . substr($dsn, 6);
        }

        $document = new DOMDocument();
        if (!$document->loadXML($schema)) {
            throw new InvalidArgumentException('Invalid test schema.');
        }
        $suffix = '_' . bin2hex(random_bytes(4));
        $tableNames = [];
        foreach ($document->getElementsByTagName('table') as $table) {
            $name = $table->getAttribute('name');
            // Leave room for the random suffix within Oracle's legacy identifier limit.
            $physicalName = $platform instanceof OraclePlatform && strlen($name) > 21
                ? substr($name, 0, 12) . '_' . substr(hash('sha256', $name), 0, 8)
                : $name;
            $tableNames[$name] = "$physicalName$suffix";
            if (!$table->hasAttribute('phpName')) {
                $table->setAttribute('phpName', str_replace(' ', '', ucwords(str_replace('_', ' ', $name))));
            }
            $table->setAttribute('name', $tableNames[$name]);
        }
        foreach ($document->getElementsByTagName('foreign-key') as $foreignKey) {
            foreach (['onDelete', 'onUpdate'] as $action) {
                if ($foreignKey->getAttribute($action) === 'RESTRICT') {
                    if ($platform instanceof OraclePlatform) {
                        $foreignKey->removeAttribute($action);
                    } elseif ($platform instanceof MssqlPlatform) {
                        $foreignKey->setAttribute($action, 'NO ACTION');
                    }
                }
            }
            $name = $foreignKey->getAttribute('foreignTable');
            if (isset($tableNames[$name])) {
                $foreignKey->setAttribute('foreignTable', $tableNames[$name]);
            }
        }
        // Behaviors can reference table names independently of foreign keys.
        foreach ($document->getElementsByTagName('behavior') as $behavior) {
            switch ($behavior->getAttribute('name')) {
                case 'concrete_inheritance':
                    $parameterName = 'extends';
                    break;
                case 'delegate':
                    $parameterName = 'to';
                    break;
                default:
                    $parameterName = null;
                    break;
            }
            foreach ($behavior->getElementsByTagName('parameter') as $parameter) {
                if ($parameter->getAttribute('name') === $parameterName) {
                    $names = array_map(\Closure::fromCallable('trim'), explode(',', $parameter->getAttribute('value')));
                    $parameter->setAttribute('value', implode(',', array_map(static fn(string $name): string => $tableNames[$name] ?? $name, $names)));
                }
            }
        }

        $builder = new QuickBuilder();
        if ($platform instanceof OraclePlatform) {
            $builder->setConfig(new QuickGeneratorConfig(['propel' => ['generator' => [
                'objectModel' => ['builders' => ['object' => ObjectBuilder::class]],
            ]]]));
        }
        $builder->setPlatform($platform);
        $builder->setSchema($document->saveXML());

        $connection = $builder->build($dsn, getenv('TEST_USER') ?: null, getenv('TEST_PASSWORD') ?: null, $adapter, $classTargets);
        $connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        return $connection;
    }
}
