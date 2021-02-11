<?php

/**
 * @see       https://github.com/laminas/laminas-db for the canonical source repository
 * @copyright https://github.com/laminas/laminas-db/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-db/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\Db\Metadata\Source;

use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Adapter\Platform\PlatformInterface;

/**
 * ANSI Information Schema.
 * @see: https://en.wikipedia.org/wiki/Information_schema
 *
 */
abstract class AnsiMetadata extends AbstractSource
{
    const ERRATAS = "ERRATAS";
    const NUMERIC_UNSIGNED = "NUMERIC_UNSIGNED";

    private $ansiTableNames = [
        'SCHEMATA' => ['INFORMATION_SCHEMA', 'SCHEMATA'],
        'TABLES'   => ['INFORMATION_SCHEMA', 'TABLES'],
        'VIEWS'    => ['INFORMATION_SCHEMA', 'VIEWS'],
        'COLUMNS'  => ['INFORMATION_SCHEMA', 'COLUMNS'],
        'TABLE_CONSTRAINTS' => ['INFORMATION_SCHEMA', 'TABLE_CONSTRAINTS'],
        'KEY_COLUMN_USAGE' => ['INFORMATION_SCHEMA', 'KEY_COLUMN_USAGE'],
        'REFERENTIAL_CONSTRAINTS' => ['INFORMATION_SCHEMA', 'REFERENTIAL_CONSTRAINTS'],
        'TRIGGERS' => ['INFORMATION_SCHEMA', 'TRIGGERS']
    ];

    protected function getSchemaTableName($name, PlatformInterface $platform = null)
    {
        if (! isset($this->ansiTableNames[$name])) {
            throw new \Exception('Invalid INFORMATION_SCHEMA table name.');
        }

        if ($platform == null) {
            $platform = $this->adapter->getPlatform();
        }

        return $platform->quoteIdentifierChain($this->ansiTableNames[$name]);
    }

    protected function prepareLoadTableNameColumns(array &$columns)
    {
    }

    protected function postLoadTableNameResult(&$row)
    {
    }


    protected function postLoadColumnDataResult(&$row)
    {
    }

    protected function prepareLoadColumnDataColumns(array &$columns)
    {
    }

    protected function loadSchemaData()
    {
        if (isset($this->data['schemas'])) {
            return;
        }
        $this->prepareDataHierarchy('schemas');

        $p = $this->adapter->getPlatform();

        $sql = 'SELECT ' . $p->quoteIdentifier('SCHEMA_NAME')
             . ' FROM ' . $this->getSchemaTableName('SCHEMATA', $p)
             . ' WHERE ' . $p->quoteIdentifier('SCHEMA_NAME')
             . ' != \'INFORMATION_SCHEMA\'';

        $results = $this->adapter->query($sql, Adapter::QUERY_MODE_EXECUTE);

        $schemas = [];
        foreach ($results->toArray() as $row) {
            $schemas[] = $row['SCHEMA_NAME'];
        }

        $this->data['schemas'] = $schemas;
    }

    protected function loadTableNameData($schema)
    {
        if (isset($this->data['table_names'][$schema])) {
            return;
        }
        $this->prepareDataHierarchy('table_names', $schema);

        $p = $this->adapter->getPlatform();

        $isColumns = [
            ['T', 'TABLE_NAME'],
            ['T', 'TABLE_TYPE'],
            ['V', 'VIEW_DEFINITION'],
            ['V', 'CHECK_OPTION'],
            ['V', 'IS_UPDATABLE'],
        ];

        $this->prepareLoadTableNameColumns($isColumns);

        array_walk($isColumns, function (&$c) use ($p) {
            $c = $p->quoteIdentifierChain($c);
        });

        $sql = 'SELECT ' . implode(', ', $isColumns)
             . ' FROM ' . $this->getSchemaTableName('TABLES', $p) . ' T'

             . ' LEFT JOIN ' . $this->getSchemaTableName('VIEWS', $p) . ' V'
             . ' ON ' . $p->quoteIdentifierChain(['T', 'TABLE_SCHEMA'])
             . '  = ' . $p->quoteIdentifierChain(['V', 'TABLE_SCHEMA'])
             . ' AND ' . $p->quoteIdentifierChain(['T', 'TABLE_NAME'])
             . '  = ' . $p->quoteIdentifierChain(['V', 'TABLE_NAME'])

             . ' WHERE ' . $p->quoteIdentifierChain(['T', 'TABLE_TYPE'])
             . ' IN (\'BASE TABLE\', \'VIEW\')';

        if ($schema != self::DEFAULT_SCHEMA) {
            $sql .= ' AND ' . $p->quoteIdentifierChain(['T', 'TABLE_SCHEMA'])
                  . ' = ' . $p->quoteTrustedValue($schema);
        } else {
            $sql .= ' AND ' . $p->quoteIdentifierChain(['T', 'TABLE_SCHEMA'])
                  . ' != \'INFORMATION_SCHEMA\'';
        }

        echo "[$sql]\n";

        $results = $this->adapter->query($sql, Adapter::QUERY_MODE_EXECUTE);

        $tables = [];
        foreach ($results->toArray() as $row) {
            $this->postLoadTableNameResult($row);
            $tables[$row['TABLE_NAME']] = [
                'table_type' => $row['TABLE_TYPE'],
                'view_definition' => $row['VIEW_DEFINITION'],
                'check_option' => $row['CHECK_OPTION'],
                'is_updatable' => ('YES' == $row['IS_UPDATABLE']),
            ];
        }


        var_dump($tables);

        $this->data['table_names'][$schema] = $tables;
    }

