<?php
declare(strict_types = 1);

namespace XCOMDatabank\Management;

use PDO;
use XCOMDatabank\Forms\FieldHidden;
use XCOMDatabank\Forms\FieldSelect;
use XCOMDatabank\Forms\FieldText;
use XCOMDatabank\Forms\FieldTextarea;
use XCOMDatabank\Forms\FieldTextNumDate;
use XCOMDatabank\Utility\Database;
use XCOMDatabank\Utility\Definitions;
use XCOMDatabank\Utility\Error;
use XCOMDatabank\Utility\Validate;

class Research
{
	public ?int $id;
		
	public string $name;
	public string $startDate;
	public string $endDate;
	public string $facility;
	public string $status;
	public ?string $codename;
	public ?string $special;
    public bool $enabled;
    public ?string $notes;
		
	function __construct() {
        $this->id = null;
		$this->name = "";
		$this->facility = "Research";
		$this->status = "";
		$this->codename = null;
		$this->special = null;
        $this->enabled = true;
        $this->notes = null;

        $query = "SELECT end_date FROM xcom_research ORDER BY end_date DESC LIMIT 1";
        $params = array();
        $queryResult = Database::runQuery('select', $query, $params);

		if($queryResult->rowCount() === 0) {
			$this->startDate = "2035-02-28";
		}
		else {
			$row = $queryResult->fetch();
			$this->startDate = $row['end_date'];
		}
		
		$this->endDate = $this->startDate;
	}
		
	public function newResearch($research) {
        $this->validateResearch($research);

        $query = "INSERT INTO xcom_research VALUES (NULL, :name, :startDate, :endDate, :facility, :status, :codename, :special, :enabled, :notes)";
        $params = $this->getParams();

        $queryResult = Database::runQuery('insert', $query, $params);

        $this->id = $queryResult;
	}
		
	public function getResearch(int $id) {
        $query = "SELECT * FROM xcom_research WHERE id = :id";
        $params[0] = array("param" => ":id", "var" => $id, "type" => PDO::PARAM_INT,);

        $queryResult = Database::runQuery('select', $query, $params);
        $row = $queryResult->fetch();
		
		$this->id = $id;
		$this->name = $row['name'];
		$this->startDate = $row['start_date'];
		$this->endDate = $row['end_date'];
		$this->facility = $row['facility'];
		$this->status = $row['status'];
		$this->codename = $row['codename'];
		$this->special = $row['special'];
        $this->enabled = boolval($row['enabled']);
        $this->notes = $row['notes'];
	}
		
	public function editResearch($research) {
        $this->validateResearch($research);

        $query = "UPDATE xcom_research SET name = :name, start_date = :startDate, end_date = :endDate, facility = :facility, status = :status, codename = :codename, special = :special, enabled = :enabled, notes = :notes WHERE id = :id";
        $params = $this->getParams();
        $params[9] = array("param" => ":id", "var" => $this->id, "type" => PDO::PARAM_INT,);

        Database::runQuery('update', $query, $params);
	}

    private function validateResearch(array $submit): void {
        // If an Info ID was submitted, make sure it is valid
        if(isset($submit['id'])) {
            $this->id = Validate::testIndex($submit['id'], 'research', false, "Research ID");
        }

        // Make sure name is a valid, non-null string
        $this->name = Validate::testString($submit['name'], -1, -1, false, "Research Name");

        // Make sure facility is a valid facility
        $this->facility = Validate::testArray($submit['facility'], Definitions::getResearchFacility(), false, "Research Facility");

        // Make sure status is a valid status
        $this->status = Validate::testArray($submit['status'], Definitions::getResearchStatus(), false, "Research Status");

        // Make sure codename is a valid string or null
        $this->codename = Validate::testString($submit['codename'], -1, -1, true, "Research Codename");

        // Make sure special status is valid or null
        $this->special = Validate::testArray($submit['special'] ?? null, Definitions::getResearchSpecial(), true, "Research Special Status");

        // Make sure start date is valid
        $this->startDate = Validate::testDate($submit['start_date'], false, "Research Start Date");

        // Make sure end date is valid
        $this->endDate = Validate::testDate($submit['end_date'], false, "Research End Date");

        // Make sure enabled is True or False
        $this->enabled = Validate::testTF($submit['enabled'], false, "Research Enabled");

        // Make sure notes is a valid string or null
        $this->notes = Validate::testString($submit['notes'], -1, -1, true, "Research Notes");

        if($this->status == "Complete") {
            self::updateUnlockedDates();
        }
    }

    public function getParams(): array {
        $params[0] = array("param" => ":name", "var" => $this->name, "type" => PDO::PARAM_STR,);
        $params[1] = array("param" => ":startDate", "var" => $this->startDate, "type" => PDO::PARAM_STR,);
        $params[2] = array("param" => ":endDate", "var" => $this->endDate, "type" => PDO::PARAM_STR,);
        $params[3] = array("param" => ":facility", "var" => $this->facility, "type" => PDO::PARAM_STR,);
        $params[4] = array("param" => ":status", "var" => $this->status, "type" => PDO::PARAM_STR,);
        $params[5] = array("param" => ":codename", "var" => $this->codename, "type" => PDO::PARAM_STR,);
        $params[6] = array("param" => ":special", "var" => $this->special, "type" => PDO::PARAM_STR,);
        $params[7] = array("param" => ":enabled", "var" => $this->enabled, "type" => PDO::PARAM_BOOL,);
        $params[8] = array("param" => ":notes", "var" => $this->notes, "type" => PDO::PARAM_STR,);
        return $params;
    }

