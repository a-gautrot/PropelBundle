<?php

/**
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Bundle\PropelBundle\DependencyInjection\Security\UserProvider;

use InvalidArgumentException;
use Override;
use Symfony\Bundle\SecurityBundle\DependencyInjection\Security\UserProvider\UserProviderFactoryInterface;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Builder\ParentNodeDefinitionInterface;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\BadMethodCallException;

/**
 * # PropelFactory
 * PropelFactory creates services for Propel user provider.
 *
 * @author William Durand <william.durand1@gmail.com>
 * @api
 */
class PropelFactory implements UserProviderFactoryInterface
{
    /**
     * @param string $key
     * @param string $providerId
     */
    public function __construct(private readonly string $key, private readonly string $providerId)
    {
    }

    /**
     * @param ContainerBuilder $container
     * @param string $id
     * @param array<array-key, mixed> $config
     * @return void
     * @throws BadMethodCallException
     */
    #[Override]
    public function create(ContainerBuilder $container, string $id, array $config): void
    {
        $container
            ->setDefinition($id, new ChildDefinition($this->providerId))
            ->addArgument($config['class'])
            ->addArgument($config['property']);
    }

    /**
     * @return string
     */
    #[Override]
    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * @param NodeDefinition $builder
     * @return void
     * @throws InvalidArgumentException
     * @psalm-suppress PossiblyNullReference Symfony's end() returns the configured parent builder.
     * @psalm-suppress UndefinedInterfaceMethod Symfony's fluent builder loses the concrete parent type at end().
     */
    #[Override]
    public function addConfiguration(NodeDefinition $builder): void
    {
        if (!$builder instanceof ParentNodeDefinitionInterface) {
            throw new InvalidArgumentException('The user provider configuration must support child nodes.');
        }
        $builder
            ->children()
            ->scalarNode('class')->isRequired()->cannotBeEmpty()->end()
            ->scalarNode('property')->defaultNull();
    }
}
