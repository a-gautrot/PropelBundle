<?php

namespace Propel\Bundle\PropelBundle\Tests\Fixtures\Model\Base;

use Exception;
use PDO;
use Propel\Bundle\PropelBundle\Tests\Fixtures\Model\Book as ChildBook;
use Propel\Bundle\PropelBundle\Tests\Fixtures\Model\BookQuery as ChildBookQuery;
use Propel\Bundle\PropelBundle\Tests\Fixtures\Model\Map\BookTableMap;
use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\ActiveQuery\ModelCriteria;
use Propel\Runtime\ActiveRecord\ActiveRecordInterface;
use Propel\Runtime\Connection\ConnectionInterface;
use Propel\Runtime\Exception\BadMethodCallException;
use Propel\Runtime\Exception\PropelException;
use Propel\Runtime\Map\TableMap;
use Propel\Runtime\Parser\AbstractParser;
use Propel\Runtime\Propel;

/**
 * # Book
 */
abstract class Book implements ActiveRecordInterface
{
    /**
     * TableMap class name
     */
    const TABLE_MAP = '\\Propel\\Bundle\\PropelBundle\\Tests\\Fixtures\\Model\\Map\\BookTableMap';

    /**
     * attribute to determine if this object has previously been saved.
     * @var bool
     */
    protected bool $new = true;

    /**
     * attribute to determine whether this object has been deleted.
     * @var bool
     */
    protected bool $deleted = false;

    /**
     * The columns that have been modified in current object.
     * Tracking modified columns allows us to only update modified columns.
     * @var array
     */
    protected array $modifiedColumns = [];

    /**
     * The (virtual) columns that are added at runtime
     * The formatters can add supplementary columns based on a resultset
     * @var array
     */
    protected array $virtualColumns = [];

    /**
     * The value for the id field.
     * @var        int|null
     */
    protected ?int $id = null;

    /**
     * The value for the name field.
     * @var        string
     */
    protected string $name = '';

    /**
     * The value for the slug field.
     * @var string
     */
    protected string $slug = '';

    /**
     * The value for the isbn field.
     * @var        string
     */
    protected string $isbn = '';

    /**
     * The value for the author_id field.
     * @var        int|null
     */
    protected ?int $author_id = null;

    /**
     * @var        Author|null
     */
    protected ?Author $aAuthor = null;

    /**
     * Flag to prevent endless save loop, if this object is referenced
     * by another object which falls in this transaction.
     *
     * @var bool
     */
    protected bool $alreadyInSave = false;

    /**
     * Initializes internal state of Acme\DemoBundle\Model\Base\Book object.
     */
    public function __construct()
    {
    }

    /**
     * Get the columns that have been modified in this object.
     * @return array A unique list of the modified column names for this object.
     */
    public function getModifiedColumns(): array
    {
        return array_unique($this->modifiedColumns);
    }

    /**
     * Compares this with another <code>Book</code> instance.  If
     * <code>obj</code> is an instance of <code>Book</code>, delegates to
     * <code>equals(Book)</code>.  Otherwise, returns <code>false</code>.
     *
     * @param mixed $obj The object to compare to.
     * @return bool Whether equal to the object specified.
     */
    public function equals(mixed $obj): bool
    {
        $thisclazz = get_class($this);
        if (!is_object($obj) || !($obj instanceof $thisclazz)) {
            return false;
        }

        if ($this === $obj) {
            return true;
        }

        if ($this->getPrimaryKey() === null
            || $obj->getPrimaryKey() === null) {
            return false;
        }

        return $this->getPrimaryKey() === $obj->getPrimaryKey();
    }

    /**
     * Returns the primary key for this object (row).
     * @return int
     */
    public function getPrimaryKey(): int
    {
        return $this->getId();
    }

    /**
     * Get the [id] column value.
     *
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Set the value of [id] column.
     *
     * @param int $v new value
     * @return ChildBook
     */
    public function setId(int $v): ChildBook
    {
        if ($this->id !== $v) {
            $this->id = $v;
            $this->modifiedColumns[] = BookTableMap::ID;
        }

        return $this;
    } // setId()

    /**
     * If the primary key is not null, return the hashcode of the
     * primary key. Otherwise, return the hash code of the object.
     *
     * @return int Hashcode
     */
    public function hashCode(): int
    {
        if ($this->getPrimaryKey() !== null) {
            return crc32(serialize($this->getPrimaryKey()));
        }

        return crc32(serialize(clone $this));
    }

    /**
     * Get the associative array of the virtual columns in this object
     *
     * @return array
     */
    public function getVirtualColumns(): array
    {
        return $this->virtualColumns;
    }

