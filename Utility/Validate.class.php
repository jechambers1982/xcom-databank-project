<?php
declare(strict_types = 1);

namespace XCOMDatabank\Utility;

use Exception;
use PDO;

class Validate
{
    // Function to Test Integer values
    public static function testInteger($num, int $min, int $max, bool $canNull, string $info) {
        if($canNull and ($num === null or $num === "")) {
            return null;
        }
        elseif(!$canNull and ($num === null or $num === "")) {
            $exception = $info." is empty or null while not allowed to be null";
            self::throwException($exception);
        }
        else {
            $num = self::testIsSet($num, $canNull, $info);

            if(is_numeric($num)) {
                if($num < $min and $min != null) {
                    $exception = $info." is below minimum value (".$min."): ".htmlspecialchars((string)$num);
                    self::throwException($exception);
                }

                if($num > $max and $max != null) {
                    $exception = $info." is above maximum value (".$max."): ".htmlspecialchars((string)$num);
                    self::throwException($exception);
                }

                return (int)$num;
            }
            else {
                $exception = $info." is not a number: ".htmlspecialchars($num);
                self::throwException($exception);
            }
        }
        return false;
    }

    public static function testFloat(?float $value, int $min, int $max, bool $canNull, string $info) {
        if($canNull and ($value === null or $value === "")) {
            return null;
        }
        elseif(!$canNull and ($value === null or $value === "")) {
            $exception = $info." is empty or null while not allowed to be null";
            self::throwException($exception);
        }
        else {
            $value = self::testIsSet($value, $canNull, $info);

            if(is_float($value)) {
                if($value <= $min and $min != null) {
                    $exception = $info." is below minimum value (".$min."): ".htmlspecialchars((string)$value);
                    self::throwException($exception);
                }

                if($value >= $max and $max != null) {
                    $exception = $info." is above maximum value (".$max."): ".htmlspecialchars((string)$value);
                    self::throwException($exception);
                }

                return (float)$value;
            }
            else {
                $exception = $info." is not a number of type float: ".htmlspecialchars($value);
                self::throwException($exception);
            }
        }
        return false;
    }

    // Function to test String Values
    public static function testString(?string $string, int $min, int $max, bool $canNull, string $info) {
        if(is_string($string)) {
            $string = trim($string);
        }

        if($canNull and ($string === null or $string == "")) {
            return null;
        }
        elseif(!$canNull and $string == null) {
            $exception = $info." is empty or null while not allowed to be null";
            self::throwException($exception);
        }
        else {
            $string = self::testIsSet($string, false, $info);

            if(!is_string($string)) {
                $exception = $info." is not a string: ".$string;
                self::throwException($exception);
            }

            if($max >= $min) {
                if(strlen($string) < $min and $min != -1) {
                    $exception = $info." is below minimum range (".$min."): ".strlen($string)." characters long.";
                    self::throwException($exception);
                }

                if(strlen($string) > $max and $max != -1) {
                    $exception = $info." is above maximum range (".$max."): ".strlen($string)." characters long.";
                    self::throwException($exception);
                }
            }
            else {
                $exception = "Error in string range size for ".$info.": max (".$max.") is shorter than min (".$min.")";
                self::throwException($exception);
            }

            return $string;
        }
        return false;
    }

    // Function to test Boolean Values (True/False)
    public static function testTF($bool, bool $canNull, string $info): ?bool {
        if(is_string($bool)) {
            $bool = trim($bool);
        }

        if($canNull and $bool === null) {
            return null;
        }
        elseif(!$canNull and $bool === null) {
            $exception = $info." is empty or null while not allowed to be null";
            self::throwException($exception);
            return false;
        }
        else {
            $bool = self::testIsSet($bool, $canNull, $info);

            if($bool === true or $bool === 1 or $bool == "True" or $bool == "Yes" or $bool == "1") {
                return true;
            }
            elseif($bool === false or $bool === 0 or $bool == "False" or $bool == "No" or $bool == "0" or $bool == "") {
                return false;
            }
            else {
                $exception = $info.' is not set to a boolean value (true/false, 1/0, "true"/"false", "yes"/"no"): '.htmlspecialchars($bool);
                self::throwException($exception);
                return false;
            }
        }
    }

