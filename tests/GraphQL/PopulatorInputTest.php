<?php

declare(strict_types=1);

namespace Sparklink\GraphQLToolsBundle\Test\GraphQL\Builder;

use PHPUnit\Framework\TestCase;
use Sparklink\GraphQLToolsBundle\Service\TypeEntityResolver;
use Sparklink\GraphQLToolsBundle\Tests\GraphQL\Fixtures\Car;
use Sparklink\GraphQLToolsBundle\Tests\GraphQL\Fixtures\CarInput;
use Sparklink\GraphQLToolsBundle\Tests\GraphQL\Fixtures\Person;
use Sparklink\GraphQLToolsBundle\Tests\GraphQL\Fixtures\PersonInput;
use Sparklink\GraphQLToolsBundle\Utils\Configuration;
use Sparklink\GraphQLToolsBundle\Utils\Populator;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;
use Symfony\Component\TypeInfo\Type;

class PopulatorInputTest extends TestCase
{
    public const MAPPING = [
        'Person' => [
            'class' => 'Sparklink\GraphQLToolsBundle\Tests\GraphQL\Fixtures\Person',
            'type'  => 'type',
        ],
        'Car' => [
            'class' => 'Sparklink\GraphQLToolsBundle\Tests\GraphQL\Fixtures\Car',
            'type'  => 'type',
        ],
        'PersonInput' => [
            'class' => 'Sparklink\GraphQLToolsBundle\Tests\GraphQL\Fixtures\PersonInput',
            'type'  => 'input',
        ],

        'CarInput' => [
            'class' => 'Sparklink\GraphQLToolsBundle\Tests\GraphQL\Fixtures\CarInput',
            'type'  => 'input',
        ],
    ];

    public function testCreate(): void
    {
        /** @var PropertyInfoExtractor $mock */
        $mock = $this->getPropertyInfoExtractorMock();

        $populator = new Populator(new TypeEntityResolver(self::MAPPING), $mock);

        $personInput           = new PersonInput();
        $personInput->fullName = 'John Doe';
        $personInput->age      = '30';

        $person = new Person();
        $populator->populateInput($person, $personInput);

        $this->assertEquals('John Doe', $person->fullName);
        $this->assertEquals('30', $person->age);
    }

    public function testUpdate(): void
    {

        /** @var PropertyInfoExtractor $mock */
        $mock = $this->getPropertyInfoExtractorMock();

        $populator = new Populator(new TypeEntityResolver(self::MAPPING), $mock);

        $person           = new Person();
        $person->fullName = 'John Doe';
        $person->age      = '30';

        $personInput           = new PersonInput();
        $personInput->fullName = 'Jane Doe';
        $personInput->age      = '31';

        $populator->populateInput($person, $personInput);

        $this->assertEquals('Jane Doe', $person->fullName);
        $this->assertEquals('31', $person->age);
    }

    public function testSetSetter(): void
    {
        /** @var PropertyInfoExtractor $mock */
        $mock = $this->getPropertyInfoExtractorMock();

        $populator = new Populator(new TypeEntityResolver(self::MAPPING), $mock);
        $accessor =  PropertyAccess::createPropertyAccessor();

        $person           = new Person();
        $person->fullName = 'John Doe';
        $person->age      = '30';

        $personInput           = new PersonInput();
        $personInput->fullName = 'Jane Doe';
        $personInput->age      = '31';

        $config = new Configuration();
        $config->get('fullName')->setSetter(function($target, $path, $value) use ($accessor) {
            $accessor->setValue($target, $path, $value);

            $firstName = explode(' ', $value)[0];
            $accessor->setValue($target, 'firstName', $firstName);
        });

        $populator->populateInput($person, $personInput, $config);

        $this->assertEquals('Jane Doe', $person->fullName);
        $this->assertEquals('31', $person->age);
        $this->assertEquals('Jane', $person->firstName);
    }

