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
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;
use Symfony\Component\PropertyInfo\Type;

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

    public function testCreateCollection(): void
    {
        $mock = $this->getPropertyInfoExtractorMock();

        $mock->expects($this->once())
                ->method('getTypes')
                ->willReturn([
                    new Type(
                        'object',
                        false,
                        'Doctrine\Common\Collections\Collection',
                        true,
                        new Type(
                            'object',
                            false,
                            null,
                            true,
                            [],
                        ),
                        new Type(
                            'object',
                            false,
                            "Sparklink\GraphQLToolsBundle\Tests\GraphQL\Fixtures\Car",
                            true,
                            [],
                            [],
                        ),
                    ),
                ]);

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
        $mock = $this->getPropertyInfoExtractorMock();

        $mock->expects($this->any())
                ->method('getTypes')
                ->willReturn([
                    new Type(
                        'object',
                        false,
                        'Doctrine\Common\Collections\Collection',
                        true,
                        new Type(
                            'object',
                            false,
                            null,
                            true,
                            [],
                        ),
                        new Type(
                            'object',
                            false,
                            "Sparklink\GraphQLToolsBundle\Tests\GraphQL\Fixtures\Car",
                            true,
                            [],
                            [],
                        ),
                    ),
                ]);

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
        $mock = $this->getPropertyInfoExtractorMock();

        $mock->expects($this->once())
                ->method('getTypes')
                ->willReturn([
                    new Type(
                        'object',
                        false,
                        'Doctrine\Common\Collections\Collection',
                        true,
                        new Type(
                            'object',
                            false,
                            null,
                            true,
                            [],
                        ),
                        new Type(
                            'object',
                            false,
                            "Sparklink\GraphQLToolsBundle\Tests\GraphQL\Fixtures\Car",
                            true,
                            [],
                            [],
                        ),
                    ),
                ]);

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
        $mock = $this->getPropertyInfoExtractorMock();

        $mock->expects($this->once())
                ->method('getTypes')
                ->willReturn([
                    new Type(
                        'object',
                        false,
                        'Doctrine\Common\Collections\Collection',
                        true,
                        new Type(
                            'object',
                            false,
                            null,
                            true,
                            [],
                        ),
                        new Type(
                            'object',
                            false,
                            "Sparklink\GraphQLToolsBundle\Tests\GraphQL\Fixtures\Car",
                            true,
                            [],
                            [],
                        ),
                    ),
                ]);

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
        return $this->getMockBuilder(PropertyInfoExtractor::class)
        ->disableOriginalConstructor()
        ->onlyMethods(['getTypes'])
        ->getMock();
    }
}
