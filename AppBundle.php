<?php

namespace App;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * # AppBundle
 * This class is made to simulate bundle containing base structure so we can work with no bundle architecture used from s4+
 * @package App
 * @api
 */
class AppBundle extends Bundle
{
    /**
     * @var string
     */
    const NAME = 'AppBundle';

    /**
     * @param ContainerInterface $container
     * @throws ServiceCircularReferenceException
     * @throws ServiceNotFoundException
     */
    public function __construct(ContainerInterface $container)
    {
        $this->setContainer($container);

        $this->path = $container->get('kernel')->getProjectDir();
    }
}
