let rootDir = "../";
var root = "";
var dirData = [];
var workingDir = "";
var freeBytes = 0;
var clipboard = "";
var cut = false;
let unauthMessage = "You are not authorized to perform this action.";
var mimeTypes = [];
var programs = {};
var username = "";
var url = window.location.protocol + "//" + window.location.hostname + "/";

//Read files in directory
async function readDir(dir = "") {
	document.querySelector("#search").value = "";

	if(mimeTypes.length == 0) {
		await loadExternalData();
	}

	let formData = new FormData();
	formData.append("dir", dir);
  formData.append("func", "list");
  setProgress("Reading files");
	let f = await fetch(rootDir + "php/FileBait.php", {method: "POST", body: formData});
	dirData = await f.text();
	if(dirData != "unauthorized") {
		dirData = JSON.parse(dirData);
	} else {
		readDir("");
		return;
	}
	workingDir = dirData[dirData.length - 2];
	freeBytes = dirData[dirData.length - 1];

	if(dir == "")
		root = workingDir; //Set relative root based on platform

	document.querySelector("#curDir").innerHTML = workingDir.substring(workingDir.lastIndexOf("/") + 1);
	if(workingDir == root)
		document.querySelector("#curDir").innerHTML = "Home";
	document.querySelector("#curDir").setAttribute("onClick", "readDir('" + workingDir + ((workingDir != "") ? "/" : "") + "')");
	document.querySelector("#freeBytes").innerHTML = humanSize(freeBytes);

	setProgress("", true);
	populateFiles();
}

//Populate FileArea
function populateFiles(query = "(.*?)") {
	if(query == "") {
		query = "(.*?)";
	}
	
	document.querySelector(".file-area").innerHTML = "";
	document.querySelector("#subfolders").innerHTML = "";
	dirData.forEach(function(file, index) {
		if(index < dirData.length - 2 && file["name"] != "." && file["name"].toLowerCase().match(query.toLowerCase()) && !(file["name"] == ".." && dirData[dirData.length - 2] == root)) {
			//File data
			var fileref = document.createElement("a");
			fileref.appendChild(document.createTextNode(file["name"]));
			fileref.append(document.createElement("br"));
			fileref.setAttribute("href", "#");
			fileref.setAttribute("class", "file-ref");
			fileref.setAttribute("title", "Size: " + humanSize(file["size"]) + " | MIME type: " + file["mime"]);
			fileref.setAttribute("filename", file["name"]);
			fileref.setAttribute("type", file["type"]);

			//Icon and link type
			var icon = document.createElement("img");
			var link = workingDir + "/" + file["name"];
			let programData = fileProgram(file, link);
			switch(file["type"]) {
				case "file" :
					let safeLink = link.substring(link.indexOf("data"));
					fileref.setAttribute("href", safeLink);
					fileref.setAttribute("data-ref", link);
					fileref.setAttribute("onClick", "if(mobileCopy) { mobileClipboardHandler('" + link + "', '" + safeLink + "'); return false; }");
					fileref.setAttribute("program", programData.openText);
					fileref.setAttribute("program-link", programData.link);
					break;
				case "dir" :
					fileref.setAttribute("onClick", "if(mobileCopy) { mobileClipboardHandler('" + link + "') } else readDir('" + link + "')");
					fileref.setAttribute("data-ref", link);
					fileref.setAttribute("program", programData.openText);
					fileref.setAttribute("program-link", programData.link);
					break;
			}
			icon.setAttribute("src", fileIcon(file["mime"], file["ext"]));
			fileref.prepend(icon);

			document.querySelector(".file-area").appendChild(fileref);

			if(file["type"] == "dir") {
				document.querySelector("#subfolders").appendChild(fileref.cloneNode(true));
			}
		}
	});
}

