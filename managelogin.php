<?php
use XCOMDatabank\Utility\User;

if(isset($_POST['submit'])) {
	
	$user = new User;
	$userinfo = [];
	
	if($_POST['type'] == "login") {
		$userinfo['username'] = $_POST['username'];
		$userinfo['password'] = $_POST['password'];
		$user->userLogin($userinfo);
	}
	elseif($_POST['type'] == "signup") {
		$userinfo['username'] = $_POST['username'];
		$userinfo['password'] = $_POST['password'];
		$user->newUser($userinfo);
	}
	elseif($_GET['type'] == "logout") {
		$user->userLogout();
	}
	else {
		$user->message = "There was an error submitting your form. Please try again.";
		header('Location: https://xcom-databank.games/admin/login.php');
		exit();
	}
	
} else {
	 if(!($_SERVER["REQUEST_URI"] == "/admin/login.php" or $_SERVER["REQUEST_URI"] == "/admin/signup.php")) {
		header('Location: https://xcom-databank.games/admin/login.php');
		exit();
	}
}