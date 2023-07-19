<?php

declare(strict_types=1);

namespace Sparklink\GraphQLToolsBundle\Test\GraphQL\Builder;

use PHPUnit\Framework\TestCase;
use Sparklink\GraphQLToolsBundle\GraphQL\Builder\CrudEntityIdBuilder;
use Sparklink\GraphQLToolsBundle\GraphQL\Builder\CrudMutationBuilder;
use Sparklink\GraphQLToolsBundle\GraphQL\Builder\CrudQueryBuilder;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

class CrudBuilderTest extends TestCase
{
    protected function setUp(): void
    {
        $this->queryBuilder     = new CrudQueryBuilder();
        $this->mutationBuilder  = new CrudMutationBuilder();
        $this->entityIdBuilder  = new CrudEntityIdBuilder();
    }

    public function testOperations()
    {
        $config = [
            'default' => [
                'create' => [
                    'name' => 'Create<Type>',
                ],
            ],
            'types'=> [
                'TEST'   => ['operations' => 'all'],
                'TEST2'  => [
                    'operations' => ['delete', 'update'],
                ],
                'TEST3' => [
                    'operations'=> ['get', 'list'],
                ],
                'TEST4'         => [
                    'operations' => ['list', 'update', 'delete'],
                    'list'       => [
                        'name' => 'List<Type>',
                    ],
                ],
                'TEST5' => [],
                'TEST6' => ['entity_id' => false],
            ],
        ];

        // Check TEST 1
        $this->assertArrayHasKey('TESTUpdate', $this->getToMappingDefinition($this->mutationBuilder, $config));
        $this->assertArrayHasKey('TESTDelete', $this->getToMappingDefinition($this->mutationBuilder, $config));
        $this->assertArrayHasKey('CreateTEST', $this->getToMappingDefinition($this->mutationBuilder, $config)); // check if the name is correctly replaced
        $this->assertArrayHasKey('TESTList', $this->getToMappingDefinition($this->queryBuilder, $config)['fields']);
        $this->assertArrayHasKey('TEST', $this->getToMappingDefinition($this->queryBuilder, $config)['fields']);

        // check TEST 2
        $this->assertArrayNotHasKey('TEST2List', $this->getToMappingDefinition($this->queryBuilder, $config)['fields']);
        $this->assertArrayHasKey('TEST2Update', $this->getToMappingDefinition($this->mutationBuilder, $config));
        $this->assertArrayHasKey('TEST2Delete', $this->getToMappingDefinition($this->mutationBuilder, $config));

        // check TEST 3
        $this->assertArrayHasKey('TEST3List', $this->getToMappingDefinition($this->queryBuilder, $config)['fields']);
        $this->assertArrayHasKey('TEST3', $this->getToMappingDefinition($this->queryBuilder, $config)['fields']);
        $this->assertArrayNotHasKey('CreateTEST3', $this->getToMappingDefinition($this->mutationBuilder, $config));
        $this->assertArrayNotHasKey('TEST3Update', $this->getToMappingDefinition($this->mutationBuilder, $config));
        $this->assertArrayNotHasKey('TEST3Delete', $this->getToMappingDefinition($this->mutationBuilder, $config));

        // check TEST 4
        $this->assertArrayNotHasKey('TEST4List', $this->getToMappingDefinition($this->queryBuilder, $config)['fields']); // check if the name is correctly replaced
        $this->assertArrayHasKey('ListTEST4', $this->getToMappingDefinition($this->queryBuilder, $config)['fields']); // same
        $this->assertArrayNotHasKey('TEST4', $this->getToMappingDefinition($this->queryBuilder, $config)['fields']);
        $this->assertArrayHasKey('TEST4Update', $this->getToMappingDefinition($this->mutationBuilder, $config));
        $this->assertArrayNotHasKey('CreateTEST4', $this->getToMappingDefinition($this->mutationBuilder, $config));

        // check TEST 5
        $this->assertArrayNotHasKey('TEST5', $this->getToMappingDefinition($this->queryBuilder, $config)['fields']);
        $this->assertArrayNotHasKey('TEST5List', $this->getToMappingDefinition($this->queryBuilder, $config)['fields']);
        $this->assertArrayNotHasKey('TEST5Update', $this->getToMappingDefinition($this->mutationBuilder, $config));
        $this->assertArrayNotHasKey('TEST5Delete', $this->getToMappingDefinition($this->mutationBuilder, $config));
        $this->assertArrayNotHasKey('CreateTEST5', $this->getToMappingDefinition($this->mutationBuilder, $config));
        $this->assertArrayHasKey('TEST5Id', $this->getToMappingDefinition($this->entityIdBuilder, $config)['types']);

        // check TEST 6
        $this->assertArrayNotHasKey('TEST6Id', $this->getToMappingDefinition($this->entityIdBuilder, $config)['types']);
    }