    /**
     * Set the value of a virtual column in this object
     *
     * @param string $name The virtual column name
     * @param mixed $value The value to give to the virtual column
     *
     * @return Book The current object, for fluid interface
     */
    public function setVirtualColumn(string $name, mixed $value): self
    {
        $this->virtualColumns[$name] = $value;

        return $this;
    }

    /**
     * Clean up internal collections prior to serializing
     * Avoids recursive loops that turn into segmentation faults when serializing
     * @deprecated in PHP 8.5
     */
    public function __sleep(): array
    {
        $this->clearAllReferences();

        return array_keys(get_object_vars($this));
    }

    /**
     * Resets all references to other model objects or collections of model objects.
     *
     * This method is a user-space workaround for PHP's inability to garbage collect
     * objects with circular references (even in PHP 5.3). This is currently necessary
     * when using Propel in certain daemon or large-volume/high-memory operations.
     *
     * @param bool $deep Whether to also clear the references on all referrer objects.
     */
    public function clearAllReferences(bool $deep = false): void
    {
        if ($deep) {
        } // if ($deep)

        $this->aAuthor = null;
    }

    /**
     * Get the [slug] column value.
     *
     * @return string
     */
    public function getSlug(): string
    {
        return $this->slug;
    }

    /**
     * Set the value of [slug] column.
     *
     * @param string $v new value
     * @return ChildBook
     */
    public function setSlug(string $v): ChildBook
    {
        if ($this->slug !== $v) {
            $this->slug = $v;
            $this->modifiedColumns[] = BookTableMap::SLUG;
        }

        return $this;
    } // setSlug()

    /**
     * Indicates whether the columns in this object are only set to default values.
     *
     * This method can be used in conjunction with isModified() to indicate whether an object is both
     * modified _and_ has some values set which are non-default.
     *
     * @return bool Whether the columns in this object are only been set with default values.
     */
    public function hasOnlyDefaultValues(): bool
    {
        // otherwise, everything was equal, so return TRUE
        return true;
    } // hasOnlyDefaultValues()

    /**
     * Reloads this object from datastore based on primary key and (optionally) resets all associated objects.
     *
     * This will only work if the object has been saved and has a valid primary key set.
     *
     * @param bool $deep (optional) Whether to also de-associated any related objects.
     * @param ConnectionInterface|null $con (optional) The ConnectionInterface connection to use.
     * @return void
     * @throws PropelException     - if this object is deleted, unsaved or doesn't have pk match in db
     */
    public function reload(bool $deep = false, ?ConnectionInterface $con = null): void
    {
        if ($this->isDeleted()) {
            throw new PropelException("Cannot reload a deleted object.");
        }

        if ($this->isNew()) {
            throw new PropelException("Cannot reload an unsaved object.");
        }

        if ($con === null) {
            $con = Propel::getServiceContainer()->getReadConnection(BookTableMap::DATABASE_NAME);
        }

        // We don't need to alter the object instance pool; we're just modifying this instance
        // already in the pool.

        $dataFetcher = ChildBookQuery::create(null, $this->buildPkeyCriteria())->setFormatter(ModelCriteria::FORMAT_STATEMENT)->find($con);
        $row = $dataFetcher->fetch();
        $dataFetcher->close();
        if (!$row) {
            throw new PropelException('Cannot find matching row in the database to reload object values.');
        }
        $this->hydrate($row, 0, true, $dataFetcher->getIndexType()); // rehydrate

        if ($deep) {  // also de-associate any related objects?
            $this->aAuthor = null;
        } // if (deep)
    }

    /**
     * Whether this object has been deleted.
     * @return bool The deleted state of this object.
     */
    public function isDeleted(): bool
    {
        return $this->deleted;
    }

    /**
     * Specify whether this object has been deleted.
     * @param bool $b The deleted state of this object.
     * @return void
     */
    public function setDeleted(bool $b): void
    {
        $this->deleted = $b;
    }

    /**
     * Returns whether the object has ever been saved.  This will
     * be false, if the object was retrieved from storage or was created
     * and then saved.
     *
     * @return bool true, if the object has never been persisted.
     */
    public function isNew(): bool
    {
        return $this->new;
    }

    /**
     * Setter for the isNew attribute.  This method will be called
     * by Propel-generated children and objects.
     *
     * @param bool $b the state of the object.
     */
    public function setNew(bool $b): void
    {
        $this->new = $b;
    }

    /**
     * Builds a Criteria object containing the primary key for this object.
     *
     * Unlike buildCriteria() this method includes the primary key values regardless
     * of whether or not they have been modified.
     *
     * @return Criteria The Criteria object containing value(s) for primary key(s).
     */
    public function buildPkeyCriteria(): Criteria
    {
        $criteria = new Criteria(BookTableMap::DATABASE_NAME);
        $criteria->add(BookTableMap::ID, $this->id);

        return $criteria;
    }

