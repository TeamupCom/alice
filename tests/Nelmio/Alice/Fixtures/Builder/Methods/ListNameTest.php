<?php

/*
 * This file is part of the Alice package.
 *
 * (c) Nelmio <hello@nelm.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Nelmio\Alice\Fixtures\Builder\Methods;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

#[CoversClass(ListName::class)]
class ListNameTest extends MethodTestCase
{
    public function setUp(): void
    {
        $this->method = new ListName();
    }

    #[DataProvider('provideSimpleFixtures')]
    public function testCanBuildSimpleFixtures(string $name, array $cases): void
    {
        $this->assertCannotBuild($name);
    }

    #[DataProvider('provideListFixtures')]
    public function testCanBuildListFixtures(string $name, array $cases): void
    {
        $this->assertCanBuild($name);
    }

    #[DataProvider('provideMalformedListFixtures')]
    #[Group('legacy')]
    public function testCanBuildMalformedListFixtures(string $name, array $cases): void
    {
        $this->assertCanBuild($name);
    }

    #[DataProvider('provideSegmentFixtures')]
    public function testCanBuildSegmentFixtures(string $name, array $cases): void
    {
        $this->assertCannotBuild($name);
    }

    #[DataProvider('provideDeprecatedSegmentFixtures')]
    #[Group('legacy')]
    public function testCanBuildDeprecatedSegmentFixtures(string $name, ?array $cases): void
    {
        $this->assertCannotBuild($name);
    }

    #[DataProvider('provideMalformedSegmentFixtures')]
    #[Group('legacy')]
    public function testCanBuildMalformedSegmentFixtures(string $name, ?array $cases): void
    {
        $this->assertCannotBuild($name);
    }

    #[DataProvider('provideSimpleFixtures')]
    public function testBuildSimpleFixtures($name, $expected): void
    {
        $this->markAsInvalidCase();
    }

    #[DataProvider('provideListFixtures')]
    public function testBuildListFixtures($name, $expected): void
    {
        $this->assertBuiltResultIsTheSame($name, $expected);
    }

    #[DataProvider('provideMalformedListFixtures')]
    #[Group('legacy')]
    public function testBuildMalformedListFixtures($name, $expected): void
    {
        $this->assertBuiltResultIsTheSame($name, $expected);
    }

    #[DataProvider('provideSegmentFixtures')]
    public function testBuildSegmentFixtures($name, $expected): void
    {
        $this->markAsInvalidCase();
    }

    #[DataProvider('provideDeprecatedSegmentFixtures')]
    #[Group('legacy')]
    public function testBuildDeprecatedSegmentFixtures($name, $expected): void
    {
        $this->markAsInvalidCase();
    }

    #[DataProvider('provideMalformedSegmentFixtures')]
    #[Group('legacy')]
    public function testBuildMalformedSegmentFixtures($name, $expected): void
    {
        $this->markAsInvalidCase();
    }
}
