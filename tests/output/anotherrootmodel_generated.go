package models

import (
	"github.com/liip/sheriff"
	"github.com/mweibel/php-to-go-tests/datatypes"
)

type AnotherRootModel struct {
	Id           *string       `json:"id,omitempty" groups:"api"`
	AnotherModel *AnotherModel `json:"another_model,omitempty" until:"2" groups:"not-api"`
}

func (data AnotherRootModel) Marshal(options *sheriff.Options) (interface{}, error) {
	dest, err := sheriff.Marshal(options, data)
	if err != nil {
		return nil, err
	}
	return dest, nil
}
