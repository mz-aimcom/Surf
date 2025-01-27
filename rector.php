<?php

declare(strict_types = 1);

use Rector\Core\Configuration\Option;
use Rector\Php74\Rector\Property\TypedPropertyRector;
use Rector\Set\ValueObject\SetList;
use Rector\TypeDeclaration\Rector\ClassMethod\AddVoidReturnTypeWhereNoReturnRector;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    // get parameters
    $parameters = $containerConfigurator->parameters();
    $parameters->set(Option::PATHS, [
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ]);
    $containerConfigurator->import(SetList::TYPE_DECLARATION_STRICT);

    $parameters->set(Option::AUTO_IMPORT_NAMES, true);
    // get services (needed for register a single rule)
    $services = $containerConfigurator->services();

    // register a single rule
    //$services->set(TypedPropertyRector::class);
    //$services->set(AddVoidReturnTypeWhereNoReturnRector::class);
};
