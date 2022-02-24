<?php
session_start();

if(isset($_SESSION["username"])) {
    header("Location: browser.php");
    die("You may not access this page until you have signed in.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="initial-scale=1, width=device-width, height=device-height, viewport-fit=cover">
	<title>Cloudmesa</title>

	<!--[if lt IE 9]>
	<script src="http://html5shim.googlecode.com/svn/trunk/html5.js"></script>
	background: linear-gradient(20deg, rgba(71, 203, 247, 1), rgba(127, 71, 247, 1));
	<![endif]-->

	<link rel="stylesheet" type="text/css" href="css/style.css">
	<link rel="icon" type="image/png" sizes="32x32" href="/favicon.png">

  <style>
      body {
        text-align: center;
        animation: colorChangingBkg ease 5s infinite;
        animation-direction: alternate;
        background: linear-gradient(20deg, rgba(188, 71, 255, 1), rgba(71, 126, 255, 1));
        background-size: 300% 300%;
        color: rgba(255, 255, 255, 1);
      }
      @keyframes colorChangingBkg {
      	0% {
      		background-position: 0% 50%;
      	}
      	100% {
      		background-position: 100% 50%;
      	}
      }

      #sign-in-form, #new-user-form {
        display: none;
      }
      .details {
        color: rgba(255, 255, 255, 0.7);
        font-size: 14px;
      }

      input[type="text"], input[type="password"] {
        border-bottom: 1px solid rgba(255, 255, 255, 0.5);
        transition-property: border-bottom;
        transition-duration: 0.5s;
        margin: 0px 1% 0px 1%;
        color: rgba(255, 255, 255, 1);
      }
      input[type="text"]::placeholder, input[type="password"]::placeholder {
        color: rgba(255, 255, 255, 0.7);
      }
      input[type="text"]:focus, input[type="password"]:focus {
        border-bottom: 1px solid rgba(255, 255, 255, 1);
      }

      .left-side, .right-side {
        box-sizing: border-box;
        padding: 2.5%;
        display: flex;
        align-items: center;
      	justify-content: flex-end;
        float: left;
        width: 50vw;
        height: 100vh;
      }
      .right-side {
        float: right;
        align-items: center;
      	justify-content: flex-start;
      }
      .left-side img {
        height: 30%;
      }


      .user-option {
        display: flex;
      	align-items: center;
      	justify-content: left;
        padding: 5%;
        cursor: pointer;
        max-width: 200px;
        white-space: nowrap;
        overflow: ellipsis;
      }
      .user-option:hover {
        background-color: rgba(0, 0, 0, 0.2);
		border-radius: 6px;
      }

      input[type="submit"].true {
        color: rgba(255, 255, 255, 0.8);
      }

      @media only screen and (max-width: 800px) {
        * {
          font-size: 16px;
        }
        .left-side, .right-side {
          box-sizing: border-box;
          padding: 2.5%;
          display: flex;
          align-items: center;
        	justify-content: center;
          width: 100vw;
          height: auto;
        }
      }
  </style>
</head>
<body>
	<script type="text/javascript" src="js/filebait.js"></script>
	<script type="text/javascript" src="js/md5.min.js"></script>

  <div class="left-side">
    <img src="logo.svg" style="max-width: 100%">
  </div>

	<div class="right-side">
		<form id="sign-in-form" autocomplete="off">
			<div class="user-option" title="Click to switch user." onClick="document.querySelector('#sign-in-form').style.display = 'none'; document.querySelector('#user-list').style.display = 'block';">
        <div class="user-image" id="sign-in-form-user-image"></div>
        <span id="sign-in-form-username"></span>
      </div>
			<input type="password" id="sign-in-form-password" placeholder="Password" required />
      <p id="sign-in-details" class="details">&nbsp;</p>
			<input type="submit" class="true" value="Sign in" />
		</form>
    <form id="new-user-form" autocomplete="off">
			<div class="user-option" title="Click to switch to an existing user." onClick="document.querySelector('#new-user-form').style.display = 'none'; document.querySelector('#user-list').style.display = 'block';">
        <div class="user-image" style="background-image: url('../user-images/default.png')"></div>
        <span>New user</span>
      </div>
      <input type="text" id="new-user-form-username" placeholder="Username" required /><br>
			<input type="password" id="new-user-form-password" placeholder="Password" required />
      <p id="new-user-details" class="details">&nbsp;</p>
			<input type="submit" class="true" value="Sign in" />
		</form>
    <div id="user-list">
      <p>Please select a user to sign in.</p>
      <div class="user-option" onClick="document.querySelector('#new-user-form').style.display = 'block'; document.querySelector('#user-list').style.display = 'none'; document.querySelector('#new-user-form-username').focus();">
        <div class="user-image" style="background-image: url('../user-images/default.png')"></div>
        <span>New user</span>
    </div>
	</div>

	<script>
  //Display the user list
  getUsers();

  //Sign-in form submission
	document.querySelector("#sign-in-form").addEventListener("submit", e => {
		preventDef(e);
		signIn();
	});

  //New user form submission
	document.querySelector("#new-user-form").addEventListener("submit", e => {
		preventDef(e);
		signIn(document.querySelector("#new-user-form-username").value, document.querySelector("#new-user-form-password").value);
	});

  //Sign-in form update user on what's happening
	document.querySelector("#sign-in-form").addEventListener("change", e => {
		document.querySelector("#sign-in-details").innerHTML = "&nbsp;";
    document.querySelector("#new-user-details").innerHTML = "&nbsp;";
	});

  //New user form update user on what's happening
	document.querySelector("#new-user-form").addEventListener("change", e => {
    document.querySelector("#sign-in-details").innerHTML = "&nbsp;";
		document.querySelector("#new-user-details").innerHTML = "&nbsp;";
	});

  //Sign the user in
	async function signIn(username = document.querySelector("#sign-in-form-username").innerHTML, password = document.querySelector("#sign-in-form-password").value) {
    document.querySelector("#sign-in-details").style.display = "block";
    document.querySelector("#sign-in-details").innerHTML = "Signing in...";
    document.querySelector("#new-user-details").innerHTML = "Signing in...";

		var formData = new FormData();
		formData.append("username", username);
		formData.append("password", md5(password));
		formData.append("func", "signin");
		let request = await fetch("php/UserManager.php", { method: "POST", body: formData });
		let response = await request.text();

		if(response == true) {
			window.location = "browser.php";
		} else if(response == false) {
			document.querySelector("#sign-in-details").style.display = "block";
			document.querySelector("#sign-in-details").innerHTML = "Sign-in details incorrect.";
      document.querySelector("#new-user-details").innerHTML = "This user already exists.";
		} else {
      document.querySelector("#sign-in-details").style.display = "block";
			document.querySelector("#sign-in-details").innerHTML = "Sign-in error. Please try again.";
      document.querySelector("#new-user-details").innerHTML = "Sign-in error. Please try again.";
    }
	}

  //Get a list of available users
  async function getUsers() {
    var formData = new FormData();
		formData.append("func", "get_users");
    let request = await fetch("php/UserManager.php", { method: "POST", body: formData });
		let users = await request.json();

    users.forEach(function(user) {
      let userOption = document.createElement("div");
      userOption.setAttribute("class", "user-option");
      let userImage = document.createElement("div");
      userImage.setAttribute("class", "user-image");
      userImage.style.backgroundImage = "url('../user-images/user-" + user["username"] + "'), url('../user-images/default.png')";
      userOption.appendChild(userImage);
      let username = document.createElement("span");
      username.appendChild(document.createTextNode(user["username"]));
      userOption.appendChild(username);

      userOption.setAttribute("onClick", "prepareSignIn('" + user["username"] + "')");

      document.querySelector("#user-list").appendChild(userOption);
    });
  }

  //Prepare the sign-in screen
  function prepareSignIn(username) {
    document.querySelector("#sign-in-form-user-image").style.backgroundImage = "url('../user-images/user-" + username + "'), url('../user-images/default.png')";
    document.querySelector("#sign-in-form-username").innerHTML = username;
    document.querySelector("#sign-in-form").style.display = "block";
    document.querySelector("#user-list").style.display = "none";

    document.querySelector("#sign-in-form-password").value = "";
    document.querySelector("#sign-in-form-password").focus();
  }
	</script>
</body>
</html>
