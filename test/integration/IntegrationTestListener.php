<?php

/**
 * @see       https://github.com/laminas/laminas-db for the canonical source repository
 * @copyright https://github.com/laminas/laminas-db/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-db/blob/master/LICENSE.md New BSD License
 */

namespace LaminasIntegrationTest\Db;

use LaminasIntegrationTest\Db\Platform\FixtureLoader;
use PDO;
use PHPUnit\Framework\BaseTestListener;
use PHPUnit_Framework_TestSuite as TestSuite;

class IntegrationTestListener extends BaseTestListener
{
    /**
     * @var PDO
     */
    private $pdo;

    /**
     * @var FixtureLoader
     */
    private $fixtureLoader;

    public function startTestSuite(TestSuite $suite)
    {
        if ($suite->getName() !== 'integration test') {
            return;
        }

        if (getenv('TESTS_LAMINAS_DB_ADAPTER_DRIVER_IBMDB2')) {
            $this->fixtureLoader = new \LaminasIntegrationTest\Db\Platform\Db2FixtureLoader();
        }
        if (getenv('TESTS_LAMINAS_DB_ADAPTER_DRIVER_MYSQL')) {
            $this->fixtureLoader = new \LaminasIntegrationTest\Db\Platform\MysqlFixtureLoader();
        }
        if (getenv('TESTS_LAMINAS_DB_ADAPTER_DRIVER_PGSQL')) {
            $this->fixtureLoader = new \LaminasIntegrationTest\Db\Platform\PgsqlFixtureLoader();
        }

        if (getenv('TESTS_LAMINAS_DB_ADAPTER_DRIVER_SQLSRV')) {
            $this->fixtureLoader = new \LaminasIntegrationTest\Db\Platform\SqlServerFixtureLoader();
        }

        if (! isset($this->fixtureLoader)) {
            return;
        }
        printf("\nIntegration test started.\n");
        $this->fixtureLoader->createDatabase();
    }

    public function endTestSuite(TestSuite $suite)
    {
        if ($suite->getName() !== 'integration test'
            || ! isset($this->fixtureLoader)
        ) {
            return;
        }
        printf("\nIntegration test ended.\n");

        $this->fixtureLoader->dropDatabase();
    }
}
