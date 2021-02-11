<?php

/**
 * @see       https://github.com/laminas/laminas-db for the canonical source repository
 * @copyright https://github.com/laminas/laminas-db/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-db/blob/master/LICENSE.md New BSD License
 */

namespace LaminasIntegrationTest\Db\Adapter\Metadata;

use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Metadata\MetadataInterface;
use Laminas\Db\Metadata\Source\Factory;

/**
 * @group integration
 * @group integration-mysql
 */
class Db2Test extends AnsiTestCase
{
    /**
     * @var MetadataInterface
     */
    public $source = null;
    public $defaultSchema = null;

    protected function setUp()
    {
        if (! getenv('TESTS_LAMINAS_DB_ADAPTER_DRIVER_IBMDB2')) {
            $this->markTestSkipped(__CLASS__ . ' integration tests are not enabled!');
        }
        $driver = null;
        if (extension_loaded('ibm_db2')) {
            $driver = 'IbmDb2';
        } elseif (extension_loaded('pdo')) {
            $driver = 'pdo_ibm';
        }

        if (! $driver) {
            $this->markTestSkipped(__CLASS__ . ' no valid DB2 driver found!');
            return;
        }

        $this->source = Factory::createSourceFromAdapter(new Adapter([
            'driver' => $driver,
            'hostname' => getenv('TESTS_LAMINAS_DB_ADAPTER_DRIVER_IBMDB2_HOSTNAME'),
            'username' => getenv('TESTS_LAMINAS_DB_ADAPTER_DRIVER_IBMDB2_USERNAME'),
            'password' => getenv('TESTS_LAMINAS_DB_ADAPTER_DRIVER_IBMDB2_PASSWORD'),
            'database' => getenv('TESTS_LAMINAS_DB_ADAPTER_DRIVER_IBMDB2_DATABASE'),
            'platform' => 'IbmDb2',
            'platform_options' => ['quote_identifiers' => false],
        ]));

        $this->defaultSchema = getenv('TESTS_LAMINAS_DB_ADAPTER_DRIVER_IBMDB2_SCHEMA');
    }
}