    protected function loadColumnData($table, $schema)
    {
        if (isset($this->data['columns'][$schema][$table])) {
            return;
        }
        $this->prepareDataHierarchy('columns', $schema, $table);
        $p = $this->adapter->getPlatform();

        $isColumns = [
            ['C', 'ORDINAL_POSITION'],
            ['C', 'COLUMN_DEFAULT'],
            ['C', 'IS_NULLABLE'],
            ['C', 'DATA_TYPE'],
            ['C', 'CHARACTER_MAXIMUM_LENGTH'],
            ['C', 'CHARACTER_OCTET_LENGTH'],
            ['C', 'NUMERIC_PRECISION'],
            ['C', 'NUMERIC_SCALE'],
            ['C', 'COLUMN_NAME'],
        ];

        $this->prepareLoadColumnDataColumns($isColumns);

        array_walk($isColumns, function (&$c) use ($p) {
            $c = $p->quoteIdentifierChain($c);
        });

        $sql = 'SELECT ' . implode(', ', $isColumns)
             . ' FROM ' . $this->getSchemaTableName('TABLES', $p) . ' T'
             . ' INNER JOIN ' . $this->getSchemaTableName('COLUMNS', $p) . ' C'
             . ' ON ' . $p->quoteIdentifierChain(['T', 'TABLE_SCHEMA'])
             . '  = ' . $p->quoteIdentifierChain(['C', 'TABLE_SCHEMA'])
             . ' AND ' . $p->quoteIdentifierChain(['T', 'TABLE_NAME'])
             . '  = ' . $p->quoteIdentifierChain(['C', 'TABLE_NAME'])
             . ' WHERE ' . $p->quoteIdentifierChain(['T', 'TABLE_TYPE'])
             . ' IN (\'BASE TABLE\', \'VIEW\')'
             . ' AND ' . $p->quoteIdentifierChain(['T', 'TABLE_NAME'])
             . '  = ' . $p->quoteTrustedValue($table);

        if ($schema != self::DEFAULT_SCHEMA) {
            $sql .= ' AND ' . $p->quoteIdentifierChain(['T', 'TABLE_SCHEMA'])
                  . ' = ' . $p->quoteTrustedValue($schema);
        } else {
            $sql .= ' AND ' . $p->quoteIdentifierChain(['T', 'TABLE_SCHEMA'])
                  . ' != \'INFORMATION_SCHEMA\'';
        }

        $results = $this->adapter->query($sql, Adapter::QUERY_MODE_EXECUTE);
        $columns = [];
        foreach ($results->toArray() as $row) {
            $this->postLoadColumnDataResult($row);
            $columns[$row['COLUMN_NAME']] = [
                'ordinal_position'         => $row['ORDINAL_POSITION'],
                'column_default'           => $row['COLUMN_DEFAULT'],
                'is_nullable'              => ('YES' == $row['IS_NULLABLE']),
                'data_type'                => $row['DATA_TYPE'],
                'character_maximum_length' => $row['CHARACTER_MAXIMUM_LENGTH'],
                'character_octet_length'   => $row['CHARACTER_OCTET_LENGTH'],
                'numeric_precision'        => $row['NUMERIC_PRECISION'],
                'numeric_scale'            => $row['NUMERIC_SCALE'],
                'numeric_unsigned'         => isset($row[self::NUMERIC_UNSIGNED]) ? $row[self::NUMERIC_UNSIGNED] : null,
                'erratas'                  => isset($row[self::ERRATAS]) ? $row[self::ERRATAS] : [],
            ];
        }

        $this->data['columns'][$schema][$table] = $columns;
    }

