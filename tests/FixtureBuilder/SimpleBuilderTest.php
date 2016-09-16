<?php

/*
 * This file is part of the Alice package.
 *  
 * (c) Nelmio <hello@nelm.io>
 *  
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Nelmio\Alice\FixtureBuilder;

use Nelmio\Alice\FixtureBag;
use Nelmio\Alice\FixtureBuilder\Denormalizer\FakeDenormalizer;
use Nelmio\Alice\FixtureBuilderInterface;
use Nelmio\Alice\FixtureSet;
use Nelmio\Alice\ObjectBag;
use Nelmio\Alice\ParameterBag;
use Prophecy\Argument;

/**
 * @covers \Nelmio\Alice\FixtureBuilder\SimpleBuilder
 */
class SimpleBuilderTest extends \PHPUnit_Framework_TestCase
{
    public function testIsAFixtureBuilder()
    {
        $this->assertTrue(is_a(SimpleBuilder::class, FixtureBuilderInterface::class, true));
    }

    /**
     * @expectedException \DomainException
     */
    public function testIsNotClonable()
    {
        $builder = new SimpleBuilder(new FakeDenormalizer());
        clone $builder;
    }

    public function testBuildSet()
    {
        $data = [
            'dummy' => new \stdClass(),
        ];
        $injectedParameters = ['foo' => 'bar'];
        $injectedObjects = [
            'another_dummy' => new \stdClass(),
        ];
        $loadedParameters = new ParameterBag(['rab' => 'oof']);
        $loadedFixtures = new FixtureBag();
        $set = new BareFixtureSet($loadedParameters, $loadedFixtures);

        $expected = new FixtureSet($loadedParameters, new ParameterBag($injectedParameters), $loadedFixtures, new ObjectBag($injectedObjects));

        $denormalizerProphecy = $this->prophesize(DenormalizerInterface::class);
        $denormalizerProphecy->denormalize($data)->willReturn($set);
        /** @var DenormalizerInterface $denormalizer */
        $denormalizer = $denormalizerProphecy->reveal();

        $builder = new SimpleBuilder($denormalizer);
        $actual = $builder->build($data, $injectedParameters, $injectedObjects);

        $this->assertEquals($expected, $actual);

        $denormalizerProphecy->denormalize(Argument::any())->shouldHaveBeenCalledTimes(1);
    }

    public function testBuildSetWithoutInjectingParametersOrObjects()
    {
        $data = ['dummy' => new \stdClass()];
        $loadedParameters = new ParameterBag(['rab' => 'oof']);
        $loadedFixtures = new FixtureBag();
        $set = new BareFixtureSet($loadedParameters, $loadedFixtures);

        $expected = new FixtureSet($loadedParameters, new ParameterBag(), $loadedFixtures, new ObjectBag());

        $denormalizerProphecy = $this->prophesize(DenormalizerInterface::class);
        $denormalizerProphecy->denormalize($data)->willReturn($set);
        /** @var DenormalizerInterface $denormalizer */
        $denormalizer = $denormalizerProphecy->reveal();

        $builder = new SimpleBuilder($denormalizer);
        $actual = $builder->build($data);

        $this->assertEquals($expected, $actual);
    }
}