    /**
     * Hydrates (populates) the object variables with values from the database resultset.
     *
     * An offset (0-based "start column") is specified so that objects can be hydrated
     * with a subset of the columns in the resultset rows.  This is needed, for example,
     * for results of JOIN queries where the resultset row includes columns from two or
     * more tables.
     *
     * @param array $row The row returned by DataFetcher->fetch().
     * @param int $startcol 0-based offset column which indicates which restultset column to start with.
     * @param bool $rehydrate Whether this object is being re-hydrated from the database.
     * @param string $indexType The index type of $row. Mostly DataFetcher->getIndexType().
     * One of the class type constants TableMap::TYPE_PHPNAME, TableMap::TYPE_STUDLYPHPNAME
     *                            TableMap::TYPE_COLNAME, TableMap::TYPE_FIELDNAME, TableMap::TYPE_NUM.
     *
     * @return int             next starting column
     * @throws PropelException - Any caught Exception will be rewrapped as a PropelException.
     */
    public function hydrate(array $row, int $startcol = 0, bool $rehydrate = false, string $indexType = TableMap::TYPE_NUM): int
    {
        try {

            $col = $row[$indexType == TableMap::TYPE_NUM ? $startcol : BookTableMap::translateFieldName('Id', TableMap::TYPE_PHPNAME, $indexType)];
            $this->id = ($col !== null) ? (int)$col : null;

            $col = $row[$indexType == TableMap::TYPE_NUM ? 1 + $startcol : BookTableMap::translateFieldName('Name', TableMap::TYPE_PHPNAME, $indexType)];
            $this->name = ($col !== null) ? (string)$col : null;

            $col = $row[$indexType == TableMap::TYPE_NUM ? 2 + $startcol : BookTableMap::translateFieldName('Isbn', TableMap::TYPE_PHPNAME, $indexType)];
            $this->isbn = ($col !== null) ? (string)$col : null;

            $col = $row[$indexType == TableMap::TYPE_NUM ? 3 + $startcol : BookTableMap::translateFieldName('AuthorId', TableMap::TYPE_PHPNAME, $indexType)];
            $this->author_id = ($col !== null) ? (int)$col : null;
            $this->resetModified();

            $this->setNew(false);

            if ($rehydrate) {
                $this->ensureConsistency();
            }

            return $startcol + 4; // 4 = BookTableMap::NUM_HYDRATE_COLUMNS.

        } catch (Exception $e) {
            throw new PropelException("Error populating ChildBook object", 0, $e);
        }
    }

    /**
     * Sets the modified state for the object to be false.
     * @param string|null $col If supplied, only the specified column is reset.
     * @return void
     */
    public function resetModified(?string $col = null): void
    {
        if ($col !== null) {
            while (($offset = array_search($col, $this->modifiedColumns)) !== false) {
                array_splice($this->modifiedColumns, $offset, 1);
            }
        } else {
            $this->modifiedColumns = [];
        }
    }

    /**
     * Checks and repairs the internal consistency of the object.
     *
     * This method is executed after an already-instantiated object is re-hydrated
     * from the database.  It exists to check any foreign keys to make sure that
     * the objects related to the current object are correct based on foreign key.
     *
     * You can override this method in the stub class, but you should always invoke
     * the base method from the overridden method (i.e. parent::ensureConsistency()),
     * in case your model changes.
     *
     * @throws PropelException
     */
    public function ensureConsistency(): void
    {
        if ($this->aAuthor !== null && $this->author_id !== $this->aAuthor->getId()) {
            $this->aAuthor = null;
        }
    } // ensureConsistency

    /**
     * Removes this object from datastore and sets delete attribute.
     *
     * @param ConnectionInterface|null $con
     * @return void
     * @throws PropelException
     * @see Book::setDeleted()
     * @see Book::isDeleted()
     */
    public function delete(?ConnectionInterface $con = null): void
    {
        if ($this->isDeleted()) {
            throw new PropelException("This object has already been deleted.");
        }

        if ($con === null) {
            $con = Propel::getServiceContainer()->getWriteConnection(BookTableMap::DATABASE_NAME);
        }

        $con->beginTransaction();
        try {
            $deleteQuery = ChildBookQuery::create()
                ->filterByPrimaryKey($this->getPrimaryKey());
            $ret = $this->preDelete($con);
            if ($ret) {
                $deleteQuery->delete($con);
                $this->postDelete($con);
                $con->commit();
                $this->setDeleted(true);
            } else {
                $con->commit();
            }
        } catch (Exception $e) {
            $con->rollBack();
            throw $e;
        }
    }

