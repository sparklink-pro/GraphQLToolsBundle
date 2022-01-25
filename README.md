# Overblog/GraphQLBundle extension & tools

## Crud Builder

The Crud Builder can generate CRUD operations for your GraphQL schema.

It need a configuration table.

|   Property   | Description                                                                                                                                                           |                               Type                               | Default |
| :----------: | --------------------------------------------------------------------------------------------------------------------------------------------------------------------- | :--------------------------------------------------------------: | :-----: |
|   default    | Configure default parameters for all types.                                                                                                                           |                              array                               |    -    |
|    types     |                                                                                                                                                                       |                              array                               |         |
|   \<type>    | Configure parameters by type.                                                                                                                                         |                              array                               |         |
|    access    | Act like access is true if not set, see [here](https://github.com/overblog/GraphQLBundle/blob/master/docs/security/fields-access-control.md)                          |                              string                              |    -    |
|  permission  | Shortcut for access, use '@=hasRole('')'. You can't define 'permission' and 'access' in same deep.                                                                    |                                                                  |    -    |
|  operations  | Define operation, by default all operations are forbidden and only generate entityId                                                                                  | [`'get'`,`'list'`,`'create'`, `'update'`, `'list`'] \|\| `'all'` |    -    |
| \<operation> | Define parameter for each operation                                                                                                                                   |                              array                               |    -    |
|    public    | Control if a operation needs to be removed from the results, see [here](https://github.com/overblog/GraphQLBundle/blob/master/docs/security/fields-public-control.md) |                              string                              |    -    |
|     name     | redefine the name of the operation, if in default config you must write <Type> somewhere in the name e.g. : "name" => "\<Type>List".                                  |                                                                  |         |

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
                'operations'  => ['get', 'list', 'create', 'update', 'delete'],
                'list'        => [
                    'permission'     => 'ROLE_USER',
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
                // Will thriow, because both "permission" and "access" are set as "permission" is a shortcut for "access".
                'permission'  => 'ROLE_ADMIN',
                'access'      => '@=hasRole("ROLE_ADMIN")',
                'list'        => [
                    'permission'     => 'ROLE_USER',
                    'orderBy'        => ['name'=>'DESC'],
                ],
            ],
        ],
    ];
}
```

## Custom Manager

By default all types uses the same `DefaultEntityTypeManager` but you can redefine it.

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

class DivisionManager extends DefaultEntityTypeManager
{
    #TODO: see to remove config
    public function list($config, $orderBy): array
    {
        return ['items' => $this->getRepository()->findBy(['parent' => null], $orderBy)];
    }
}
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

### Timefull

### Upload

## Builder

### Query builder

The query builder can generate, for each type, two queries: `Type` and `TypeList`.

### Mutation Builder

The query builder can generate a mutation for a given type `TypeUpdate` and `TypeDelete`.