    protected function loadConstraintData($table, $schema)
    {
        if (isset($this->data['constraints'][$schema][$table])) {
            return;
        }

        $this->prepareDataHierarchy('constraints', $schema, $table);

        $isColumns = [
            ['T', 'TABLE_NAME'],
            ['TC', 'CONSTRAINT_NAME'],
            ['TC', 'CONSTRAINT_TYPE'],
            ['KCU', 'COLUMN_NAME'],
            ['RC', 'MATCH_OPTION'],
            ['RC', 'UPDATE_RULE'],
            ['RC', 'DELETE_RULE'],
            ['KCU', 'REFERENCED_TABLE_SCHEMA'],
            ['KCU', 'REFERENCED_TABLE_NAME'],
            ['KCU', 'REFERENCED_COLUMN_NAME'],
        ];

        $p = $this->adapter->getPlatform();

        array_walk($isColumns, function (&$c) use ($p) {
            $c = $p->quoteIdentifierChain($c);
        });

        $sql = 'SELECT ' . implode(', ', $isColumns)
             . ' FROM ' . $this->getSchemaTableName('TABLES', $p) . ' T'

             . ' INNER JOIN ' . $this->getSchemaTableName('TABLE_CONSTRAINTS', $p) . ' TC'
             . ' ON ' . $p->quoteIdentifierChain(['T', 'TABLE_SCHEMA'])
             . '  = ' . $p->quoteIdentifierChain(['TC', 'TABLE_SCHEMA'])
             . ' AND ' . $p->quoteIdentifierChain(['T', 'TABLE_NAME'])
             . '  = ' . $p->quoteIdentifierChain(['TC', 'TABLE_NAME'])

             . ' LEFT JOIN ' . $this->getSchemaTableName('KEY_COLUMN_USAGE', $p) . ' KCU'
             . ' ON ' . $p->quoteIdentifierChain(['TC', 'TABLE_SCHEMA'])
             . '  = ' . $p->quoteIdentifierChain(['KCU', 'TABLE_SCHEMA'])
             . ' AND ' . $p->quoteIdentifierChain(['TC', 'TABLE_NAME'])
             . '  = ' . $p->quoteIdentifierChain(['KCU', 'TABLE_NAME'])
             . ' AND ' . $p->quoteIdentifierChain(['TC', 'CONSTRAINT_NAME'])
             . '  = ' . $p->quoteIdentifierChain(['KCU', 'CONSTRAINT_NAME'])

             . ' LEFT JOIN ' . $this->getSchemaTableName('REFERENTIAL_CONSTRAINTS', $p) . ' RC'
             . ' ON ' . $p->quoteIdentifierChain(['TC', 'CONSTRAINT_SCHEMA'])
             . '  = ' . $p->quoteIdentifierChain(['RC', 'CONSTRAINT_SCHEMA'])
             . ' AND ' . $p->quoteIdentifierChain(['TC', 'CONSTRAINT_NAME'])
             . '  = ' . $p->quoteIdentifierChain(['RC', 'CONSTRAINT_NAME'])

             . ' WHERE ' . $p->quoteIdentifierChain(['T', 'TABLE_NAME'])
             . ' = ' . $p->quoteTrustedValue($table)
             . ' AND ' . $p->quoteIdentifierChain(['T', 'TABLE_TYPE'])
             . ' IN (\'BASE TABLE\', \'VIEW\')';

        if ($schema != self::DEFAULT_SCHEMA) {
            $sql .= ' AND ' . $p->quoteIdentifierChain(['T', 'TABLE_SCHEMA'])
            . ' = ' . $p->quoteTrustedValue($schema);
        } else {
            $sql .= ' AND ' . $p->quoteIdentifierChain(['T', 'TABLE_SCHEMA'])
            . ' != \'INFORMATION_SCHEMA\'';
        }

        $sql .= ' ORDER BY CASE ' . $p->quoteIdentifierChain(['TC', 'CONSTRAINT_TYPE'])
              . " WHEN 'PRIMARY KEY' THEN 1"
              . " WHEN 'UNIQUE' THEN 2"
              . " WHEN 'FOREIGN KEY' THEN 3"
              . " ELSE 4 END"

              . ', ' . $p->quoteIdentifierChain(['TC', 'CONSTRAINT_NAME'])
              . ', ' . $p->quoteIdentifierChain(['KCU', 'ORDINAL_POSITION']);

        $results = $this->adapter->query($sql, Adapter::QUERY_MODE_EXECUTE);

        $realName = null;
        $constraints = [];
        foreach ($results->toArray() as $row) {
            if ($row['CONSTRAINT_NAME'] !== $realName) {
                $realName = $row['CONSTRAINT_NAME'];
                $isFK = ('FOREIGN KEY' == $row['CONSTRAINT_TYPE']);
                if ($isFK) {
                    $name = $realName;
                } else {
                    $name = '_laminas_' . $row['TABLE_NAME'] . '_' . $realName;
                }
                $constraints[$name] = [
                    'constraint_name' => $name,
                    'constraint_type' => $row['CONSTRAINT_TYPE'],
                    'table_name'      => $row['TABLE_NAME'],
                    'columns'         => [],
                ];
                if ($isFK) {
                    $constraints[$name]['referenced_table_schema'] = $row['REFERENCED_TABLE_SCHEMA'];
                    $constraints[$name]['referenced_table_name']   = $row['REFERENCED_TABLE_NAME'];
                    $constraints[$name]['referenced_columns']      = [];
                    $constraints[$name]['match_option']       = $row['MATCH_OPTION'];
                    $constraints[$name]['update_rule']        = $row['UPDATE_RULE'];
                    $constraints[$name]['delete_rule']        = $row['DELETE_RULE'];
                }
            }
            $constraints[$name]['columns'][] = $row['COLUMN_NAME'];
            if ($isFK) {
                $constraints[$name]['referenced_columns'][] = $row['REFERENCED_COLUMN_NAME'];
            }
        }

        $this->data['constraints'][$schema][$table] = $constraints;
    }