//Upload array of files
async function uploadFiles(files, dir = workingDir, name = "") {
	setProgress("Uploading files");

	var temp = workingDir;
	workingDir = dir;
	for(var i = 0; i < files.length; i++) {
		let formData = new FormData();
		formData.append("file", files[i]);
		formData.append("dir", workingDir);
    formData.append("func", "upload");
		if(name != "") formData.append("name", name);
		setProgress("Uploading " + files[i].name);

		let f = await fetch(rootDir + "php/FileBait.php", {method: "POST", body: formData});
		let r = await f.text();
		if(r == "unauthorized") alert(unauthMessage);
	}
	workingDir = temp;

	setProgress("", true);
	readDir(workingDir);
}

/*Set clipboard*/
function setClipboard(val) {
	clipboard = val;
	document.querySelector("#pasteOption").style.color = "black";
}

/*Paste (cut/copy) file*/
async function pasteFile(delOriginal = false) {
	setProgress("Moving files");

	let formData = new FormData();
	formData.append("file", clipboard);
	formData.append("dir", workingDir);
	formData.append("cut", delOriginal);
  formData.append("func", "copy");

	let f = await fetch(rootDir + "php/FileBait.php", {method: "POST", body: formData});
	let r = await f.text();

  switch(r) {
	  case "exists":
		  alert("'" + clipboard.substring(clipboard.lastIndexOf("/") + 1) + "' already exists.");
		  break;
		case "unauthorized":
		  alert(unauthMessage);
		  break;
		case "sub":
		  alert("You cannot copy a folder within itself.");
		  break;
		}

  if(cut) {
	  clipboard = "";
		document.querySelector("#pasteOption").style.color = "rgb(200, 200, 200)";
	}

	setProgress("", true);
	readDir(workingDir);
}

//Delete file
async function delFile(file) {
  setProgress("Deleting files");

	let formData = new FormData();
	formData.append("file", file);
  formData.append("func", "delete");
	let f = await fetch(rootDir + "php/FileBait.php", {method: "POST", body: formData});
	let r = await f.text();
	if(r == "unauthorized") alert(unauthMessage);

	setProgress("", true);
	readDir(workingDir);
}

//New directory
async function newDir(mobilePrompt = false) {
	let formData = new FormData();
	formData.append("dir", workingDir);
	if(mobilePrompt) {
		let name = prompt("Name for new directory", "");
		if(name === null) return; //return on cancel
		formData.append("name", name || "New directory");
	}
  formData.append("func", "newdir");
	let f = await fetch(rootDir + "php/FileBait.php", {method: "POST", body: formData});
	let r = await f.text();
	if(r == "unauthorized") alert(unauthMessage);

	readDir(workingDir);
}

//Rename file
async function rename(file, newName) {
	let formData = new FormData();
  formData.append("file", file);
	formData.append("newName", newName);
  formData.append("func", "rename");
	let f = await fetch(rootDir + "php/FileBait.php", {method: "POST", body: formData});
	let r = await f.text();
	if(r == "unauthorized") alert(unauthMessage);

	readDir(workingDir);
}

//Decompress archive
async function decompressArchive(file) {
	setProgress("Decompressing archive");

	let formData = new FormData();
	formData.append("file", file);
	formData.append("func", "decompress");
	let f = await fetch(rootDir + "php/FileBait.php", {method: "POST", body: formData});
	let r = await f.text();
	if(r == "unauthorized") alert(unauthMessage);
	if(r == "notinst") alert("Please install \"p7zip-full\" on your server to enable archive extraction.");

	setProgress("", true);
	readDir(workingDir);
}

//Compress folder
async function compressFolder(file) {
	setProgress("Compressing folder");

	let formData = new FormData();
	formData.append("file", file);
	formData.append("func", "compress");
	let f = await fetch(rootDir + "php/FileBait.php", {method: "POST", body: formData});
	let r = await f.text();
	if(r == "unauthorized") alert(unauthMessage);
	if(r == "notinst") alert("Please install \"p7zip-full\" on your server to enable folder compression.");

	setProgress("", true);
	readDir(workingDir);
}

//Preview media
function previewMedia(type, link) {
	setProgress("Opening media");

	//types: 0 == picture; 1 == video
	switch(type) {
		case 0:
			document.querySelector("#pic-preview").querySelector("img").setAttribute("src", link);
			document.querySelector("#pic-preview").style.display = "block";
			break;
		case 1:
			document.querySelector("#vid-preview").querySelector("video").setAttribute("src", link);
			document.querySelector("#vid-preview").style.display = "block";
			break;
		case 2:
			audioSet(link);
			document.querySelector("#audio-preview").style.display = "block";
			break;
	}

	setProgress("", true);
}

