Builder
===========

The CrudBuilder is a class that help you to create a GraphQL type and a GraphQL query and mutation and generate CRUD operations for your GraphQL schema

### Query builder
The query builder can generate, for each type, two queries: `Type` and `TypeList`.

### Mutation Builder
The query builder can generate a mutation for a given type `TypeUpdate` and `TypeDelete`.

## Operations

### EntityTypeId
\***Note:** By default, only the entityTypeId is generated.

Generate a graphQL scalar for a given type. Its name is the name of the type suffixed by `Id` (e.g. `CarId`). EntityTypeId is a graphql representation of an entity and is can be used in the GraphQL schema.  

### *Commons parameters*

Each operation (`get`, `list`, `create`, `update`, `delete`) can have the following parameters:
- `access`: Act like access is true if not set, see [here](https://github.com/overblog/GraphQLBundle/blob/master/docs/security/fields-access-control.md)   
- `permission`: Shortcut for access, use expression '@=hasRole()'
- `public`: Control if a operation needs to be removed from the results, see [here](https://github.com/overblog/GraphQLBundle/blob/master/docs/security/fields-public-control.md) 
- `name`: redefine the name of the operation, if in `default` config you must write explicitly '\<Type>' somewhere in the name (e.g. : "name" => "\<Type>List")




### Get
Generate a query for a given type, this query will expect an id as argument .

```gql
query Car($id: CarId!) {
  res: Car(id:$id) {
      id
      # ...
  }
}
```
*Default name:* '\<Type>' (e.g. : 'Car')

### List 

Generate a query list for a given type. 
```gql
query CarList {
    res: CarList {
        items{
            id
            #...
        }
    }
}
```
*default name:* '\<Type>List' (e.g. : 'CarList').

In `list` operation, you can add `orderBy` and `criterias` parameters.
- `orderBy[]`: Define order, the manager will sort the result by this order using the repository method `findBy`
- `criterias[]`: Define criterias, the manager will filter the result by this criterias using the repository method `findBy`


### Create
Generate a graphQL mutation that create a new entity
```gql
mutation CreateCar($input: CarInput!) {
    res: CreateCar(input: $input) {
        id
        #...
    }
}
```
*default name:* '\<Type>Create' (e.g. : 'CarCreate').

### Update
Generate a graphQL mutation that update an entity. This mutation will expect an id as argument.
```gql
mutation CarUpdate($item: CarId!, $input: CarInput!) {
    res: CarUpdate(item:$item ,input: $input) {
        id
    name
    }
}
```

*default name:* '\<Type>Update' (e.g. : 'CarUpdate').

### Delete
Generate a graphQL mutation that delete an entity. This mutation will expect an id as argument.
```gql
mutation CarDelete($item: CarId!) {
    res: CarDelete(item:$item) 
}
```
*default name:* '\<Type>Delete' (e.g. : 'CarDelete').


## Usage

Requires a configuration table that defines the operations.

### Example

```php
namespace App\GraphQL\Builder;

class CrudConfig
{
    public const CONFIG = [
        'default' => [
            'permission'     => "@=hasRole('ROLE_ADMIN')",for
            'get'            => [
                'public'          => "@=hasRole('ROLE_ADMIN')",
            ],
            'create'      => [
                // You need to define <Type> in name of the field
                'name' => '<Type>Create',
            ],
            'list'   => [
                'permission' => 'ROLE_ADMIN',
                'orderBy'    => [
                    'name' => 'ASC',
                ],
            ],
        ],
        'types'  => [
            'Car' => [
                'permission' => 'ROLE_ADMIN',
                'operations'  => ['get', 'list', 'create', 'update', 'delete'],
                'list'        => [
                    'permission'     => 'ROLE_USER',
                    // You can use orderBy  in type list to sort the list
                    'orderby'        => [
                        'name' => 'DESC',
                    ],
                ],
            ],
            'Bus'     => [
                'operations'=> 'all',
                'get'       => [
                    'name'   => 'GetAutoCar',
                    'public' => "@=hasRole('ROLE_ADMIN')",
                ],
            ],
            'Truck'   => [
                'operations'  => ['create', 'update', 'delete'],
                'list'        => [
                    'permission'     => 'ROLE_USER',
                ],
            ],
            'Bike'    => [
                // Will throw an error, because both "permission" and "access" are set as "permission" is a shortcut for "access".
                'permission'  => 'ROLE_ADMIN',
                'access'      => '@=hasRole("ROLE_ADMIN")',
                'list'        => [
                    'permission'     => 'ROLE_USER',
                    'orderBy'        => ['name'=>'DESC'],
                    // You can use criterias in type list 
                    'criterias'  => [
                        'wheels' => 3,
                        'colors' => 'blue',
                     ],
                ],
            ],
        ],
    ];
}
```