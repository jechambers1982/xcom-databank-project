<?php
declare(strict_types = 1);

namespace XCOMDatabank\Utility;

class Error
{
    public static function returnError(string $error): string {
        return '<p class="print-error">'.$error.'</p>';
    }
}