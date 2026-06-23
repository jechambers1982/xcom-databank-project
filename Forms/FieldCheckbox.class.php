<?php
declare(strict_types = 1);

namespace XCOMDatabank\Forms;

class FieldCheckbox Extends Field
{
    public static array $divClass;
    public static array $infoArray;
    public static string $label;

    function __construct(string $name, string $value, string $type, array $infoArray, string $label, array $divClass)
    {
        parent::__construct($name, $value);

        // Default type to checkbox unless explicitly set to radio
        if($type == "radio") {
            self::$inputArray['type'] = $type;
        } else {
            self::$inputArray['type'] = "checkbox";
        }
        self::$infoArray = $infoArray;
        self::$divClass = $divClass;
        self::$label = $label;
    }

    public static function getField(string $name, string $value, string $type, array $infoArray, string $label, array $divClass): string {

        $field = new static($name, $value, $type, $infoArray, $label, $divClass);
        return self::printField($field::$inputArray, $field::$infoArray, $field::$label, $field::$divClass);
    }

    public static function printField(array $inputArray, array $infoArray, string $label, array $divClass): string {
        $openDiv = "";
        $closeDiv = "";

        if(sizeof($divClass) > 0) {
            $openDiv = '<div class="' . implode(' ', $divClass).'">'."\n";
            $closeDiv = "\n".'</div>';
        }

        $formInput = '';
        $selectedValues = array();
        foreach($infoArray as $option) {
            $formInput .= '<input';
            foreach($inputArray as $key => $item) {
                if($key == "value") {
                    array_push($selectedValues, $item);
                } else {
                    $formInput .= ' '.$key.'="'.$item.'"';
                }
            }

            foreach($option as $key => $item) {
                $formInput .= ' '.$key.'="'.$item.'"';
                if($key == "value") {
                    if(in_array($item, $selectedValues)) {
                        $formInput .= ' selected';
                    }
                }
            }
            $formInput .= ">"."\n";
        }

        $formLabel = "";
        if($label != "") {
            $formLabel = '<label for="' . $inputArray['value'] . '">' . $label . '</label>';
        }

        return $openDiv.$formInput.$formLabel.$closeDiv;
    }
}