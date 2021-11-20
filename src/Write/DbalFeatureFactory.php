<?php

declare(strict_types=1);

namespace Pheature\Dbal\Toggle\Write;

use Pheature\Core\Toggle\Write\Feature;
use Pheature\Core\Toggle\Write\FeatureId;
use Pheature\Core\Toggle\Write\Payload;
use Pheature\Core\Toggle\Write\Segment;
use Pheature\Core\Toggle\Write\SegmentId;
use Pheature\Core\Toggle\Write\SegmentType;
use Pheature\Core\Toggle\Write\Strategy;
use Pheature\Core\Toggle\Write\StrategyId;
use Pheature\Core\Toggle\Write\StrategyType;

use function array_map;
use function json_decode;

/**
 * @psalm-import-type WriteStrategy from \Pheature\Core\Toggle\Write\Strategy
 * @psalm-import-type DbalFeature from \Pheature\Dbal\Toggle\Read\DbalFeatureFactory
 */
final class DbalFeatureFactory
{
    /**
     * @param DbalFeature $featureData
     * @return Feature
     */
    public static function createFromDbalRepresentation(array $featureData): Feature
    {
        /** @var WriteStrategy[] $strategiesData */
        $strategiesData = json_decode($featureData['strategies'], true, 12, JSON_THROW_ON_ERROR);
        /** @var callable $strategyCallback */
        $strategyCallback = static function (array $strategy): Strategy {
            /** @var WriteStrategy $strategy */
            $segments = array_map(
                static function (array $segment): Segment {
                    return new Segment(
                        SegmentId::fromString($segment['segment_id']),
                        SegmentType::fromString($segment['segment_type']),
                        Payload::fromArray($segment['criteria'])
                    );
                },
                $strategy['segments']
            );

            return new Strategy(
                StrategyId::fromString($strategy['strategy_id']),
                StrategyType::fromString($strategy['strategy_type']),
                $segments
            );
        };

        /** @var Strategy[] $strategies */
        $strategies = array_map($strategyCallback, $strategiesData);

        return new Feature(
            FeatureId::fromString($featureData['feature_id']),
            (bool)$featureData['enabled'],
            $strategies
        );
    }
}
