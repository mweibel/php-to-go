package models

import (
	"github.com/liip/sheriff"
	"github.com/mweibel/php-to-go-tests/datatypes"
)

type AnotherModel struct {
	Id *string `json:"id,omitempty" groups:"api"`
}

func (data AnotherModel) Marshal(options *sheriff.Options) (interface{}, error) {
	dest, err := sheriff.Marshal(options, data)
	if err != nil {
		return nil, err
	}
	return dest, nil
}
