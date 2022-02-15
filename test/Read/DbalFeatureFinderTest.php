<?php

namespace Pheature\Test\Dbal\Toggle\Read;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Result;
use Pheature\Core\Toggle\Read\ChainToggleStrategyFactory;
use Pheature\Core\Toggle\Read\SegmentFactory;
use Pheature\Core\Toggle\Read\ToggleStrategyFactory;
use Pheature\Dbal\Toggle\Exception\DbalFeatureNotFound;
use Pheature\Dbal\Toggle\Read\DbalFeatureFactory;
use Pheature\Dbal\Toggle\Read\DbalFeatureFinder;
use PHPUnit\Framework\TestCase;

class DbalFeatureFinderTest extends TestCase
{
    private const FEATURE_ID = 'some_feature';

    public function testItThrowsAnExceptionIfGivenFeatureNotFoundInDatabase(): void
    {
        $this->expectException(DbalFeatureNotFound::class);

        $statement = $this->createMock(Result::class);
        $statement->expects(self::once())
            ->method('fetchAssociative')
            ->willReturn(false);
        $connection = $this->createMock(Connection::class);
        $connection->expects(self::once())
            ->method('executeQuery')
            ->with(
                'SELECT * FROM pheature_toggles WHERE feature_id = :feature_id',
                ['feature_id' => self::FEATURE_ID]
            )
            ->willReturn($statement);
        $segmentFactory = $this->createMock(SegmentFactory::class);
        $toggleStrategyFactory = $this->createMock(ToggleStrategyFactory::class);
        $strategyFactory = new ChainToggleStrategyFactory($segmentFactory, $toggleStrategyFactory);

        $finder = new DbalFeatureFinder($connection, new DbalFeatureFactory($strategyFactory));
        $finder->get(self::FEATURE_ID);
    }

    public function testItShouldGetAFeatureFromDatabase(): void
    {
        $statement = $this->createMock(Result::class);
        $statement->expects(self::once())
            ->method('fetchAssociative')
            ->willReturn([
                'feature_id' => self::FEATURE_ID,
                'enabled' => 1,
                'strategies' => '[]',
            ]);
        $connection = $this->createMock(Connection::class);
        $connection->expects(self::once())
            ->method('executeQuery')
            ->with(
                'SELECT * FROM pheature_toggles WHERE feature_id = :feature_id',
                ['feature_id' => self::FEATURE_ID]
            )
            ->willReturn($statement);
        $segmentFactory = $this->createMock(SegmentFactory::class);
        $toggleStrategyFactory = $this->createMock(ToggleStrategyFactory::class);
        $strategyFactory = new ChainToggleStrategyFactory($segmentFactory, $toggleStrategyFactory);

        $finder = new DbalFeatureFinder($connection, new DbalFeatureFactory($strategyFactory));
        $feature = $finder->get(self::FEATURE_ID);
        self::assertSame(self::FEATURE_ID, $feature->id());
        self::assertSame(true, $feature->isEnabled());
        self::assertSame(0, $feature->strategies()->count());
    }

    public function testItShouldFindAllFeaturesFromDatabase(): void
    {
        $statement = $this->createMock(Result::class);
        $statement->expects(self::once())
            ->method('fetchAllAssociative')
            ->willReturn([
                [
                    'feature_id' => self::FEATURE_ID,
                    'enabled' => 1,
                    'strategies' => '[]',
                ],
                [
                    'feature_id' => 'another_feature',
                    'enabled' => 1,
                    'strategies' => '[]',
                ],
            ]);
        $connection = $this->createMock(Connection::class);
        $connection->expects(self::once())
            ->method('executeQuery')
            ->with(
                'SELECT * FROM pheature_toggles ORDER BY created_at DESC'
            )
            ->willReturn($statement);
        $segmentFactory = $this->createMock(SegmentFactory::class);
        $toggleStrategyFactory = $this->createMock(ToggleStrategyFactory::class);
        $strategyFactory = new ChainToggleStrategyFactory($segmentFactory, $toggleStrategyFactory);

        $finder = new DbalFeatureFinder($connection, new DbalFeatureFactory($strategyFactory));
        $features = $finder->all();
        self::assertCount(2, $features);
    }
}
