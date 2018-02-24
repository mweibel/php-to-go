package models

import (
	"github.com/liip/sheriff"
	"github.com/mweibel/php-to-go-tests/datatypes"
)

type RootModel struct {
	Id                         *string                  `json:"id,omitempty" groups:"api"`
	AnotherModel               *AnotherModel            `json:"another_model,omitempty" until:"2" groups:"not-api"`
	StringSinceV3              *string                  `json:"string_since_v3,omitempty" since:"3" groups:"not-api"`
	AnotherModelList           *[]AnotherModel          `json:"another_model_list,omitempty" groups:"api"`
	IntArray                   *[]int                   `json:"int_array,omitempty"`
	MapStringAnotherModel      *map[string]AnotherModel `json:"map_string_another_model,omitempty" since:"3" groups:"api"`
	SomeBool                   *bool                    `json:"some_bool,omitempty" until:"2" groups:"api"`
	SomeInt                    *int                     `json:"some_int,omitempty"`
	TwoDimensionalAnotherModel *[][]AnotherModel        `json:"two_dimensional_another_model,omitempty" groups:"api"`
	CustomGetter               *[]string                `json:"custom_getter,omitempty" groups:"not-api" accessor:"getCustomGetterOrNull"`
	SomeFloat                  *float64                 `json:"some_float,omitempty"`
	SomeDateTime               *datatypes.DateTime      `json:"some_date_time,omitempty"`
	SomeDate                   *datatypes.Date          `json:"some_date,omitempty"`
	SomeDateIntl               *datatypes.IntlDate      `json:"some_date_intl,omitempty"`
	SomeStringArray            *[]string                `json:"some_string_array,omitempty" until:"2" groups:"api"`
}

func (data RootModel) Marshal(options *sheriff.Options) (interface{}, error) {
	dest, err := sheriff.Marshal(options, data)
	if err != nil {
		return nil, err
	}
	return data.AfterMarshal(options, dest)
}
