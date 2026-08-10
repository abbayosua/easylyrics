package db

import (
	"encoding/json"
	"fmt"
)

func jsonMarshal(v map[string]string) (string, error) {
	b, err := json.Marshal(v)
	if err != nil {
		return "", fmt.Errorf("marshal json: %w", err)
	}
	return string(b), nil
}

func jsonUnmarshal(s string, v *map[string]string) error {
	if s == "" {
		s = "{}"
	}
	if err := json.Unmarshal([]byte(s), v); err != nil {
		return fmt.Errorf("unmarshal json: %w", err)
	}
	return nil
}
