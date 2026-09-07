<?php 
$nocheck=TRUE;
require("inc/menu.php");
require("inc/html.php");
require("inc/common.php");

$config=getconfig();

$output="";
$nav="";
$direction="";
$ownedby="";
$genre="";
$filter=array();
$url="";

$fields="u.fname, m.title, m.reldate, m.rating, m.runtime, ml.album, ml.page, ml.sleeve ";
$from="movie m inner join user u on m.userid = u.id left join movie_location ml on ml.movieid = m.id";
$where="1=1";
$orderby="m.title";

if (!saneempty($_GET["userid"] ?? "")) {
	$ownedby=db_result(doquery("select concat(fname, \" \", lname) from user where id = ".intval($_GET["userid"])),0);
	$filter[]="<B>Owner:</B> ".$ownedby;
	$where.=" and m.userid = ".intval($_GET["userid"]);
}
if (!saneempty($_GET["genreid"] ?? "")) {
	$genre=db_result(doquery("select name from genre where id = ".intval($_GET["genreid"])),0);
	$filter[]="<B>Genre:</B> ".$genre;
	$where.=" and find_in_set(".intval($_GET["genreid"]).",m.genreid)";
}
if (!saneempty($_GET["year"] ?? "")) {
	$filter[]="<B>Released year:</B> ".intval($_GET["year"]);
	$where.=" and m.reldate = ".intval($_GET["year"]);
}
if (!saneempty($_GET["region"] ?? "")) {
	$filter[]="<B>Region:</B> ".intval($_GET["region"]);
	$where.=" and m.region = ".intval($_GET["region"]);
	if (saneempty($_GET["media"] ?? "")) {
		$filter[]="<B>Media:</B> DVD";
		$where.=" and m.mediaid = 1";
	}
}
if (!saneempty($_GET["media"] ?? "")) {
	$medianame=db_result(doquery("select name from media where id = ".intval($_GET["media"])),0);
	$filter[]="<B>Media:</B> ".$medianame;
	$where.=" and m.mediaid = ".intval($_GET["media"]);
}
if (!saneempty($_GET["s"] ?? "")) {
	$filter[]="<B>Search for:</B> ".$_GET["s"];
	if (intval($config["mysqlversion"])>=4.0) {
		$where.=" and match(m.title) against (\"".greatescape($_GET["s"])."\" IN BOOLEAN MODE)";
	} else {
		$where.=" and match(m.title) against (\"".greatescape($_GET["s"])."\")";
	}
}

if (!saneempty($filter)) {
	$output.=table(tr(td("Filters in effect:<BR />\n".implode("; ",$filter))),0,1,0,"","filters");
}

$query="select ".$fields." from ".$from." where ".$where." order by ".$orderby;

// $output.=$query."<BR /><BR />";

$result=doquery($query);

if (mysqli_num_rows($result)==0) {
	$output.="No Movies found";
} else {

	$numrows=mysqli_num_rows($result);

	while ($row=mysqli_fetch_assoc($result)) {
	        if(is_numeric($row["title"][0]))
	                $cmp='0-9';
	        else
	                $cmp=$row["title"][0];
	        if(strtoupper($cmp)!=strtoupper($lastchar))
	        {
			$rows.=tr(td("","100%","","","","5"));
	                $rows.=tr(td("<B>".strtoupper($cmp)."</B>","100%","letter","","","5"));
	                $lastchar=$cmp;
	        }

		$cells=td(htmlspecialchars($row["title"]));
		$cells.=td($row["reldate"]);
		$cells.=td($row["runtime"]." mins");

		$location="";
		if ($row["album"]!==NULL && $row["page"]!==NULL && $row["sleeve"]!==NULL) {
			$location="A".intval($row["album"])." / P".intval($row["page"])." / S".intval($row["sleeve"]);
		}
		$cells.=td($location);

		$rating="";
		for ($i=0;$i<$row["rating"];$i++) {
			$rating.="*";
		}

		$cells.=td($rating);

		$rows.=tr($cells);
	}

	$output.=table($rows,0,2,2,"100%");

	$output.="<BR /><BR />\n";
	$output.="Total number of Movies: ".$numrows;
}

require("inc/pheader.php");
echo $output;
require("inc/pfooter.php");

?>
