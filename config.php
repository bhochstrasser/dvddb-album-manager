<?php
require("inc/menu.php");
require("inc/html.php");
require("inc/common.php");

$output="";
$rows="";

if ($admin==FALSE) {
	header("Location: index.php");
	die();
}

if (isset($_POST["action"])) {
	$newconfig=$_POST["newconfig"];
	foreach ($newconfig as $item => $value) {
			doquery("update config set value=\"".greatescape($value)."\" where item=\"".greatescape($item)."\" and visible=1");
	}
	$output.="Settings saved\n<BR /><BR />";
}

$config=getconfig();

$result=doquery("select item, value, comments from config where visible=1 order by item");

$output.=form_begin("config.php","POST");
$output.=input_hidden("action", "save");

$rows.=tr(td("Config item","","tablehead").td("Value","","tablehead").td("Comments","","tablehead"));
$c=0;

while ($row=mysqli_fetch_assoc($result)) {
	if ($c++%2==0) $class="tablecell0"; else $class="tablecell1";
	$cells=td($row["item"],"",$class);
	$cells.=td(input_text("newconfig[".$row["item"]."]", 50, 255, htmlspecialchars($row["value"])),"",$class);
	$cells.=td($row["comments"],"",$class);

	$rows.=tr($cells);
}

$output.=table($rows,0,1,0,"","datatable");
$output.="<P>";
$output.=submit("Save settings");
$output.=form_end();

$content["body"] =& $output;
dopage($content);

?>