    // Function to test if a value is included in a particular array
    public static function testArray($value, array $array, bool $canNull, string $info) {
        if(is_string($value)) {
            $value = trim($value);
        }

        if($canNull and $value == null) {
            return null;
        }
        elseif(!$canNull  and $value == null) {
            $exception = $info." is empty or null while not allowed to be null";
            self::throwException($exception);
        }
        else {
            $value = self::testIsSet($value, $canNull, $info);

            foreach($array as $item) {
                if(is_array($item)) {
                    if(in_array($value, $item)) {
                        return $value;
                    }
                } else {
                    if($value == $item) {
                        return $value;
                    }
                }
            }
            $exception = $info." is not a valid value: ".htmlspecialchars($value);
            self::throwException($exception);
        }
        return false;
    }

    // Function to test if Database Index Exists
    public static function testIndex($index, string $table, bool $canNull, string $info): ?int {
        if($canNull and ($index == null or $index == "")) {
            return null;
        }
        elseif(!$canNull and ($index == null or $index == "")) {
            $exception = $info." is empty or null while not allowed to be null";
            self::throwException($exception);
            return -1;
        }
        else {
            $index = self::testIsSet($index, $canNull, $info);
            $table = 'xcom_'.$table;

            $query = 'SELECT id FROM '.$table.' WHERE id ='.$index;
            $params = array();
            $queryResult = Database::runQuery('select', $query, $params);
            $count = $queryResult->rowCount();

            if($count == 0) {
                $exception = $info." does not exist as index in ".$table;
                self::throwException($exception);
                return -1;
            }
            else {
                return intval($index);
            }
        }
    }

    // Function to test if an inputted string is a proper date
    public static function testDate($date, bool $canNull, string $info) {
        $date = trim($date);
        if($canNull and empty($date)) {
            return null;
        }
        elseif(!$canNull and empty($date)) {
            $exception = $info." is empty or null while not allowed to be null";
            self::throwException($exception);
            return -1;
        } else {
            $date = self::testIsSet($date, false, $info);
            list($y, $m, $d) = explode('-', $date);
            if(checkdate(intval($m), intval($d), intval($y))){
                return $date;
            } else {
                $exception = $info." is not a valid date: ".htmlspecialchars($date);
                self::throwException($exception);
            }
        }
        return false;
    }

    // Function to test if URL is valid
    public static function testURL($url, bool $canNull, string $info): ?string {
        $url = trim($url);
        if($canNull and empty($url)) {
            return null;
        }
        elseif(!$canNull and empty($url)) {
            $exception = $info." is empty or null while not allowed to be null";
            self::throwException($exception);
        }
        else {
            if(filter_var($url, FILTER_VALIDATE_URL)) {
                return $url;
            }
            else {
                $exception = $info." is not a valid URL or File: ".htmlspecialchars($url);
                self::throwException($exception);
            }
        }
        return "";
    }

    // Function to test if something is set or not
    public static function testIsSet($variable, bool $canNull, string $info) {

        if(isset($variable)) {
            return $variable;
        }
        elseif($canNull) {
            return null;
        }
        else {
            $exception = $info." is not set and cannot be null.";
            self::throwException($exception);
            return false;
        }
    }

