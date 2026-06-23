<?php
declare(strict_types = 1);

namespace XCOMDatabank\Forms;

class FieldHidden extends Field {
    
    function __construct(string $name, string $value) {
        parent::__construct($name, $value);
        
        self::$inputArray['type'] = "hidden";
    }

    public static function getField(string $name, string $value): string {
        $field = new static($name, $value);

        $formInput = '<input';
        foreach(self::$inputArray as $key => $item) {
            $formInput .= ' '.$key.'="'.$item.'"';
        }
        $formInput .= ">";

        return $formInput;
    }
}