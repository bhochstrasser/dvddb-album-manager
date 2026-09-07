<?php
require("inc/menu.php");
require("inc/html.php");
require("inc/common.php");
require("inc/controls.php");

if ($admin==FALSE) {
	header("Location: index.php");
	die();
}

$config=getconfig();
$output="";

if (isset($_POST["action"])) {
	$total=0;
	foreach ($_POST["cols"] as $bit => $enabled) {
		if (strtolower($enabled)=="on") $total+=intval($bit);
	}
	doquery("update config set value=\"".$total."\" where item=\"movcolumns\"");
	header("Location: columnadmin.php");
	die();
}

$ccols=db_result(doquery("select value from config where item=\"movcolumns\""),0);

$output.="Pick columns to display by default on the <A HREF=\"listmovies.php\">movie</A> page. Users can make their own personal choices on the <A HREF=\"settings.php\">settings</A> page. The setting here is just the default. Album storage can be shown as a compact Location (A/P/S) column or as separate Album, Page, and Sleeve columns.<BR /><BR />\n";

$output.=form_begin("columnadmin.php","POST");
$output.=input_hidden("action","save");

$output.=columns($ccols);

$output.="<BR />\n";
$output.=submit("Save changes");
$output.=form_end();


$content["body"] =& $output;
dopage($content);

?>
