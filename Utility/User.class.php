<?php

declare(strict_types = 1);

namespace XCOMDatabank\Utility;

use PDO;

class User {
	
	public ?int $id;
	public string $username;
	public string $pwhash;
	public string $level;
	public ?string $charTable;
	public ?int $charId;
	public string $message;
	private string $url = "https://admin.xcom-databank.games";
	
	function __construct() {
		$this->id = null;
		$this->username = "";
		$this->pwhash = "";
		$this->level = "basic";
		$this->charTable = null;
		$this->charId = null;
	}
	
	public function newUser($user): void {
        $this->validateUser($user);

        $query = "INSERT INTO xcom_users VALUES (NULL, :username, :password, :level, :charTable, :charID)";
        $params = $this->getParams();

        $queryResult = Database::runQuery('insert', $query, $params);

        $this->id = $queryResult;
	}
	
	public function editUser($user) {
        $this->validateUser($user);

        $query = "UPDATE xcom_objective SET mission_type_id = :missionType, description = :description, enabled = :enabled, notes = :notes WHERE id = :id";
        $params = $this->getParams();
        $params[5] = array("param" => ":id", "var" => $this->id, "type" => PDO::PARAM_INT,);

        Database::runQuery('update', $query, $params);
	}
	
	public function userLogin($user) {

        $query = "SELECT * FROM xcom_users WHERE user_name = :username";
        $params[0] = array("param" => ":username", "var" => $user['username'], "type" => PDO::PARAM_STR,);

        $queryResult = Database::runQuery('select', $query, $params);
		
		if($queryResult->rowCount() == 1) {
			$row = $queryResult->fetch();
			$this->username = $user['username'];
			$this->level = $row['user_level'];
			if(password_verify($user['password'], $row['user_pwd'])) {
				session_regenerate_id();
				$_SESSION["username"] = $this->username;
				$_SESSION["level"] = $this->level;
				$_SESSION['LAST_ACTIVITY'] = time();
				header('Location: '.$this->url.'/index.php');
				exit();
			}
			else {
				header('Location: '.$this->url.'/login.php?login=invalid');
				exit();
			}
		} else {
			header('Location: '.$this->url.'/login.php?login=invalid');
			exit();
		}
	}

    private function validateUser(array $submit): void {
        // If a User ID was submitted, make sure it is valid
        if(isset($submit['id'])) {
            $this->id = Validate::testIndex($submit['id'], 'users', false, "User ID");
        }

        // Test Username
        $this->username = Validate::testUsername($submit['username']);

        // Test Password
        $this->pwhash = Validate::testPassword($submit['password']);
    }

    private function getParams(): array {
        $params[0] = array("param" => ":username", "var" => $this->username, "type" => PDO::PARAM_STR,);
        $params[1] = array("param" => ":password", "var" => $this->pwhash, "type" => PDO::PARAM_STR,);
        $params[2] = array("param" => ":level", "var" => $this->level, "type" => PDO::PARAM_STR,);
        $params[3] = array("param" => ":charTable", "var" => $this->charTable, "type" => PDO::PARAM_NULL,);
        $params[4] = array("param" => ":charID", "var" => $this->charId, "type" => PDO::PARAM_NULL,);
        return $params;
    }
	
	public function userLogout() {
		session_unset();
		session_destroy();
	}
	
}