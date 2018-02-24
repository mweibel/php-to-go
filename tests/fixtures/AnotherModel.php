<?php

namespace PHPToGo\Tests\fixtures;

use JMS\Serializer\Annotation as Serializer;

class AnotherModel
{
    /**
     * @var string
     * @Serializer\Type("string")
     * @Serializer\Groups({"api"})
     */
    public $id;

    /**
     * @var string
     * @Serializer\Type("string")
     * @Serializer\Groups({"api"})
     */
    public $ignoredPropertyName;
}
