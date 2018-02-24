package models

import (
	"github.com/liip/sheriff"
	"github.com/mweibel/php-to-go-tests/datatypes"
)

type AbstractModel struct {
	SomeStringArray *[]string `json:"some_string_array,omitempty" until:"2" groups:"api"`
}

func (data AbstractModel) Marshal(options *sheriff.Options) (interface{}, error) {
	dest, err := sheriff.Marshal(options, data)
	if err != nil {
		return nil, err
	}
	return dest, nil
}