    protected function loadConstraintDataKeys($schema)
    {
        if (isset($this->data['constraint_keys'][$schema])) {
            return;
        }

        $this->prepareDataHierarchy('constraint_keys', $schema);

        $p = $this->adapter->getPlatform();

        $isColumns = [
            ['T', 'TABLE_NAME'],
            ['KCU', 'CONSTRAINT_NAME'],
            ['KCU', 'COLUMN_NAME'],
            ['KCU', 'ORDINAL_POSITION'],
        ];

        array_walk($isColumns, function (&$c) use ($p) {
            $c = $p->quoteIdentifierChain($c);
        });

        $sql = 'SELECT ' . implode(', ', $isColumns)
        . ' FROM ' . $this->getSchemaTableName('TABLES', $p) . 'T'

        . ' INNER JOIN ' . $this->getSchemaTableName('KEY_COLUMN_USAGE', $p) . 'KCU'
        . ' ON ' . $p->quoteIdentifierChain(['T', 'TABLE_SCHEMA'])
        . '  = ' . $p->quoteIdentifierChain(['KCU', 'TABLE_SCHEMA'])
        . ' AND ' . $p->quoteIdentifierChain(['T', 'TABLE_NAME'])
        . '  = ' . $p->quoteIdentifierChain(['KCU', 'TABLE_NAME'])

        . ' WHERE ' . $p->quoteIdentifierChain(['T', 'TABLE_TYPE'])
        . ' IN (\'BASE TABLE\', \'VIEW\')';

        if ($schema != self::DEFAULT_SCHEMA) {
            $sql .= ' AND ' . $p->quoteIdentifierChain(['T', 'TABLE_SCHEMA'])
            . ' = ' . $p->quoteTrustedValue($schema);
        } else {
            $sql .= ' AND ' . $p->quoteIdentifierChain(['T', 'TABLE_SCHEMA'])
            . ' != \'INFORMATION_SCHEMA\'';
        }

        $results = $this->adapter->query($sql, Adapter::QUERY_MODE_EXECUTE);

        $data = [];
        foreach ($results->toArray() as $row) {
            $data[] = array_change_key_case($row, CASE_LOWER);
        }

        $this->data['constraint_keys'][$schema] = $data;
    }

