<?php
require("inc/menu.php");
require("inc/html.php");
require("inc/common.php");

$config=getconfig();

$output="";

if (isset($_GET["visit"])) {
	doquery("update user set lastcheck = now() where id = ".intval($_COOKIE["userid"]));
}

$output.="Welcome, ".$_COOKIE["username"]."\n<P>\n";

$result=doquery("select movie.id as movieid, movie.title, date_format(movie.insertdate,\"".$config["dateformat"]."\") as insertdate, concat(u.fname, \" \", u.lname) as name, u.id as userid from movie, user u, user u2 where movie.insertdate > u2.lastcheck and movie.userid = u.id and u2.id = ".intval($_COOKIE["userid"])." and u.id != ".intval($_COOKIE["userid"])." order by u.fname, movie.title");

$rowcount=mysqli_num_rows($result);

if ($rowcount==0) {
	$output.="There have been no new movies entered since you last checked.\n";
} else {
	if ($rowcount==1) {
		$output.="Since your last visit there has been just 1 new movie added: (Click <A HREF=\"index.php?visit=true\">here</A> to clear this list.)\n";
	} else {
		$output.="Since your last visit there have been ".$rowcount." new movies added: (Click <A HREF=\"index.php?visit=true\">here</A> to clear this list.)\n";
	}
	$output.="<P>\n";

	$lastname="";
	$rows="";
	while ($row=mysqli_fetch_assoc($result)) {
		if ($lastname==$row["name"]) {
			$rows.=tr(td().td("<A HREF=\"viewmovie.php?movieid=".$row["movieid"]."\">&quot;".htmlspecialchars($row["title"])."&quot;</A> on ".$row["insertdate"]));
		} else {
			if (!saneempty($lastname)) {
				$rows.=tr(td().td());
			}
			$rows.=tr(td("<A HREF=\"listmovies.php?userid=".$row["userid"]."\">".$row["name"]."</A> added&nbsp;").td("<A HREF=\"viewmovie.php?movieid=".$row["movieid"]."\">&quot;".htmlspecialchars($row["title"])."&quot;</A> on ".$row["insertdate"]));
		}
		$lastname=$row["name"];
	}
	$output.=table($rows,0,1,0);
}

$content["body"] =& $output;
dopage($content);
?>