    public function processForm(array $submit, string $redirect): string {
        if(isset($submit['id'])) {
            if(is_numeric($submit['id'])) {
                $this->editResearch($submit);
            } else {
                return Error::returnError("Info ID is set, but Info ID is not numeric");
            }
        } else {
            $this->newResearch($submit);
        }
        header('Location: '.$redirect);
        return "";
    }

    public static function getResearchForm(Research $research) {
        if(!empty($research->id) and is_numeric($research->id)) {
            echo FieldHidden::getField('id',strval($research->id));
        }

        echo FieldText::getField('name', $research->name, 'form-control', 'name',
            'Name', array('col-md-3', 'pb-2', 'form-floating'), true);

        echo FieldTextNumDate::getField('start_date', $research->startDate, 'form-control', 'start-date',
            'Start Date', array('col-md-3','pb-2','form-floating'), true);

        echo FieldTextNumDate::getField('end_date', $research->endDate, 'form-control', 'end-date',
            'End Date', array('col-md-3','pb-2','form-floating'), true);

        echo FieldSelect::getField('facility', $research->facility, 'form-control', 'facility',
            'Facility', array('col-md-3','pb-2','form-floating'), true, Definitions::getResearchFacility());

        echo FieldSelect::getField('status', $research->status, 'form-control', 'status',
            'Status', array('col-md-3','pb-2','form-floating'), true, Definitions::getResearchStatus());

        echo FieldText::getField('codename', $research->codename, 'form-control', 'type',
            'Codename', array('col-md-3','pb-2','form-floating'), false);

        echo FieldSelect::getField('special', $research->special, 'form-control', 'special',
            'Special Status', array('col-md-3','pb-2','form-floating'), false, Definitions::getResearchSpecial());

        echo FieldSelect::getField('enabled', strval($research->enabled), 'form-control', 'enabled',
            'Enabled?', array('col-md-3','pb-2','form-floating'), true, Definitions::arrayYesNo());

        echo FieldTextarea::getField('notes', $research->notes, 'form-control', 'notes', 'Notes',
            array('col-md-4','pb-2','form-floating'), false, '5');
    }

    public static function getListPage(): void {
        $query = "SELECT * FROM `xcom_research` ORDER BY
                                enabled DESC,
								CASE
									when status = 'In Progress' then 1
									when status = 'Unlocked' then 3
									when status = 'Locked' then 4
									else 2
								END, end_date DESC, start_date DESC, name";
        $params = array();

        $queryResult = Database::runQuery('select', $query, $params);

        $listString = '<div class="container admin-table research-list">'."\n";
        $listString .= '<div class="row head-row">'."\n";
        $listString .= '<div class="col-4">Name</div>'."\n";
        $listString .= '<div class="col-4">Dates</div>'."\n";
        $listString .= '<div class="col-2">Status</div>'."\n";
        $listString .= '<div class="col-2">Enabled?</div>'."\n";
        $listString .= '</div>'."\n";

        foreach ($queryResult as $row) {
            $researchColor = "row-lgray";
            if($row['enabled'] == 0) {
                $researchColor = "row-red";
            } else {
                if($row['status'] == "Complete") {
                    if($row['facility'] == "Research") {
                        $researchColor = "row-green";
                    } else {
                        $researchColor = "row-purple";
                    }
                }
                elseif($row['status'] == "In Progress") {
                    $researchColor = "row-yellow";
                }
                elseif($row['status'] == "Unlocked") {
                    $researchColor = "row-blue";
                }
                elseif($row['status'] == "Paused") {
                    $researchColor = "row-orange";
                }
            }

            $listString .= '<div class="row '.$researchColor.'">'."\n";
            $listString .= '<div class="col-4">';
            $listString .= '<a href="/management/research.php?id='.$row['id'].'">'.$row['name'].' ('.$row['codename'].')</a>';
            $listString .= '</div>'."\n";

            $listString .= '<div class="col-4">';
            $listString .= $row['start_date'].' - '.$row['end_date'];
            $listString .= '</div>'."\n";

            $listString .= '<div class="col-2">';
            $listString .= $row['status'];
            $listString .= '</div>'."\n";

            $listString .= '<div class="col-2">';
            $listString .= $row['enabled'] ? "Yes" : "No";
            $listString .= '</div>'."\n";
            $listString .= '</div>'."\n";

        }
        $listString .= '</div>'."\n";

        echo $listString;
    }
	
	public static function getCurrentResearch() {
        $query = "SELECT * FROM xcom_research WHERE status = 'In Progress'";
        $params = array();
        $queryResult = Database::runQuery('select', $query, $params);

        if ($queryResult->rowCount() == 1) {
            $row = $queryResult->fetch();
        } else {
            $row['name'] = "Nome";
        }

        return $row['name'];
	}

    private static function updateUnlockedDates(): void {
        $query = "SELECT id FROM xcom_research WHERE status = 'Unlocked'";
        $params = array();
        $queryResult = Database::runQuery('select', $query, $params);

        foreach($queryResult as $row) {
            $query = "UPDATE xcom_research SET start_date = :date, end_date = :date WHERE id = :id";
            $params[0] = array("param" => ":date", "var" => Info::getCurrentDateShort(), "type" => PDO::PARAM_STR,);
            $params[1] = array("param" => ":id", "var" => $row['id'], "type" => PDO::PARAM_INT,);
            $queryResult = Database::runQuery('select', $query, $params);
        }
    }
}