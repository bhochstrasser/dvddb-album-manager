<?php
$nocheck=TRUE;
require("inc/menu.php");
require("inc/html.php");
require("inc/common.php");
require("inc/password.php");

$config=getconfig();

$output="";

if (isset($_POST["email"])) {
	$result=doquery("select id from user where email = \"".greatescape($_POST["email"])."\"");
	if (mysqli_num_rows($result)==0) {
		$output.="Email address not found, sorry.";
	} else {
		$row=mysqli_fetch_assoc($result);

		$pass=genpass();

		doquery("update user set password=\"".md5($pass)."\" where id = ".$row["id"]);

		$to=$_POST["email"];
		$subject=$config["sitename"]." password";
		$message="Someone, possibly you, has requested a new password for your MovieDB database login.\n\n";
		$message.="Your new password is \"".$pass."\". Please proceed to ".$config["home"].", log in and change your password from the Edit user page.";
		$headers="From: ".$config["siteemail"];

		mail($to,$subject,$message,$headers);
		$output.="Your new password has been emailed!";
	}
} else {
	$output.="If you've lost your password, enter your email address below, and a new password will be generated and emailed to you.\n<P>\n";
	$output.=form_begin("password.php","POST");
	$output.="Email address: ".input_text("email",30,255)."<BR /><BR />\n";
	$output.=submit("Generate new password");
	$output.=form_end();
}

$content["body"] =& $output;
dopage($content);

?>
