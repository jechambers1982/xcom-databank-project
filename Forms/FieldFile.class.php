<?php
declare(strict_types = 1);

namespace XCOMDatabank\Forms;

class FieldFile Extends Field
{
    public static string $label;
    public static array $divClass;

    function __construct(string $name, string $value, string $id, string $class, bool $required, string $accept,
                            string $label, array $divClass)
    {
        parent::__construct($name, $value);

        self::$inputArray['class'] = $class;
        self::$inputArray['id'] = $id;
        self::$inputArray['required'] = $required;
        self::$inputArray['accept'] = $accept;
        self::$inputArray['type'] = "file";

        self::$label = $label;
        self::$divClass = $divClass;
    }

    public static function getField(string $name, ?string $value, string $id, string $class, bool $required, string $accept,
                                    string $label, array $divClass): string {

        $field = new static($name, $value, $id, $class, $required, $accept, $label, $divClass);
        return self::printField($field::$inputArray, $field::$label, $field::$divClass);
    }

    public static function printField(array $inputArray, string $label, array $divClass): string {
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
        }
        $formInput .= ">";
        $formLabel = "";
        if($label == "") {
            $formLabel = '<label for="' . $inputArray['id'] . '">' . $label . '</label>';
        }

        return $openDiv.$formInput.$formLabel.$closeDiv;
    }
}