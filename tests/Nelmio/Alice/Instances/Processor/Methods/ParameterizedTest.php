<?php

/*
 * This file is part of the Alice package.
 *
 * (c) Nelmio <hello@nelm.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Nelmio\Alice\Instances\Processor\Methods;

use PHPUnit\Framework\Attributes\CoversClass;
use Nelmio\Alice\Fixtures\ParameterBag;
use Nelmio\Alice\Instances\Processor\Processable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(Parameterized::class)]
class ParameterizedTest extends TestCase
{
    /**
     * @var Parameterized
     */
    private $method;

    public function setUp(): void
    {
        $this->method = new Parameterized(new ParameterBag());
    }

    public function testIsAProcessorMethod(): void
    {
        $this->assertInstanceOf('Nelmio\Alice\Instances\Processor\Methods\MethodInterface', $this->method);
    }

    #[DataProvider('provideProcessables')]
    public function testCanProcess($processable, $expected): void
    {
        $actual = $this->method->canProcess($processable);

        $this->assertEquals($expected, $actual);
    }

    public function testProcessSimpleParameter(): void
    {
        $parameters = new ParameterBag([
            'foo' => 'bar',
        ]);
        $method = new Parameterized($parameters);

        $processable = new Processable('<{foo}>');
        $expected = 'bar';

        $method->canProcess($processable);
        $actual = $method->process($processable, []);

        $this->assertEquals($expected, $actual);
    }

    public function testThrowExceptionIfNoParameterKeyFound(): void
    {
        $this->expectException(\UnexpectedValueException::class);
        $parameters = new ParameterBag([]);
        $method = new Parameterized($parameters);

        $processable = new Processable('<{}>');
        $method->canProcess($processable);
        $method->process($processable, []);
    }

    public function testThrowExceptionIfParameterNotFound(): void
    {
        $this->expectException(\UnexpectedValueException::class);
        $parameters = new ParameterBag([]);
        $method = new Parameterized($parameters);

        $processable = new Processable('<{foo}>');
        $method->canProcess($processable);
        $method->process($processable, []);
    }

    public static function provideProcessables()
    {
        return [
            'regular' => [
                new Processable('<{foo}>'),
                true,
            ],
            'empty' => [
                new Processable('<{}>'),
                true,
            ],
            'composite' => [
                new Processable('<{<{part1}> <{part2}>}>'),
                true,
            ],
            'successive' => [
                new Processable('<{foo}> <{bar}>'),
                true,
            ],
            'dynamic' => [
                new Processable('<{username_<current()>}>'),
                true,
            ],

            'regular string' => [
                new Processable('hello!'),
                false,
            ],
            'string with function' => [
                new Processable('<current()>'),
                false,
            ],
        ];
    }
}
