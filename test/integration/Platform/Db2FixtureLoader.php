<?php

/**
 * @see       https://github.com/laminas/laminas-db for the canonical source repository
 * @copyright https://github.com/laminas/laminas-db/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-db/blob/master/LICENSE.md New BSD License
 */

namespace LaminasIntegrationTest\Db\Platform;

class Db2FixtureLoader implements FixtureLoader
{

    private $fixtureFile = __DIR__ . '/../TestFixtures/db2.sql';
    /**
     * @var resource
     */
    private $connection = null;

    public function createDatabase()
    {
        $this->connect();

        if (false === db2_exec($this->connection, sprintf(
            "CREATE SCHEMA %s",
            getenv('TESTS_LAMINAS_DB_ADAPTER_DRIVER_IBMDB2_SCHEMA')
        ))) {
            throw new \Exception(sprintf(
                "I cannot create the DB2 %s schema. %s",
                getenv('TESTS_LAMINAS_DB_ADAPTER_DRIVER_IBMDB2_SCHEMA'),
                print_r(db2_stmt_errormsg(), true)
            ));
        }

        if (false === db2_exec($this->connection, sprintf(
            "SET CURRENT SCHEMA = %s",
            getenv('TESTS_LAMINAS_DB_ADAPTER_DRIVER_IBMDB2_SCHEMA')
        ))) {
            throw new \Exception(sprintf(
                "I cannot switch to the test schema : %s",
                getenv('TESTS_LAMINAS_DB_ADAPTER_DRIVER_IBMDB2_SCHEMA'),
                print_r(db2_stmt_errormsg(), true)
            ));
        }

        if (false === db2_exec($this->connection, file_get_contents($this->fixtureFile))) {
            throw new \Exception(sprintf(
                "I cannot create the table for %s schema. Check the %s file. %s ",
                getenv('TESTS_LAMINAS_DB_ADAPTER_DRIVER_IBMDB2_SCHEMA'),
                $this->fixtureFile,
                print_r(db2_stmt_errormsg(), true)
            ));
        }

        $this->disconnect();
    }

    public function dropDatabase()
    {
        $this->connect();

        if (false === db2_exec($this->connection,(sprintf(
            "DROP SCHEMA %s",
            getenv('TESTS_LAMINAS_DB_ADAPTER_DRIVER_IBMDB2_SCHEMA')
        )))) {
            throw new \Exception(sprintf(
                "I cannot drop  %s schema. %s",
                getenv('TESTS_LAMINAS_DB_ADAPTER_DRIVER_IBMDB2_SCHEMA'),
                print_r(db2_stmt_errormsg(), true)
            ));
        }

        $this->disconnect();
    }

    protected function connect()
    {
        $this->connection = db2_connect (
            getenv('TESTS_LAMINAS_DB_ADAPTER_DRIVER_IBMDB2_DATABASE'),
            getenv('TESTS_LAMINAS_DB_ADAPTER_DRIVER_IBMDB2_USERNAME'),
            getenv('TESTS_LAMINAS_DB_ADAPTER_DRIVER_IBMDB2_PASSWORD')
        );

        if(false === $this->connection) {
            throw new \Exception(sprintf(
                "Cannot connect to DB2 database: %s. %s",
                getenv('TESTS_LAMINAS_DB_ADAPTER_DRIVER_IBMDB2_DATABASE'),
                print_r(db2_stmt_error(), true)
            ));
        }

        db2_autocommit($this->connection, true);
    }

    protected function disconnect()
    {
        $this->connection = null;
    }
}
