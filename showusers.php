<?php 
require("inc/menu.php");
require("inc/html.php");
require("inc/common.php");

$config=getconfig();

$output="";
$cells="";
$rows="";

$result=doquery("select user.id, user.email, user.fname, user.lname, user.city, region.name, country.name, count(movie.id) from (user, region, country) left join movie on (user.id=movie.userid) where user.regioncode=region.code and user.countrycode=country.code and user.enabled=1 group by user.id, movie.userid order by id");
if (mysqli_num_rows($result)==0) {
	$output.="No users in database!\n";
} else {
	$cells.=td("User", "", "tablehead");
	$cells.=td("Email", "", "tablehead");
	$cells.=td("City", "", "tablehead");
	$cells.=td("Region", "", "tablehead");
	$cells.=td("Country", "", "tablehead");
	$cells.=td("Number of movies", "", "tablehead");

	$rows.=tr($cells);
	$c=0;

	while ($row=mysqli_fetch_row($result)) {
		if ($c++%2==0) $class="tablecell0"; else $class="tablecell1";
		$cells=td("<A HREF=\"listmovies.php?userid=".$row[0]."\">".htmlspecialchars($row[2])." ".htmlspecialchars($row[3])."</A>","", $class);
		$cells.=td("<A HREF=\"mailto:".$row[1]."\">".$row[1]."</A>", "", $class);
		$cells.=td(htmlspecialchars($row[4]), "", $class);
		$cells.=td($row[5], "", $class);
		$cells.=td($row[6], "", $class);
		$cells.=td($row[7], "", $class);

		$rows.=tr($cells);
	}

	$output.=table($rows,0,1,0,"","datatable");
}

$content["body"] =& $output;
dopage($content);

?>