    /**
     * Code to be run before deleting the object in database
     * @param ConnectionInterface|null $con
     * @return bool
     */
    public function preDelete(?ConnectionInterface $con = null): bool
    {
        return true;
    }

    /**
     * Code to be run after deleting the object in database
     * @param ConnectionInterface|null $con
     */
    public function postDelete(?ConnectionInterface $con = null): void
    {

    }

    /**
     * Persists this object to the database.
     *
     * If the object is new, it inserts it; otherwise an update is performed.
     * All modified related objects will also be persisted in the doSave()
     * method.  This method wraps all precipitate database operations in a
     * single transaction.
     *
     * @param ConnectionInterface|null $con
     * @return int                 The number of rows affected by this insert/update and any referring fk objects' save() operations.
     * @throws PropelException
     * @see doSave()
     */
    public function save(?ConnectionInterface $con = null): int
    {
        if ($this->isDeleted()) {
            throw new PropelException("You cannot save an object that has been deleted.");
        }

        if ($con === null) {
            $con = Propel::getServiceContainer()->getWriteConnection(BookTableMap::DATABASE_NAME);
        }

        $con->beginTransaction();
        $isInsert = $this->isNew();
        try {
            $ret = $this->preSave($con);
            if ($isInsert) {
                $ret = $ret && $this->preInsert($con);
            } else {
                $ret = $ret && $this->preUpdate($con);
            }
            if ($ret) {
                $affectedRows = $this->doSave($con);
                if ($isInsert) {
                    $this->postInsert($con);
                } else {
                    $this->postUpdate($con);
                }
                $this->postSave($con);
                BookTableMap::addInstanceToPool($this);
            } else {
                $affectedRows = 0;
            }
            $con->commit();

            return $affectedRows;
        } catch (Exception $e) {
            $con->rollBack();
            throw $e;
        }
    }

    /**
     * Code to be run before persisting the object
     * @param ConnectionInterface|null $con
     * @return bool
     */
    public function preSave(?ConnectionInterface $con = null): bool
    {
        return true;
    }

    /**
     * Code to be run before inserting to database
     * @param ConnectionInterface|null $con
     * @return bool
     */
    public function preInsert(?ConnectionInterface $con = null): bool
    {
        return true;
    }

    /**
     * Code to be run before updating the object in database
     * @param ConnectionInterface|null $con
     * @return bool
     */
    public function preUpdate(?ConnectionInterface $con = null): bool
    {
        return true;
    }

    /**
     * Performs the work of inserting or updating the row in the database.
     *
     * If the object is new, it inserts it; otherwise an update is performed.
     * All related objects are also updated in this method.
     *
     * @param ConnectionInterface $con
     * @return int                 The number of rows affected by this insert/update and any referring fk objects' save() operations.
     * @throws PropelException
     * @see save()
     */
    protected function doSave(ConnectionInterface $con): int
    {
        $affectedRows = 0; // initialize var to track total num of affected rows
        if (!$this->alreadyInSave) {
            $this->alreadyInSave = true;

            // We call the save method on the following object(s) if they
            // were passed to this object by their corresponding set
            // method.  This object relates to these object(s) by a
            // foreign key reference.

            if ($this->aAuthor !== null) {
                if ($this->aAuthor->isModified() || $this->aAuthor->isNew()) {
                    $affectedRows += $this->aAuthor->save($con);
                }
                $this->setAuthor($this->aAuthor);
            }

            if ($this->isNew() || $this->isModified()) {
                // persist changes
                if ($this->isNew()) {
                    $this->doInsert($con);
                } else {
                    $this->doUpdate($con);
                }
                $affectedRows += 1;
                $this->resetModified();
            }

            $this->alreadyInSave = false;

        }

        return $affectedRows;
    } // doSave()

    /**
     * Returns whether the object has been modified.
     *
     * @return bool True if the object has been modified.
     */
    public function isModified(): bool
    {
        return !empty($this->modifiedColumns);
    }

    /**
     * Declares an association between this object and a ChildAuthor object.
     *
     * @param ChildAuthor|null $v
     * @return ChildBook
     */
    public function setAuthor(?ChildAuthor $v = null): ChildBook
    {
        if ($v === null) {
            $this->setAuthorId((int)null);
        } else {
            $this->setAuthorId($v->getId());
        }

        $this->aAuthor = $v;

        // Add binding for other direction of this n:n relationship.
        // If this object has already been added to the ChildAuthor object, it will not be re-added.
        $v?->addBook($this);

        return $this;
    }

