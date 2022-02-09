
Scalars
=======

GraphlBunlde extensions and tools provides a set of scalars for GraphQL.

FileItem
------

You can extend the FileItem scalar to use your own File entity as a scalar.

The scalar accept either a `File` instance (`Symfony\Component\HttpFoundation\File\File`) or an object containing an `ìd` or `uid` property (if this is the case, it returns the instance of your file entity grabbed from the repository).

Exemple:

```php
<?php

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

Date
------
Represent date without time.

Expected input format: `YYYY-MM-DD` (ex: `2019-01-01`)

DateTime
------
Represent date with time.

Expected input format: `YYYY-MM-DD HH:MM:SS`

DateTimeTz
------
Represent date with time and timezone.

Expected format: `YYYY-MM-DD HH:MM:SS TZ`

Email
------
Represent an email
Expected input format : valid email

Json
------
Represent Json.
Exect Json 

Time
------
Represent time without seconds.

Expected format: `HH:MM` . e.g: "12:00"'

Timefull
------
Represents Time with seconds. 

Expected format: `HH:MM:SS`. e.g: "12:12:30"

Upload
------
Represent a file.