<?php

/*
 * This file is part of the Alice package.
 *
 * (c) Nelmio <hello@nelm.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Nelmio\Alice\Instances\Instantiator;

use Nelmio\Alice\Fixtures\Fixture;
use Nelmio\Alice\support\extensions\CustomInstantiator;
use PHPUnit\Framework\TestCase;

class InstantiatorTest extends TestCase
{
    const USER = 'Nelmio\Alice\support\models\User';

    /**
     * @var Instantiator
     */
    protected $instantiator;

    protected function createInstantiator(array $options = [])
    {
        $defaults = [
            'methods' => []
        ];
        $options = array_merge($defaults, $options);

        return $this->instantiator = new Instantiator($options['methods']);
    }

    public function testAddInstantiator(): void
    {
        $class = self::USER;
        $fixture = new Fixture($class, 'referenced', [], null);

        $this->createInstantiator();
        $this->instantiator->addInstantiator(new CustomInstantiator);
        $object = $this->instantiator->instantiate($fixture);
        $this->assertInstanceOf($class, $object);
        $this->assertNotNull($object->uuid);
    }

    public function testOnlyMethodInterfacesCanBeUsedToInstantiateTheInstantiator(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("All methods passed into Instantiator must implement MethodInterface.");
        $instantiator = new Instantiator(['CustomInstantiator']);
    }
}