    /**
     * Insert the row in the database.
     *
     * @param ConnectionInterface $con
     *
     * @throws PropelException
     * @see doSave()
     */
    protected function doInsert(ConnectionInterface $con): void
    {
        $modifiedColumns = [];
        $index = 0;

        $this->modifiedColumns[] = BookTableMap::ID;
        if ($this->id !== null) {
            throw new PropelException('Cannot insert a value for auto-increment primary key (' . BookTableMap::ID . ')');
        }

        // check the columns in natural order for more readable SQL queries
        if ($this->isColumnModified(BookTableMap::ID)) {
            $modifiedColumns[':p' . $index++] = 'ID';
        }
        if ($this->isColumnModified(BookTableMap::NAME)) {
            $modifiedColumns[':p' . $index++] = 'NAME';
        }
        if ($this->isColumnModified(BookTableMap::ISBN)) {
            $modifiedColumns[':p' . $index++] = 'ISBN';
        }
        if ($this->isColumnModified(BookTableMap::AUTHOR_ID)) {
            $modifiedColumns[':p' . $index++] = 'AUTHOR_ID';
        }

        $columns = implode(', ', $modifiedColumns);
        $placeholders = implode(', ', array_keys($modifiedColumns));
        $sql = "INSERT INTO book ($columns) VALUES ($placeholders)";

        try {
            $stmt = $con->prepare($sql);
            foreach ($modifiedColumns as $identifier => $columnName) {
                switch ($columnName) {
                    case 'ID':
                        $stmt->bindValue($identifier, $this->id, PDO::PARAM_INT);
                        break;
                    case 'NAME':
                        $stmt->bindValue($identifier, $this->name);
                        break;
                    case 'ISBN':
                        $stmt->bindValue($identifier, $this->isbn);
                        break;
                    case 'AUTHOR_ID':
                        $stmt->bindValue($identifier, $this->author_id, PDO::PARAM_INT);
                        break;
                }
            }
            $stmt->execute();
        } catch (Exception $e) {
            Propel::log($e->getMessage(), Propel::LOG_ERR);
            throw new PropelException("Unable to execute INSERT statement [$sql]", 0, $e);
        }

        try {
            $pk = $con->lastInsertId();
        } catch (Exception $e) {
            throw new PropelException('Unable to get autoincrement id.', 0, $e);
        }
        $this->setId($pk);

        $this->setNew(false);
    }

    /**
     * Has specified column been modified?
     *
     * @param string $col column fully qualified name (TableMap::TYPE_COLNAME), e.g. Book::AUTHOR_ID
     * @return bool True if $col has been modified.
     */
    public function isColumnModified(string $col): bool
    {
        return in_array($col, $this->modifiedColumns);
    }

    /**
     * Logs a message using Propel::log().
     *
     * @param string $msg
     * @param int $priority One of the Propel::LOG_* logging levels
     * @return void
     */
    protected function log(string $msg, int $priority = Propel::LOG_INFO): void
    {
        Propel::log(get_class($this) . ': ' . $msg, $priority);
    }

    /**
     * Update the row in the database.
     *
     * @param ConnectionInterface $con
     *
     * @return int Number of updated rows
     * @see doSave()
     */
    protected function doUpdate(ConnectionInterface $con): int
    {
        $selectCriteria = $this->buildPkeyCriteria();
        $valuesCriteria = $this->buildCriteria();

        return $selectCriteria->doUpdate($valuesCriteria, $con);
    }

    /**
     * Build a Criteria object containing the values of all modified columns in this object.
     *
     * @return Criteria The Criteria object containing all modified values.
     */
    public function buildCriteria(): Criteria
    {
        $criteria = new Criteria(BookTableMap::DATABASE_NAME);

        if ($this->isColumnModified(BookTableMap::ID)) $criteria->add(BookTableMap::ID, $this->id);
        if ($this->isColumnModified(BookTableMap::NAME)) $criteria->add(BookTableMap::NAME, $this->name);
        if ($this->isColumnModified(BookTableMap::ISBN)) $criteria->add(BookTableMap::ISBN, $this->isbn);
        if ($this->isColumnModified(BookTableMap::AUTHOR_ID)) $criteria->add(BookTableMap::AUTHOR_ID, $this->author_id);
        return $criteria;
    }

    /**
     * Code to be run after inserting to database
     * @param ConnectionInterface|null $con
     */
    public function postInsert(?ConnectionInterface $con = null): void
    {

    }

    /**
     * Code to be run after updating the object in database
     * @param ConnectionInterface|null $con
     */
    public function postUpdate(?ConnectionInterface $con = null): void
    {

    }

    /**
     * Code to be run after persisting the object
     * @param ConnectionInterface|null $con
     */
    public function postSave(?ConnectionInterface $con = null): void
    {

    }

