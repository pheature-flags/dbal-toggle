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

final class DbalFeatureFactory
{
    /**
     * @param  array<string, string> $featureData
     * @return Feature
     */
    public static function createFromDbalRepresentation(array $featureData): Feature
    {
        /** @var array<string, mixed> $strategiesData */
        $strategiesData = json_decode($featureData['strategies'], true, 12, JSON_THROW_ON_ERROR);
        /** @var callable $strategyCallback */
        $strategyCallback = static function (array $strategy): Strategy {
            $segments = array_map(
                static function (array $segment): Segment {
                    /** @var array<string, mixed> $criteria */
                    $criteria = $segment['criteria'];
                    return new Segment(
                        SegmentId::fromString((string)$segment['segment_id']),
                        SegmentType::fromString((string)$segment['segment_type']),
                        Payload::fromArray($criteria)
                    );
                },
                (array)$strategy['segments']
            );

            return new Strategy(
                StrategyId::fromString((string)$strategy['strategy_id']),
                StrategyType::fromString((string)$strategy['strategy_type']),
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
