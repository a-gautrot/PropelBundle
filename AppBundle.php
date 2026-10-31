<?php

namespace App;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * # AppBundle
 * This class simulates a bundle containing the base structure for applications
 * using the bundle-less architecture introduced in Symfony 4.
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
