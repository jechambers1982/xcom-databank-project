<?php
declare(strict_types = 1);

namespace XCOMDatabank\Forms;

class FieldSelect extends Field
{
    public static string $label;
    public static array $divClass;
    public static array $options;
    public static bool $disabled;

    function __construct(string $name, ?string $value, string $class, string $id, string $label,
        array $divClass, bool $required, array $options, bool $disabled) {

        parent::__construct($name, $value);

        self::$inputArray['class'] = $class;
        self::$inputArray['id'] = $id;
        self::$inputArray['required'] = $required;

        self::$label = $label;
        self::$divClass = $divClass;
        self::$options = $options;
        self::$disabled = $disabled;
    }

    public static function getField(string $name, ?string $value, string $class, string $id, string $label,
        array $divClass, bool $required, array $options, bool $disabled = false): string {

        $field = new static($name, $value, $class, $id, $label, $divClass, $required, $options, $disabled);
        return self::printField($field::$inputArray, $field::$label, $field::$divClass, $field::$options, $field::$disabled);
    }

    private static function printField(array $inputArray, string $label, array $divClass, array $options, bool $disabled): string {
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
            if ($disabled) {
                $formInput .= ' disabled';
            }
        }
        $formInput .= '>'."\n";
        foreach($options as $item) {
            $cleanArray = array();
            if(!isset($item['value'])) {
                $cleanArray['value'] = $item;
                $cleanArray['text'] = $item;
            }
            elseif(!isset($item['text'])) {
                if (isset($item['name'])) {
                    $cleanArray['text'] = $item['name'];
                } else {
                    $cleanArray['text'] = $item;
                }
            } else {
                $cleanArray['value'] = $item['value'];
                $cleanArray['text'] = $item['text'];
            }
            $formInput .= '<option';
            if($inputArray['value'] == $cleanArray['value']) {
                $formInput .= ' selected';
            }

            $formInput .= ' value="'.$cleanArray['value'].'">';
            $formInput .= $cleanArray['text'];
            $formInput .= '</option>'."\n";
        }

        $formInput .= '</select>'."\n";

        $formLabel = '<label for="'.$inputArray['id'].'">'.$label.'</label>'."\n";

        return $openDiv.$formInput.$formLabel.$closeDiv;
    }
}