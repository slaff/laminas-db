<?php

/**
 * @see       https://github.com/laminas/laminas-db for the canonical source repository
 * @copyright https://github.com/laminas/laminas-db/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-db/blob/master/LICENSE.md New BSD License
 */

namespace LaminasIntegrationTest\Db\Adapter\Metadata;

use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Metadata\MetadataInterface;
use Laminas\Db\Metadata\Object\ConstraintObject;
use Laminas\Db\Metadata\Source\Factory;
use PHPUnit\Framework\TestCase;

/**
 * @group integration
 * @group integration-mysql
 */
class MysqlTest extends TestCase
{
    /**
     * @var MetadataInterface
     */
    public $source = null;

    protected function setUp()
    {
        if (! getenv('TESTS_LAMINAS_DB_ADAPTER_DRIVER_MYSQL')) {
            $this->markTestSkipped(__CLASS__ . ' integration tests are not enabled!');
        }
        $driver = null;
        if (extension_loaded('mysqli')) {
            $driver = 'mysqli';
        } elseif (extension_loaded('pdo')) {
            $driver = 'pdo_mysql';
        }

        if (! $driver) {
            $this->markTestSkipped(__CLASS__ . ' no valid MySQL driver found!');
            return;
        }

        $this->source = Factory::createSourceFromAdapter(new Adapter([
            'driver' => $driver,
            'hostname' => getenv('TESTS_LAMINAS_DB_ADAPTER_DRIVER_MYSQL_HOSTNAME'),
            'username' => getenv('TESTS_LAMINAS_DB_ADAPTER_DRIVER_MYSQL_USERNAME'),
            'password' => getenv('TESTS_LAMINAS_DB_ADAPTER_DRIVER_MYSQL_PASSWORD'),
            'database' => getenv('TESTS_LAMINAS_DB_ADAPTER_DRIVER_MYSQL_DATABASE')
        ]));
    }

    public function testGetTableNames()
    {
        if (! $this->source) {
            $this->markTestSkipped('MySQL not configured in unit test configuration file');
        }

        $expected = ['test', 'test_audit_trail', 'test_charset'];
        $actual = $this->source->getTableNames();

        self::assertEquals($expected, $actual);
    }

    public function testGetColumnNames()
    {
        if (! $this->source) {
            $this->markTestSkipped('MySQL not configured in unit test configuration file');
        }

        $expected = ['id', 'name', 'value'];
        $actual = $this->source->getColumnNames('test');

        self::assertEquals($expected, $actual);
    }

    public function testGetViewNames()
    {
        if (! $this->source) {
            $this->markTestSkipped('MySQL not configured in unit test configuration file');
        }

        $expected = ['test_view'];
        $actual = $this->source->getViewNames();

        self::assertEquals($expected, $actual);
    }

    public function testGetTriggerNames()
    {
        if (! $this->source) {
            $this->markTestSkipped('MySQL not configured in unit test configuration file');
        }

        $expected = ['after_test_update'];
        $actual = $this->source->getTriggerNames();

        self::assertEquals($expected, $actual);
    }

    public function testGetConstraints()
    {
        if (! $this->source) {
            $this->markTestSkipped('MySQL not configured in unit test configuration file');
        }
        
        $actual = $this->source->getConstraints('test');
        self::assertEquals(1, count($actual));

        $constraint = $actual[0];
        self::assertInstanceOf(ConstraintObject::class, $constraint);
        self::assertEquals(true, $constraint->isPrimaryKey());
        self::assertEquals('test', $constraint->getTableName());
    }
}