    /**
     * Retrieves a field from the object by name passed in as a string.
     *
     * @param string $name name
     * @param string $type The type of fieldname the $name is of:
     *                      one of the class type constants TableMap::TYPE_PHPNAME, TableMap::TYPE_STUDLYPHPNAME
     *                      TableMap::TYPE_COLNAME, TableMap::TYPE_FIELDNAME, TableMap::TYPE_NUM.
     *                      Defaults to TableMap::TYPE_PHPNAME.
     * @return string|int|null  Value of field.
     * @throws PropelException
     */
    public function getByName(string $name, string $type = TableMap::TYPE_PHPNAME): string|int|null
    {
        $pos = BookTableMap::translateFieldName($name, $type, TableMap::TYPE_NUM);
        return $this->getByPosition($pos);
    }

    /**
     * Retrieves a field from the object by Position as specified in the xml schema.
     * Zero-based.
     *
     * @param int $pos position in xml schema
     * @return string|int|null Value of field at $pos
     */
    public function getByPosition(int $pos): string|int|null
    {
        return match ($pos) {
            0 => $this->getId(),
            1 => $this->getName(),
            2 => $this->getIsbn(),
            3 => $this->getAuthorId(),
            default => null,
        }; // switch()
    }

    /**
     * Get the [name] column value.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Set the value of [name] column.
     *
     * @param string $v new value
     * @return ChildBook
     */
    public function setName(string $v): ChildBook
    {
        if ($this->name !== $v) {
            $this->name = $v;
            $this->modifiedColumns[] = BookTableMap::NAME;
        }

        return $this;
    } // setName()

    /**
     * Get the [isbn] column value.
     *
     * @return string
     */
    public function getIsbn(): string
    {
        return $this->isbn;
    }

    /**
     * Set the value of [isbn] column.
     *
     * @param string $v new value
     * @return ChildBook
     */
    public function setIsbn(string $v): ChildBook
    {
        if ($this->isbn !== $v) {
            $this->isbn = $v;
            $this->modifiedColumns[] = BookTableMap::ISBN;
        }

        return $this;
    } // setIsbn()

    /**
     * Get the [author_id] column value.
     *
     * @return int
     */
    public function getAuthorId(): int
    {
        return $this->author_id;
    }

    /**
     * Set the value of [author_id] column.
     *
     * @param int $v new value
     * @return ChildBook
     */
    public function setAuthorId(int $v): ChildBook
    {
        if ($this->author_id !== $v) {
            $this->author_id = $v;
            $this->modifiedColumns[] = BookTableMap::AUTHOR_ID;
        }

        if ($this->aAuthor !== null && $this->aAuthor->getId() !== $v) {
            $this->aAuthor = null;
        }

        return $this;
    } // setAuthorId()

    /**
     * Sets a field from the object by name passed in as a string.
     *
     * @param string $name
     * @param mixed $value field value
     * @param string $type The type of fieldname the $name is of:
     *                       one of the class type constants TableMap::TYPE_PHPNAME, TableMap::TYPE_STUDLYPHPNAME
     *                       TableMap::TYPE_COLNAME, TableMap::TYPE_FIELDNAME, TableMap::TYPE_NUM.
     *                       Defaults to TableMap::TYPE_PHPNAME.
     * @return void
     * @throws PropelException
     */
    public function setByName(string $name, mixed $value, string $type = TableMap::TYPE_PHPNAME): void
    {
        $pos = BookTableMap::translateFieldName($name, $type, TableMap::TYPE_NUM);

        $this->setByPosition($pos, $value);
    }

    /**
     * Sets a field from the object by Position as specified in the xml schema.
     * Zero-based.
     *
     * @param int $pos position in xml schema
     * @param mixed $value field value
     * @return void
     */
    public function setByPosition(int $pos, mixed $value): void
    {
        switch ($pos) {
            case 0:
                $this->setId($value);
                break;
            case 1:
                $this->setName($value);
                break;
            case 2:
                $this->setIsbn($value);
                break;
            case 3:
                $this->setAuthorId($value);
                break;
        } // switch()
    }

    /**
     * Generic method to set the primary key (id column).
     *
     * @param int $key Primary key.
     * @return void
     */
    public function setPrimaryKey(int $key): void
    {
        $this->setId($key);
    }

    /**
     * Returns true if the primary key for this object is null.
     * @return bool
     */
    public function isPrimaryKeyNull(): bool
    {
        return $this->getId() === null;
    }

    /**
     * Makes a copy of this object that will be inserted as a new row in table when saved.
     * It creates a new object filling in the simple attributes, but skipping any primary
     * keys that are defined for the table.
     *
     * If desired, this method can also make copies of all associated (fkey referrers)
     * objects.
     *
     * @param bool $deepCopy Whether to also copy all rows that refer (by fkey) to the current row.
     * @return ChildBook|Book Clone of current object.
     * @throws PropelException
     */
    public function copy(bool $deepCopy = false): ChildBook|Book
    {
        // we use get_class(), because this might be a subclass
        $clazz = get_class($this);
        $copyObj = new $clazz();
        $this->copyInto($copyObj, $deepCopy);

        return $copyObj;
    }

