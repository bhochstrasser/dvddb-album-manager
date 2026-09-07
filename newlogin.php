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
	// Field checks.
	if (saneempty($_POST["email"])) $error.="Email address must be entered.<BR />\n";
	if (saneempty($_POST["pass1"])) $error.="Password must be entered.<BR />\n";
	if (saneempty($_POST["firstname"])) $error.="First name must be entered.<BR />\n";
	if (saneempty($_POST["lastname"])) $error.="Last name must be entered.<BR />\n";
	if (saneempty($_POST["city"])) $error.="City must be entered.<BR />\n";
	if ($_POST["pass1"]!=$_POST["pass2"]) $error.="Passwords do not match.<BR />\n";
	if (strlen($_POST["pass1"])<6) $error.="Password must be at least 6 characters long.<BR />\n";

	if (!saneempty($error)) {
		$output.=$error;
		$output.="<P>\nPlease go back and correct these errors before proceeding.\n";
	} else {
		// Check to see if account already exists.
		$result=doquery("select count(*) from user where email=\"".greatescape($_POST["email"])."\"");
		$row=mysqli_fetch_row($result);
		if ($row[0]!=0) {
			$output.="<P>\nAccount appears to already exist. Perhaps you need to <A HREF=\"login.php\">login</A>?\n";
		} else {
			// Insert login
			$password=md5($_POST["pass1"]);
			doquery("insert user values (NULL, \"".greatescape($_POST["email"])."\", \"".$password."\", \"".greatescape($_POST["firstname"])."\", \"".greatescape($_POST["lastname"])."\", \"".greatescape($_POST["city"])."\", \"".greatescape($_POST["region"])."\", \"".greatescape($_POST["country"])."\", 0, 0, now(), now())");
			$output.="Your account has been created, but must now be activated. An email has been sent to the database admins who will activate your account for you.<P>\nWhen it has been activated, you will receive email notification.";

			// Notify admins
			$admins=doquery("select email from user where enabled =1 and admin =1");

			$subject="New MovieDB database user requires approval!";
			$message="A new user account has been created. Here are the details:\n\n";
			$message.="Name: ".$_POST["firstname"]." ".$_POST["lastname"]."\n";
			$message.="Location: ".$_POST["city"].", ".$_POST["region"].", ".$_POST["country"]."\n\n";
			$message.="Heard about it from: ".$_POST["where"]."\n\n";
			$message.="If this is a valid user, please activate it at ".$config["home"]."/useradmin.php";

			$headers = "From: ".$config["siteemail"];

			if (mysqli_num_rows($admins)==0) { // No admins in user table, fall back on config address
				if ($config["approvalemail"]!="approvalmail@yourdomain.com") {
					$to=$config["approvalemail"];
					mail($to,$subject,$message,$headers);
				}
			} else {
				while ($row=mysqli_fetch_row($admins)) {
					mail($row[0],$subject,$message,$headers);
				}
			}
		}
	}

} else {
	$regions=restoarray(doquery("select code, name from region order by name"));
	$countries=restoarray(doquery("select code, name from country order by name"));

	$output.="Use this form to create a new login.<P>\n";
	$output.=form_begin("newlogin.php","POST");
	$output.=input_hidden("action","create");

	$rows.=tr(td("Email address:","35%","","RIGHT").td(input_text("email",30,255)." (This will be your login id)","","","LEFT"));
	$rows.=tr(td("Password:","35%","","RIGHT").td(input_passwd("pass1",30,50),"","","LEFT"));
	$rows.=tr(td("Confirm password:","35%","","RIGHT").td(input_passwd("pass2",30,50),"","","LEFT"));
	$rows.=tr(td("First Name:","35%","","RIGHT").td(input_text("firstname",30,255),"","","LEFT"));
	$rows.=tr(td("Last Name:","35%","","RIGHT").td(input_text("lastname",30,255),"","","LEFT"));
	$rows.=tr(td("City:","35%","","RIGHT").td(input_text("city",30,255),"","","LEFT"));
	$rows.=tr(td("Region:","35%","","RIGHT").td(input_select("region","",$regions),"","","LEFT"));
	$rows.=tr(td("Country:","35%","","RIGHT").td(input_select("country","",$countries),"","","LEFT"));
	$rows.=tr(td().td());
	$rows.=tr(td("Where did you hear about this site?","35%","","RIGHT").td(input_text("where",30,255),"","","LEFT"));
	$rows.=tr(td(submit("Create Account"),"","","CENTER","","2"));

	$output.=table($rows,0,2,0,"100%");
	$output.=form_end();
}

$content["body"] =& $output;
dopage($content);

?>
