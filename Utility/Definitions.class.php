<?php
declare(strict_types = 1);

namespace XCOMDatabank\Utility;

class Definitions
{
    public static array $activityChainType = array (
        "Covert",
        "Mission"
    );

    public static array $activityTypeStatus = array (
        "Success",
        "Failed",
        "Ongoing"
    );

    public static array $alienFaction = array (
        "Aliens",
        "ADVENT",
        "Lost",
        "MOCX"
    );

    public static array $chainStatus = array (
        "Completed",
        "Failed",
        "Ongoing",
        "Abandoned"
    );

    public static array $chosen = array(
        array("text" => "None", "value" => ""),
        array("text" => "Assassin", "value" => "Assassin"),
        array("text" => "Hunter", "value" => "Hunter"),
        array("text" => "Warlock", "value" => "Warlock"),
    );

    public static array $chosenResult = array(
        array("text" => "None", "value" => ""),
        array("text" => "Killed", "value" => "Killed"),
        array("text" => "Extracted Information", "value" => "Extracted Information"),
        array("text" => "Captured Soldier", "value" => "Captured Soldier"),
        array("text" => "Indecisive Engagement", "value" => "Indecisive Engagement"),
        array("text" => "XCOM Destroyed", "value" => "XCOM Destroyed"),
    );

    public static array $covertStatus = array(
        "Complete",
        "In Progress",
        "Ambushed"
    );
    
    public static array $difficulty = array(
        "Easy",
        "Moderate",
        "Difficult",
        "Very Difficult"
    );
    
    public static array $extra = array(
        // Do I want to load these from the database?
        // Not all campaigns will have all of these
        "Commander's Avatar",
        "Dominated",
        "Double Agent",
        "Hacked",
        "Militia Androids",
        "Militia MECs",
        "Militia Skirmishers",
        "Mind Controlled",
        "Reaper Agent",
        "Rescued Soldier",
        "Resistance Militia",
        "Resistance Operative",
        "Skirmisher Warrior",
        "Spawned Item/Ability",
        "Volunteer Army",
        "XCOM Personnel"
    );

    public static array $faction = array(
        "Reapers",
        "Skirmishers",
        "Templars"
    );

    public static array $intelligence = array(
        "Standard",
        "Above Average",
        "Gifted",
        "Genius",
        "Savant"
    );

    public static array $mocxClass = array(
        "Grenadier",
        "MEC Trooper",
        "Phantom",
        "Psionic",
        "Ranger",
        "Rookie",
        "Sharpshooter",
        "Skirmisher",
        "Specialist",
        "Zealot"
    );

    public static array $mocxMissionStatus = array(
        "Active",
        "Bleed Out (Evac)",
        "Captured",
        "Evacuated",
        "Killed"
    );

    public static array $mocxStatus = array (
        "Active",
        "Captured",
        "Killed"
    );

    public static array $operativeRequirement = array (
        "Soldier",
        "Corporal+",
        "Sergeant+",
        "Lieutenant+",
        "Captain+",
        "Major+",
        "Colonel",
        "Reaper",
        "Skirmisher",
        "Templar",
        "Engineer",
        "Scientist",
        "Alien Alloys",
        "Elerium Crystals",
        "Supplies",
        "Intel"
    );

    public static array $operativeStatus = array (
        "Active",
        "Captured",
        "Killed",
        "Wounded"
    );
   
    public static array $rating = array (
        "Flawless",
        "Excellent",
        "Good",
        "Fair",
        "Poor",
        "Infiltrating"
    );

    public static array $researchFacility = array (
        "Research",
        "Shadow Chamber"
    );

    public static array $researchSpecial = array (
        array("text" => "None", "value" => ""),
        array("text" => "Breakthrough", "value" => "Breakthrough"),
        array("text" => "Inspired", "value" => "Inspired"),
        array ("text" => "Instant", "value" => "Instant"),
    );

    public static array $researchStatus = array (
        "Complete",
        "In Progress",
        "Locked",
        "Paused",
        "Unlocked"
    );

