<?php
declare(strict_types=1);

namespace Pheature\Test\Dbal\Toggle\Exception;

use InvalidArgumentException;
use Pheature\Core\Toggle\Exception\FeatureNotFoundException;
use Pheature\Dbal\Toggle\Exception\DbalFeatureNotFound;
use PHPUnit\Framework\TestCase;

final class DbalFeatureNotFoundTest extends TestCase
{
    private const FEATURE_ID = 'some_feature_id';

    public function testItShouldImplementFeatureNotFoundException(): void
    {
        $exception = DbalFeatureNotFound::withId(self::FEATURE_ID);

        $this->assertInstanceOf(FeatureNotFoundException::class, $exception);
        $this->assertInstanceOf(InvalidArgumentException::class, $exception);
    }

    public function testItShouldContainTheCorrectErrorMessage(): void
    {
        $exception = DbalFeatureNotFound::withId(self::FEATURE_ID);

        $expectedMessage = sprintf('There is not feature with id %s in database.', self::FEATURE_ID);
        $this->assertSame($expectedMessage, $exception->getMessage());
    }
}