    /**
     * Sets contents of passed object to values from current object.
     *
     * If desired, this method can also make copies of all associated (fkey referrers)
     * objects.
     *
     * @param object $copyObj An object of ChildBook (or compatible) type.
     * @param bool $deepCopy Whether to also copy all rows that refer (by fkey) to the current row.
     * @param bool $makeNew Whether to reset autoincrement PKs and make the object new.
     * @throws PropelException
     */
    public function copyInto(object $copyObj, bool $deepCopy = false, bool $makeNew = true): void
    {
        $copyObj->setName($this->getName());
        $copyObj->setIsbn($this->getIsbn());
        $copyObj->setAuthorId($this->getAuthorId());
        if ($makeNew) {
            $copyObj->setNew(true);
            $copyObj->setId(null); // this is a auto-increment column, so set to default value
        }
    }

    /**
     * Get the associated ChildAuthor object
     *
     * @param ConnectionInterface|null $con Optional Connection object.
     * @return Author|ChildAuthor The associated ChildAuthor object.
     */
    public function getAuthor(?ConnectionInterface $con = null): Author|ChildAuthor
    {
        if ($this->aAuthor === null && ($this->author_id !== null)) {
            $this->aAuthor = ChildAuthorQuery::create()->findPk($this->author_id, $con);
            /* The following can be used additionally to
                guarantee the related object contains a reference
                to this object.  This level of coupling may, however, be
                undesirable since it could result in an only partially populated collection
                in the referenced object.
                $this->aAuthor->addBooks($this);
             */
        }

        return $this->aAuthor;
    }

    /**
     * Clears the current object and sets all attributes to their default values
     */
    public function clear(): void
    {
        $this->id = null;
        $this->name = null;
        $this->isbn = null;
        $this->author_id = null;
        $this->alreadyInSave = false;
        $this->clearAllReferences();
        $this->resetModified();
        $this->setNew(true);
        $this->setDeleted(false);
    }

    /**
     * Return the string representation of this object
     *
     * @return string The value of the 'name' column
     */
    public function __toString(): string
    {
        return $this->getName();
    }

    /**
     * Derived method to catches calls to undefined methods.
     *
     * Provides magic import/export method support (fromXML()/toXML(), fromYAML()/toYAML(), etc.).
     * Allows to define default __call() behavior if you overwrite __call()
     *
     * @param string $name
     * @param mixed $params
     *
     * @return array|string
     * @throws PropelException
     */
    public function __call(string $name, mixed $params): array|string
    {
        if (str_starts_with($name, 'get')) {
            $virtualColumn = substr($name, 3);
            if ($this->hasVirtualColumn($virtualColumn)) {
                return $this->getVirtualColumn($virtualColumn);
            }

            $virtualColumn = lcfirst($virtualColumn);
            if ($this->hasVirtualColumn($virtualColumn)) {
                return $this->getVirtualColumn($virtualColumn);
            }
        }

        if (str_starts_with($name, 'from')) {
            $format = substr($name, 4);

            return $this->importFrom($format, reset($params));
        }

        if (str_starts_with($name, 'to')) {
            $format = substr($name, 2);
            $includeLazyLoadColumns = $params[0] ?? true;

            return $this->exportTo($format, $includeLazyLoadColumns);
        }

        throw new BadMethodCallException("Call to undefined method: $name.");
    }

    /**
     * Checks the existence of a virtual column in this object
     *
     * @param string $name The virtual column name
     * @return bool
     */
    public function hasVirtualColumn(string $name): bool
    {
        return array_key_exists($name, $this->virtualColumns);
    }

    /**
     * Get the value of a virtual column in this object
     *
     * @param string $name The virtual column name
     * @return mixed
     *
     * @throws PropelException
     */
    public function getVirtualColumn(string $name): mixed
    {
        if (!$this->hasVirtualColumn($name)) {
            throw new PropelException("Cannot get value of inexistent virtual column $name.");
        }

        return $this->virtualColumns[$name];
    }

    /**
     * Populate the current object from a string, using a given parser format
     * <code>
     * $book = new Book();
     * $book->importFrom('JSON', '{"Id":9012,"Name":"Don Juan","ISBN":"0140422161","Price":12.99,"PublisherId":1234,"AuthorId":5678}');
     * </code>
     *
     * @param mixed $parser A AbstractParser instance,
     *                       or a format name ('XML', 'YAML', 'JSON', 'CSV')
     * @param string $data The source data to import from
     *
     * @return Book The current object, for fluid interface
     * @throws PropelException
     */
    public function importFrom(mixed $parser, string $data): self
    {
        if (!$parser instanceof AbstractParser) {
            $parser = AbstractParser::getParser($parser);
        }

        $this->fromArray($parser->toArray($data));

        return $this;
    }

