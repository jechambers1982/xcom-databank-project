<?php
declare(strict_types = 1);

namespace XCOMDatabank\Forms;

class FieldTextNum extends FieldText {
    
    function __construct(string $name, string $value, string $class, string $id,
        string $label, array $divClass, bool $required, bool $disabled, string $min, string $max, string $step = '1') {
            
            parent::__construct($name, $value, $class, $id, $label, $divClass, $required, $disabled);
            
            self::$inputArray['min'] = $min;
            self::$inputArray['max'] = $max;
            self::$inputArray['step'] = $step;
            self::$inputArray['type'] = "number";
    }

    public static function getField(string $name, ?string $value, string $class, string $id, string $label,
                                    array $divClass, bool $required, bool $disabled = false, string $min = '0', string $max = '0', string $step = '1'): string {

        $field = new static($name, $value, $class, $id, $label, $divClass, $required, $disabled, $min, $max, $step);
        return self::printField($field::$inputArray, $field::$label, $field::$divClass, $field::$disabled);
    }
}