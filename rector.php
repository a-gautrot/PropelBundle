<?php

declare(strict_types=1);

use Rector\CodeQuality\Rector\CallLike\AddNameToBooleanArgumentRector;
use Rector\CodeQuality\Rector\CallLike\AddNameToNullArgumentRector;
use Rector\Config\RectorConfig;
use Rector\Exception\Configuration\InvalidConfigurationException;

try {
    return RectorConfig::configure()
        ->withPaths([
            __DIR__ . '/Command',
            __DIR__ . '/Controller',
            __DIR__ . '/DataCollector',
            __DIR__ . '/DataFixtures',
            __DIR__ . '/DependencyInjection',
            __DIR__ . '/Form',
            __DIR__ . '/Logger',
            __DIR__ . '/Request',
            __DIR__ . '/Resources',
            __DIR__ . '/Security',
            __DIR__ . '/Service',
            __DIR__ . '/Tests',
            __DIR__ . '/Twig',
            __DIR__ . '/Util',
            __DIR__ . '/Validator'
        ])
        ->withSkip([
            __DIR__ . '/Tests/Fixtures/Model',
            __DIR__ . '/Resources/skeleton',
        ])
        ->withPhpSets(php81: true)
        ->withPreparedSets(symfonyCodeQuality: true)
        ->withComposerBased(symfony: true)
        ->withTypeCoverageLevel(0)
        ->withDeadCodeLevel(0)
        ->withCodeQualityLevel(0)
        ->withRules([
            AddNameToBooleanArgumentRector::class,
            AddNameToNullArgumentRector::class,
        ]);
} catch (InvalidConfigurationException $e) {
}
