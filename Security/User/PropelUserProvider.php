<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Bundle\PropelBundle\Security\User;

use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * # PropelUserProvider
 * Provides easy to use provisioning for Propel model users.
 *
 * @author William DURAND <william.durand1@gmail.com>
 */
class PropelUserProvider implements UserProviderInterface
{
    /**
     * A Model class name.
     *
     * @var string
     */
    protected string $class;

    /**
     * A Query class name.
     *
     * @var string
     */
    protected string $queryClass;

    /**
     * A property to use to retrieve the user.
     *
     * @var string|null
     */
    protected ?string $property = null;

    /**
     * Default constructor
     *
     * @param string $class The User model class.
     * @param string|null $property The property to use to retrieve a user.
     */
    public function __construct(string $class, ?string $property = null)
    {
        $this->class = $class;
        $this->queryClass = $class . 'Query';
        $this->property = $property;
    }

    /**
     * @param string $username
     * @return UserInterface
     * @throws UserNotFoundException
     * @deprecated since Symfony 5.3, use loadUserByIdentifier() instead
     */
    public function loadUserByUsername(string $username): UserInterface
    {
        return $this->loadUserByIdentifier($username);
    }

    /**
     * @param string $identifier
     * @return UserInterface
     */
    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        $queryClass = $this->queryClass;
        $query = $queryClass::create();

        if ($this->property !== null) {
            $filter = 'filterBy' . ucfirst($this->property);
            $query->$filter($identifier);
        } else {
            $query->filterByUsername($identifier);
        }

        $user = $query->findOne();

        if ($user === null) {
            throw new UserNotFoundException("User \"$identifier\" not found.");
        }

        return $user;
    }

    /**
     * @param UserInterface $user
     * @return UserInterface
     */
    public function refreshUser(UserInterface $user): UserInterface
    {
        if (!$user instanceof $this->class) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', get_class($user)));
        }

        $queryClass = $this->queryClass;

        return $queryClass::create()->findPk($user->getPrimaryKey());
    }

    /**
     * @param string $class
     * @return bool
     */
    public function supportsClass(string $class): bool
    {
        return $class === $this->class;
    }
}
