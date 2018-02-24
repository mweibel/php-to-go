# php-to-go
[![Build Status](https://travis-ci.org/mweibel/php-to-go.svg?branch=master)](https://travis-ci.org/mweibel/php-to-go)
[![Coverage Status](https://coveralls.io/repos/github/mweibel/php-to-go/badge.svg?branch=master)](https://coveralls.io/github/mweibel/php-to-go?branch=master)

Library for generating Go structs using [sheriff](https://github.com/liip/sheriff) out of PHP models which use [JMS Serializer](https://jmsyst.com/libs/serializer).

## Status

Alpha.

Has not been tested in real production workload yet. Has been tested locally against a test system using big models and quite some data.

Documentation of what it does should become better too.

## Contributions

Contributions in any form are welcome. 
I try to keep this library as small as possible. If you plan a big PR it might be better to ask first in an issue.

If you change PHP code please ensure to accompany it with an automated test.

## Why

Can be used to turn an existing serialization solution using PHP and JMS Serializer into one based on Go and sheriff.

## How

```php
<?php
// where to find your models
$srcGlob = './models/*.php';
// target directory of your Go files. Needs to be within $GOPATH.
$targetDirectory = getenv('GOPATH').'/src/github.com/mweibel/php-to-go-tests';
// package name of the Go structs
$packageName = 'models';
// list of ignored files within the target directory
$ignoredFiles = [];
// list of ignored property names (in case some are misbehaving or so)
$ignoredPropertyNames = [];
// echo what is being done
$verbose = true;

$generator = new PHPToGo\CodeGenerator($srcGlob, $targetDirectory, $packageName, $ignoredFiles, $ignoredPropertyNames, $verbose);
$generator->generate();
```

The generated files can then be incorporated into any Go program.

The code generator detects if there are methods annotated using `VirtualProperty`. 
In this case the generated model needs an AfterMarshal function receiver on that type.
As the code generator will overwrite the files it generated (on repeated execution), customizations to the generated types
should go into a separate file.

Example noop `AfterMarshal` function on a type called `RootModel`:

```go
package models

import "github.com/liip/sheriff"

func (rm RootModel) AfterMarshal(options *sheriff.Options, data interface{}) (interface{}, error) {
	return data, nil
}
``` 

If you want to interface with existing PHP code you can use e.g. [goridge](https://github.com/spiral/goridge).

# License

MIT (see LICENSE).
