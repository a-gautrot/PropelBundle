<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Propel\Bundle\PropelBundle\Command;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();
    $services->defaults()->autowire()->autoconfigure()->bind('$container', service('service_container'));

    $commands = [
        'build_command' => [Command\BuildCommand::class, 'propel:build'],
        'database_create_command' => [Command\DatabaseCreateCommand::class, 'propel:database:create'],
        'database_drop_command' => [Command\DatabaseDropCommand::class, 'propel:database:drop'],
        'database_reverse_command' => [Command\DatabaseReverseCommand::class, 'propel:database:reverse'],
        'fixtures_dump_command' => [Command\FixturesDumpCommand::class, 'propel:fixtures:dump'],
        'fixtures_load_command' => [Command\FixturesLoadCommand::class, 'propel:fixtures:load'],
        'form_generate_command' => [Command\FormGenerateCommand::class, 'propel:form:generate'],
        'graphviz_generate_command' => [Command\GraphvizGenerateCommand::class, 'propel:graphviz:generate'],
        'migration_diff_command' => [Command\MigrationDiffCommand::class, 'propel:migration:diff'],
        'migration_down_command' => [Command\MigrationDownCommand::class, 'propel:migration:down'],
        'migration_migrate_command' => [Command\MigrationMigrateCommand::class, 'propel:migration:migrate'],
        'migration_status_command' => [Command\MigrationStatusCommand::class, 'propel:migration:status'],
        'migration_up_command' => [Command\MigrationUpCommand::class, 'propel:migration:up'],
        'model_build_command' => [Command\ModelBuildCommand::class, 'propel:model:build'],
        'schema_convert_command' => [Command\SchemaConvertCommand::class, 'propel:schema:convert'],
        'sql_build_command' => [Command\SqlBuildCommand::class, 'propel:sql:build'],
        'sql_insert_command' => [Command\SqlInsertCommand::class, 'propel:sql:insert'],
        'table_drop_command' => [Command\TableDropCommand::class, 'propel:table:drop'],
    ];

    foreach ($commands as $id => [$class, $name]) {
        $services->set('propel_bundle_propel.command.' . $id, $class)
            ->tag('console.command', ['command' => $name]);
    }
};
