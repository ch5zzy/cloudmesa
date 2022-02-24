<?php
//Get the requested function
$func = $_POST["func"];

session_start();
$_SESSION["LAST_ACTIVITY"] = time();

function isSubDir($path, $parent_folder) {
    $dir = dirname($path);
    $folder = substr($path, strlen($dir));

    $dir = realpath($dir);
    if($dir === FALSE OR $folder === FALSE OR $folder === '.') {
        return FALSE;
    }

    $path = $dir . DIRECTORY_SEPARATOR . $folder;
    if(strcasecmp($path, $parent_folder) > 0) {
        return TRUE;
    }

    return FALSE;
}

function preventUnauth($dirOp = NULL) {
	$safeDir = realpath(getcwd() . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . "data");
	if($_SESSION["write"])
		$safeDir = realpath($safeDir . DIRECTORY_SEPARATOR . $_SESSION["username"]);
	
	if($dirOp == NULL)
		$dirOp = $safeDir;
	if(!isSubDir($dirOp, $safeDir) and $dirOp != $safeDir) {
		die("unauthorized");
	}
}

function writeOnly() {
	if($_SESSION["write"] == 0) {
		die("unauthorized");
	}
}

//Define delete function to be used in various others
function delete($file) {
	$file = realpath($file);
	preventUnauth($file);

	function remove_dir($dir) {
		$files = array_diff(scandir($dir), array("..", "."));
		foreach($files as $f) {
			if(is_dir($dir . DIRECTORY_SEPARATOR . $f)) {
				remove_dir($dir . DIRECTORY_SEPARATOR . $f);
			} else {
				unlink($dir . DIRECTORY_SEPARATOR . $f);
			}
		}
		rmdir($dir);
	}

	if(is_dir($file)) {
		remove_dir($file);
	} else {
		unlink($file);
	}
}

//List all files and directories
if($func == "list") {
	$dir = $_POST["dir"];
	if($dir == "") {
		if(!$_SESSION["write"])
			$dir = "../data"; //if read only, give them access to all files
		else
			$dir = "../data/" . $_SESSION["username"];
		if(!file_exists($dir)) mkdir($dir);
	}
	$dir = realpath($dir);
	
	preventUnauth($dir);
	
	$folders = array();
	$files = array();

	function dir_size($dr) {
		$sz = filesize($dr);
		$fls = array_diff(scandir($dr), array(".", ".."));
		foreach($fls as $f) {
			if(is_dir($dr . DIRECTORY_SEPARATOR . $f)) {
				$sz += dir_size($dr . DIRECTORY_SEPARATOR . $f);
			} else {
				$sz += filesize($dr . DIRECTORY_SEPARATOR . $f);
			}
		}
		return $sz;
	}

	foreach(scandir($dir) as $file) {
		$f = array();
		$f["name"] = $file;
		$f["type"] = filetype($dir . DIRECTORY_SEPARATOR . $file);
		$f["ext"] = pathinfo($file, PATHINFO_EXTENSION);
		$f["mime"] = mime_content_type($dir . DIRECTORY_SEPARATOR . $file);
		if(!is_dir($dir . DIRECTORY_SEPARATOR . $file)) {
			$f["size"] = filesize($dir . DIRECTORY_SEPARATOR . $file);
		} else {
			$f["size"] = dir_size($dir . DIRECTORY_SEPARATOR . $file);
		}

		if($f["type"] == "dir") {
			array_push($folders, $f);
		} else {
			array_push($files, $f);
		}
	}

	sort($folders);
	sort($files);
	$dirData = array_merge($folders, $files);
	array_push($dirData, realpath($dir));
	array_push($dirData, disk_free_space("/"));

	echo json_encode($dirData);
}

//Get available MIME type icons
if($func == "icons") {
	$icons = array();
	foreach(scandir("../icons") as $icon) {
		array_push($icons, strtolower($icon));
	}
	echo json_encode($icons);
}

