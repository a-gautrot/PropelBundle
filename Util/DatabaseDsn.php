<?php

namespace Propel\Bundle\PropelBundle\Util;

/**
 * # DatabaseDsn
 * DSN handling for database administration commands.
 */
final class DatabaseDsn
{
    /**
     * @param string $dsn
     * @return string|null
     */
    public static function getDatabaseName(string $dsn): ?string
    {
        $parameters = explode(':', $dsn, 2)[1] ?? '';
        preg_match('/(?:^|;)(?:dbname|Database)=([^;]+)/i', $parameters, $matches);

        return $matches[1] ?? null;
    }

    /**
     * @param string $dsn
     * @return string
     */
    public static function forAdministration(string $dsn): string
    {
        [$driver, $parameters] = explode(':', $dsn, 2);
        $parameters = preg_replace('/(?:^|;)(?:dbname|Database)=[^;]*/i', '', $parameters);
        $parameters = ltrim($parameters, ';');
        if (in_array(strtolower($driver), ['sqlsrv', 'dblib', 'mssql'], strict: true)) {
            $key = strtolower($driver) === 'sqlsrv' ? 'Database' : 'dbname';
            $parameters .= ($parameters === '' ? '' : ';') . "$key=master";
        }
        if (strtolower($driver) === 'mssql') {
            $driver = 'dblib';
        }

        return "$driver:$parameters";
    }

    /**
     * @param string $name
     * @param string $adapter
     * @return string
     */
    public static function quoteDatabaseName(string $name, string $adapter): string
    {
        return match ($adapter) {
            'mysql' => '`' . str_replace('`', '``', $name) . '`',
            'sqlsrv', 'mssql' => '[' . str_replace(']', ']]', $name) . ']',
            default => '"' . str_replace('"', '""', $name) . '"',
        };
    }
}
