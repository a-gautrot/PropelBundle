<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Symfony\Set\SymfonySetList;
use Rector\ValueObject\PhpVersion;

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
    ->withPhpLevel(PhpVersion::PHP_74)
    ->withSets([SymfonySetList::SYMFONY_CODE_QUALITY])
    ->withTypeCoverageLevel(0)
    ->withDeadCodeLevel(0)
    ->withCodeQualityLevel(0);
