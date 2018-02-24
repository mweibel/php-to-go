<?php

namespace PHPToGo\Tests\fixtures;

use JMS\Serializer\Annotation as Serializer;

class AnotherRootModel
{
    /**
     * @var string
     * @Serializer\Type("string")
     * @Serializer\Groups({"api"})
     */
    public $id;

    /**
     * @var AnotherModel
     * @Serializer\Until("2")
     * @Serializer\Type("PHPToGo\Tests\fixtures\AnotherModel")
     * @Serializer\Groups({"not-api"})
     */
    public $anotherModel;
}
