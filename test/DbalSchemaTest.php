<?php

declare(strict_types=1);

namespace Pheature\Test\Dbal\Toggle;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Schema\SchemaException;
use Pheature\Dbal\Toggle\DbalSchema;
use PHPUnit\Framework\TestCase;

class DbalSchemaTest extends TestCase
{
    private string $dbPath;
    private Connection $connection;

    protected function setUp(): void
    {
        $this->dbPath = realpath(__DIR__) . '/test.sqlite';
        touch($this->dbPath);
        $this->connection = DriverManager::getConnection(['url' => 'sqlite:///' . $this->dbPath]);
    }

    protected function tearDown(): void
    {
        unlink($this->dbPath);
    }

    public function testItShouldCreateValidDatabaseSchema(): void
    {
        $schema = new DbalSchema($this->connection);

        $initializeIfNotExists = false;
        $schema($initializeIfNotExists);

        $statement = $this->connection->executeQuery('SELECT `sql` FROM sqlite_master sm WHERE sm.name = "pheature_toggles"');

        $tableSchema = method_exists($statement, 'fetchOne') ? $statement->fetchOne() : $statement->fetch()['sql'];

        $this->assertStringContainsString('CREATE TABLE pheature_toggles', $tableSchema);
        $this->assertStringContainsString('feature_id VARCHAR(36) NOT NULL', $tableSchema);
        $this->assertStringContainsString('name VARCHAR(140) NOT NULL', $tableSchema);
        $this->assertStringContainsString('enabled BOOLEAN NOT NULL', $tableSchema);
        $this->assertStringContainsString('strategies CLOB DEFAULT \'[]\' NOT NULL --(DC2Type:json)', $tableSchema);
        $this->assertStringContainsString('created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)', $tableSchema);
        $this->assertStringContainsString('updated_at DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)', $tableSchema);
        $this->assertStringContainsString('PRIMARY KEY(feature_id))', $tableSchema);
    }

    public function testItShouldThrowAnExceptionIfSchemaTableAlreadyExists(): void
    {
        $schema = new DbalSchema($this->connection);

        $this->expectException(SchemaException::class);
        $this->expectExceptionMessage("The table with name 'public.pheature_toggles' already exists.");

        $initializeIfNotExists = false;
        $schema($initializeIfNotExists);
        $schema($initializeIfNotExists);
    }

    public function testItShouldDoNothingIfSchemaTableAlreadyExistsSettingInitializeIfNotExistsToTrue(): void
    {
        $schema = new DbalSchema($this->connection);

        $initializeIfNotExists = true;
        $schema($initializeIfNotExists);
        $schema($initializeIfNotExists);

        $statement = $this->connection->executeQuery('SELECT `sql` FROM sqlite_master sm WHERE sm.name = "pheature_toggles"');

        $tableSchema = method_exists($statement, 'fetchOne') ? $statement->fetchOne() : $statement->fetch()['sql'];
        $this->assertStringContainsString('CREATE TABLE pheature_toggles', $tableSchema);
    }
}
