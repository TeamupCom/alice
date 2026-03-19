<?php

/*
 * This file is part of the Alice package.
 *
 * (c) Nelmio <hello@nelm.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Nelmio\Alice\Fixtures;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

class FixtureTest extends TestCase
{
    const USER = 'Nelmio\Alice\support\models\User';
    const STATIC_USER = 'Nelmio\Alice\support\models\StaticUser';
    const GROUP = 'Nelmio\Alice\support\models\UserGroup';
    const CONTACT = 'Nelmio\Alice\support\models\Contact';

    public function testWillParseFlagsOutOfTheClass(): void
    {
        $fixture = new Fixture(self::USER.' (local)', 'user', [], null);

        $this->assertEquals(self::USER, $fixture->getClass());
    }

    public function testWillParseFlagsOutOfTheName(): void
    {
        $fixture = new Fixture(self::USER, 'user (local)', [], null);

        $this->assertEquals('user', $fixture->getName());
    }

    #[Group('legacy')]
    public function testIsLocalWithLocalClassFlag(): void
    {
        $fixture = new Fixture(self::USER.' (local)', 'user', [], null);

        $this->assertTrue($fixture->isLocal());
    }

    #[Group('legacy')]
    public function testIsLocalWithLocalNameFlag(): void
    {
        $fixture = new Fixture(self::USER, 'user (local)', [], null);

        $this->assertTrue($fixture->isLocal());
    }

    public function testIsNotLocalWithNeitherClassNorNameFlag(): void
    {
        $fixture = new Fixture(self::USER, 'user', [], null);

        $this->assertFalse($fixture->isLocal());
    }

    public function testIsTemplateWithTemplateNameFlag(): void
    {
        $fixture = new Fixture(self::USER, 'user (template)', [], null);

        $this->assertTrue($fixture->isTemplate());
    }

    public function testIsNotTemplateWithoutTemplateNameFlag(): void
    {
        $fixture = new Fixture(self::USER, 'user', [], null);

        $this->assertFalse($fixture->isTemplate());
    }

    public function testIsNotTemplateWithExtendsNameFlag($value = ''): void
    {
        $fixture = new Fixture(self::USER, 'user (extends user_template)', [], null);

        $this->assertFalse($fixture->isTemplate());
    }

    public function testExtendTemplateRequiresThatTheArgumentIsATemplate(): void
    {
        $this->expectExceptionMessage("Argument must be a template, not just a fixture.");
        $this->expectException(\InvalidArgumentException::class);
        $fixture1 = new Fixture(self::USER, 'user1', [], null);
        $fixture2 = new Fixture(self::USER, 'user2', [], null);
        $fixture1->extendTemplate($fixture2);
    }

    public function testExtendTemplateWillMapUnsetPropertiesOnTheFixture(): void
    {
        $template = new Fixture(self::USER, 'user_full (template)', ['name' => 'John Doe', 'email' => 'john@doe.org'], null);
        $fixture = new Fixture(self::USER, 'user', ['email' => 'jane@doe.org'], null);

        $fixture->extendTemplate($template);
        $properties = $fixture->getProperties();
        $this->assertEquals('John Doe', $properties['name']->getValue());
    }

    public function testExtendTemplateWillNotMapSetPropertiesOnTheFixture(): void
    {
        $template = new Fixture(self::USER, 'user_full (template)', ['name' => 'John Doe', 'email' => 'john@doe.org'], null);
        $fixture = new Fixture(self::USER, 'user', ['email' => 'jane@doe.org'], null);

        $fixture->extendTemplate($template);
        $properties = $fixture->getProperties();
        $this->assertEquals('jane@doe.org', $properties['email']->getValue());
    }

    public function testGetExtensionsReturnsAListOfAllTemplateNamesTheFixtureExtends(): void
    {
        $fixture = new Fixture(self::USER, 'user (extends user_name, extends user_email)', [], null);

        $this->assertEquals(['user_name', 'user_email'], $fixture->getExtensions());
    }

    public function testHasExtensionsIsFalseWhenNoExtensionsExist(): void
    {
        $fixture = new Fixture(self::USER, 'user', [], null);

        $this->assertFalse($fixture->hasExtensions());
    }

    public function testHasExtensionsIsTrueWhenExtensionsExist(): void
    {
        $fixture = new Fixture(self::USER, 'user (extends user_name, extends user_email)', [], null);

        $this->assertTrue($fixture->hasExtensions());
    }

    public function testGetPropertiesWillReturnOnlyBasicValueProperties(): void
    {
        $fixture = new Fixture(self::USER, 'user', ['name' => 'John Doe', 'email' => 'john@doe.org', '__construct' => ['1', '2'], '__set' => 'setterFunc'], null);

        $properties = $fixture->getProperties();
        $this->assertEquals(['name' => $properties['name'], 'email' => $properties['email']], $fixture->getProperties());
    }

    public function testHasClassFlagWillReturnIfClassFLagExists(): void
    {
        $fixture = new Fixture(self::USER.' (local)', 'user', [], null);

        $this->assertTrue($fixture->hasClassFlag('local'));
        $this->assertFalse($fixture->hasClassFlag('badname'));
    }

    public function testHasNameFlagWillReturnIfNameFLagExists(): void
    {
        $fixture = new Fixture(self::USER, 'user (local)', [], null);

        $this->assertTrue($fixture->hasNameFlag('local'));
        $this->assertFalse($fixture->hasNameFlag('badname'));
    }

    public function testGetConstructorMethodWillReturnTheMethodName(): void
    {
        $fixture = new Fixture(self::STATIC_USER, 'user', ['__construct' => ['create' => ['alice@example.com']]], null);

        $this->assertEquals('create', $fixture->getConstructorMethod());
    }

    public function testGetConstructorArgsWillReturnTheArgumentsList(): void
    {
        $fixture = new Fixture(self::STATIC_USER, 'user', ['__construct' => ['create' => ['alice@example.com']]], null);

        $this->assertEquals(['alice@example.com'], $fixture->getConstructorArgs());
    }

    public function testShouldUseConstructorWillReturnTrueIfThereIsNoConstructorInTheSpec(): void
    {
        $fixture = new Fixture(self::USER, 'user', [], null);

        $this->assertTrue($fixture->shouldUseConstructor());
    }

    public function testShouldUseConstructorWillReturnFalseIfTheConstructorSpecIsFalse(): void
    {
        $fixture = new Fixture(self::USER, 'user', ['__construct' => false], null);

        $this->assertFalse($fixture->shouldUseConstructor());
    }

    public function testShouldUseConstructorWillReturnTrueIfTheConstructorSpecIsDefined(): void
    {
        $fixture = new Fixture(self::USER, 'user', ['__construct' => ['1', '2']], null);

        $this->assertTrue($fixture->shouldUseConstructor());
    }

    public function testHasCustomerSetterWillReturnIfTheSpecDefinesACustomSetter(): void
    {
        $setFixture = new Fixture(self::USER, 'user', ['__set' => 'setterFunc'], null);
        $noSetFixture = new Fixture(self::USER, 'user', [], null);

        $this->assertTrue($setFixture->hasCustomSetter());
        $this->assertFalse($noSetFixture->hasCustomSetter());
    }

    public function testGetCustomSetterWillReturnTheCustomSetterValue(): void
    {
        $setFixture = new Fixture(self::USER, 'user', ['__set' => 'setterFunc'], null);
        $noSetFixture = new Fixture(self::USER, 'user', [], null);

        $this->assertEquals('setterFunc', $setFixture->getCustomSetter());
        $this->assertNull($noSetFixture->getCustomSetter());
    }

    #[DataProvider('provideValidNames')]
    public function testValidNamesValidation($name): void
    {
        $fixture = new Fixture(self::USER, $name, [], null);
        $this->assertEquals($name, $fixture->getName());
    }

    #[DataProvider('provideInvalidNames')]
    #[Group('legacy')]
    public function testNamesValidation($name): void
    {
        $fixture = new Fixture(self::USER, $name, [], null);
        $this->assertEquals($name, $fixture->getName());
    }

    public static function provideValidNames(): array
    {
        return [
            ['u'],
            ['À'],
            ['u.'],
            ['.u'],
            ['u_'],
            ['_u'],
            ['u/'],
            ['/u'],
            ['u0'],
            ['0u'],
        ];
    }

    public static function provideInvalidNames(): array
    {
        return [
            [''],
            [' '],
            ['.'],
            ['_'],
            ['/.'],
            ['0'],
            ['10'],
            ['user-name'],
            ['user{1..3}'],
            ['user{alice, bob}'],
            ['user*'],
            ['User'],
            ['us er'],
            ['user""'],
            ['user\'\''],
        ];
    }
}