    public function testPermission(): void
    {
        $configuration = [
            'default' => [
                'permission'  => 'ROLE_1',
                'list'        => [
                    'permission' => 'ROLE_2',
                ],
                'create'         => [
                    'permission' => 'ROLE_2',
                ],
            ],
            'types'  => [
                'TEST'      => [
                    'permission' => 'ROLE_3',
                ],
                'TEST2'     => [
                    'operations' => 'all',
                    'access'     => "@=hasRole('ROLE_3')",
                    'list'       => [
                        'permission' => 'ROLE_4',
                    ],
                ],
                'TEST3' => [
                    'access' => "@=hasRole('ROLE_3')",
                ],
                'TEST4' => [
                    'operations' => 'all',
                    'list'       => [
                        'permission' => 'ROLE_4',
                    ],
                ],
                'TEST5' => [],
            ],
        ];

        $this->assertEquals(['access' => "@=hasRole('ROLE_4')"], $this->getPermission($configuration, 'TEST2', 'list'));
        $this->assertEquals(['access' => "@=hasRole('ROLE_4')"], $this->getPermission($configuration, 'TEST4', 'list'));

        $this->assertEquals(['access' => "@=hasRole('ROLE_3')"], $this->getPermission($configuration, 'TEST', 'get'));
        $this->assertEquals(['access' => "@=hasRole('ROLE_3')"], $this->getPermission($configuration, 'TEST3', 'get'));

        $this->assertEquals(['access' => "@=hasRole('ROLE_2')"], $this->getPermission($configuration, 'TEST5', 'list'));
        $this->assertEquals(['access' => "@=hasRole('ROLE_2')"], $this->getPermission($configuration, 'TEST5', 'create'));

        $this->assertEquals(['access' => "@=hasRole('ROLE_1')"], $this->getPermission($configuration, 'TEST4', 'get'));
        $this->assertEquals(['access' => "@=hasRole('ROLE_1')"], $this->getPermission($configuration, 'TEST4', 'delete'));
    }

    public function testPublic(): void
    {
        $configuration = [
            'default' => [
                'public'       => '@=hasRole("ROLE_1")',
                'list'         => [
                    'public' => '@=hasRole("ROLE_2")',
                ],
                'create'         => [
                    'public' => '@=hasRole("ROLE_2")',
                ],
            ],
            'types'  => [
                'TEST'      => [
                    'public'       => '@=hasRole("ROLE_3")',
                ],
                'TEST2'     => [
                    'public'     => "@=hasRole('ROLE_3')",
                    'list'       => [
                        'public' => '@=hasRole("ROLE_4")',
                    ],
                ],
                'TEST3' => [
                    'public' => '@=hasRole("ROLE_3")',
                ],
                'TEST4' => [
                    'public'     => '@=hasRole("ROLE_3")',
                    'list'       => [
                        'public' => '@=hasRole("ROLE_4")',
                    ],
                ],
                'TEST5' => [],
            ],
        ];

        $this->assertEquals(['public' => '@=hasRole("ROLE_4")'], $this->getPublic($configuration, 'TEST4', 'list'));
        $this->assertEquals(['public' => '@=hasRole("ROLE_4")'], $this->getPublic($configuration, 'TEST2', 'list'));

        $this->assertEquals(['public' => '@=hasRole("ROLE_3")'], $this->getPublic($configuration, 'TEST', 'get'));
        $this->assertEquals(['public' => '@=hasRole("ROLE_3")'], $this->getPublic($configuration, 'TEST3', 'get'));

        $this->assertEquals(['public' => '@=hasRole("ROLE_2")'], $this->getPublic($configuration, 'TEST5', 'list'));
        $this->assertEquals(['public' => '@=hasRole("ROLE_2")'], $this->getPublic($configuration, 'TEST5', 'create'));

        $this->assertEquals(['public' => '@=hasRole("ROLE_1")'], $this->getPublic($configuration, 'TEST5', 'delete'));
        $this->assertEquals(['public' => '@=hasRole("ROLE_1")'], $this->getPublic($configuration, 'TEST5', 'get'));
    }

