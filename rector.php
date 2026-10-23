<?php

declare(strict_types=1);

use Contao\Rector\Set\ContaoSetList;
use Rector\Config\RectorConfig;
use Rector\Php84\Rector\Param\ExplicitNullableParamTypeRector;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Symfony\Set\SymfonySetList;
use Rector\TypeDeclaration\Rector\ClassMethod\AddVoidReturnTypeWhereNoReturnRector;

return RectorConfig::configure()
    ->withPaths([__DIR__ . '/src', __DIR__ . '/contao'])
    ->withRules([
        AddVoidReturnTypeWhereNoReturnRector::class,
        ExplicitNullableParamTypeRector::class,
    ])
    ->withImportNames(importShortClasses: false, removeUnusedImports: true)
    ->withSets([
        LevelSetList::UP_TO_PHP_83,
        SymfonySetList::COMPOSER_BASED,
        SymfonySetList::ANNOTATIONS_TO_ATTRIBUTES,
        // Load the Contao rules directly: the level sets reference removed Symfony sets.
        ContaoSetList::CONTAO_49,
        ContaoSetList::CONTAO_413,
        ContaoSetList::CONTAO_50,
        ContaoSetList::CONTAO_51,
        ContaoSetList::CONTAO_53,
        ContaoSetList::FQCN,
        ContaoSetList::ANNOTATIONS_TO_ATTRIBUTES,
    ]);
