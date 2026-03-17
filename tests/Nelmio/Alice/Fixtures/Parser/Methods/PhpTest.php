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
use Nelmio\Alice\Fixtures\ParameterBag;
use Nelmio\Alice\Fixtures\Parser\Methods\Php as PhpParser;
use PHPUnit\Framework\TestCase;

class PhpTest extends TestCase
{
    private static $dir;

    /**
     * @var PhpParser
     */
    private $parser;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        self::$dir = __DIR__.'/../Files/Php';
    }

    public static function tearDownAfterClass(): void
    {
        self::$dir = null;

        parent::tearDownAfterClass();
    }


    public function setUp(): void
    {
        $this->parser = new PhpParser();
    }

    public function testIsAParserMethod(): void
    {
        $this->assertTrue(
            is_a(
                PhpParser::class,
                MethodInterface::class,
                true
            )
        );
    }

    #[DataProvider('provideFiles')]
    public function testCanParsePhpFiles($file, $expected): void
    {
        $actual = $this->parser->canParse($file);

        $this->assertEquals($expected, $actual);
    }

    public function testParseReturnsAPhpArray(): void
    {
        $data = $this->parser->parse(self::$dir.'/regular_file.php');

        $this->assertSame(
            [
                'username' => '<username()>',
            ],
            $data
        );
    }

    #[Group('legacy')]
    public function testCanParseAContextToParsedFiles(): void
    {
        $parser = new PhpParser(['value' => 'test']);
        $data = $parser->parse(self::$dir.'/contextual_file.php');

        $this->assertSame(
            [
                'contextual' => 'test',
                'username' => '<username()>',
            ],
            $data
        );
    }

    public function testThrowExceptionIfFileDoesntReturnArray(): void
    {
        $file = self::$dir.'/no_return.php';
        try {
            $this->parser->parse($file);
            $this->fail(sprintf('Expected parsing file "%s" to throw an exception', $file));
        } catch (\UnexpectedValueException $exception) {
            $this->assertSame(
                sprintf('Included file "%s" must return an array of data', $file),
                $exception->getMessage()
            );
        }

        $file = self::$dir.'/wrong_return.php';
        try {
            $this->parser->parse($file);

            $this->fail(sprintf('Expected parsing file "%s" to throw an exception', $file));
        } catch (\UnexpectedValueException $exception) {
            $this->assertSame(
                sprintf('Included file "%s" must return an array of data', $file),
                $exception->getMessage()
            );
        }
    }

    public function testIncludeFiles(): void
    {
        $data = $this->parser->parse(self::$dir.'/include/main.php');

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
        $data = $this->parser->parse(self::$dir.'/include_order/main.php');

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
        $data = $this->parser->parse(self::$dir.'/include_overlap/main.php');

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

    public function testLoadParameters(): void
    {
        $parameterBag = $this->createMock(ParameterBag::class);
        $parameterBag->expects($this->once())->method('set')->with('foo', 'bar');

        $loader = $this->createMock(Loader::class);
        $loader->expects($this->once())->method('getFakerProcessorMethod');
        $loader->expects($this->once())->method('getParameterBag')->willReturn($parameterBag);

        $parser = new PhpParser($loader);
        $parser->parse(self::$dir.'/file_with_parameters.php');
    }

    public function testLoadParametersOfIncludedFiles(): void
    {
        $actual = ['foo' => null];
        $callCount = 0;

        $parameterBag = $this->createMock(ParameterBag::class);
        $parameterBag->expects($this->exactly(3))
            ->method('set')
            ->willReturnCallback(function ($key, $value) use (&$actual, &$callCount) {
                $expectedCalls = [['foo', 'boo'], ['ping', 'pong'], ['foo', 'bar']];
                $this->assertEquals($expectedCalls[$callCount][0], $key);
                $this->assertEquals($expectedCalls[$callCount][1], $value);
                $callCount++;

                if ($key === 'foo') {
                    $actual['foo'] = $value;
                }
            });

        $loader = $this->createMock(Loader::class);
        $loader->expects($this->atLeastOnce())->method('getFakerProcessorMethod');
        $loader->expects($this->exactly(2))->method('getParameterBag')->willReturn($parameterBag);

        $parser = new PhpParser($loader);
        $parser->parse(self::$dir.'/include_parameters/main1.php');

        $this->assertEquals('bar', $actual['foo']);
    }

    public static function provideFiles()
    {
        return [
            'php file' => [
                'test.php',
                true,
            ],
            'relative php file' => [
                './../test.php',
                true,
            ],
            'absolute file file' => [
                __FILE__,
                true,
            ],

            'xml file' => [
                'test.xml',
                false,
            ],
            'YAML file' => [
                'test.yml',
                false,
            ],
            'YAML with another extension' => [
                'test.yaml',
                false,
            ],
        ];
    }
}
