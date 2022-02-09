
Scalars
=======

GraphlBunlde extensions and tools provides a set of scalars for GraphQL.



Date
------
Represent date without time.

Expected format: `Y-m-d`. 

Ex: `2019-01-01`

DateTime
------
Represent date with time.

Expected  format: `Y-m-d H:i:s`. 

Ex: `2019-01-01 12:00:00`

DateTimeTz
------
Represent date with time and timezone.

Expected format: `\DateTime::ATOM(Y-m-d\TH:i:sP)`

Ex : `2019-01-01T12:00:00+01:00`

Time
------
Represent time without seconds.

Expected format: `H:i`. 

Ex:  "12:00"'

Timefull
------
Represents Time with seconds. 

Expected format: `H:i:s`.

Ex: "12:12:30"


Email
------
Represent an email

Expected format: `valide email`


Json
------
Represent Json.

Expect `Json` 

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