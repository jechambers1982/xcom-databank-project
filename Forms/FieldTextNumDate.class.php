<?php
declare(strict_types = 1);

namespace XCOMDatabank\Forms;

class FieldTextNumDate extends FieldTextNum {
    
    function __construct(string $name, string $value, string $class, string $id,
        string $label, array $divClass, bool $required, bool $disabled, string $min, string $max, string $pattern) {
            
            parent::__construct($name, $value, $class, $id, $label, $divClass, $required, $disabled, $min, $max);
            
            self::$inputArray['pattern'] = $pattern;
            self::$inputArray['type'] = "date";
    }

    public static function getField(string $name, ?string $value, string $class, string $id, string $label,
                                    array $divClass, bool $required, bool $disabled = false, string $min = '0', string $max = '0', $pattern = 'Y-m-d'): string {

        $field = new static($name, $value, $class, $id, $label, $divClass, $required, $disabled, $min, $max, $pattern);
        return self::printField($field::$inputArray, $field::$label, $field::$divClass, $disabled);
    }
}