    public function testSetGetter(): void
    {
        $accessor =  PropertyAccess::createPropertyAccessor();

        /** @var PropertyInfoExtractor $mock */
        $mock = $this->getPropertyInfoExtractorMock();
        $method = class_exists(\Symfony\Component\TypeInfo\Type::class) ? 'getType' : 'getTypes';
        $type = $this->createMockType(
            'object',
            false,
            'Doctrine\Common\Collections\Collection',
            true,
            $this->createMockType('object', false, null, true, []),
            $this->createMockType('object', false, "Sparklink\GraphQLToolsBundle\Tests\GraphQL\Fixtures\Car", true, [], [])
        );
        $mock->expects($this->once())
                ->method($method)
                ->willReturn(class_exists(\Symfony\Component\TypeInfo\Type::class) ? $type : [$type]);

        $populator = new Populator(new TypeEntityResolver(self::MAPPING), $mock);

        $car          = new Car();
        $car->id      = 1;
        $car->name    = 'Ford';
        $car->model   = 'Mustang';
        $car->year    = '1967';

        $carTwo         = new Car();
        $carTwo->id     = 2;
        $carTwo->name   = 'Ferrari';
        $carTwo->model  = 'Testarossa';
        $carTwo->year   = '1984';

        $person           = new Person();
        $person->fullName = 'John Doe';
        $person->age      = '30';
        $person->cars[]   = $car;
        $person->cars[]   = $carTwo;

        $carInput           = new CarInput();
        $carInput->name     = 'Ford';
        $carInput->model    = 'Mustang';
        $carInput->year     = '1967';
        $carInput->color    = 'yellow';

        $carTwoInput           = new CarInput();
        $carTwoInput->name     = 'Ferrari';
        $carTwoInput->model    = 'Testarossa';
        $carTwoInput->year     = '1984';
        $carTwoInput->color    = 'red';

        $personInput           = new PersonInput();
        $personInput->fullName = 'John Doe';
        $personInput->age      = '30';

        $personInput->cars[] = $carInput;
        $personInput->cars[] = $carTwoInput;

        $config = new Configuration();
        $config->get('cars')->setGetter(function($target, $path) use ($accessor, $person) {

            $this->assertEquals('cars', $path);
            $this->assertEquals($target, $person);

            return $accessor->getValue($target, $path);
        });

        $populator->populateInput($person, $personInput, $config);
        
        $this->assertEquals('John Doe', $person->fullName);
        $this->assertEquals('30', $person->age);
    }

    public function testIgnoreNulls(): void
    {
        /** @var PropertyInfoExtractor $mock */
        $mock = $this->getPropertyInfoExtractorMock();

        $populator = new Populator(new TypeEntityResolver(self::MAPPING), $mock);

        $person           = new Person();
        $person->fullName = 'John Doe';
        $person->age      = '30';

        $personInput           = new PersonInput();
        $personInput->fullName = 'Jane Doe';
        $personInput->age      = null;

        $config = new Configuration();
        $config->get('age')->setIgnoreNull(true);

        $populator->populateInput($person, $personInput, $config);

        $this->assertEquals('Jane Doe', $person->fullName);
        $this->assertEquals('30', $person->age);
    }

    public function testIgnorePath(): void
    {
        /** @var PropertyInfoExtractor $mock */
        $mock = $this->getPropertyInfoExtractorMock();

        $populator = new Populator(new TypeEntityResolver(self::MAPPING), $mock);

        $person           = new Person();
        $person->fullName = 'John Doe';
        $person->age      = '30';

        $personInput           = new PersonInput();
        $personInput->fullName = 'Jane Doe';
        $personInput->age      = "5";

        $config = new Configuration();
        $config->get('age')->setIgnored(function($target, $path) {
            return $target->age > 10;
        });

        $populator->populateInput($person, $personInput, $config);

        $this->assertEquals('Jane Doe', $person->fullName);
        $this->assertEquals('30', $person->age);
    }