    /**
     * @dataProvider invalidConfigProvider
     */
    public function testInvalidConfigurationException($configuration, $path, $exception = true): void
    {
        if ($exception) {
            $this->expectException(InvalidConfigurationException::class);
            $this->expectExceptionMessage(sprintf('Invalid configuration for path "%s": Cannot use both "access" and "permission" keys on same level.', $path));
            $this->getConfiguration($configuration);
        } else {
            $this->assertEquals(['access' => '@=hasRole("ROLE_4")'], $this->getPermission($configuration, 'TEST', 'list'));
        }
    }

    private function getPermission(array $configuration, string $type, string $operation): array
    {
        $configuration = $this->getConfiguration($configuration);

        return $this->callMethod($this->queryBuilder, 'getAccess', [
            $configuration,
            $type,
            $operation,
        ]);
    }

    private function getPublic(array $configuration, string $type, string $operation): array
    {
        $configuration = $this->getConfiguration($configuration);

        return $this->callMethod($this->queryBuilder, 'getPublic', [
            $configuration,
            $type,
            $operation,
        ]);
    }

    /**
     * Get the processed configuration.
     */
    private function getConfiguration(array $configuration): array
    {
        return $this->callMethod($this->queryBuilder, 'getConfiguration', [$configuration]);
    }

    private static function callMethod($obj, $name, array $args): array
    {
        $class  = new \ReflectionClass($obj);
        $method = $class->getMethod($name);
        $method->setAccessible(true);

        return $method->invokeArgs($obj, $args);
    }

    private function getToMappingDefinition($builder, $config)
    {
        return $builder->toMappingDefinition($config);
    }

    /**
     * Provide configuration for testInvalidConfigurationException.
     * Both keys "access" and "permission" are used on the same level for every configuration's level.
     * Expect the last configuration to be valid.
     */
    public function invalidConfigProvider(): array
    {
        return [
            // first level
            [
                [
                    'default' => [
                        'access'     => '@=hasRole("ROLE_1")',
                        'permission' => 'ROLE_1',
                    ],
                    'types'  => [
                        'TEST' => [],
                    ],
                ],
            'builder.default',
            ],
           // second level
            [
                [
                    'default' => [
                        'access'     => '@=hasRole("ROLE_1")',
                        'list'       => [
                            'access'     => '@=hasRole("ROLE_2")',
                            'permission' => 'ROLE_2',
                        ],
                    ],
                    'types'  => [
                        'TEST' => [],
                    ],
                ],
                'builder.default.list',
            ],
            // third level
            [
                [
                    'default' => [
                        'access'     => '@=hasRole("ROLE_1")',
                    ],
                    'types'  => [
                        'TEST' => [
                            'access'           => '@=hasRole("ROLE_3")',
                            'permission'       => 'ROLE_3',
                            'operations'       => 'all',
                        ],
                    ],
                ],
                'builder.types.TEST',
            ],
            // fourth leel
            [
                [
                    'default' => [
                        'access'     => '@=hasRole("ROLE_1")',
                    ],
                    'types'  => [
                        'TEST' => [
                            'access'     => '@=hasRole("ROLE_3")',
                            'operations' => 'all',
                            'list'       => [
                                'access'     => '@=hasRole("ROLE_4")',
                                'permission' => 'ROLE_4',
                            ],
                        ],
                    ],
                ],
                'builder.types.TEST.list',
            ],
            // Should not throw an exception
            [
                [
                    'default' => [
                        'access'     => '@=hasRole("ROLE_1")',
                    ],
                    'types'  => [
                        'TEST' => [
                            'access'     => '@=hasRole("ROLE_3")',
                            'list'       => [
                                'access'     => '@=hasRole("ROLE_4")',
                            ],
                        ],
                    ],
                ],
                '',
                false,
            ],
        ];
    }
}
