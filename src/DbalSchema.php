<?php

declare(strict_types=1);

namespace Pheature\Dbal\Toggle;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\SchemaConfig;

final class DbalSchema
{
    private const TABLE_NAME = 'pheature_toggles';
    private Schema $schema;
    private AbstractPlatform $platform;
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        if (method_exists($connection, 'createSchemaManager')) {
            $this->schema = $connection->createSchemaManager()->introspectSchema();
        } else {
            /** @psalm-suppress DeprecatedMethod */
            $this->schema = $connection->getSchemaManager()->createSchema();
        }
        $this->platform = $connection->getDatabasePlatform();
        $this->connection = $connection;
    }

    public function __invoke(bool $initializeIfNotExists): void
    {
        if (!$this->shouldBeExecuted($initializeIfNotExists)) {
            return;
        }

        $this->createPheatureTogglesTable();

        $queries = $this->schema->toSql($this->platform);
        foreach ($queries as $query) {
            if (false !== strpos($query, self::TABLE_NAME)) {
                $this->connection->executeQuery($query);
            }
        }
    }

    private function createPheatureTogglesTable(): void
    {
        $table = $this->schema->createTable(self::TABLE_NAME);
        $table->addColumn(
            'feature_id',
            'string',
            [
                'length' => 36,
            ]
        );
        $table->setPrimaryKey(['feature_id']);
        $table->addColumn(
            'name',
            'string',
            [
                'length' => 140,
            ]
        );
        $table->addColumn('enabled', 'boolean');
        if ($this->isSqlite()) {
            $table->addColumn(
                'strategies',
                'json',
                [
                    'default' => '[]'
                ]
            );
        } else {
            $table->addColumn('strategies', 'json');
        }
        $table->addColumn('created_at', 'datetime_immutable');
        $table->addIndex(['created_at']);
        $table->addColumn(
            'updated_at',
            'datetime_immutable',
            [
                'notnull' => false,
                'default' => null,
            ]
        );
    }

    private function shouldBeExecuted(bool $initializeIfNotExists): bool
    {
        if (!$initializeIfNotExists) {
            return true;
        }

        if ($this->schema->hasTable(self::TABLE_NAME)) {
            return false;
        }

        return true;
    }

    private function isSqlite(): bool
    {
        $driver = $this->connection->getDriver();

        if (
            class_exists(\Doctrine\DBAL\Driver\PDOSqlite\Driver::class)
            && $driver instanceof \Doctrine\DBAL\Driver\PDOSqlite\Driver
        ) {
            return true;
        }

        if (
            class_exists(\Doctrine\DBAL\Driver\PDO\SQLite\Driver::class)
            && $driver instanceof \Doctrine\DBAL\Driver\PDO\SQLite\Driver
        ) {
            return true;
        }

        if (
            class_exists(\Doctrine\DBAL\Driver::class)
            && $driver instanceof \Doctrine\DBAL\Driver
        ) {
            /**
             * @psalm-suppress DeprecatedMethod
             */
            return 'sqlite' === $driver->getDatabasePlatform()->getName();
        }

        return false;
    }
}
