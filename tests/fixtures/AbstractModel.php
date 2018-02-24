<?php

namespace PHPToGo\Tests\fixtures;

use JMS\Serializer\Annotation as Serializer;

abstract class AbstractModel
{
    /**
     * @var string[]
     * @Serializer\Until("2")
     * @Serializer\Type("array<string>")
     * @Serializer\Groups({"api"})
     */
    protected $someStringArray = [];
}
