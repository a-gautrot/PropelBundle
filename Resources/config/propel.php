<?php

use Propel\Bundle\PropelBundle\DataCollector\PropelDataCollector;
use Propel\Bundle\PropelBundle\DataFixtures\Dumper\YamlDataDumper;
use Propel\Bundle\PropelBundle\DataFixtures\Loader\XmlDataLoader;
use Propel\Bundle\PropelBundle\DataFixtures\Loader\YamlDataLoader;
use Propel\Bundle\PropelBundle\Form\Type\ModelType;
use Propel\Bundle\PropelBundle\Form\TypeGuesser;
use Propel\Bundle\PropelBundle\Logger\PropelLogger;
use Propel\Bundle\PropelBundle\Service\SchemaLocator;
use Propel\Bundle\PropelBundle\Twig\Extension\SyntaxExtension;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    $parameters = $container->parameters();
    $parameters->set('propel.dbal.default_connection', 'default');
    $parameters->set('propel.schema_locator.class', SchemaLocator::class);
    $parameters->set('propel.data_collector.class', PropelDataCollector::class);
    $parameters->set('propel.logger.class', PropelLogger::class);
    $parameters->set('propel.twig.extension.syntax.class', SyntaxExtension::class);
    $parameters->set('form.type_guesser.propel.class', TypeGuesser::class);
    $parameters->set('propel.form.type.model.class', ModelType::class);
    $parameters->set('propel.dumper.yaml.class', YamlDataDumper::class);
    $parameters->set('propel.loader.yaml.class', YamlDataLoader::class);
    $parameters->set('propel.loader.xml.class', XmlDataLoader::class);

    $services = $container->services();
    $services->set('propel.schema_locator', '%propel.schema_locator.class%')
        ->public()
        ->args([service('service_container'), service('file_locator'), '%propel.configuration%']);

    $services->set('propel.logger', '%propel.logger.class%')
        ->public()
        ->tag('monolog.logger', ['channel' => 'propel'])
        ->args([service('logger')->nullOnInvalid(), service('debug.stopwatch')->nullOnInvalid()]);

    $services->set('propel.data_collector', '%propel.data_collector.class%')
        ->private()
        ->args([service('propel.logger')])
        ->tag('data_collector', ['template' => '@Propel/Collector/propel', 'id' => 'propel']);

    $services->set('propel.twig.extension.syntax', '%propel.twig.extension.syntax.class%')
        ->tag('twig.extension');

    $services->set('form.type_guesser.propel', '%form.type_guesser.propel.class%')
        ->tag('form.type_guesser');

    $services->set('propel.form.type.model', '%propel.form.type.model.class%')
        ->tag('form.type', ['alias' => 'model']);

    $services->set('propel.dumper.yaml', '%propel.dumper.yaml.class%')
        ->public()
        ->args(['%kernel.project_dir%', '%propel.configuration%']);

    $services->set('propel.loader.yaml', '%propel.loader.yaml.class%')
        ->public()
        ->args(['%kernel.project_dir%', '%propel.configuration%', service('faker.generator')->nullOnInvalid()]);

    $services->set('propel.loader.xml', '%propel.loader.xml.class%')
        ->public()
        ->args(['%kernel.project_dir%', '%propel.configuration%']);
};
