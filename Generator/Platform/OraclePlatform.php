<?php

namespace Propel\Bundle\PropelBundle\Generator\Platform;

use Override;
use Propel\Generator\Model\Domain;
use Propel\Generator\Model\PropelTypes;
use Propel\Generator\Platform\OraclePlatform as BaseOraclePlatform;

/**
 * # OraclePlatform
 * PDO_OCI supports streamed BLOB values; Propel's legacy LONG RAW OBJECT mapping loses their contents.
 */
class OraclePlatform extends BaseOraclePlatform
{
    /**
     * @return void
     */
    #[Override]
    protected function initializeTypeMap(): void
    {
        parent::initializeTypeMap();
        $this->setSchemaDomainMapping(new Domain(PropelTypes::OBJECT, 'BLOB'));
    }
}