    public static array $sectors = array (
        "East Africa",
        "East Asia",
        "Eastern Europe",
        "Eastern United States",
        "New Arctic",
        "New Australia",
        "New Brazil",
        "New Chile",
        "New India",
        "New Indonesia",
        "New Mexico",
        "South Africa",
        "West Africa",
        "West Asia",
        "Western Europe",
        "Western United States",
        "Chosen Assassin Stronghold",
        "Chosen Hunter Stronghold",
        "Chosen Warlock Stronghold",
        "Alien Fortress"
    );

    public static array $soldierStatus = array (
        array("text" => "Active", "value" => 0),
        array("text" => "Killed", "value" => 1),
        array("text" => "Captured", "value" => 2),
    );
    
    public static array $status = array (
        "Active",
        "Lightly Wounded",
        "Wounded",
        "Gravely Wounded",
        "Shaken",
        "Killed",
        "Captured"
    );

    public static array $vipStatus = array (
        "Captured",
        "Hired from Black Market",
        "Mission Reward",
        "Recruited via Covert Op",
        "Scan Reward"
    );

    public static array $vipType = array (
        "Engineer",
        "Scientist",
        "Dark VIP"
    );

    public static function getActivityChainType(): array {
        return self::$activityChainType;
    }

    public static function getActivityTypeStatus(): array {
        return self::$activityTypeStatus;
    }

    public static function getAlienFaction(): array {
        return self::$alienFaction;
    }

    public static function getChainStatus(): array {
        return self::$chainStatus;
    }

    public static function getChosen(): array {
        return self::$chosen;
    }
    
    public static function getChosenResult() : array {
        return self::$chosenResult;
    }

    public static function getCovertStatus(): array {
        return self::$covertStatus;
    }
    
    public static function getDifficulty() : array {
        return self::$difficulty;
    }
    
    public static function getExtra() : array {
        return self::$extra;
    }

    public static function getFactions(): array {
        return self::$faction;
    }

    public static function getIntelligence(): array {
        return self::$intelligence;
    }

    public static function getMOCXClass(): array {
        return self::$mocxClass;
    }

    public static function getMOCXMissionStatus(): array {
        return self::$mocxMissionStatus;
    }

    public static function getMOCXStatus(): array {
        return self::$mocxStatus;
    }

    public static function getOperativeRequirements(): array {
        return self::$operativeRequirement;
    }

    public static function getOperativeStatus(): array {
        return self::$operativeStatus;
    }
    
    public static function getRating() : array {
        return self::$rating;
    }

    public static function getResearchFacility(): array {
        return self::$researchFacility;
    }

    public static function getResearchSpecial(): array {
        return self::$researchSpecial;
    }

    public static function getResearchStatus(): array {
        return self::$researchStatus;
    }

    public static function getSectors(): array {
        return self::$sectors;
    }

    public static function getSoldierStatus(): array {
        return self::$soldierStatus;
    }
    
    public static function getStatus() : array {
        return self::$status;
    }

    public static function getVipStatus(): array {
        return self::$vipStatus;
    }

    public static function getVipType(): array {
        return self::$vipType;
    }

    public static function arrayYesNo(): array {
        // Create an Array with Yes (1) or No (0) values
        return array(
            array(
                'text' => "Yes",
                'value' => 1,
            ),
            array(
                'text' => "No",
                'value' => 0,
            ),
        );
    }

    public static function arrayGender(): array {
        // Create an Array with Male (0) or Female (1)
        return array(
            array(
                'text' => "Male",
                'value' => 0,
            ),
            array(
                'text' => "Female",
                'value' => 1,
            ),
        );
    }

    // Function to turn one-dimensional array into a two-dimensional array
    public static function oneToTwo(array $array): array {
        $oneArray = $array;
        $twoArray = array();

        foreach($oneArray as $list) {
            $newArray = array(
                'text' => $list,
                'value' => $list,
            );

            array_push($twoArray, $newArray);
        }

        return $twoArray;
    }

