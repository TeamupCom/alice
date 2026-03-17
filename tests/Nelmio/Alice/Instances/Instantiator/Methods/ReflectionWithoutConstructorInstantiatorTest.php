<?php

/*
 * This file is part of the Alice package.
 *
 *  (c) Nelmio <hello@nelm.io>
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace Nelmio\Alice\Instances\Instantiator\Methods;

use PHPUnit\Framework\Attributes\CoversClass;
use Nelmio\Alice\Fixtures\Fixture;
use Nelmio\Alice\Instances\Instantiator\DummyClasses\DummyWithDefaultConstructor;
use Nelmio\Alice\Instances\Instantiator\DummyClasses\DummyWithExplicitDefaultConstructor;
use Nelmio\Alice\Instances\Instantiator\DummyClasses\DummyWithOptionalAndRequiredParameterInConstructor;
use Nelmio\Alice\Instances\Instantiator\DummyClasses\DummyWithOptionalParameterInConstructor;
use Nelmio\Alice\Instances\Instantiator\DummyClasses\DummyWithPrivateConstructor;
use Nelmio\Alice\Instances\Instantiator\DummyClasses\DummyWithProtectedConstructor;
use Nelmio\Alice\Instances\Instantiator\DummyClasses\DummyWithRequiredParameterInConstructor;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(ReflectionWithoutConstructor::class)]
class ReflectionWithoutConstructorInstantiatorTest extends TestCase
{
    /**
     * @var ReflectionWithoutConstructor
     */
    private $instantiator;

    public function setUp(): void
    {
        $this->instantiator = new ReflectionWithoutConstructor();
    }

    public function testIsAnInstantiatorMethod(): void
    {
        $this->assertTrue(is_a(ReflectionWithoutConstructor::class, MethodInterface::class, true));
    }

    #[DataProvider('provideFixtures')]
    public function testCanInstantiateObjectWithDefaultConstructor(Fixture $fixture, $expected): void
    {
        $actual = $this->instantiator->canInstantiate($fixture);

        $this->assertEquals($expected, $actual);
    }

    public function testInstantiateFixture(): void
    {
        $class = 'stdClass';
        $fixture = self::createFixtureForClass($class);
        $this->instantiator->canInstantiate($fixture);
        $actual = $this->instantiator->instantiate($fixture);

        $this->assertInstanceOf($class, $actual);

        $class = DummyWithDefaultConstructor::class;
        $fixture = self::createFixtureForClass(DummyWithDefaultConstructor::class);
        $this->instantiator->canInstantiate($fixture);
        $actual = $this->instantiator->instantiate($fixture);

        $this->assertInstanceOf($class, $actual);
    }

    public static function provideFixtures(): array
    {
        $returned = [];

        $returned['private constructor'] = [
            self::createFixtureForClass(DummyWithPrivateConstructor::class),
            true,
        ];

        $returned['protected constructor'] = [
            self::createFixtureForClass(DummyWithProtectedConstructor::class),
            true,
        ];

        $returned['private constructor with fixture constructor different from __construct'] = [
            new Fixture(
                DummyWithPrivateConstructor::class,
                'dummy',
                [
                    '__construct' => [
                        'namedConstruct' => [],
                    ],
                ],
                null
            ),
            false,
        ];

        $returned['default constructor'] = [
            self::createFixtureForClass(DummyWithDefaultConstructor::class),
            false,
        ];

        $returned['explicit default constructor'] = [
            self::createFixtureForClass(DummyWithExplicitDefaultConstructor::class),
            false,
        ];

        $returned['named constructor'] = [
            new Fixture(
                DummyWithPrivateConstructor::class,
                'dummy',
                [
                    '__construct' => [
                        'namedConstruct' => [],
                    ],
                ],
                null
            ),
            false,
        ];

        $returned['constructor with optional parameter'] = [
            self::createFixtureForClass(DummyWithOptionalParameterInConstructor::class),
            false,
        ];

        $returned['constructor with required parameter'] = [
            self::createFixtureForClass(DummyWithRequiredParameterInConstructor::class),
            false,
        ];

        $returned['constructor with optional and required parameter'] = [
            self::createFixtureForClass(DummyWithOptionalAndRequiredParameterInConstructor::class),
            false,
        ];

        return $returned;
    }

    /**
     * @param class-string $class
     */
    private static function createFixtureForClass(string $class): Fixture
    {
        return new Fixture($class, 'dummy', [], null);
    }
}
