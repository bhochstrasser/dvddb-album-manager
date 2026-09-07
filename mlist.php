<?php 
require("inc/menu.php");
require("inc/html.php");
require("inc/common.php");

$config=getconfig();

$output="";
$rows="";
$cells="";

if ($admin==FALSE) {
        header("Location: index.php");
        die();
}

$action=$_POST["action"] ?? "";

if (!saneempty($action)) {
	if (saneempty($_POST["subject"])) $error="Please specify a subject.<BR />\n";
	if (saneempty($_POST["note"])) $error="Please enter a message.<BR />\n";

	if (!saneempty($error)) {
		$output.="The following errors occured. Please return and correct them:<BR /><BR />\n";
		$output.=$error;
	} else {
		$result=doquery("select fname,email from user where enabled=1");

		while ($row=mysqli_fetch_row($result)){
			$to=$row[1];
			$message="Dear ".$row[0].",\n\n".ungreatescape($_POST["note"]);
			$headers = "From: ".$config["siteemail"];
			mail($to,$_POST["subject"],$message,$headers);
			$output.="Mail sent to <B>".$to."</B><BR />\n";
		}
	}
} else {
	$output.="<P>\nThis form will send an email out to every active user of the DVD database. Use it with care.<BR /><BR />\n";
	$output.=form_begin("mlist.php","POST");
	$output.=input_hidden("action","mailout");

	$rows.=tr(td("Subject: ","","","RIGHT").td(input_text("subject",40,255),"","","LEFT"));
	$rows.=tr(td("Message: ","","","RIGHT","TOP").td(textarea("note","",20,40),"","","LEFT"));
	$rows.=tr(td(submit("Send Mail"),"","","CENTER","",2));

	$output.=table($rows,0,2,0,"100%");
}

$content["body"] =& $output;
dopage($content);

?>
