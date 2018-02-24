<?php

namespace PHPToGo\Tests\fixtures;

use JMS\Serializer\Annotation as Serializer;

class IgnoredClass
{
    /**
     * @var string
     * @Serializer\Type("string")
     * @Serializer\Groups({"api"})
     */
    public $id;
}