    public function testCreateCollection(): void
    {
        /** @var PropertyInfoExtractor $mock */
        $mock = $this->getPropertyInfoExtractorMock();
        $method = class_exists(\Symfony\Component\TypeInfo\Type::class) ? 'getType' : 'getTypes';
        $type = $this->createMockType(
            'object',
            false,
            'Doctrine\Common\Collections\Collection',
            true,
            $this->createMockType('object', false, null, true, []),
            $this->createMockType('object', false, "Sparklink\GraphQLToolsBundle\Tests\GraphQL\Fixtures\Car", true, [], [])
        );

        $mock->expects($this->once())
                ->method($method)
                ->willReturn(class_exists(\Symfony\Component\TypeInfo\Type::class) ? $type : [$type]);

        $populator = new Populator(new TypeEntityResolver(self::MAPPING), $mock);

        $carInput           = new CarInput();
        $carInput->name     = 'Ford';
        $carInput->model    = 'Mustang';
        $carInput->year     = '1967';
        $carInput->color    = 'yellow';

        $carTwoInput           = new CarInput();
        $carTwoInput->name     = 'Ferrari';
        $carTwoInput->model    = 'Testarossa';
        $carTwoInput->year     = '1984';
        $carTwoInput->color    = 'red';

        $personInput           = new PersonInput();
        $personInput->fullName = 'John Doe';
        $personInput->age      = '30';

        $personInput->cars[] = $carInput;
        $personInput->cars[] = $carTwoInput;

        $person = new Person();
        $populator->populateInput($person, $personInput);

        $this->assertEquals('John Doe', $person->fullName);

        $this->assertNull($person->cars[0]->id);
        $this->assertNull($person->cars[1]->id);

        $this->assertEquals('Ford', $person->cars[0]->name);
        $this->assertEquals('Ferrari', $person->cars[1]->name);

        $this->assertEquals('yellow', $person->cars[0]->color);
        $this->assertEquals('red', $person->cars[1]->color);
    }

    public function testUpdateCollection()
    {
        /** @var PropertyInfoExtractor $mock */
        $mock = $this->getPropertyInfoExtractorMock();
        $method = class_exists(\Symfony\Component\TypeInfo\Type::class) ? 'getType' : 'getTypes';
        $type = $this->createMockType(
            'object',
            false,
            'Doctrine\Common\Collections\Collection',
            true,
            $this->createMockType('object', false, null, true, []),
            $this->createMockType('object', false, "Sparklink\GraphQLToolsBundle\Tests\GraphQL\Fixtures\Car", true, [], [])
        );

        $mock->expects($this->any())
                ->method($method)
                ->willReturn(class_exists(\Symfony\Component\TypeInfo\Type::class) ? $type : [$type]);

        $populator = new Populator(new TypeEntityResolver(self::MAPPING), $mock);

        $car          = new Car();
        $car->id      = 1;
        $car->name    = 'Ford';
        $car->model   = 'Mustang';
        $car->year    = '1967';

        $carTwo         = new Car();
        $carTwo->id     = 2;
        $carTwo->name   = 'Ferrari';
        $carTwo->model  = 'Testarossa';
        $carTwo->year   = '1984';

        $person           = new Person();
        $person->fullName = 'John Doe';
        $person->age      = '30';
        $person->cars[]   = $car;
        $person->cars[]   = $carTwo;

        $carInput          = new CarInput();
        $carInput->id      = 1;
        $carInput->name    = 'Ford';
        $carInput->model   = 'Mustang';
        $carInput->year    = '1968';

        $carTwoInput          = new CarInput();
        $carTwoInput->id      = 2;
        $carTwoInput->name    = 'Ferrari';
        $carTwoInput->model   = 'Testarossa';
        $carTwoInput->year    = '1985';

        $personInput           = new PersonInput();
        $personInput->cars[]   = $carInput;
        $personInput->cars[]   = $carTwoInput;

        $populator->populateInput($person, $personInput);

        $this->assertEquals('John Doe', $person->fullName);
        $this->assertEquals('1', $person->cars[0]->id);
        $this->assertEquals('2', $person->cars[1]->id);
        $this->assertEquals('1968', $person->cars[0]->year);
        $this->assertEquals('1985', $person->cars[1]->year);
    }

