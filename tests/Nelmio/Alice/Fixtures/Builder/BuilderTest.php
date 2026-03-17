<?php

/*
 * This file is part of the Alice package.
 *
 * (c) Nelmio <hello@nelm.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Nelmio\Alice\Fixtures\Builder;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Nelmio\Alice\Fixtures\Builder\Methods\MethodInterface;
use Nelmio\Alice\Fixtures\Fixture;
use Nelmio\Alice\Fixtures\Loader;
use PHPUnit\Framework\TestCase;
use Nelmio\Alice\support\models\User;

#[CoversClass(Builder::class)]
class BuilderTest extends TestCase
{
    use BuilderProviderTrait;

    private const USER = User::class;

    private Builder $builder;

    public function setUp(): void
    {
        $loader = new Loader();

        $loaderReflection = new \ReflectionObject($loader);
        $builderReflection = $loaderReflection->getProperty('builder');

        $this->builder = $builderReflection->getValue($loader);
    }

    public function testCanCreateBuilder(): void
    {
        new Builder([]);

        $method1Prophecy = $this->prophesize('Nelmio\Alice\Fixtures\Builder\Methods\MethodInterface');
        $method1Prophecy->canBuild(Argument::any())->shouldNotBeCalled();
        /** @var MethodInterface $method1 */
        $method1 = $method1Prophecy->reveal();

        $method2Prophecy = $this->prophesize('Nelmio\Alice\Fixtures\Builder\Methods\MethodInterface');
        $method2Prophecy->canBuild(Argument::any())->shouldNotBeCalled();
        /** @var MethodInterface $method2 */
        $method2 = $method2Prophecy->reveal();

        new Builder([$method1, $method2]);
    }

    public function testThrowExeptionIfMethodsAreNotMethods(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Builder([new \stdClass()]);
    }

    public function testAddBuilder(): void
    {
        $builder = new Builder([]);;
        $builder->addBuilder(new CustomMethod);

        $fixtures = $builder->build(self::USER, 'spec dumped', ['thisShould' => 'be gone']);
        $this->assertEmpty($fixtures[0]->getProperties());
    }

    #[DataProvider('provideSimpleFixtures')]
    public function testBuildSimpleFixtures($name, $expected): void
    {
        $actual = $this->builder->build('Dummy', $name, []);

        if (is_array($expected)) {
            $this->assertIsArray($actual);
            $this->assertCount(count($expected), $actual);
        } else {
            $this->assertNull($actual);
        }
        $this->assertEquals($expected, $actual);
    }

    #[DataProvider('provideListFixtures')]
    public function testBuildListFixtures($name, $expected): void
    {
        $actual = $this->builder->build('Dummy', $name, []);

        if (is_array($expected)) {
            $this->assertIsArray($actual);
            $this->assertCount(count($expected), $actual);
        } else {
            $this->assertNull($actual);
        }
        $this->assertEquals($expected, $actual);
    }

    #[Group('legacy')]
    #[DataProvider('provideMalformedListFixtures')]
    public function testBuildMalformedListFixtures($name, $expected): void
    {
        $actual = $this->builder->build('Dummy', $name, []);

        if (is_array($expected)) {
            $this->assertIsArray($actual);
            $this->assertCount(count($expected), $actual);
        } else {
            $this->assertNull($actual);
        }
        $this->assertEquals($expected, $actual);
    }

    #[DataProvider('provideSegmentFixtures')]
    public function testBuildSegmentFixtures($name, $expected): void
    {
        $actual = $this->builder->build('Dummy', $name, []);

        if (is_array($expected)) {
            $this->assertIsArray($actual);
            $this->assertCount(count($expected), $actual);
        } else {
            $this->assertNull($actual);
        }
        $this->assertEquals($expected, $actual);
    }

    #[DataProvider('provideDeprecatedSegmentFixtures')]
    #[Group('legacy')]
    public function testBuildDeprecatedSegmentFixtures($name, $expected): void
    {
        $actual = $this->builder->build('Dummy', $name, []);

        if (is_array($expected)) {
            $this->assertIsArray($actual);
            $this->assertCount(count($expected), $actual);
        } else {
            $this->assertNull($actual);
        }
        $this->assertEquals($expected, $actual);
    }

    #[DataProvider('provideMalformedSegmentFixtures')]
    #[Group('legacy')]
    public function testBuildMalformedSegmentFixtures($name, $expected): void
    {
        $actual = $this->builder->build('Dummy', $name, []);

        if (is_array($expected)) {
            $this->assertIsArray($actual);
            $this->assertCount(count($expected), $actual);
        } else {
            $this->assertNull($actual);
        }
        $this->assertEquals($expected, $actual);
    }

    #[Group('legacy')]
    public function testReturnsNullWhenCannotBuildAFixture(): void
    {
        $builder = new Builder([]);
        $this->assertNull($builder->build('Dummy', 'dummy', []));
    }
}

class CustomMethod implements MethodInterface
{
    public function canBuild($name): bool
    {
        return $name === 'spec dumped';
    }

    public function build($class, $name, array $spec): array
    {
        return [new Fixture($class, $name, [], null)];
    }
}
