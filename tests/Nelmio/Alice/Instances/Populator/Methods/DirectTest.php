<?php

/*
 * This file is part of the Alice package.
 *  
 * (c) Nelmio <hello@nelm.io>
 *  
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Nelmio\Alice\Instances\Populator\Methods;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Nelmio\Alice\Fixtures\Fixture;
use Nelmio\Alice\Instances\Populator\Fixtures\Direct\CompositeCamelCaseDummy;
use Nelmio\Alice\Instances\Populator\Fixtures\Direct\CompositeMixedCaseDummy;
use Nelmio\Alice\Instances\Populator\Fixtures\Direct\CompositeSnakeCase1Dummy;
use Nelmio\Alice\Instances\Populator\Fixtures\Direct\CompositeSnakeCase2Dummy;
use Nelmio\Alice\Instances\Populator\Fixtures\Direct\CompositeSnakeCase3Dummy;
use Nelmio\Alice\Instances\Populator\Fixtures\Direct\PrivateDummy;
use Nelmio\Alice\Instances\Populator\Fixtures\Direct\ProtectedDummy;
use Nelmio\Alice\Instances\Populator\Fixtures\Direct\PublicDummy;
use Nelmio\Alice\Instances\Populator\Fixtures\Direct\SimpleCamelCaseDummy;
use Nelmio\Alice\Instances\Populator\Fixtures\Direct\SimpleSnakeCaseDummy;
use Nelmio\Alice\Util\TypeHintChecker;
use PHPUnit\Framework\TestCase;

#[CoversClass(Direct::class)]
class DirectTest extends TestCase
{
    /**
     * @var Fixture
     */
    private $fixture;

    /**
     * @var Direct
     */
    private $direct;

    protected function setUp(): void
    {
        $this->fixture = $this->createMock(Fixture::class);
        $this->fixture->expects($this->never())->method('isLocal');

        $typeHintChecker = $this->createStub(TypeHintChecker::class);
        $typeHintChecker->method('check')->willReturnArgument(2);

        $this->direct = new Direct($typeHintChecker);
    }

    #[DataProvider('provideProperties')]
    #[Group('legacy')]
    public function testCanSet($property, $model, $expected): void
    {
        $actual = $this->direct->canSet($this->fixture, $model, $property, null);

        $this->assertSame($expected, $actual);
    }

    public function testSetPropertyViaSetter(): void
    {
        $property = 'name';
        $name = 'John Doe';

        $typeHintChecker = $this->createMock(TypeHintChecker::class);
        $typeHintChecker->expects($this->once())->method('check')->willReturnArgument(2);

        $direct = new Direct($typeHintChecker);

        $model = $this->createMock(PublicDummy::class);
        $model->expects($this->once())->method('setName')->with($name);

        $direct->set($this->fixture, $model, $property, $name);
    }

    #[DataProvider('provideModelsToSet')]
    public function testSetProperty($model, $property, $value, $expectedProperty): void
    {
        $this->direct->set($this->fixture, $model, $property, $value);

        $this->assertEquals($value, $model->$expectedProperty);
    }

    #[DataProvider('provideLegacyModelsToSet')]
    public function testLegacySetProperty($model, $property, $value, $expectedProperty): void
    {
        $this->direct->set($this->fixture, $model, $property, $value);

        $this->assertEquals($value, $model->$expectedProperty);
    }

    #[Group('legacy')]
    public function testSetPropertyViaPrivateSetter(): void
    {
        $this->direct->set($this->fixture, $model = new PrivateDummy(), 'name', $value = 'John Doe');

        $this->assertEquals($value, $model->name);
    }

    #[Group('legacy')]
    public function testSetPropertyViaProtectedSetter(): void
    {
        $this->direct->set($this->fixture, $model = new ProtectedDummy(), 'name', $value = 'John Doe');

        $this->assertEquals($value, $model->name);
    }

    public static function provideProperties()
    {
        return [
            'simple property with camelCase setter' => [
                'name',
                new SimpleCamelCaseDummy(),
                true,
            ],
            'simple property with snake_case setter' => [
                'name',
                new SimpleSnakeCaseDummy(),
                true,
            ],

            'composite camelCase property with camelCase setter' => [
                'fullName',
                new CompositeCamelCaseDummy(),
                true,
            ],
            'composite snake_case property with camelCase setter' => [
                'full_name',
                new CompositeCamelCaseDummy(),
                true,
            ],

            'composite camelCase property with snake_case1 setter' => [
                'fullName',
                new CompositeSnakeCase1Dummy(),
                false,
            ],
            'composite snake_case property with snake_case1 setter' => [
                'full_name',
                new CompositeSnakeCase1Dummy(),
                true,
            ],

            'composite camelCase property with snake_case2 setter' => [
                'fullName',
                new CompositeSnakeCase2Dummy(),
                false,
            ],
            'composite snake_case property with snake_case2 setter' => [
                'full_name',
                new CompositeSnakeCase2Dummy(),
                true,
            ],

            'composite camelCase property with snake_case3 setter' => [
                'fullName',
                new CompositeSnakeCase3Dummy(),
                true,
            ],
            'composite snake_case property with snake_case3 setter' => [
                'full_name',
                new CompositeSnakeCase3Dummy(),
                true,
            ],

            'composite camelCase property with mixed setter (BC preserved)' => [
                'fullName',
                new CompositeMixedCaseDummy(),
                true,
            ],
            'composite snake_case property with mixed setter (BC preserved)' => [
                'full_name',
                new CompositeMixedCaseDummy(),
                true,
            ],

            'no setter' => [
                'propertyWithoutAccessor',
                new SimpleCamelCaseDummy(),
                false,
            ],
        ];
    }

    public static function provideModelsToSet()
    {
        $value = 'John Doe';

        return [
            'simple property with camelCase setter' => [
                new SimpleCamelCaseDummy(),
                'name',
                $value,
                'name',
            ],

            'composite camelCase property with camelCase setter' => [
                new CompositeCamelCaseDummy(),
                'fullName',
                $value,
                'fullName',
            ],
            'composite snake_case property with camelCase setter' => [
                new CompositeCamelCaseDummy(),
                'full_name',
                $value,
                'fullName',
            ],

            'composite camelCase property with mixed setter (BC preserved)' => [
                new CompositeMixedCaseDummy(),
                'fullName',
                $value,
                'fullname',
            ],
            'composite snake_case property with mixed setter (BC preserved)' => [
                new CompositeMixedCaseDummy(),
                'full_name',
                $value,
                'fullname',
            ],

            'public setter' => [
                new PublicDummy(),
                'name',
                $value,
                'name',
            ],
        ];
    }

    public static function provideLegacyModelsToSet()
    {
        $value = 'John Doe';

        return [
            'simple property with snake_case setter' => [
                new SimpleSnakeCaseDummy(),
                'name',
                $value,
                'name',
            ],

            'composite snake_case property with snake_case1 setter' => [
                new CompositeSnakeCase1Dummy(),
                'full_name',
                $value,
                'full_name',
            ],

            'composite snake_case property with snake_case2 setter' => [
                new CompositeSnakeCase2Dummy(),
                'full_name',
                $value,
                'full_name',
            ],

            'composite camelCase property with snake_case3 setter' => [
                new CompositeSnakeCase3Dummy(),
                'fullName',
                $value,
                'full_name',
            ],
            'composite snake_case property with snake_case3 setter' => [
                new CompositeSnakeCase3Dummy(),
                'full_name',
                $value,
                'full_name',
            ],

            'composite camelCase property with mixed setter (BC preserved)' => [
                new CompositeMixedCaseDummy(),
                'fullName',
                $value,
                'fullname',
            ],
            'composite snake_case property with mixed setter (BC preserved)' => [
                new CompositeMixedCaseDummy(),
                'full_name',
                $value,
                'fullname',
            ],

            'public setter' => [
                new PublicDummy(),
                'name',
                $value,
                'name',
            ],
        ];
    }
}
