<?php

/*
 * This file is part of the Alice package.
 *
 * (c) Nelmio <hello@nelm.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Nelmio\Alice\Fixtures\Parser\Methods;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Nelmio\Alice\Fixtures\Loader;
use Nelmio\Alice\Fixtures\Parser\Methods\Yaml as YamlParser;
use PHPUnit\Framework\TestCase;
use Nelmio\Alice\Fixtures\ParameterBag;

class YamlTest extends TestCase
{
    private static $dir;

    private YamlParser $parser;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        self::$dir = __DIR__.'/../Files/Yaml';
    }

    public static function tearDownAfterClass(): void
    {
        self::$dir = null;

        parent::tearDownAfterClass();
    }


    public function setUp(): void
    {
        $this->parser = new YamlParser();
    }

    public function testIsAParserMethod(): void
    {
        $this->assertTrue(
            is_a(
                'Nelmio\Alice\Fixtures\Parser\Methods\Yaml',
                'Nelmio\Alice\Fixtures\Parser\Methods\MethodInterface',
                true
            )
        );
    }

    #[DataProvider('provideFiles')]
    public function testCanParseYamlFiles(string $file, bool $expected): void
    {
        $actual = $this->parser->canParse($file);

        $this->assertEquals($expected, $actual);
    }

    public function testParseReturnsAYamlArray(): void
    {
        $data = $this->parser->parse(self::$dir.'/regular_file.yml');

        $this->assertSame(
            [
                'username' => '<username()>',
            ],
            $data
        );
    }

    public function testParseReturnsInterpretedConstants(): void
    {
        if (!defined('Symfony\\Component\\Yaml\\Yaml::PARSE_CONSTANT')) {
            $this->markTestSkipped('This test needs symfony/yaml v3.2 or higher.');
        }
        $fixture = self::$dir.'/file_with_constants.yml';
        if (method_exists(\Symfony\Component\Yaml\Yaml::class, 'parseFile')) {
            $fixture = self::$dir.'/file_with_constants_sf34.yml';
        }
        $data = $this->parser->parse($fixture);

        $this->assertSame(
            [
                'max_int' => PHP_INT_MAX,
            ],
            $data
        );
    }

    #[Group('legacy')]
    public function testCanParseAContextToParsedFiles(): void
    {
        $parser = new YamlParser(['value' => 'test']);
        $data = $parser->parse(self::$dir.'/contextual_file.yml.php');

        $this->assertSame(
            [
                'contextual' => 'test',
                'username' => '<username()>',
            ],
            $data
        );
    }

    public function testIncludeFiles(): void
    {
        $data = $this->parser->parse(self::$dir.'/include/main.yml');

        $this->assertSame(
            [
                'Nelmio\Alice\Entity\Product' => [
                    'product_base (template)' => [
                        'status' => 'in_stock',
                    ],
                    'product1 (extends product_base)' => [
                        'amount' => 45,
                    ]
                ],
                'Nelmio\Alice\Entity\Shop' => [
                    'shop' => [
                        'status' => 'none',
                    ],
                ],
            ],
            $data
        );
    }

    public function testIncludedFilesAreParsedBeforeParsedFile(): void
    {
        $data = $this->parser->parse(self::$dir.'/include_order/main.yml');

        $this->assertSame(
            [
                'Bar' => [
                    'bar' => [
                        'id' => 100,
                        'text' => '<word()>',
                    ],
                ],
                'Foo' => [
                    'foo' => [
                        'id' => 200,
                        'text' => '<word()>',
                    ],
                ],
                'Main' => [
                    'main' => [
                        'id' => 300,
                        'text' => '<word()>',
                    ],
                ],
            ],
            $data
        );
    }

    public function testLastFixtureDeclaredIsKept(): void
    {
        $data = $this->parser->parse(self::$dir.'/include_overlap/main.yml');

        $this->assertSame(
            [
                'Nelmio\Alice\Entity\Product' => [
                    'product0' => [
                        'value' => 'second',
                    ],
                ],
            ],
            $data
        );
    }

    public function testDontReturnParametersWhenNoParameterIsDeclared(): void
    {
        $data = $this->parser->parse(self::$dir.'/regular_file.yml');

        $this->assertArrayNotHasKey('parameters', $data);
    }

    public function testLoadParameters(): void
    {
        $parameterBagProphecy = $this->prophesize('Nelmio\Alice\Fixtures\ParameterBag');
        $parameterBagProphecy->set('foo', 'bar')->shouldBeCalled();

        $loaderProphecy = $this->prophesize('Nelmio\Alice\Fixtures\Loader');
        $loaderProphecy->getFakerProcessorMethod()->shouldBeCalled();
        $loaderProphecy->getParameterBag()->willReturn($parameterBagProphecy->reveal());
        /* @var Loader $loader */
        $loader = $loaderProphecy->reveal();

        $parser = new YamlParser($loader);
        $parser->parse(self::$dir.'/file_with_parameters.yml');

        $loaderProphecy->getParameterBag()->shouldHaveBeenCalledTimes(1);
        $parameterBagProphecy->set(Argument::cetera())->shouldHaveBeenCalledTimes(1);
    }

    public function testLoadParametersOfIncludedFiles(): void
    {
        $parameterBag = $this->createMock(ParameterBag::class);

        $actual = ['foo' => null];
        $parameterBagProphecy
            ->set('foo', 'boo')
            ->will(function($args) use (&$actual) {
                $actual['foo'] = $args[1];
            })
        ;
        $parameterBagProphecy->set('ping', 'pong')->shouldBeCalled();
        $parameterBagProphecy
            ->set('foo', 'bar')
            ->will(function($args) use (&$actual) {
                $actual['foo'] = $args[1];
            })
        ;

        $loaderProphecy = $this->prophesize('Nelmio\Alice\Fixtures\Loader');
        $loaderProphecy->getFakerProcessorMethod()->shouldBeCalled();
        $loaderProphecy->getParameterBag()->willReturn($parameterBagProphecy->reveal());
        /* @var Loader $loader */
        $loader = $loaderProphecy->reveal();

        $parser = new YamlParser($loader);
        $parser->parse(self::$dir.'/include_parameters/main1.yml');

        $this->assertEquals('bar', $actual['foo']);

        $loaderProphecy->getParameterBag()->shouldHaveBeenCalledTimes(2);
        $parameterBagProphecy->set(Argument::cetera())->shouldHaveBeenCalledTimes(3);
    }

    public static function provideFiles(): array
    {
        return [
            'YAML file' => [
                'test.yml',
                true,
            ],
            'YAML with another extension' => [
                'test.yaml',
                true,
            ],
            'relative YAML file' => [
                './../test.yml',
                true,
            ],

            'php file' => [
                'test.php',
                false,
            ],
            'xml file' => [
                'test.xml',
                false,
            ],
        ];
    }
}
