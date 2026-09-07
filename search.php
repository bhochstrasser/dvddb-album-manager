<?php
require("inc/menu.php");
require("inc/html.php");
require("inc/common.php");

$config=getconfig();

$genres[]=array("", "All");
$users[]=array("", "All");
$years[]=array("", "All");
$regions[]=array(0=> "", 1=> "All");
$media[]=array(0 => "", 1=> "All");
$output="";

$genreres=doquery("select id, name from genre order by name");
while ($row=mysqli_fetch_row($genreres)) {
	$genres[]=array($row[0], $row[1]);
}

$userres=doquery("select id, concat(fname,\" \", lname) from user where enabled=1 order by fname");
while ($row=mysqli_fetch_row($userres)) {
	$users[]=array($row[0], $row[1]);
}

for ($i=$config["lowdate"];$i<=date("Y");$i++) {
	$years[]=array($i,$i);
}

for ($i=0;$i<=6;$i++) {
	$regions[]=array(0 => "$i", 1=> "Region ".$i);
}

$mediares=doquery("select id, name from media order by name");
while ($row=mysqli_fetch_row($mediares)) {
	$media[]=array($row[0], $row[1]);
}

$output.=form_begin("listmovies.php","GET");
$output.="Apply the following filters: <BR /><BR />\n";

$tr=tr(td("Genre:").td(input_select("genreid","",$genres)));
$tr.=tr(td("Region:").td(input_select("region","",$regions)));
$tr.=tr(td("Owner:").td(input_select("userid","",$users)));
$tr.=tr(td("Media:").td(input_select("media","",$media)));
$tr.=tr(td("Release year:&nbsp;&nbsp;").td(input_select("year","",$years)));

$output.=table($tr,0,0,4);


$output.=submit("Search");
$output.=form_end();

$output.="<BR />\n";

$output.=form_begin("listmovies.php","GET");
$output.="Search by title keywords:<BR /><BR />\n";
$output.=input_text("s",40,255);
$output.=submit("Go");
$output.=form_end();

$content["body"] =& $output;
dopage($content);

?>
