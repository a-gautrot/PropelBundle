<?php

declare(strict_types=1);

use Propel\Generator\Config\GeneratorConfig;
use Propel\Generator\Manager\ModelManager;
use Propel\Generator\Platform\SqlitePlatform;
use Symfony\Component\Filesystem\Filesystem;

require dirname(__DIR__) . '/vendor/autoload.php';

$outputDirectory = $argv[1] ?? null;
if ($outputDirectory === null || !is_dir($outputDirectory)) {
    fwrite(STDERR, "Usage: php Tests/build-models.php <existing-output-directory>\n");
    exit(1);
}

// Use the generator API: Propel's console commands are incompatible with Symfony 8.
$config = new GeneratorConfig(__DIR__ . '/Fixtures', ['propel' => [
    'paths' => [
        'phpDir' => $outputDirectory,
        'loaderScriptDir' => $outputDirectory,
    ],
    'generator' => ['platformClass' => SqlitePlatform::class],
]]);

$manager = new ModelManager();
$manager->setFilesystem(new Filesystem());
$manager->setGeneratorConfig($config);
$manager->setSchemas([new SplFileInfo(__DIR__ . '/Fixtures/Model/book.schema.xml')]);
$manager->setWorkingDirectory($outputDirectory);
$manager->build();
