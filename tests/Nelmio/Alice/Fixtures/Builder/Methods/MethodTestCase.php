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

use Nelmio\Alice\Fixtures\Builder\BuilderProviderTrait;
use PHPUnit\Framework\TestCase;

abstract class MethodTestCase extends TestCase
{
    use BuilderProviderTrait;

    /**
     * @var MethodInterface
     */
    protected $method;

    public function testIsABuilderMethod(): void
    {
        $this->assertInstanceOf(MethodInterface::class, $this->method);
    }

    abstract public function testCanBuildSimpleFixtures(string $name, array $cases);

    abstract public function testCanBuildListFixtures(string $name, array $cases);

    abstract public function testCanBuildMalformedListFixtures(string $name, array $cases);

    abstract public function testCanBuildSegmentFixtures(string $name, array $cases);

    abstract public function testCanBuildDeprecatedSegmentFixtures(string $name, ?array $cases);

    abstract public function testCanBuildMalformedSegmentFixtures(string $name, ?array $cases);

    abstract public function testBuildSimpleFixtures($name, $expected);

    abstract public function testBuildListFixtures($name, $expected);

    abstract public function testBuildMalformedListFixtures($name, $expected);

    abstract public function testBuildSegmentFixtures($name, $expected);

    abstract public function testBuildDeprecatedSegmentFixtures($name, $expected);

    abstract public function testBuildMalformedSegmentFixtures($name, $expected);

    /**
     * @param string $name Reference name
     */
    public function assertCanBuild(string $name): void
    {
        $actual = $this->method->canBuild($name);

        $this->assertTrue($actual);
    }

    /**
     * @param string $name Reference name
     */
    public function assertCannotBuild(string $name): void
    {
        $actual = $this->method->canBuild($name);

        $this->assertFalse($actual);
    }

    public function assertBuiltResultIsTheSame(string $name, ?array $expected): void
    {
        $this->assertTrue($this->method->canBuild($name));
        $actual = $this->method->build('Dummy', $name, []);

        if (is_array($expected)) {
            $this->assertIsArray($actual);
            $this->assertCount(count($expected), $actual);
        } else {
            $this->assertNull($actual);
        }
        $this->assertEquals($expected, $actual);
    }

    public function markAsInvalidCase(): void
    {
        $this->assertTrue(true, 'Invalid scenario.');
    }
}
