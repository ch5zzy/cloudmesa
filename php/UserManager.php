<?php
$func = $_POST["func"];

$dbCon = new PDO("sqlite:wddata.db");
if($dbCon == null)
	die("Connection failed");

session_start();

if($func == "signin") {
	$username = $_POST["username"];
	$password = $_POST["password"];

	$userData = $dbCon->query("SELECT hash, write FROM users WHERE username='{$username}'")->fetchAll();

	if(!$userData) {
		$id = uniqid("u", true);
		$password = password_hash($password, PASSWORD_BCRYPT);

		$dbCon->query("INSERT INTO users (id, username, hash) VALUES ('{$id}', '{$username}', '{$password}')");
		$_SESSION["username"] = $username;
		$_SESSION["write"] = 1;
		echo true;
	} else foreach($userData as $user) {
		if(password_verify($password, $user["hash"])) {
			$_SESSION["username"] = $username;
			$_SESSION["write"] = $user["write"];
			echo true;
		} else {
			echo false;
		}
	}
}

if($func == "signout") {
	session_unset();
	session_destroy();
}

if($func == "get_users") {
	echo json_encode($dbCon->query("SELECT username FROM users")->fetchAll());
}

if($func == "data") {
	echo $_SESSION["username"];
}
?>
