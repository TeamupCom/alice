<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\PHPUnit\AnnotationsToAttributes\Rector\ClassMethod\DataProviderAnnotationToAttributeRector;
use Rector\PHPUnit\CodeQuality\Rector\Class_\YieldDataProviderRector;
use Rector\PHPUnit\CodeQuality\Rector\StmtsAwareInterface\DeclareStrictTypesTestsRector;
use Rector\PHPUnit\PHPUnit120\Rector\Class_\AllowMockObjectsWithoutExpectationsAttributeRector;
use Rector\Privatization\Rector\Class_\FinalizeTestCaseClassRector;
use Rector\TypeDeclaration\Rector\Class_\AddTestsVoidReturnTypeWhereNoReturnRector;

return RectorConfig::configure()
    ->withCache('.cache/rector')
    ->withPaths([
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ])
    // uncomment to reach your current PHP version
    ->withComposerBased(phpunit: true)
    ->withSkip([
        AllowMockObjectsWithoutExpectationsAttributeRector::class,
        FinalizeTestCaseClassRector::class,
        DeclareStrictTypesTestsRector::class,
        YieldDataProviderRector::class
    ])
    ->withPreparedSets(phpunitCodeQuality: true)
    ->withRules([
        AddTestsVoidReturnTypeWhereNoReturnRector::class,
    ])
    ->withAttributesSets(phpunit: true)
    ->withImportNames(importShortClasses: false)
    // ->withPhpSets()
    //->withTypeCoverageLevel(0)
    //->withDeadCodeLevel(0)
    //->withCodeQualityLevel(0)
;