    /**
     * Populates the object using an array.
     *
     * This is particularly useful when populating an object from one of the
     * request arrays (e.g. $_POST).  This method goes through the column
     * names, checking to see whether a matching key exists in populated
     * array. If so the setByName() method is called for that column.
     *
     * You can specify the key type of the array by additionally passing one
     * of the class type constants TableMap::TYPE_PHPNAME, TableMap::TYPE_STUDLYPHPNAME,
     * TableMap::TYPE_COLNAME, TableMap::TYPE_FIELDNAME, TableMap::TYPE_NUM.
     * The default key type is the column's TableMap::TYPE_PHPNAME.
     *
     * @param array $arr An array to populate the object from.
     * @param string $keyType The type of keys the array uses.
     * @return void
     * @throws PropelException
     */
    public function fromArray(array $arr, string $keyType = TableMap::TYPE_PHPNAME): void
    {
        $keys = BookTableMap::getFieldNames($keyType);

        if (array_key_exists($keys[0], $arr)) $this->setId($arr[$keys[0]]);
        if (array_key_exists($keys[1], $arr)) $this->setName($arr[$keys[1]]);
        if (array_key_exists($keys[2], $arr)) $this->setIsbn($arr[$keys[2]]);
        if (array_key_exists($keys[3], $arr)) $this->setAuthorId($arr[$keys[3]]);
    }

    /**
     * Exports the object as an array.
     *
     * You can specify the key type of the array by passing one of the class
     * type constants.
     *
     * @param string $keyType (optional) One of the class type constants TableMap::TYPE_PHPNAME, TableMap::TYPE_STUDLYPHPNAME,
     *                                        TableMap::TYPE_COLNAME, TableMap::TYPE_FIELDNAME, TableMap::TYPE_NUM.
     *                                        Defaults to TableMap::TYPE_PHPNAME.
     * @param bool $includeLazyLoadColumns (optional) Whether to include lazy loaded columns. Defaults to TRUE.
     * @param array $alreadyDumpedObjects List of objects to skip to avoid recursion
     * @param bool $includeForeignObjects (optional) Whether to include hydrated related objects. Default to FALSE.
     *
     * @return array|string
     * @throws PropelException
     */
    public function toArray(string $keyType = TableMap::TYPE_PHPNAME, bool $includeLazyLoadColumns = true, array $alreadyDumpedObjects = [], bool $includeForeignObjects = false): array|string
    {
        if (isset($alreadyDumpedObjects['Book'][$this->getPrimaryKey()])) {
            return '*RECURSION*';
        }
        $alreadyDumpedObjects['Book'][$this->getPrimaryKey()] = true;
        $keys = BookTableMap::getFieldNames($keyType);
        $result = [
            $keys[0] => $this->getId(),
            $keys[1] => $this->getName(),
            $keys[2] => $this->getIsbn(),
            $keys[3] => $this->getAuthorId(),
        ];
        $virtualColumns = $this->virtualColumns;
        foreach ($virtualColumns as $key => $virtualColumn) {
            $result[$key] = $virtualColumn;
        }

        if ($includeForeignObjects) {
            if ($this->aAuthor !== null) {
                $result['Author'] = $this->aAuthor->toArray($keyType, $includeLazyLoadColumns, $alreadyDumpedObjects, true);
            }
        }

        return $result;
    }

    /**
     * Export the current object properties to a string, using a given parser format
     * <code>
     * $book = BookQuery::create()->findPk(9012);
     * echo $book->exportTo('JSON');
     *  => {"Id":9012,"Name":"Don Juan","ISBN":"0140422161","Price":12.99,"PublisherId":1234,"AuthorId":5678}');
     * </code>
     *
     * @param mixed $parser A AbstractParser instance, or a format name ('XML', 'YAML', 'JSON', 'CSV')
     * @param bool $includeLazyLoadColumns (optional) Whether to include lazy load(ed) columns. Defaults to TRUE.
     * @return string  The exported data
     * @throws PropelException
     */
    public function exportTo(mixed $parser, bool $includeLazyLoadColumns = true): string
    {
        if (!$parser instanceof AbstractParser) {
            $parser = AbstractParser::getParser($parser);
        }

        return $parser->fromArray($this->toArray(TableMap::TYPE_PHPNAME, $includeLazyLoadColumns, [], true));
    }

}
