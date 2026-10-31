<?php

namespace App;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * # AppBundle
 * Simulates a bundle containing the base structure for the bundle-less architecture used since Symfony 4.
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
        $this->name = self::NAME;
        $this->container = $container;

        $this->path = $container->get('kernel')->getProjectDir();
    }
}
