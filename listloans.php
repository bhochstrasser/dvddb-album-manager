<?php 
require("inc/menu.php");
require("inc/html.php");
require("inc/common.php");

$config=getconfig();

$output="";
$cells="";
$rows="";

$user=array();

$loans=doquery("select loan.id as loanid, movie.title, movie.userid as ownerid, loan.userid as loaneeid, loan.loanee, loan.loaneeemail, date_format(loan.loandate,\"".$config["dateformat"]."\") as loandate from loan,movie where loan.movieid = movie.id order by loan.id");
$users=doquery("select id, email, fname, lname from user");

while ($row=mysqli_fetch_row($users)) {
	$user[$row[0]]=array("email" => $row[1],"firstname" => $row[2], "lastname" => $row[3]);
}

if (mysqli_num_rows($loans)==0) {
	$output.="No Movies currently loaned out.\n";
} else {
	$cells=td("Owner","","tablehead");
	$cells.=td("Movie","","tablehead");
	$cells.=td("On loan to","","tablehead");
	$cells.=td("Loaned on date","","tablehead");
	$cells.=td("Action","","tablehead");

	$rows.=tr($cells);
	$c=0;

	while ($row=mysqli_fetch_assoc($loans)) {
		if ($c++%2==0) $class="tablecell0"; else $class="tablecell1";
		if ($row["loaneeid"]==0) {
			if (!saneempty($row["loaneeemail"])) {
				$loanee="<A HREF=\"mailto:".$row["loaneeemail"]."\">".$row["loanee"]."</A>\n";
			} else {
				$loanee=$row["loanee"];
			}
		} else {
			$loanee="<A HREF=\"mailto:".$user[$row["loaneeid"]]["email"]."\">".$user[$row["loaneeid"]]["firstname"]." ".$user[$row["loaneeid"]]["lastname"]."</A>";
		}
		$cells=td("<A HREF=\"mailto:".$user[$row["ownerid"]]["email"]."\">".$user[$row["ownerid"]]["firstname"]." ".$user[$row["ownerid"]]["lastname"]."</A>","",$class);
		$cells.=td(htmlspecialchars($row["title"]),"",$class);
		$cells.=td($loanee,"",$class);
		$cells.=td($row["loandate"],"",$class);
		if ($row["ownerid"]==intval($_COOKIE["userid"])) {
			$cells.=td("<A HREF=\"loan.php?action=return&loanid=".$row["loanid"]."\">Mark as returned</A>","",$class);
		} else {
			$cells.=td("","",$class);
		}
		$rows.=tr($cells);
	}
	$output.=table($rows,0,1,0,"","datatable");

	$rowcount=mysqli_num_rows($loans);

	$output.="<P>\nTotal number of current loans: ".$rowcount."\n";
}

$content["body"] =& $output;
dopage($content);

?>
