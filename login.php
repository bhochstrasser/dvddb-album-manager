<?php
$nocheck=TRUE;
require("inc/menu.php");
require("inc/html.php");
require("inc/common.php");

$config=getconfig();

$output="";
$error="";
$rows="";

$action=$_POST["action"] ?? "";

if (!saneempty($action)) {
	// Basic field checks
	if (saneempty($_POST["email"])) $error.="Email address must be filled in.<BR />\n";
	if (saneempty($_POST["password"])) $error.="Password must be filled in.<BR />\n";

	if (!saneempty($error)) {
		$output.=$error;
		$output.="<P>Please go back and correct the above errors.\n";
	} else {
		// Check password
		$result=doquery("select password, id, fname, enabled from user where email=\"".greatescape($_POST["email"])."\"");
		if (mysqli_num_rows($result)==0) {
			$error.="User not found, go to the <A HREF=\"newlogin.php\">new account</A> page to create a login.\n";
		} else {
			$row=mysqli_fetch_assoc($result);

			if ($row["enabled"]!=1) {
				$error.="Your account exists but is not activated. Try again later.\n";
			} else {
				if (md5($_POST["password"])==$row["password"]) {
					// login ok, set some cookies.
					setcookie("username",$row["fname"],time()+(3600*24*365));
					setcookie("userid",$row["id"],time()+(3600*24*365));
					setcookie("userpass",$row["password"],time()+(3600*24*365));
					// Go to main page
					redirect("index.php");
					die();
				} else {
					$error.="Incorrect password.\n";
				}
			}
		}
		if (!saneempty($error)) {
			$output.=$error;
		}
	}
} else {

	$output.=form_begin("login.php","POST");
	$output.=input_hidden("action","login");

	$rows.=tr(td("Email:","40%","","RIGHT").td(input_text("email",30,255)));
	$rows.=tr(td("Password:","","","RIGHT").td(input_passwd("password",30,50)));
	$rows.=tr(td(submit("Login"),"","","CENTER","",2));

	$output.=table($rows,0,2,0,"100%");
	$output.=form_end();
	$output.="<P>Forgot your password? Click <A HREF=\"password.php\">here</A>\n";
}

$content["body"] =& $output;
dopage($content);

?>
