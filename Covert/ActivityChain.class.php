<?php
declare(strict_types = 1);

namespace XCOMDatabank\Covert;

use PDO;
use XCOMDatabank\Forms\FieldHidden;
use XCOMDatabank\Forms\FieldSelect;
use XCOMDatabank\Forms\FieldText;
use XCOMDatabank\Forms\FieldTextNumDate;
use XCOMDatabank\Management\Info;
use XCOMDatabank\Utility\Database;
use XCOMDatabank\Utility\Definitions;
use XCOMDatabank\Utility\Error;
use XCOMDatabank\Utility\Validate;

class ActivityChain
{
	public ?int $id;
	public int $type;					// Activity Change Type (e.g. Counter Dark Event) - will be the id of the type from xcom_activity_chain_type
	public string $title;					// Flavor text to hopefully uniquely describe the current Activity Chain (e.g. "Rescue Engineer, Dr. Sophie Hoffman")
	public string $status;					// Completed, Ongoing, Failed, Abandoned (special case if it seems clear Odd is not going to finish one, so it's not left dangling)
	public string $startDate;				// Overall Chain Start Date
	public ?string $endDate;				// Overall Chain In date - should be allowed to be null if ongoing
		
	function __construct() {
		// Set Default Values
        $this->id = null;
		$this->type = -1;
		$this->title = "";
		$this->status = "";
        $this->startDate = Info::getCurrentDateShort();
		$this->endDate = null;
	}
		
	public function newActivityChain($activityChain) {
        $this->validateActivityChain($activityChain);

        $query = "INSERT INTO xcom_activity_chain VALUES (NULL, :type, :title, :status, :startDate, :endDate)";
        $params = $this->getParams();

        $queryResult = Database::runQuery('insert', $query, $params);

        $this->id = $queryResult;
	}
			
	public function getActivityChain(int $id) {
        $query = "SELECT * FROM xcom_activity_chain WHERE id = :id";
        $params[0] = array("param" => ":id", "var" => $id, "type" => PDO::PARAM_INT,);

        $queryResult = Database::runQuery('select', $query, $params);
        $row = $queryResult->fetch();
		
		$this->id = $id;
		$this->type = intval($row['chain_type']);
		$this->title = $row['title'];
		$this->status = $row['status'];
		$this->startDate = $row['start_date'];
		$this->endDate = $row['end_date'];
	}
		
	public function editActivityChain($activityChain) {
        $this->validateActivityChain($activityChain);

        $query = "UPDATE xcom_activity_chain SET chain_type = :type, title = :title, status = :status, start_date = :startDate, 
                        end_date = :endDate WHERE id = :id";
        $params = $this->getParams();
        $params[5] = array("param" => ":id", "var" => $this->id, "type" => PDO::PARAM_INT,);

        Database::runQuery('update', $query, $params);
	}

    private function validateActivityChain(array $submit): void {
        // If an Activity Chain ID was submitted, make sure it is valid
        if(isset($submit['id'])) {
            $this->id = Validate::testIndex($submit['id'], 'activity_chain', false, "Activity Chain ID");
        }

        // Activity Chain
        $this->type = Validate::testIndex($submit['type'], 'activity_chain_type', false, "Activity Chain Type");

        // Title
        $this->title = Validate::testString($submit['title'], -1, -1, false, "Activity Chain Title");

        // Status
        $this->status = Validate::testArray($submit['status'], Definitions::getChainStatus(), false, "Activity Chain Status");

        // Start Date
        $this->startDate = Validate::testDate($submit['start_date'], false, "Activity Chain Start Date");

        // End Date
        $this->endDate = Validate::testDate($submit['end_date'], true, "Activity Chain End Date");
    }

    public function getParams(): array {
        $params[0] = array("param" => ":type", "var" => $this->type, "type" => PDO::PARAM_INT,);
        $params[1] = array("param" => ":title", "var" => $this->title, "type" => PDO::PARAM_STR,);
        $params[2] = array("param" => ":status", "var" => $this->status, "type" => PDO::PARAM_STR,);
        $params[3] = array("param" => ":startDate", "var" => $this->startDate, "type" => PDO::PARAM_STR,);
        $params[4] = array("param" => ":endDate", "var" => $this->endDate ?? null, "type" => PDO::PARAM_STR,);
        return $params;
    }

    public function processForm(array $submit, string $redirect = ''): string {
        if(isset($submit['id'])) {
            if(is_numeric($submit['id'])) {
                $this->editActivityChain($submit);
            } else {
                return Error::returnError("Covert Chain ID is set, but Covert Chain ID is not numeric");
            }
        } else {
            $this->newActivityChain($submit);
        }
        //header('Location: '.$redirect);
        return "";
    }

