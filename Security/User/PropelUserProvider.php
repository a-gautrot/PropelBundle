<?php

/**
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Bundle\PropelBundle\Security\User;

use LogicException;
use Override;
use Propel\Runtime\ActiveQuery\ModelCriteria;
use Propel\Runtime\Exception\PropelException;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * # PropelUserProvider
 * Provides easy to use provisioning for Propel model users.
 *
 * @author William DURAND <william.durand1@gmail.com>
 * @api
 * @implements UserProviderInterface<UserInterface>
 */
class PropelUserProvider implements UserProviderInterface
{
    /**
     * A Query class name.
     *
     * @var string
     */
    protected string $queryClass;

    /**
     * Default constructor
     *
     * @param string $class The User model class.
     * @param string|null $property The property to use to retrieve a user.
     */
    public function __construct(
        /**
         * A Model class name.
         */
        protected string  $class,
        /**
         * A property to use to retrieve the user.
         */
        protected ?string $property = null
    ) {
        $this->queryClass = "{$this->class}Query";
    }

    /**
     * @param string $identifier
     * @return UserInterface
     * @throws LogicException
     * @throws PropelException
     * @throws UserNotFoundException
     */
    #[Override]
    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        $queryClass = $this->queryClass;
        if (!is_a($queryClass, ModelCriteria::class, allow_string: true)) {
            throw new LogicException('The user query class must extend ModelCriteria.');
        }
        $query = $queryClass::create();

        if ($this->property !== null) {
            $filter = 'filterBy' . ucfirst($this->property);
            $query->$filter($identifier);
        } else {
            if (!method_exists($query, 'filterByUsername')) {
                throw new LogicException('The user query must define filterByUsername() or configure a property.');
            }
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
    #[Override]
    public function refreshUser(UserInterface $user): UserInterface
    {
        if (!$user instanceof $this->class) {
            $userClass = $user::class;
            throw new UnsupportedUserException("Instances of \"$userClass\" are not supported.");
        }

        $queryClass = $this->queryClass;
        if (!is_a($queryClass, ModelCriteria::class, allow_string: true)
            || !method_exists($user, 'getPrimaryKey')) {
            throw new UnsupportedUserException('The user must be a Propel model with a primary key.');
        }
        return $queryClass::create()->findPk($user->getPrimaryKey());
    }

    /**
     * @param string $class
     * @return bool
     */
    #[Override]
    public function supportsClass(string $class): bool
    {
        return $class === $this->class;
    }
}