    public static array $country = array (
        "ADVENT",
        "Afghanistan",
        "Albania",
        "Algeria",
        "Andorra",
        "Angola",
        "Antigua and Barbuda",
        "Argentina",
        "Armenia",
        "Australia",
        "Austria",
        "Azerbaijan",
        "Bahamas",
        "Bahrain",
        "Bangladesh",
        "Barbados",
        "Belarus",
        "Belgium",
        "Belize",
        "Benin",
        "Bhutan",
        "Bolivia",
        "Bosnia and Herzegovina",
        "Botswana",
        "Brazil",
        "Brunei",
        "Bulgaria",
        "Burkina Faso",
        "Burundi",
        "Cambodia",
        "Cameroon",
        "Canada",
        "Cape Verde",
        "Central African Republic",
        "Chad",
        "Chile",
        "China",
        "Colombia",
        "Comoros",
        "Congo",
        "Costa Rica",
        "Côte d’Ivoire",
        "Croatia",
        "Cuba",
        "Cyprus",
        "Czech Republic",
        "DR Congo",
        "Denmark",
        "Djibouti",
        "Dominica",
        "Dominican Republic",
        "East Timor",
        "Ecuador",
        "Egypt",
        "El Salvador",
        "England",
        "Equatorial Guinea",
        "Eritrea",
        "Estonia",
        "Eswatini",
        "Ethiopia",
        "EXALT",
        "Fiji",
        "Finland",
        "France",
        "Gabon",
        "Gambia",
        "Georgia",
        "Germany",
        "Ghana",
        "Greece",
        "Grenada",
        "Guatemala",
        "Guinea",
        "Guinea-Bissau",
        "Guyana",
        "Haiti",
        "Honduras",
        "Hong Kong",
        "Hungary",
        "Iceland",
        "India",
        "Indonesia",
        "Iran",
        "Iraq",
        "Ireland",
        "Israel",
        "Italy",
        "Jamaica",
        "Japan",
        "Jordan",
        "Kazakhstan",
        "Kenya",
        "Kiribati",
        "Kosovo",
        "Kuwait",
        "Kyrgyzstan",
        "Laos",
        "Latvia",
        "Lebanon",
        "Lesotho",
        "Liberia",
        "Libya",
        "Liechtenstein",
        "Lithuania",
        "Luxembourg",
        "Madagascar",
        "Malawi",
        "Malaysia",
        "Maldives",
        "Mali",
        "Malta",
        "Marshall Islands",
        "Mauritania",
        "Mauritius",
        "Mexico",
        "Micronesia",
        "Moldova",
        "Monaco",
        "Mongolia",
        "Montenegro",
        "Morocco",
        "Mozambique",
        "Myanmar",
        "Namibia",
        "Nauru",
        "Nepal",
        "Netherlands",
        "New Zealand",
        "Nicaragua",
        "Niger",
        "Nigeria",
        "North Korea",
        "Northern Ireland",
        "North Macedonia",
        "Norway",
        "Oman",
        "Pakistan",
        "Palau",
        "Palestine",
        "Panama",
        "Papua New Guinea",
        "Paraguay",
        "Peru",
        "Philippines",
        "Poland",
        "Portugal",
        "Puerto Rico",
        "Qatar",
        "Romania",
        "Russia",
        "Rwanda",
        "Saint Kitts and Nevis",
        "Saint Lucia",
        "Saint Vincent and the Grenadines",
        "Samoa",
        "San Marino",
        "Saudi Arabia",
        "Scotland",
        "Senegal",
        "Serbia",
        "Seychelles",
        "Sierra Leone",
        "Singapore",
        "Slovakia",
        "Slovenia",
        "Solomon Islands",
        "Somalia",
        "South Africa",
        "South Korea",
        "South Sudan",
        "Spain",
        "Sri Lanka",
        "Sudan",
        "Suriname",
        "Sweden",
        "Switzerland",
        "Syria",
        "São Tomé and Príncipe",
        "Taiwan",
        "Tajikistan",
        "Tanzania",
        "Thailand",
        "Togo",
        "Tonga",
        "Trinidad and Tobago",
        "Tunisia",
        "Turkey",
        "Turkmenistan",
        "Tuvalu",
        "Uganda",
        "Ukraine",
        "United Arab Emirates",
        "United Kingdom",
        "United States",
        "Uruguay",
        "Uzbekistan",
        "Vanuatu",
        "Vatican City",
        "Venezuela",
        "Vietnam",
        "Wales",
        "XCOM",
        "Yemen",
        "Zambia",
        "Zimbabwe"
    );

    public static function getCountry(): array {
        return self::$country;
    }
}