<?php

// adminInclude.php
// Main include file for every page under the admin.xcom-databank.games domain. Must be called first thing on all pages.
	
// Error lines if I need to display them
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

date_default_timezone_set('America/New_York');

include_once 'mainInclude.php';
	
	// If the session is set
	if(isset($_SESSION['level'])) {
		// If session level is not a valid value, destroy the session and redirect to login page.
		// Otherwise, refresh session timeout
		if($_SESSION['level'] != "admin" and $_SESSION['level'] != "logger" and $_SESSION['level'] != "basic") { // could this even be checked against a db table, so I don't have to update this file if I add a new role?
			session_unset();
			session_destroy(); // destroy session if session has an invalid value for "level"
			header('Location: https://admin.xcom-databank.games/login.php'); // again, database configurable?
			exit();
		} else {
			if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > 1800)) {
				// last request was more than 30 minutes ago
				session_unset();     // unset $_SESSION variable for the run-time 
				session_destroy();   // destroy session data in storage
			}
			$_SESSION['LAST_ACTIVITY'] = time(); // update last activity time stamp
		}
		// If userLevel is logger, and they attempt to visit any page other than index, login, signup, or log-based pages
		if($_SESSION['level'] == "logger" and !($_SERVER["REQUEST_URI"] == "/login.php" or $_SERVER["REQUEST_URI"] == "/signup.php" or $_SERVER["REQUEST_URI"] == "/index.php" or $_SERVER["REQUEST_URI"] == "/log.php" or $_SERVER["REQUEST_URI"] == "/manage/logInfo.php")) {
			header('Location: https://admin.xcom-databank.games/index.php');
			exit();
		}
		// If userLevel is basic, and they attempt to visit any page other than the login, signup, or index pages, redirect them to the index page.
		if($_SESSION['level'] == "basic" and !($_SERVER["REQUEST_URI"] == "/login.php" or $_SERVER["REQUEST_URI"] == "/signup.php" or $_SERVER["REQUEST_URI"] == "/index.php")) {
			header('Location: https://admin.xcom-databank.games/login.php');
			exit();
		}
	}
	// If session is not set, automatically redirect them to the login page (unless they are already there or on the signup page)
	elseif(!($_SERVER["REQUEST_URI"] == "/login.php" or $_SERVER["REQUEST_URI"] == "/signup.php"))
	{
		header('Location: https://admin.xcom-databank.games/login.php');
		exit();
	}