//Copy/cut a file
if($func == "copy") {
	writeOnly();
	$file = $_POST["file"];
	$newDir = $_POST["dir"];
	if($newDir == "")
		$newDir = "../data/";
	$file = realpath($file);
	$newDir = realpath($newDir);
	
	preventUnauth($newDir);
	
	if(isSubDir($newDir, $file) or $newDir == $file)
		die("sub");
	
	$filePos = $newDir . DIRECTORY_SEPARATOR . basename($file);

	function xcopy($currPos, $newPos) {
		mkdir($newPos);
		$files = array_diff(scandir($currPos), array("..", "."));
		foreach($files as $f) {
			if(is_dir($currPos . DIRECTORY_SEPARATOR . $f)) {
				xcopy($currPos . DIRECTORY_SEPARATOR . $f, $newPos . DIRECTORY_SEPARATOR . $f);
			} else {
				copy($currPos . DIRECTORY_SEPARATOR . $f, $newPos . DIRECTORY_SEPARATOR . $f);
			}
		}
	}

	if(!is_dir($file)) {
		//File copy
		if(file_exists($filePos)) {
			echo "exists"; //File exists;
		} else {
			copy($file, $filePos);
			if($_POST["cut"] == "true") {
				unlink($file);
			}
			echo "success";
		}
	} else {
		if(file_exists($filePos)) {
			echo "exists"; //File exists;
		} else {
			xcopy($file, $filePos);
			if($_POST["cut"] == "true") {
				delete($file);
			}
			echo "success";
		}
	}
}

//Delete a file/directory
if($func == "delete") {
	writeOnly();
	preventUnauth(realpath($_POST["file"]));
	delete($_POST["file"]);
}

//Rename a file/directory
if($func == "rename") {
	writeOnly();
	$file = $_POST["file"];
	preventUnauth(realpath($file));
	$newName = $_POST["newName"];
	if(pathinfo($newName, PATHINFO_EXTENSION) == "" && !is_dir($file))
		$newName .= "." . pathinfo($file, PATHINFO_EXTENSION);
	rename($file, dirname($file) . DIRECTORY_SEPARATOR . $newName);
}

//Decompress an archive
if($func == "decompress") {
	writeOnly();
	$file = $_POST["file"];
	preventUnauth(realpath($file));
	if(`which 7z`) {
		exec("7z x \"" . $file . "\" -o\"" . dirname($file) . DIRECTORY_SEPARATOR . pathinfo($file, PATHINFO_FILENAME) . "\" -aou", $output); //decompress while keeping directory structure and renaming existing files
		echo json_encode($output);
	} else {
		echo "notinst";
	}
}

//Compress an archive
if($func == "compress") {
	writeOnly();
	$file = $_POST["file"];
	preventUnauth(realpath($file));
	if(`which 7z`) {
		exec("7z a \"" . dirname($file) . DIRECTORY_SEPARATOR . pathinfo($file, PATHINFO_FILENAME) . ".zip\" \"" . $file . DIRECTORY_SEPARATOR . "\"", $output); //compress folder
		echo json_encode($output);
	} else {
		echo "notinst";
	}
}

//Create a new directory
if($func == "newdir") {
	writeOnly();
	$dir = $_POST["dir"];
	preventUnauth(realpath($dir));
	$name = "New directory";

	if(isset($_POST["name"]) && trim($_POST["name"]) != "") {
		$name = $_POST["name"];
	}

	$dirPos = $dir . DIRECTORY_SEPARATOR . $name;
	$dirCount = 1;
	while(file_exists($dirPos)) {
		$dirPos = $dir . DIRECTORY_SEPARATOR .  $name . " (" . $dirCount . ")";
		$dirCount++;
	}
	mkdir($dirPos);

	echo json_encode($resData);
}

//Upload a file
if($func == "upload") {
	writeOnly();
	$dir = $_POST["dir"];
	if($dir == "")
		$dir = "../data/";
	$dir = realpath($dir);
	//preventUnauth($dir);
	$resData = array();

	$filePos = $dir . DIRECTORY_SEPARATOR . $_FILES["file"]["name"];
	if(isset($_POST["name"])) $filePos = $dir . DIRECTORY_SEPARATOR . $_POST["name"];

	if(file_exists($filePos)) {
		unlink($filePos);
	}
	move_uploaded_file($_FILES["file"]["tmp_name"], $filePos);

	echo json_encode($resData);
}
?>
