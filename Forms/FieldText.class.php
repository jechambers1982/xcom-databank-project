<?php
declare(strict_types = 1);

namespace XCOMDatabank\Forms;

class FieldText extends Field {
    
    public static string $label;
    public static array $divClass;
    public static bool $disabled;
    
    function __construct(string $name, ?string $value, string $class, string $id,
       string $label, array $divClass, bool $required, bool $disabled) {
        
        parent::__construct($name, $value);
        
        self::$inputArray['class'] = $class;
        self::$inputArray['id'] = $id;
        self::$inputArray['required'] = $required;
        self::$inputArray['type'] = "text";
        
        self::$label = $label;
        self::$divClass = $divClass;
        self::$disabled = $disabled;
    }
    
    public static function getField(string $name, ?string $value, string $class, string $id,
        string $label, array $divClass, bool $required, bool $disabled = false): string {
        
        $field = new static($name, $value, $class, $id, $label, $divClass, $required, $disabled);
        return self::printField($field::$inputArray, $field::$label, $field::$divClass, $field::$disabled);
    }

    public static function printField(array $inputArray, string $label, array $divClass, bool $disabled): string {
        $openDiv = "";
        $closeDiv = "";

        if(sizeof($divClass) > 0) {
            $openDiv = '<div class="' . implode(' ', $divClass).'">'."\n";
            $closeDiv = "\n".'</div>';
        }

        $formInput = '<input';
        foreach($inputArray as $key => $item) {
            if($key == "required") {
                if($item == true)
                    $formInput .= ' required';
            }
            else {
                $formInput .= ' '.$key.'="'.$item.'"';
            }
            if ($disabled) {
                $formInput .= ' disabled';
            }
        }
        $formInput .= ">";
        $formLabel = "";
        if($label != "") {
            $formLabel = '<label for="' . $inputArray['id'] . '">' . $label . '</label>';
        }

        return $openDiv.$formInput.$formLabel.$closeDiv;
    }
}