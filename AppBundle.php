<?php

namespace App;

use LogicException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\HttpKernel\KernelInterface;

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

        $kernel = $container->get('kernel');
        if (!$kernel instanceof KernelInterface) {
            throw new LogicException('The kernel service must implement KernelInterface.');
        }
        $this->path = $kernel->getProjectDir();
    }
}
