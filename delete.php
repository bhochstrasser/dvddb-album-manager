<?php
require("inc/menu.php");
require("inc/html.php");
require("inc/common.php");

$config=getconfig();

$output="";

$query="select title from movie where id = ".intval($_POST["movieid"]);

if ($admin==FALSE) {
	$query.=" and userid = ".intval($_COOKIE["userid"]);
}

$result=doquery($query);

if (mysqli_num_rows($result)==0) {
	$output.="Error - Movie not found\n";
} else {
	if (isset($_POST["sure"])) {
		// Do delete
		if ($_POST["sure"]=="Yes") {
			doquery("delete from movie where id = ".intval($_POST["movieid"]));
			header("Location: listmovies.php?userid=".intval($_COOKIE["userid"]));
			die();
		} else {
			header("Location: editmovie.php?movieid=".intval($_POST["movieid"]));
			die();
		}
	} else {
		$movietitle=mysqli_fetch_row($result);
		$output.="<CENTER>\n";
		$output.="Are you sure you want to delete the movie <B>&quot;".htmlspecialchars($movietitle[0])."&quot;</B>?<BR /><BR />\n";
		$output.=form_begin("delete.php","POST");
		$output.=input_hidden("movieid",$_POST["movieid"]);
		$output.=submit("Yes","sure");
		$output.=submit("No","sure");
		$output.=form_end();
		$output.="</CENTER>\n";
	}
}

$content["body"] =& $output;
dopage($content);

?>
