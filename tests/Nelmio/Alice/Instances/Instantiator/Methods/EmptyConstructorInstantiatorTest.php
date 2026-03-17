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

use Nelmio\Alice\Fixtures\Fixture;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Nelmio\Alice\Instances\Instantiator\Methods\EmptyConstructor
 */
class EmptyConstructorInstantiatorTest extends TestCase
{
    /**
     * @var EmptyConstructor
     */
    private $instantiator;

    public function setUp(): void
    {
        $this->instantiator = new EmptyConstructor();
    }

    public function testIsAnInstantiatorMethod(): void
    {
        $this->assertTrue(
            is_a(
                'Nelmio\Alice\Instances\Instantiator\Methods\EmptyConstructor',
                'Nelmio\Alice\Instances\Instantiator\Methods\MethodInterface',
                true
            )
        );
    }

    #[DataProvider('provideFixtures')]
    public function testCanInstantiateObjectWithDefaultConstructor(Fixture $fixture, bool $expected): void
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

        $returned['default constructor'] = [
            self::createFixtureForClass(DummyWithDefaultConstructor::class),
            true,
        ];

        $returned['explicit default constructor'] = [
            self::createFixtureForClass(DummyWithExplicitDefaultConstructor::class),
            true,
        ];

        $returned['constructor with optional parameter'] = [
            self::createFixtureForClass(DummyWithOptionalParameterInConstructor::class),
            true,
        ];


        $returned['private constructor'] = [
            self::createFixtureForClass(DummyWithPrivateConstructor::class),
            false,
        ];

        $returned['protected constructor'] = [
            self::createFixtureForClass(DummyWithProtectedConstructor::class),
            false,
        ];

        $returned['named constructor'] = [
            new Fixture(
                DummyWithNamedConstructor::class,
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

        $returned['constructor with required parameter'] = [
            self::createFixtureForClass('Nelmio\Alice\Instances\Instantiator\DummyClasses\DummyWithRequiredParameterInConstructor'),
            false,
        ];

        $returned['constructor with optional and required parameter'] = [
            self::createFixtureForClass('Nelmio\Alice\Instances\Instantiator\DummyClasses\DummyWithOptionalAndRequiredParameterInConstructor'),
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
