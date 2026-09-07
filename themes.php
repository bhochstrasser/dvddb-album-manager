<?php
require("inc/menu.php");
require("inc/html.php");
require("inc/common.php");

if ($admin==FALSE) {
        header("Location: index.php");
        die();
}

if (isset($_POST["theme"])) {
	doquery("update config set value=\"".greatescape($_POST["theme"])."\" where item=\"theme\"");
}

$config=getconfig();

$output="";
$rows="";
$cells="";

$ctheme=db_result(doquery("select value from config where item = \"theme\""),0);

$output.=form_begin("themes.php","POST");

$rows.=tr(td("Default theme","","tablehead").td("Theme","","tablehead").td("Description","","tablehead"));

$handle=opendir("themes");
$c=0;

while (false !== ($file = readdir($handle))) {
	if ($c++%2==0) $class="tablecell0"; else $class="tablecell1";
	$desc="";
	$ch=FALSE;
	if ($file!="."&&$file!=".."&&$file!="CVS") {
		if ($fp=@fopen("themes/".$file."/description.html","r")) {
			$desc=fgets($fp, 4096);
			fclose($fp);
		}
		if ($ctheme==$file) $ch=TRUE;

		$rows.=tr(td(input_radio("theme",$file,$ch),"",$class).td($file,"",$class).td($desc,"",$class));
	}
}
closedir($handle);

$output.=table($rows,0,1,0,"","configtable");
$output.="<BR />\n";
$output.=submit("Save changes");
$output.=form_end();

$content["body"] =& $output;
dopage($content);

?>
