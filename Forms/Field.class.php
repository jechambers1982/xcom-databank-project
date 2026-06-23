<?php
declare(strict_types = 1);

namespace XCOMDatabank\Forms;

class Field {
    
    public static array $inputArray;
    
    function __construct(string $name, ?string $value)
    {
        self::$inputArray = array();
        self::$inputArray['name'] = $name;
        self::$inputArray['value'] = $value;
    }

    public static function repeatButton($lastElement): string {
        $button = '<div class="col-md-1 pe-3 pb-2">'."\n";

        if($lastElement) {
		    $button .= '<button class="btn btn-success btn-add" type="button">'."\n";
		    $button .= '<i class="fa fa-plus" aria-hidden="true"></i>'."\n";
            $button .= '</button>'."\n";
		} else {
            $button .= '<button class="btn btn-danger btn-add" type="button">'."\n";
		    $button .= '<i class="fa fa-times" aria-hidden="true"></i>'."\n";
            $button .= '</button>'."\n";
		}
		$button .= '</div>'."\n";

        return $button;
    }
}