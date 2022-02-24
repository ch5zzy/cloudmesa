<?php
session_start();

if(!isset($_SESSION["username"])) {
    header("Location: index.php");
    die("You may not access this page until you have signed in.");
}

//End the session (sign out after 30mins if user refreshes)
if (isset($_SESSION["LAST_ACTIVITY"]) && (time() - $_SESSION["LAST_ACTIVITY"] > 1800)) {
    session_unset();
    session_destroy();
}

$_SESSION["LAST_ACTIVITY"] = time();
?>

<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="initial-scale=1, width=device-width, height=device-height, viewport-fit=cover">
	<title>Cloudmesa</title>

	<!--[if lt IE 9]>
	<script src="http://html5shim.googlecode.com/svn/trunk/html5.js"></script>
	<![endif]-->

	<link rel="stylesheet" type="text/css" href="css/style.css">
	<link rel="icon" type="image/png" sizes="32x32" href="/favicon.png">
</head>
<body>
	<!--Scripts-->
	<script type="text/javascript" src="js/jsmediatags.min.js"></script>
	<script type="text/javascript" src="js/medium.js"></script>
	<script type="text/javascript" src="js/filebait.js"></script>

	<!--Topbar-->
	<div class="topbar">
		<div class="name">
      <div class="user-image" onClick="document.querySelector('#user-image-file').click()" title="Click to choose a new profile image.">
        <input type="file" id="user-image-file" accept="image/*" style="display: none" />
      </div>
			<span id="username"><?php echo $_SESSION["username"]; ?></span>
		</div>
    <!--Mobile user image-->
		<div class="content">
			<input id="search" type="text" placeholder="Search this directory" style="width: 100%; color: white" onkeyup="populateFiles(document.querySelector('#search').value)" autocomplete="off" />
		</div>
	</div>

	<!--ContentArea-->
	<div class="content-area">
		<!--Sidebar-->
		<div class="sidebar">
			<!--Toolbar-->
	   		<div class="toolbar">
	   			<a href="#" onClick="signOut()">Sign out</a>
	   		</div>

	   		<div class="content">
				<span><img src="icons/ui/server-disk.png"><span id="freeBytes"></span> free</span><br><br>
				<img src="icons/ui/home-folder.png"><a href="#" onClick="readDir()">Return to Home</a><br><br>
				<img src="icons/ui/open-folder.png"><a id="curDir" href="#"></a>
				<div id="subfolders" style="padding-left: 5%"></div>
			</div>
		</div>

		<!--Small screen toolbar-->
		<div class="mobile-toolbar">
			<a onClick="readDir()" href="#"><img src="icons/ui/home-folder.png"></a>
            <a onClick="newDir(true)" href="#">+<img src="icons/directory.png"></a>
			<a onClick="document.querySelector('#file-upload').click()" href="#"><img src="icons/ui/upload.png"></a>
			<a onClick="copyMode()" href="#"><img id="mobile-copy-paste" src="icons/ui/copy.png"></a>
			<a onClick="signOut()" href="#"><img src="icons/ui/sign-out.png"></a>
		</div>

		<!--FileArea-->
		<div class="file-area">
			<!--<a title="496 bytes" href="data/welcome.txt" class="file-ref" data-ref="/home/pi/www/webdesktop/data/welcome.txt"><img src="icons/file.png">welcome.txt<br></a>-->
		</div>

		<!--Loading popup-->
		<div id="progress-popup" class="bottom-right-area" style="display: none">
			<img src="progress.gif" style="width: 32px; height: 32px; padding-right: .5vw">
			<span id="op-progress"></span>
		</div>
	</div>

	<!--Context menus-->
	<!--File context menu-->
	<div id="file-context" class="context-menu">
		<ul>
			<li onClick="setClipboard(selectedElem.getAttribute('data-ref')); cut = true">Cut</li>
			<li onClick="setClipboard(selectedElem.getAttribute('data-ref')); cut = false; copyToClipboard(url + selectedElem.getAttribute('href'))">Copy</li>
			<li onClick="delFile(selectedElem.getAttribute('data-ref'))">Delete</li>
			<li onClick="showPopup('file-rename'); document.querySelector('#new-file-name').value = selectedElem.getAttribute('filename'); document.querySelector('#new-file-name').focus()">Rename</li>
			<li id="open-with-program" onClick="openWithProgram(selectedElem.getAttribute('href'), selectedElem.getAttribute('program-link'))" style="display: hidden">No program available</li>
		</ul>
	</div>
	<!--FileArea context menu-->
	<div id="file-area-context" class="context-menu">
		<ul>
			<li onClick="newDir()">Create new directory</li>
			<li id="pasteOption" onClick="if(clipboard != '') pasteFile(cut)" style="color: rgb(200, 200, 200)">Paste</li>
			<li onClick="document.querySelector('#background-file').click()"><input type="file" id="background-file" accept="image/*" style="display: none" />Change background</li>
		</ul>
	</div>
	<!--File rename popup-->
	<div id="file-rename" class="popup">
		<ul>
			<li><input id="new-file-name" type="text" placeholder="New name" autocomplete="off" /> <button onClick="rename(selectedElem.getAttribute('data-ref'), document.querySelector('#new-file-name').value); document.querySelector('#new-file-name').value = ''; hidePopup('file-rename')">Rename</button> <button class="false" onClick="hidePopup('file-rename')">Cancel</button></li>
		</ul>
	</div>

	<!--Special text input for clipboard-->
	<input type="text" id="clipboard-box" value="" style="width: 0px; height: 0px; display: none" />

	<!--Special file input for small screen devices-->
	<input type="file" id="file-upload" style="display: none" />

  <!--Medium for videos-->
  <div id="vid-preview" class="popover">
    <button class="false close-button" onClick="var vid = document.querySelector('#vid-preview'); vid.querySelector('video').setAttribute('src', ''); vid.style.display = 'none'; vid.querySelector('video').pause()">×</button>
    <video src="" controls>
      Your browser does not support Medium for videos.
    </video>
  </div>

  <!--Medium for pictures-->
  <div id="pic-preview" class="popover">
    <button class="false close-button" onClick="var pic = document.querySelector('#pic-preview'); pic.querySelector('img').setAttribute('src', ''); pic.style.display = 'none'">×</button>
    <img src="">
  </div>
  
  <!--Medium for audio-->
  <div id="audio-preview" class="popover">
    <button class="false close-button" onClick="var audioElem = document.querySelector('#audio-preview'); audioElem.style.display = 'none'">×</button>
	<canvas id="audio-player-visualizer"></canvas>
	<div class="audio-player-song-data">
		<div><img src="" id="audio-player-song-image"></img></div>
		<div id="audio-player-song-title"></div>
		<div id="audio-player-song-artist"></div>
	</div>
	<div class="audio-player-controls">
	  <h2><span id="audio-player-time"></span> / <span id="audio-player-track-length"></span></h2>
	  <input id="audio-player-seek" type="range" min="0" max="100" value="0" step="0.01" onchange="audioSeek()" />
	  <h1 id="audio-player-toggle" onclick="audioToggle()">play</h1>
    </div>
  </div>

	<!--Runtime scripts-->
	<script>
    //Load in external data then read directory
    readDir();

		//Control context menu in FileArea
		var menuVis = false;
		var selectedElem;
		var selectedEv;
		var mobileCopy = false;
		document.addEventListener("contextmenu", e => {
			selectedElem = e.target;
			selectedEv = e;

			window.addEventListener("click", e => {
				document.querySelector("#file-context").style.display = "none";
				document.querySelector("#file-area-context").style.display = "none";
				menuVis = false;
			});
			//Right-click on file link
			if(selectedElem.tagName == "A" && selectedElem.classList.contains("file-ref") && selectedElem.getAttribute("filename") != "..") {
				preventDef(e);

        //Check if file can be opened with a program
				if(selectedElem.getAttribute("program") != "") {
          document.querySelector("#open-with-program").innerHTML = selectedElem.getAttribute("program");
					document.querySelector("#open-with-program").style.display = "block";
				} else {
					document.querySelector("#open-with-program").style.display = "none";
				}

				document.querySelector("#file-area-context").style.display = "none";

				document.querySelector("#file-context").style.left = e.pageX + "px";
				document.querySelector("#file-context").style.top = e.pageY + "px";

				document.querySelector("#file-context").style.display = "block";

				menuVis = !menuVis;

			}

			//Right-click in FileArea
			if(selectedElem.tagName == "DIV" && selectedElem.classList.contains("file-area")) {
				preventDef(e);

				document.querySelector("#file-context").style.display = "none";

				document.querySelector("#file-area-context").style.left = e.pageX + "px";
				document.querySelector("#file-area-context").style.top = e.pageY + "px";

				document.querySelector("#file-area-context").style.display = "block";

				menuVis = !menuVis;
			}
		});
		//Control popup
		function showPopup(id) {
			document.querySelector("#" + id).style.left = selectedEv.pageX + "px";
			document.querySelector("#" + id).style.top = selectedEv.pageY + "px";

			document.querySelector("#" + id).style.display = "block";
		}
		function hidePopup(id) {
			document.querySelector("#" + id).style.display = "none";
		}

		//Control drop area and file uploads
		document.querySelector(".file-area").addEventListener("drag", e => preventDef(e));
		document.querySelector(".file-area").addEventListener("dragstart", e => preventDef(e));
		document.querySelector(".file-area").addEventListener("dragend", e => preventDef(e));
		document.querySelector(".file-area").addEventListener("dragover", e => preventDef(e));
		document.querySelector(".file-area").addEventListener("dragenter", e => preventDef(e));
		document.querySelector(".file-area").addEventListener("dragleave", e => preventDef(e));
		document.querySelector(".file-area").addEventListener("drop", e => {
			preventDef(e);
			uploadFiles(e.dataTransfer.files);
		});
		document.querySelector("#file-upload").addEventListener("change", e => {
			uploadFiles(e.target.files);
		});

    //Control changing the background
		document.querySelector("#background-file").addEventListener("change", e => {
			changeBackground(e.target.files);
		});

    //Control changing the user image
		document.querySelector("#user-image-file").addEventListener("change", e => {
			changeUserImage(e.target.files);
		});

		//Control copying to clipboard
		function copyToClipboard(text) {
			let elem = document.querySelector("#clipboard-box");
			elem.setAttribute("value", text);
			elem.style.display = "block";
			elem.select();
			document.execCommand("copy");
			elem.style.display = "none";
		}
		
		//Control mobile copy/paste
		function copyMode() {
			if(clipboard != "") {
				pasteFile(cut);
				copyButton.setAttribute("src", "icons/ui/copy.png");
				clipboard = "";
			} else {
				mobileCopy = !mobileCopy;
				copyButton = document.querySelector("#mobile-copy-paste");
				if(mobileCopy)
					copyButton.setAttribute("src", "icons/ui/question.png");
				else {
					copyButton.setAttribute("src", "icons/ui/copy.png");
					readDir(workingDir);
				}
			}
		}

		//Handle signing out
		async function signOut() {
		    var formData = new FormData();
		    formData.append("func", "signout");
		    let request = await fetch("php/UserManager.php", { method: "POST", body: formData });
		    let response = await request.text();
		    window.location = "/";
		}

		//Control loading indicator
		var progressVis = false;
		function setProgress(op = "Operation in progress", off = false) {
			if(off) progressVis = true;
			if(progressVis && op == "") {
				document.querySelector("#progress-popup").style.display = "none";
			} else {
				document.querySelector("#progress-popup").style.display = "block";
				document.querySelector("#op-progress").innerHTML = op;
			}
			progressVis = !progressVis;
		}
	</script>
</body>
</html>
