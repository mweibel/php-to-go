package datatypes

import (
	"time"
)

type DateTime time.Time

func (dt *DateTime) UnmarshalJSON(data []byte) error {
	t, err := time.Parse(`"2006-01-02T15:04:05-0700"`, string(data))
	if err != nil {
		return err
	}
	*dt = DateTime(t)
	return nil
}

func (dt *DateTime) MarshalJSON() ([]byte, error) {
	t := time.Time(*dt)
	return []byte(t.Format(`"2006-01-02T15:04:05-0700"`)), nil
}

func (dt *DateTime) String() string {
	t := time.Time(*dt)
	return t.String()
}

type Date time.Time

func (d *Date) UnmarshalJSON(data []byte) error {
	t, err := time.Parse(`"02.01.2006"`, string(data))
	if err != nil {
		return err
	}
	*d = Date(t)
	return nil
}

func (d *Date) MarshalJSON() ([]byte, error) {
	t := time.Time(*d)
	return []byte(t.Format(`"02.01.2006"`)), nil
}

func (d *Date) String() string {
	t := time.Time(*d)
	return t.String()
}

type IntlDate time.Time

func (d *IntlDate) UnmarshalJSON(data []byte) error {
	t, err := time.Parse(`"2006-01-02"`, string(data))
	if err != nil {
		return err
	}
	*d = IntlDate(t)
	return nil
}

func (d *IntlDate) MarshalJSON() ([]byte, error) {
	t := time.Time(*d)
	return []byte(t.Format(`"2006-01-02"`)), nil
}

func (d *IntlDate) String() string {
	t := time.Time(*d)
	return t.String()
}
