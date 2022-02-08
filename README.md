# Overblog/GraphQLBundle extension & tools



Installation
===========

This bundle use the [Overblog/GraphQLBundle](https://github.com/overblog/GraphQLBundle), first you need to install it.


1. Install the bundle:
```bash
composer require sparklink/graphql-tools-bundle
```
2. In your project's root query and mutation attach: 

    In `Query`, the `CrudQuery` and `CrudEntityId` builders
    <br>
    ```php
        namespace App\GraphQL\Root;

        use App\GraphQL\Builder\CrudConfig;
        use Overblog\GraphQLBundle\Annotation as GQL;

        #[GQL\Type]
        #[GQL\FieldsBuilder(name: 'CrudQuery', config: CrudConfig::CONFIG)]
        #[GQL\FieldsBuilder(name: 'CrudEntityId', config: CrudConfig::CONFIG)]
        class Query
        {
            //...
        }
    ```
    In `Mutation`, the `CrudMutation` builder
    <br>
    ```php
        namespace App\GraphQL\Root;

        use App\GraphQL\Builder\CrudConfig;
        use Overblog\GraphQLBundle\Annotation as GQL;

        #[GQL\Type]
        #[GQL\FieldsBuilder(name: 'CrudMutation', config: CrudConfig::CONFIG)]
        class Mutation
        {
            //...
        }   
    ```




Crud Builder
===========

The Crud Builder can generate CRUD operations for your GraphQL schema.

It need a configuration table.

|   Property   |                                                                              Description                                                                              |                                                Type                                                 | Default |
| :----------: | :-------------------------------------------------------------------------------------------------------------------------------------------------------------------: | :-------------------------------------------------------------------------------------------------: | :-----: |
|    access    |             Act like access is true if not set, see [here](https://github.com/overblog/GraphQLBundle/blob/master/docs/security/fields-access-control.md)              |                                     string(e.g. = 'ROLE_ADMIN')                                     |    -    |
|  criterias   |                                                                   Only available in type operation                                                                    |                                    array: [property => criteria]                                    |    -    |
|   default    |                                                              Configure default parameters for all types.                                                              |        [`access`\|`permission`,`public`, `'get'`,`'list'`,`'create'`, `'update'`, `'list`']         |    -    |
|   \<type>    |                                                                     Configure parameters by type.                                                                     |                   [ `access`\|`permission`,`public`, `operations`, `<operation>`]                   |    -    |
|     name     |               redefine the name of the operation, if in `default` config you must write \<Type> somewhere in the name (e.g. : "name" => "\<Type>List")                |                                               string                                                |    -    |
| \<operation> |                                                Configure seperatly `'get'`,`'list'`,`'create'`, `'update'`, `'list`'.                                                 | [ `access`\|`permission`,`public`,`name`] <br> <b> in `'list'`:</b> `'orderBy[]'` & `'criterias[]'` |    -    |
|  operations  |                                         Define operations, by default all operations are forbidden and only generate entityId                                         |                   [`'get'`,`'list'`,`'create'`, `'update'`, `'list`'] \| `'all'`                    |    -    |
|   orderBy    |                                                                   Only available in type operation                                                                    |                                     array: [property => order]                                      |    -    |
|  permission  |                                  Shortcut for access, use '@=hasRole('')'. You can't define 'permission' and 'access' in same deep.                                   |                                string(e.g. :@=hasRole('ROLE_ADMIN'))                                |    -    |
|    public    | Control if a operation needs to be removed from the results, see [here](https://github.com/overblog/GraphQLBundle/blob/master/docs/security/fields-public-control.md) |          string(e.g. :@=service('security.authorization_checker').isGranted('ROLE_ADMIN'))          |    -    |
|    types     |                                                                                                                                                                       |                                            array:\<type>                                            |    -    |


**Note:** `access` and `permission` can be defined in several deeps (e.g. `default` and `<type>`). the deeper the config, the more priority it has.

#### Example

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
                    // You can use orderBy to sort the list
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

## Custom Manager

By default all types uses the same [`DefaultEntityTypeManager`](src/Manager/DefaultEntityTypeManager.php) but you can redefine it.

### Example

```yaml
# config/services.yaml
App\GraphQL\Manager\CarManager:
  tags:
    - { name: sparklink.type_manager, type: Car }
```

```php
// src/App/GraphQL/Manager/CarManager.php
namespace App\GraphQL\Manager;

use Sparklink\GraphQLToolsBundle\Manager\DefaultEntityTypeManager;

class CarManager extends DefaultEntityTypeManager
{
    public function list(array $criterias = [], array $orderBy = [], array $args = [], ResolveInfo $info = null): array
    {
        return ['items' => $this->getRepository()->findBy($criterias, $orderBy)];
    }
}
```

## Command Dump Manager
This command dumps the manager used by type/entity.
```bash
$ bin/console graphql:dump-managers
```

## Scalars

### FileItem

You can extends the FileItem scalar to use your own File entity as a scalar.
The scalar accept either a `File` instance (`Symfony\Component\HttpFoundation\File\File`) or an object containing an `ìd` or `uid` property (if this is the case, it returns the instance of your file entity grabbed from the repository).

Exemple:

```php
<?php

declare(strict_types=1);

namespace App\GraphQL\Scalar;

use App\Entity\File;
use Overblog\GraphQLBundle\Annotation as GQL;
use Sparklink\GraphQLToolsBundle\GraphQL\Scalar\FileItem as ScalarFileItem;

#[GQL\Scalar(name: 'FileItem', scalarType: "@=newObject('App\\\GraphQL\\\Scalar\\\FileItem', [service('doctrine')])")]
final class FileItem extends ScalarFileItem
{
    protected function getFileEntityClass(): string
    {
        return File::class;
    }
}

```

### Date

### DateTime

### DateTimeTz

### Email

### Json

### Time
Represent time without seconds. Example: "12:00"'

### Timefull
Represents Time with seconds. Example: "12:12:30"

### Upload

## Builder

### Query builder

The query builder can generate, for each type, two queries: `Type` and `TypeList`.

### Mutation Builder

The query builder can generate a mutation for a given type `TypeUpdate` and `TypeDelete`.