//Determine file icon
function fileIcon(mime, ex) {
  let cat = mime.substring(0, mime.indexOf("/")).toLowerCase() + ".png";
  let type = mime.substring(mime.indexOf("/") + 1).toLowerCase() + ".png";
  let ext = ex.toLowerCase() + ".png";

	if(mimeTypes.includes(ext)) return "/icons/" + ext;
  if(mimeTypes.includes(type)) return "/icons/" + type;
  if(mimeTypes.includes(cat)) return "/icons/" + cat;

  return "/icons/generic.png";
}

//Open file with program
function openWithProgram(fileLink, programLink) {
	let actionType = programLink.substring(0, programLink.indexOf(":"));
	let link = programLink.substring(programLink.indexOf(":") + 1);

	switch(actionType) {
		case "link":
			setProgress("Opening file with app");
			window.open(link + encodeURIComponent(url + fileLink));
			setProgress("", true);
			break;
		case "app":
			setProgress("Opening file with app");
			window.open(url + "apps/" + link + encodeURI(url + fileLink));
			setProgress("", true);
			break;
		case "func":
			eval(link);
			break;
	}
}

//Determine if file can be opened with a program
function fileProgram(file, link) {
	let safeLink = link.substring(link.indexOf("data"));
	let ext = file["ext"].toLowerCase();
	let type = file["type"];
	let programData = {openText: "", link: ""};

	if(!(programs.ext[ext] == undefined)) {
		programData.openText = programs.openText[programs.ext[ext]];
		programData.link = programs.link[programs.ext[ext]];
	}
	
	if(!(programs.type[type] == undefined)) {
		programData.openText = programs.openText[programs.type[type]];
		programData.link = programs.link[programs.type[type]];
	}

	return programData;
}

//Load file icons, background, user image, and programs
async function loadExternalData() {
  var formData = new FormData();
  formData.append("func", "icons");
  var f = await fetch(rootDir + "php/FileBait.php", {method: "POST", body: formData});
  mimeTypes = await f.json();

	formData = new FormData();
	f = await fetch(rootDir + "js/programs.json");
	programs = await f.json();

	formData = new FormData();
	formData.append("func", "data");
	f = await fetch(rootDir + "php/UserManager.php", {method: "POST", body: formData});
	username = await f.text();

	let contentContainer = document.querySelector(".content-area");
	contentContainer.style.backgroundImage = "url('../backgrounds/background-" + username + "'), url('../backgrounds/generic')";

	let userImage = document.querySelector(".user-image");
	userImage.style.backgroundImage = "url('../user-images/user-" + username + "'), url('../user-images/default.png')";
}

//Change background
async function changeBackground(background) {
	let r = await uploadFiles(background, "../backgrounds/", "background-" + username);
	location.reload();
}

//Change user image
async function changeUserImage(image) {
	let r = await uploadFiles(image, "../user-images/", "user-" + username);
	location.reload();
}

//Deal with copy/paste on mobile
function mobileClipboardHandler(dataLink, safeLink = "") {
	setClipboard(dataLink);
	cut = false;
	copyToClipboard(url + safeLink);
	mobileCopy = false;
	copyButton.setAttribute("src", "icons/ui/paste.png");
	alert("Copied to clipboard.");
}

//Convert bytes to human readable size
function humanSize(size) {
	var sizeTypes = ["bytes", "kilobytes", "megabytes", "gigabytes", "terabytes", "petabytes", "exabytes", "zettabytes", "yottabytes"];
	for(i = 0; i < sizeTypes.length; i++) {
		if(size < 1000) return Math.round(size * 100)/100 + " " + sizeTypes[i];
		size /= 1000;
	}
	return Math.round(size * 100)/100 + " " + sizeTypes[sizeTypes.length - 1];
}

//General prevent default handler
function preventDef(e) {
	e.preventDefault();
	e.stopPropagation();
}
