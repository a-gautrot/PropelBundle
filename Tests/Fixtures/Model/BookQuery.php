<?php

namespace Propel\Bundle\PropelBundle\Tests\Fixtures\Model;

use Exception;
use Propel\Bundle\PropelBundle\Tests\Fixtures\Model\Base\BookQuery as BaseBookQuery;
use Propel\Runtime\Connection\ConnectionInterface;

/**
 * Skeleton subclass for performing query and update operations on the 'book' table.
 *
 *
 *
 * You should add additional methods to this class to meet the
 * application requirements.  This class will only be generated as
 * long as it does not already exist in the output directory.
 *
 */
class BookQuery extends BaseBookQuery
{
    /**
     * @var bool
     */
    private bool $bySlug = false;
    /**
     * @var bool
     */
    private bool $byAuthorSlug = false;

    /**
     * fake for test
     * @param mixed $key
     * @param ConnectionInterface|null $con
     * @return Book|null
     */
    public function findPk($key, ?ConnectionInterface $con = null): ?Book
    {
        if ($key === 1) {
            $book = new Book();
            $book->setId(1);

            return $book;
        }

        return null;
    }

    /**
     * fake for test
     * @param mixed $slug
     * @param mixed $comparison
     * @return $this
     */
    public function filterByAuthorSlug($slug = null, $comparison = null): self
    {
        if ($slug === 'my-author') {
            $this->byAuthorSlug = true;
        }

        return $this;
    }

    /**
     * fake for test
     * @param mixed $slug
     * @param mixed $comparison
     * @return $this
     */
    public function filterBySlug($slug = null, $comparison = null): self
    {
        if ($slug === 'my-book') {
            $this->bySlug = true;
        }

        return $this;
    }

    /**
     * fake for test
     * @param mixed $name
     * @param mixed $comparison
     * @return mixed
     * @throws Exception
     */
    public function filterByName($name = null, $comparison = null)
    {
        throw new Exception('Test should never call this method');
    }

    /**
     * fake for test
     * @param ConnectionInterface|null $con
     * @return Book|null
     */
    public function findOne(?ConnectionInterface $con = null): ?Book
    {
        if ($this->bySlug) {
            $book = new Book();
            $book->setId(1);
            $book->setTitle('My Book');

            return $book;
        } elseif ($this->byAuthorSlug) {
            $book = new Book();
            $book->setId(2);
            $book->setTitle('My Kewl Book');

            return $book;
        }

        return null;
    }
} // BookQuery
