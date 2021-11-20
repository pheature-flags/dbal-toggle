<?php

declare(strict_types=1);

namespace Pheature\Dbal\Toggle\Read;

use Pheature\Core\Toggle\Read\ChainToggleStrategyFactory;
use Pheature\Core\Toggle\Read\Feature as IFeature;
use Pheature\Core\Toggle\Read\ToggleStrategies;
use Pheature\Model\Toggle\Feature;

use function array_map;
use function json_decode;

/**
 * @psalm-import-type WriteStrategy from \Pheature\Core\Toggle\Write\Strategy
 * @psalm-type DbalFeature array{feature_id: string, enabled: int, strategies: string}
 */
final class DbalFeatureFactory
{
    private const MAX_DEPTH = 512;
    private ChainToggleStrategyFactory $strategyFactory;

    public function __construct(ChainToggleStrategyFactory $strategyFactory)
    {
        $this->strategyFactory = $strategyFactory;
    }

    /** @param DbalFeature $data */
    public function create(array $data): IFeature
    {
        /** @var WriteStrategy[] $strategies */
        $strategies = json_decode($data['strategies'], true, self::MAX_DEPTH, JSON_THROW_ON_ERROR);

        return new Feature(
            $data['feature_id'],
            new ToggleStrategies(
                ...array_map(
                    fn(array $strategy) => $this->strategyFactory->createFromArray($strategy),
                    $strategies
                )
            ),
            (bool)$data['enabled']
        );
    }
}