    public static function getActivityChainForm(ActivityChain $chain) {
        if(!empty($chain->id) and is_numeric($chain->id)) {
            echo FieldHidden::getField('id',strval($chain->id));
        }

        echo FieldSelect::GetField('type', strval($chain->type), 'form-control', 'type',
            'Chain Type', array('col-md-6','pb-2','form-floating'), true, ActivityChainType::getActivityChainTypeArray());

        echo FieldText::getField('title', $chain->title, 'form-control', 'title',
            "Chain Title", array('col-md-6', 'pb-2', 'form-floating'), true);


        echo FieldSelect::GetField('status', $chain->status, 'form-control', 'status',
            'Status', array('col-md-4','pb-2','form-floating'), true, Definitions::getChainStatus());

        echo FieldTextNumDate::GetField('start_date', $chain->startDate, 'form-control', 'start-date',
            'Start Date', array('col-md-4','pb-2','form-floating'), true, false,'2035-02-28', '2037-12-31');

        echo FieldTextNumDate::GetField('end_date', $chain->endDate ?? $chain->startDate, 'form-control', 'end-date',
            'End Date', array('col-md-4','pb-2','form-floating'), false, false,'2035-02-28', '2037-12-31');
    }

    public static function getStepsForm(ActivityChain $chain): void {
        $query = "SELECT step.id 
                FROM xcom_activity_chain_steps as step
                    INNER JOIN xcom_activity_chain as chain ON chain.id = step.activity_chain
            WHERE chain.id = :chainID";
        $params[0] = array("param" => ":chainID", "var" => $chain->id, "type" => PDO::PARAM_INT,);

        $queryResult = Database::runQuery('select', $query, $params);

        echo '<div class="repeat-parent col-12">'."\n";

        if($queryResult->rowCount() == 0) {
            $chainRows[0] = null;
        } else {
            $chainRows = $queryResult->fetchAll();
        }

        foreach($chainRows as $step) {
            $newStep = new ActivityChainStep();
            if($step > 0) {
                $newStep->getActivityChainStep(intval($step['id']));
                echo FieldHidden::getField('step_id[]',strval($newStep->id));
            }

            echo '<div class="field-repeat mission-info row gx-0 gy-3">'."\n";
            ActivityChainStep::getActivityChainStepForm($newStep);
            echo '</div>'."\n";
        }

        echo '</div>';
    }

    public static function getListPage(): void {
        $query = "SELECT * FROM xcom_activity_chain ORDER BY FIELD(status, 'Ongoing', 'Completed', 'Abandoned', 'Failed'), start_date desc";
        $params = array();

        $queryResult = Database::runQuery('select', $query, $params);

        $listString = '<div class="container admin-table info-list">'."\n";
        $listString .= '<div class="row head-row">'."\n";
        $listString .= '<div class="col-3">Type</div>'."\n";
        $listString .= '<div class="col-3">Title</div>'."\n";
        $listString .= '<div class="col-3">Status</div>'."\n";
        $listString .= '<div class="col-3">Dates</div>'."\n";
        $listString .= '</div>'."\n";

        while ($row = $queryResult->fetch()) {
            $rowClass = '';
            if ($row['status'] == "Completed") {
                $rowClass = "row-green";
            } elseif ($row['status'] == "Ongoing") {
                $rowClass = "row-yellow";
            } elseif ($row['status'] == "Abandoned") {
                $rowClass = "row-orange";
            } elseif ($row['status'] == "Failed") {
                $rowClass = "row-red";
            }

            $currentChain = new ActivityChain;
            $currentChain->getActivityChain(intval($row['id']));

            $listString .= '<div class="row '.$rowClass.'">'."\n";
            $listString .= '<div class="col-3">';
            $listString .= $currentChain->chainType();
            $listString .= '</div>'."\n";

            $listString .= '<div class="col-3">';
            $listString .= '<a href="/covert/chain.php?id='.$currentChain->id.'">'.$currentChain->title.'</a>';
            $listString .= '</div>'."\n";

            $listString .= '<div class="col-3">';
            $listString .= $currentChain->status;
            $listString .= '</div>'."\n";

            $listString .= '<div class="col-3">';
            $listString .= $currentChain->startDate.' - '.$currentChain->endDate;
            $listString .= '</div>'."\n";
            $listString .= '</div>'."\n";

        }
        $listString .= '</div>'."\n";

        echo $listString;
    }
	
	// Get the text name of the Chain Type based on the Chain Type ID
	public function chainType(): string {
        $query = "SELECT type.name FROM xcom_activity_chain_type as type 
				INNER JOIN xcom_activity_chain as chain ON type.id = chain.chain_type
			WHERE chain.id = :id";
        $params[0] = array("param" => ":id", "var" => $this->id, "type" => PDO::PARAM_INT,);

        $queryResult = Database::runQuery('select', $query, $params);

		$row = $queryResult->fetch();
		
		return $row['name'];
	}
	
	// Send an array of objects of type ActivityChainStep representing all the steps in this Activity Chain
	public function chainSteps(): array {
        $query = "Select * FROM xcom_activity_chain_steps WHERE activity_chain = :id ORDER BY step";
        $params[0] = array("param" => ":id", "var" => $this->id, "type" => PDO::PARAM_INT,);

        $queryResult = Database::runQuery('select', $query, $params);
		
		$stepArray = array();
		while ($row = $queryResult->fetch()) {
			$step = new ActivityChainStep();
			$step->getActivityChainStep(intval($row['id']));
			array_push($stepArray, $step);
		}
		return $stepArray;
	}
}