    public function testUpdateAndCreateCollection()
    {
        /** @var PropertyInfoExtractor $mock */
        $mock = $this->getPropertyInfoExtractorMock();
        $method = class_exists(\Symfony\Component\TypeInfo\Type::class) ? 'getType' : 'getTypes';
        $type = $this->createMockType(
            'object',
            false,
            'Doctrine\Common\Collections\Collection',
            true,
            $this->createMockType('object', false, null, true, []),
            $this->createMockType('object', false, "Sparklink\GraphQLToolsBundle\Tests\GraphQL\Fixtures\Car", true, [], [])
        );

        $mock->expects($this->once())
                ->method($method)
                ->willReturn(class_exists(\Symfony\Component\TypeInfo\Type::class) ? $type : [$type]);

        $populator = new Populator(new TypeEntityResolver(self::MAPPING), $mock);

        $config = new Configuration();
        $config->ignore('car.id');

        $car          = new Car();
        $car->id      = 1;
        $car->name    = 'Ford';
        $car->model   = 'Mustang';
        $car->year    = '1967';

        $carTwo         = new Car();
        $carTwo->id     = 2;
        $carTwo->name   = 'Ferrari';
        $carTwo->model  = 'Testarossa';
        $carTwo->year   = '1984';

        $person           = new Person();
        $person->fullName = 'John Doe';
        $person->age      = '30';
        $person->cars[]   = $car;
        $person->cars[]   = $carTwo;

        $carInput          = new CarInput();
        $carInput->id      = 1;
        $carInput->name    = 'Ford';
        $carInput->model   = 'Mustang';
        $carInput->year    = '1968';

        $carTwoInput          = new CarInput();
        $carTwoInput->id      = 2;
        $carTwoInput->name    = 'Ferrari';
        $carTwoInput->model   = 'Testarossa';
        $carTwoInput->year    = '1985';

        $carThreeInput          = new CarInput();
        $carThreeInput->name    = 'Porche';
        $carThreeInput->model   = '911';
        $carThreeInput->year    = '1985';

        $personInput           = new PersonInput();
        $personInput->cars[]   = $carInput;
        $personInput->cars[]   = $carTwoInput;
        $personInput->cars[]   = $carThreeInput;

        $populator->populateInput($person, $personInput, $config);

        $this->assertEquals('John Doe', $person->fullName);

        $this->assertEquals('1', $person->cars[0]->id);
        $this->assertEquals('2', $person->cars[1]->id);
        $this->assertNull($person->cars[2]->id);

        $this->assertEquals('1968', $person->cars[0]->year);
        $this->assertEquals('1985', $person->cars[1]->year);
        $this->assertEquals('1985', $person->cars[2]->year);
    }

    public function testUpdateCollectionIgnoreId()
    {
        /** @var PropertyInfoExtractor $mock */
        $mock = $this->getPropertyInfoExtractorMock();
        $method = class_exists(\Symfony\Component\TypeInfo\Type::class) ? 'getType' : 'getTypes';
        $type = $this->createMockType(
            'object',
            false,
            'Doctrine\Common\Collections\Collection',
            true,
            $this->createMockType('object', false, null, true, []),
            $this->createMockType('object', false, "Sparklink\GraphQLToolsBundle\Tests\GraphQL\Fixtures\Car", true, [], [])
        );

        $mock->expects($this->once())
                ->method($method)
                ->willReturn(class_exists(\Symfony\Component\TypeInfo\Type::class) ? $type : [$type]);

        $populator = new Populator(new TypeEntityResolver(self::MAPPING), $mock);

        $config = new Configuration();
        $config->ignore('cars.id');

        $car          = new Car();
        $car->id      = 1;
        $car->name    = 'Ford';
        $car->model   = 'Mustang';
        $car->year    = '1967';

        $person           = new Person();
        $person->fullName = 'John Doe';
        $person->age      = '30';
        $person->cars[]   = $car;

        $carInput          = new CarInput();
        $carInput->id      = 1;
        $carInput->name    = 'Ferrari';
        $carInput->model   = 'Testarossa';
        $carInput->year    = '1984';

        $personInput           = new PersonInput();
        $personInput->cars[]   = $carInput;

        $populator->populateInput($person, $personInput, $config);

        $this->assertEquals('John Doe', $person->fullName);
        $this->assertNull($person->cars[0]->id);
        $this->assertEquals('1984', $person->cars[0]->year);
        $this->assertEquals('Ferrari', $person->cars[0]->name);
    }

