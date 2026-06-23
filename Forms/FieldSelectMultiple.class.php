<?php
declare(strict_types = 1);

namespace XCOMDatabank\Forms;

class FieldSelectMultiple Extends Field
{
    public static string $label;
    public static array $divClass;
    public static array $options;

    function __construct(string $name, array $value, string $class, string $id, string $label,
                         array $divClass, bool $required, array $options) {

        parent::__construct($name, '');

        self::$inputArray['class'] = $class;
        self::$inputArray['id'] = $id;
        self::$inputArray['required'] = $required;
        self::$inputArray['value'] = $value;

        self::$label = $label;
        self::$divClass = $divClass;
        self::$options = $options;
    }

    public static function getField(string $name, array $value, string $class, string $id, string $label,
                                    array $divClass, bool $required, array $options): string {

        $field = new static($name, $value, $class, $id, $label, $divClass, $required, $options);
        return self::printField($field::$inputArray, $field::$label, $field::$divClass, $field::$options);
    }

    private static function printField(array $inputArray, string $label, array $divClass, array $options): string {
        $openDiv = "";
        $closeDiv = "";

        if(sizeof($divClass) > 0) {
            $openDiv = '<div class="' . implode(' ', $divClass).'">'."\n";
            $closeDiv = "\n".'</div>';
        }

        $formInput = '<select';
        foreach($inputArray as $key => $item) {
            if($key == "required") {
                if($item == true)
                    $formInput .= ' required';
            }
            elseif($key != 'value') {
                $formInput .= ' '.$key.'="'.$item.'"';
            }
        }
        $formInput .= ' multiple>'."\n";

        foreach($options as $item) {
            $formInput .= '<option';
            if(in_array($item['value'], $inputArray['value'])) {
                $formInput .= ' selected';
            }
            if ($item['value'] == "") {
                $formInput .= ' disabled';
            }
            $formInput .= ' value="'.$item['value'].'">';
            $formInput .= $item['text'];
            $formInput .= '</option>'."\n";
        }

        $formInput .= '</select>';

        $formLabel = '<label for="'.$inputArray['id'].'">'.$label.'</label>';

        return $openDiv.$formInput.$formLabel.$closeDiv;
    }
}