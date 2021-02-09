<?php

/**
 * @see       https://github.com/laminas/laminas-db for the canonical source repository
 * @copyright https://github.com/laminas/laminas-db/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-db/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\Db\Metadata\Source;

use Laminas\Db\Adapter\Platform\PlatformInterface;

class Db2Metadata extends AnsiMetadata
{
    private $aliases = [
        'KEY_COLUMN_USAGE' => ['QSYS2', 'SYSKEYCST'],
        'TRIGGERS' => ['QSYS2', 'SYSTRIGGERS']
    ];

    protected function getSchemaTableName($name, PlatformInterface $platform = null)
    {
        if (isset($this->aliases[$name])) {
            if ($platform == null) {
                $platform = $this->adapter->getPlatform();
            }
            return $platform->quoteIdentifierChain($this->aliases[$name]);
        }

        return parent::getSchemaTableName($name, $platform);
    }
}