    // Function to set to make sure an Image is valid
    public static function testImage($imgArray, string $directory, string $name, bool $canNull, string $info, ?int $skillID = 0): ?string {
        $uploadOk = -1;
        if($canNull and empty($imgArray)) {
            return null;
        }
        elseif(!$canNull and empty($imgArray)) {
            $exception = $info." is empty or null while not allowed to be null";
            self::throwException($exception);
        }
        elseif(!is_array($imgArray)) {
            return $imgArray;
        }
        else {
            // If image is for a skill, append an ID to it to account for duplicate skill names
            if($directory == "skill") {
                if($skillID > 0) {
                    $appendID = "-".$skillID;
                } else {
                    $query = "SELECT id FROM xcom_skills ORDER BY id DESC LIMIT 1";
                    $params = array();
                    $queryResult = Database::runQuery('select', $query, $params);

                    $row = $queryResult->fetch();
                    $latestID = $row['id'];
                    $latestID += 1;

                    $appendID = "-".$latestID;
                }
            } else {
                $appendID = "";
            }

            //Set targeted upload file directory and name
            $fileName = basename($imgArray["name"]);
            $imageFileType = strtolower(pathinfo($fileName,PATHINFO_EXTENSION));
            $target_file = "/home/joshch9/xcom-databank/img/".$directory."/".strtolower(trim(str_replace(" ","-",$name))).$appendID.".".$imageFileType;
            $upload_dir = "/img/".$directory."/".strtolower(trim(str_replace(" ","-",$name))).$appendID.".".$imageFileType;

            //remove apostrophes
            $target_file = str_replace("'","",$target_file);
            $upload_dir = str_replace("'","",$upload_dir);

            //remove commas
            $target_file = str_replace(",","",$target_file);
            $upload_dir = str_replace(",","",$upload_dir);

            // Check if image file is an actual image or fake image
            if(isset($post["submit"])) {
                $check = getimagesize($imgArray["tmp_name"]);
                if($check !== false) {
                    $uploadOk = 1;
                } else {
                    $exception = $info." does not appear to be a valid file";
                    self::throwException($exception);
                    $uploadOk = 0;
                }
            }

            // Check file size
            if ($imgArray["size"] > 500000) {
                $exception = $info." is too large a file";
                self::throwException($exception);
                $uploadOk = 0;
            }

            // Allow certain file formats
            if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg") {
                $exception = $info." is not a valid filetype (jpg, png, jpeg): ".$imageFileType;
                self::throwException($exception);
                $uploadOk = 0;
            }

            // Check if $uploadOk is set to 0 by an error
            if ($uploadOk == 0) {
                $exception = $info." was not uploaded.";
                self::throwException($exception);
                return null;
            } else {
                // if everything is ok, try to upload file
                // Create image sizes if upload is of a mission photo
                if ($info == "Mission Image") {
                    list($width, $height, $type, $attr) = getimagesize($imgArray["tmp_name"]);
                    // Resize to 690x388 for homepage/mission pages
                    $imageWidth = 690;
                    $imageHeight = 388;
                    $target_filename = "/home/joshch9/xcom-databank/img/" . $directory . "/" . strtolower(trim(str_replace(" ", "-", $name))) . "-690." . $imageFileType;

                    $src = imagecreatefromstring(file_get_contents($imgArray["tmp_name"]));
                    $dst = imagecreatetruecolor($imageWidth, $imageHeight);

                    imagecopyresampled($dst, $src, 0, 0, 0, 0, $imageWidth, $imageHeight, $width, $height);
                    imagedestroy($src);
                    imageinterlace($dst, 1);
                    imagejpeg($dst, $target_filename);
                    imagedestroy($dst);
                    $upload_dir = "/img/" . $directory . "/" . strtolower(trim(str_replace(" ", "-", $name))) . "-690." . $imageFileType;
                }

                // Upload image as uploaded
                if (move_uploaded_file($imgArray["tmp_name"], $target_file)) {
                    $testURL = "https://xcom-databank.games" . $upload_dir;
                    if (filter_var($testURL, FILTER_VALIDATE_URL)) {
                        return $upload_dir;
                    } else {
                        $exception = $info . " is not a valid URL or File: " . htmlspecialchars($testURL);
                        self::throwException($exception);
                    }
                    return $upload_dir;
                } else {
                    $exception = "There was an error uploading " . $info . " to location " . $target_file;
                    self::throwException($exception);
                    return null;
                }
            }
        }
        return null;
    }

    // Function to test login username
    static function testUsername($username) {
        if(strlen($username) < 4 or strlen($username) > 16){
            header('Location: https://xcom-databank.games/admin/signup.php?signup=uninvalid');
            exit();
        }
        elseif((!preg_match("/^[a-zA-Z0-9]*$/", $username)) or $username == "admin") {
            header('Location: https://xcom-databank.games/admin/signup.php?signup=uninvalid');
            exit();
        }
        else {

            $query = 'SELECT user_name FROM xcom_users WHERE user_name = :username';
            $params[0] = array("param" => ":username", "var" => $username, "type" => PDO::PARAM_STR,);
            $queryResult = Database::runQuery('select', $query, $params);

            if($queryResult->rowCount() != 0) {
                header('Location: https://xcom-databank.games/admin/signup.php?signup=exists');
                exit();
            } else {
                return $username;
            }
        }
    }

    // Function to test Password
    static function testPassword($password) {
        if(strlen($password) < 8){
            header('Location: https://xcom-databank.games/admin/signup.php?signup=pwinvalid');
            exit();
        }
        elseif(!preg_match("/^(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.*[!@#\$%^&*])(?=.{8,})/", $password)) {
            header('Location: https://xcom-databank.games/admin/signup.php?signup=pwinvalid');
            exit();
        }
        else {
            return password_hash($password, PASSWORD_DEFAULT);
        }
    }

    // Function to throw exception if something is amiss
    public static function throwException(string $text): void {
        $text = trim($text);

        try {
            throw new Exception($text);
        }
        catch(Exception $e) {
			exit('Caught exception: '.$e->getMessage()."\n");
        }
    }
}