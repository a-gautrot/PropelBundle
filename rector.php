<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\DeadCode\Rector\ClassMethod\RemoveArgumentFromDefaultParentCallRector;
use Rector\DeadCode\Rector\MethodCall\RemoveNullArgOnNullDefaultParamRector;
use Rector\ValueObject\PhpVersion;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/AppBundle.php',
        __DIR__ . '/Command',
        __DIR__ . '/Controller',
        __DIR__ . '/DataCollector',
        __DIR__ . '/DataFixtures',
        __DIR__ . '/DependencyInjection',
        __DIR__ . '/Form',
        __DIR__ . '/Logger',
        __DIR__ . '/PropelBundle.php',
        __DIR__ . '/Request',
        __DIR__ . '/Resources',
        __DIR__ . '/Security',
        __DIR__ . '/Service',
        __DIR__ . '/Tests',
        __DIR__ . '/Twig',
        __DIR__ . '/Util',
        __DIR__ . '/Validator',
    ])
    ->withSkip([
        __DIR__ . '/Resources/skeleton',
        __DIR__ . '/Tests/Fixtures/Model',
    ])
    ->withPhpVersion(PhpVersion::PHP_82)
    ->withPhpSets(php82: true)
    ->withComposerBased(symfony: true)
    ->withPreparedSets(namedArgs: true, symfonyCodeQuality: true)
    ->withCodeQualityLevel(0)
    ->withDeadCodeLevel(0)
    ->withTypeCoverageLevel(0)
    ->withRules([
        RemoveArgumentFromDefaultParentCallRector::class,
        RemoveNullArgOnNullDefaultParamRector::class,
    ])
    ->withImportNames();
