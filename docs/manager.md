
Manager
===========

A Manager is an interface between GraphQL operations and the repository.
It handle the CRUD operations using the repository.

By default all types uses the same [`DefaultEntityTypeManager`](src/Manager/DefaultEntityTypeManager.php) but you can redefine it.
#### Example
1. first, create a new class that extends `DefaultEntityTypeManager` and override the method you need.
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

2. Then, define your custom-manager in your service:
```yaml
# config/services.yaml
App\GraphQL\Manager\CarManager:
  tags:
    - { name: sparklink.type_manager, type: Car }
```
