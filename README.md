Overblog/GraphQLBundle extension & tools



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
