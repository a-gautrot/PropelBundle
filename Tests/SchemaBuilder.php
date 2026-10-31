<?php

namespace Propel\Bundle\PropelBundle\Tests;

use DOMDocument;
use Exception;
use InvalidArgumentException;
use PDO;
use Propel\Generator\Platform\MysqlPlatform;
use Propel\Generator\Platform\PgsqlPlatform;
use Propel\Generator\Platform\SqlitePlatform;
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
        [$platform, $adapter] = match (strtok($dsn, ':')) {
            'mysql' => [new MysqlPlatform(), new MysqlAdapter()],
            'pgsql' => [new PgsqlPlatform(), new PgsqlAdapter()],
            'sqlite' => [new SqlitePlatform(), new SqliteAdapter()],
            default => throw new InvalidArgumentException('TEST_DSN must use mysql, pgsql, or sqlite.'),
        };

        $document = new DOMDocument();
        if (!$document->loadXML($schema)) {
            throw new InvalidArgumentException('Invalid test schema.');
        }
        $suffix = '_' . bin2hex(random_bytes(4));
        $tableNames = [];
        foreach ($document->getElementsByTagName('table') as $table) {
            $name = $table->getAttribute('name');
            $tableNames[$name] = "$name$suffix";
            if (!$table->hasAttribute('phpName')) {
                $table->setAttribute('phpName', str_replace(' ', '', ucwords(str_replace('_', ' ', $name))));
            }
            $table->setAttribute('name', $tableNames[$name]);
        }
        foreach ($document->getElementsByTagName('foreign-key') as $foreignKey) {
            $name = $foreignKey->getAttribute('foreignTable');
            if (isset($tableNames[$name])) {
                $foreignKey->setAttribute('foreignTable', $tableNames[$name]);
            }
        }
        // Behaviors can reference table names independently of foreign keys.
        foreach ($document->getElementsByTagName('behavior') as $behavior) {
            $parameterName = match ($behavior->getAttribute('name')) {
                'concrete_inheritance' => 'extends',
                'delegate' => 'to',
                default => null,
            };
            foreach ($behavior->getElementsByTagName('parameter') as $parameter) {
                if ($parameter->getAttribute('name') === $parameterName) {
                    $names = array_map(trim(...), explode(',', $parameter->getAttribute('value')));
                    $parameter->setAttribute('value', implode(',', array_map(static fn(string $name): string => $tableNames[$name] ?? $name, $names)));
                }
            }
        }

        $builder = new QuickBuilder();
        $builder->setPlatform($platform);
        $builder->setSchema($document->saveXML());

        $connection = $builder->build($dsn, getenv('TEST_USER') ?: null, getenv('TEST_PASSWORD') ?: null, $adapter, $classTargets);
        $connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        return $connection;
    }
}
