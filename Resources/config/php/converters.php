<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Propel\Bundle\PropelBundle\Request\ParamConverter\PropelParamConverter;

return static function (ContainerConfigurator $container): void {
    $container->parameters()->set('propel.converter.propel.class', PropelParamConverter::class);

    $container->services()->set('propel.converter.propel.orm', '%propel.converter.propel.class%')
        ->tag('controller.argument_value_resolver', ['name' => PropelParamConverter::class, 'priority' => 110]);
};