    public function testCreateSimpleRelation(): void
    {
        /** @var PropertyInfoExtractor $mock */
        $mock = $this->getPropertyInfoExtractorMock();

        $populator = new Populator(new TypeEntityResolver(self::MAPPING), $mock);

        $person           = new Person();
        $person->fullName = 'John Doe';
        $person->age      = '30';

        $carInput           = new CarInput();
        $carInput->name     = 'Ford';
        $carInput->model    = 'Mustang';
        $carInput->year     = '1967';
        $carInput->color    = 'red';
        $carInput->owner    = $person;

        $car = new Car();

        $populator->populateInput($car, $carInput);

        $this->assertEquals('John Doe', $car->owner->fullName);
    }

    private function getPropertyInfoExtractorMock()
    {
        $methods = class_exists(\Symfony\Component\TypeInfo\Type::class) ? ['getType'] : ['getTypes'];

        return $this->getMockBuilder(PropertyInfoExtractor::class)
        ->disableOriginalConstructor()
        ->onlyMethods($methods)
        ->getMock();
    }

    private function createMockType(
        string $builtinType,
        bool $nullable = false,
        ?string $class = null,
        bool $collection = false,
        $collectionKeyType = null,
        $collectionValueType = null
    ) {
        if (class_exists(\Symfony\Component\TypeInfo\Type::class)) {
            if ($collection) {
                $mainType = $class !== null ? \Symfony\Component\TypeInfo\Type::object($class) : \Symfony\Component\TypeInfo\Type::builtin('array');
                
                // Extract class name from collection value type
                $valType = null;
                if (\is_array($collectionValueType)) {
                    $valType = isset($collectionValueType[0]) ? $this->getMockTypeClassName($collectionValueType[0]) : null;
                } elseif ($collectionValueType !== null) {
                    $valType = $this->getMockTypeClassName($collectionValueType);
                }
                
                $valueType = $valType ? \Symfony\Component\TypeInfo\Type::object($valType) : null;
                
                // Extract class name from collection key type
                $kType = null;
                if (\is_array($collectionKeyType)) {
                    $kType = isset($collectionKeyType[0]) ? $this->getMockTypeClassName($collectionKeyType[0]) : null;
                } elseif ($collectionKeyType !== null) {
                    $kType = $this->getMockTypeClassName($collectionKeyType);
                }
                
                $keyType = $kType ? \Symfony\Component\TypeInfo\Type::object($kType) : null;
                
                $type = \Symfony\Component\TypeInfo\Type::collection($mainType, $valueType, $keyType);
            } else {
                if ($builtinType === 'object') {
                    $type = \Symfony\Component\TypeInfo\Type::object($class);
                } else {
                    $type = \Symfony\Component\TypeInfo\Type::builtin($builtinType);
                }
            }

            if ($nullable) {
                $type = Type::nullable($type);
            }

            return $type;
        }

        $className = 'Symfony\Component\PropertyInfo\Type';
        return new $className(
            $builtinType,
            $nullable,
            $class,
            $collection,
            $collectionKeyType,
            $collectionValueType
        );
    }

    private function getMockTypeClassName($type): ?string
    {
        if ($type instanceof \Symfony\Component\TypeInfo\Type) {
            if ($type instanceof \Symfony\Component\TypeInfo\Type\ObjectType) {
                return $type->getClassName();
            }
            if ($type instanceof \Symfony\Component\TypeInfo\Type\WrappingTypeInterface) {
                return $this->getMockTypeClassName($type->getWrappedType());
            }
            return null;
        }

        return $type->getClassName();
    }
}
