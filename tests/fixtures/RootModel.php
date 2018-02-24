<?php

namespace PHPToGo\Tests\fixtures;

use JMS\Serializer\Annotation as Serializer;

class RootModel extends AbstractModel implements InterfaceModel
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

    /**
     * @var string
     * @Serializer\Since("3")
     * @Serializer\Type("string")
     * @Serializer\Groups({"not-api"})
     */
    public $stringSinceV3;

    /**
     * @var AnotherModel[]
     * @Serializer\Type("array<PHPToGo\Tests\fixtures\AnotherModel>")
     * @Serializer\Groups({"api"})
     */
    public $anotherModelList = [];

    /**
     * @var int[]
     * @Serializer\Type("array<int>")
     */
    public $intArray = [];

    /**
     * @var AnotherModel[]
     * @Serializer\Since("3")
     * @Serializer\Type("array<string, PHPToGo\Tests\fixtures\AnotherModel>")
     * @Serializer\Groups({"api"})
     */
    public $mapStringAnotherModel = [];

    /**
     * Whether the product is purchasable online (i.e. it has any link to a retailer).
     *
     * @var bool
     * @Serializer\Until("2")
     * @Serializer\Type("boolean")
     * @Serializer\Groups({"api"})
     */
    public $someBool;

    /**
     * @var int
     * @Serializer\Type("integer")
     */
    public $someInt = 0;

    /**
     * @var AnotherModel[][]
     * @Serializer\Type("array<array<PHPToGo\Tests\fixtures\AnotherModel>>")
     * @Serializer\Groups({"api"})
     */
    public $twoDimensionalAnotherModel = [];

    /**
     * @var string[]
     * @Serializer\Type("array<string>")
     * @Serializer\Groups({"not-api"})
     * @Serializer\Accessor(getter="getCustomGetterOrNull")
     */
    public $customGetter = [];

    /**
     * @var float
     * @Serializer\Type("float")
     */
    public $someFloat = 1.0;

    /**
     * @var \DateTime
     * @Serializer\Type("DateTime")
     */
    public $someDateTime;

    /**
     * @var \DateTime
     * @Serializer\Type("DateTime<'d.m.Y'>")
     */
    public $someDate;

    /**
     * @var \DateTime
     * @Serializer\Type("DateTime<'Y-m-d'>")
     */
    public $someDateIntl;

    /**
     * @Serializer\Since("3")
     * @Serializer\Type("PHPToGo\Tests\fixtures\AnotherModel")
     * @Serializer\Groups({"api"})
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("another_model")
     *
     * @return AnotherModel|null
     */
    public function getAnotherModelInV3()
    {
        return $this->anotherModel ?: null;
    }

    public function getId(): string
    {
        return $this->id;
    }
}
