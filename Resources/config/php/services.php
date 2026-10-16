<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Symfony\Component\DependencyInjection\ContainerInterface;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();
    $services->defaults()->autowire()->autoconfigure()->private();
    $services->load('Propel\\Bundle\\PropelBundle\\', '../../../*')
        ->exclude('../../../{Console,Propel,Model,Migrations,Tests,Helpers,DependencyInjection,Form,Listener,Validator,Resources/config,Resources/skeleton,Twig/Extension/SyntaxExtension.php,DataCollector,AppBundle.php,vendor}');
    $services->alias(ContainerInterface::class, 'service_container');
};
