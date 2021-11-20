<?php

namespace Pheature\Test\Dbal\Toggle\Cli;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Pheature\Dbal\Toggle\Cli\InitSchema;
use Pheature\Dbal\Toggle\DbalSchema;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class InitSchemaTest extends TestCase
{
    public function testItSgouldBeASymdonyCommand(): void
    {
        $schema = $this->createConfiguredMock(Schema::class, [
            'toSql' => [],
        ]);
        $schema->expects(self::once())
            ->method('createTable')
            ->with('pheature_toggles')
            ->willReturn($this->createMock(Table::class));
        $schemaManager = $this->createConfiguredMock(AbstractSchemaManager::class, [
            'createSchema' => $schema,
        ]);
        $platform = $this->createMock(AbstractPlatform::class);

        if (method_exists(Connection::class, 'createSchemaManager')) {
            $connection = $this->createConfiguredMock(Connection::class, [
                'createSchemaManager' => $schemaManager,
                'getDatabasePlatform' => $platform,
            ]);
        } else {
            $connection = $this->createConfiguredMock(Connection::class, [
                'getSchemaManager' => $schemaManager,
                'getDatabasePlatform' => $platform,
            ]);
        }
        $initSchema = new InitSchema(new DbalSchema($connection));

        self::assertSame('pheature:dbal:init-toggle', $initSchema->getName());
        self::assertSame('Create Pheature toggles database schema.', $initSchema->getDescription());

        $input = $this->createMock(InputInterface::class);
        $output = $this->createMock(OutputInterface::class);
        $output->expects(self::once())
            ->method('writeLn')
            ->with('<info>Pheature Toggle database schema successfully created.</info>');
        self::assertSame(0, $initSchema->run($input, $output));
    }
}