    protected function loadConstraintReferences($table, $schema)
    {
        parent::loadConstraintReferences($table, $schema);

        $p = $this->adapter->getPlatform();

        $isColumns = [
            ['RC', 'TABLE_NAME'],
            ['RC', 'CONSTRAINT_NAME'],
            ['RC', 'UPDATE_RULE'],
            ['RC', 'DELETE_RULE'],
            ['KCU', 'REFERENCED_TABLE_SCHEMA'],
            ['KCU', 'REFERENCED_TABLE_NAME'],
            ['KCU', 'REFERENCED_COLUMN_NAME'],
        ];

        array_walk($isColumns, function (&$c) use ($p) {
            $c = $p->quoteIdentifierChain($c);
        });

        $sql = 'SELECT ' . implode(', ', $isColumns)
        . 'FROM ' . $this->getSchemaTableName('TABLES', $p) . 'T'

        . ' INNER JOIN ' . $this->getSchemaTableName('REFERENTIAL_CONSTRAINTS', $p) . 'RC'
        . ' ON ' . $p->quoteIdentifierChain(['T', 'TABLE_SCHEMA'])
        . '  = ' . $p->quoteIdentifierChain(['RC', 'CONSTRAINT_SCHEMA'])
        . ' AND ' . $p->quoteIdentifierChain(['T', 'TABLE_NAME'])
        . '  = ' . $p->quoteIdentifierChain(['RC', 'TABLE_NAME'])

        . ' INNER JOIN ' . $this->getSchemaTableName('KEY_COLUMN_USAGE', $p) . 'KCU'
        . ' ON ' . $p->quoteIdentifierChain(['RC', 'CONSTRAINT_SCHEMA'])
        . '  = ' . $p->quoteIdentifierChain(['KCU', 'TABLE_SCHEMA'])
        . ' AND ' . $p->quoteIdentifierChain(['RC', 'TABLE_NAME'])
        . '  = ' . $p->quoteIdentifierChain(['KCU', 'TABLE_NAME'])
        . ' AND ' . $p->quoteIdentifierChain(['RC', 'CONSTRAINT_NAME'])
        . '  = ' . $p->quoteIdentifierChain(['KCU', 'CONSTRAINT_NAME'])

        . 'WHERE ' . $p->quoteIdentifierChain(['T', 'TABLE_TYPE'])
        . ' IN (\'BASE TABLE\', \'VIEW\')';

        if ($schema != self::DEFAULT_SCHEMA) {
            $sql .= ' AND ' . $p->quoteIdentifierChain(['T', 'TABLE_SCHEMA'])
            . ' = ' . $p->quoteTrustedValue($schema);
        } else {
            $sql .= ' AND ' . $p->quoteIdentifierChain(['T', 'TABLE_SCHEMA'])
            . ' != \'INFORMATION_SCHEMA\'';
        }

        $results = $this->adapter->query($sql, Adapter::QUERY_MODE_EXECUTE);

        $data = [];
        foreach ($results->toArray() as $row) {
            $data[] = array_change_key_case($row, CASE_LOWER);
        }

        $this->data['constraint_references'][$schema] = $data;
    }

    protected function loadTriggerData($schema)
    {
        if (isset($this->data['triggers'][$schema])) {
            return;
        }

        $this->prepareDataHierarchy('triggers', $schema);

        $p = $this->adapter->getPlatform();

        $isColumns = [
//            'TRIGGER_CATALOG',
//            'TRIGGER_SCHEMA',
            'TRIGGER_NAME',
            'EVENT_MANIPULATION',
//             'EVENT_OBJECT_CATALOG',
            'EVENT_OBJECT_SCHEMA',
            'EVENT_OBJECT_TABLE',
            'ACTION_ORDER',
            'ACTION_CONDITION',
            'ACTION_STATEMENT',
            'ACTION_ORIENTATION',
            'ACTION_TIMING',
            'ACTION_REFERENCE_OLD_TABLE',
            'ACTION_REFERENCE_NEW_TABLE',
            'ACTION_REFERENCE_OLD_ROW',
            'ACTION_REFERENCE_NEW_ROW',
            'CREATED',
        ];

        array_walk($isColumns, function (&$c) use ($p) {
            $c = $p->quoteIdentifier($c);
        });

        $sql = 'SELECT ' . implode(', ', $isColumns)
        . ' FROM ' . $this->getSchemaTableName('TRIGGERS', $p)
        . ' WHERE ';

        if ($schema != self::DEFAULT_SCHEMA) {
            $sql .= $p->quoteIdentifier('TRIGGER_SCHEMA')
            . ' = ' . $p->quoteTrustedValue($schema);
        } else {
            $sql .= $p->quoteIdentifier('TRIGGER_SCHEMA')
            . ' != \'INFORMATION_SCHEMA\'';
        }

        $results = $this->adapter->query($sql, Adapter::QUERY_MODE_EXECUTE);

        $data = [];
        foreach ($results->toArray() as $row) {
            $row = array_change_key_case($row, CASE_LOWER);
            if (null !== $row['created']) {
                $row['created'] = new \DateTime($row['created']);
            }
            $data[$row['trigger_name']] = $row;
        }

        $this->data['triggers'][$schema] = $data;
    }
}
