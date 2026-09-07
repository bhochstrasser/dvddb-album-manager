<?php 
require("inc/menu.php");
require("inc/html.php");
require("inc/common.php");

$config=getconfig();

$cols=$config["movcolumns"];

$output="";
$nav="";
$direction="";
$ownedby="";
$genre="";
$filter=array();
$url="";
$limit="";

if (saneempty($_GET["page"] ?? "")) $page=0; else $page=intval($_GET["page"]);

$offset=$page*$config["numperpage"];

$fields="movie.userid, movie.id as movieid, movie.title, movie.genreid, movie.reldate, left(movie.comments,".$config["commentchars"].") as comments, length(comments) as comlen,  movie.rating, movie.region, movie.runtime, ";
$fields.="user.email, concat(user.fname, \" \", user.lname) as name, ";
$fields.="loan.id as loanid, media.name as medianame, ";
$fields.="movie_location.album as album, movie_location.page as albumpage, movie_location.sleeve as sleeve";

$from="movie,user,media";
$joins="left join loan on (movie.id = loan.movieid) left join movie_location on (movie.id = movie_location.movieid ";
$where="movie.userid = user.id and movie.mediaid = media.id";
$orderby="title";
if (!isset($_GET["showall"])||$config["showall"]==0) {
	$limit=" limit ".$offset.",".$config["numperpage"];
} else {
	$url=addpar($url, "showall=1");
}
if (!saneempty($_GET["userid"] ?? "")) {
	$ownedby=db_result(doquery("select concat(fname, \" \", lname) from user where id = ".intval($_GET["userid"])),0);
	$filter[]="<B>Owner:</B> ".$ownedby;
	$where.=" and movie.userid = ".intval($_GET["userid"]);
	$url=addpar($url, "userid=".intval($_GET["userid"]));
}
if (!saneempty($_GET["genreid"] ?? "")) {
	$genre=db_result(doquery("select name from genre where id = ".intval($_GET["genreid"])),0);
	$filter[]="<B>Genre:</B> ".$genre;
	$where.=" and find_in_set(".intval($_GET["genreid"]).",movie.genreid)";
	$url=addpar($url, "genreid=".intval($_GET["genreid"]));
}
if (!saneempty($_GET["year"] ?? "")) {
	$filter[]="<B>Released year:</B> ".intval($_GET["year"]);
	$where.=" and movie.reldate = ".intval($_GET["year"]);
	$url=addpar($url, "year=".intval($_GET["year"]));
}
if (!saneempty($_GET["region"] ?? "")) {
	$filter[]="<B>Region:</B> ".intval($_GET["region"]);
	$where.=" and movie.region = ".intval($_GET["region"]);
	$url=addpar($url, "region=".intval($_GET["region"]));
	if (saneempty($_GET["media"] ?? "")) {
		$filter[]="<B>Media:</B> DVD";
		$where.=" and movie.mediaid = 1";
		$url=addpar($url, "media=1");
	}
}
if (!saneempty($_GET["media"] ?? "")) {
	$medianame=db_result(doquery("select name from media where id = ".intval($_GET["media"])),0);
	$filter[]="<B>Media:</B> ".$medianame;
	$where.=" and movie.mediaid = ".intval($_GET["media"]);
	$url=addpar($url, "media=".intval($_GET["media"]));
}
if (!saneempty($_GET["s"] ?? "")) {
	$filter[]="<B>Search for:</B> ".greatescape($_GET["s"]);
	if (intval($config["mysqlversion"])>=4.0) {
		$where.=" and match(movie.title) against (\"".greatescape($_GET["s"])."\" IN BOOLEAN MODE)";
	} else {
		$where.=" and match(movie.title) against (\"".greatescape($_GET["s"])."\")";
	}
	$url=addpar($url, "s=".str_replace(" ","+",htmlspecialchars($_GET["s"])));
}

$printurl="print.php".$url;
$url="listmovies.php".$url;
$navurl=$url;

if (intval($_GET["desc"] ?? 0)==1) {
	$direction.=" desc";
	$navurl=addpar($navurl, "desc=1");
}
if (!saneempty($_GET["order"] ?? "")) {
	$validorders=array("name","title","reldate","runtime","rating","album","albumpage","sleeve");
	if (in_array($_GET["order"],$validorders)) {
		$orderby=$_GET["order"] ?? "";
		$navurl=addpar($navurl, "order=".$_GET["order"]);
		if (saneempty($_GET["desc"] ?? "")) {
			$url=addpar($url, "desc=1");
		}
	}
}

if (!saneempty($filter)) {
	$output.=table(tr(td("Filters in effect:<BR />\n".implode("; ",$filter))),0,1,0,"","filters");
	$output.="<BR />\n";
}

$output.="<A HREF=\"".$printurl."\"><IMG BORDER=\"0\" SRC=\"images/print.gif\" ALT=\"Print this list\"></A> - Print results\n<BR />\n";

$query="select ".$fields." from (".$from.") ".$joins.") where ".$where." order by ".$orderby.$direction.$limit;

//$output.=$query."<BR /><BR />";

$result=doquery($query);

if (ckbit(8,$cols)) {
	$genres=array();
	$query="select id, name from genre order by id";
	$genreres=doquery($query);
	while ($genrerow=mysqli_fetch_assoc($genreres)) {
		$genres[$genrerow["id"]]=$genrerow["name"];
	}
}

if (mysqli_num_rows($result)==0) {
	$output.="No Movies found";
} else {
	// Build nav bar
	$grandtotal=db_result(doquery("select count(*) from (".$from.") ".$joins.") where ".$where),0);

	if (isset($_GET["showall"])&&$config["showall"]==1) {
		$first="1";
		$last=$grandtotal;
	} else {
		$first=($page*$config["numperpage"])+1;
		$last=$first+$config["numperpage"]-1;
		if ($last>$grandtotal) $last=$grandtotal;
	}

	$nowshowing="Showing movie #".$first." to #".$last." of ".$grandtotal." total\n";

	if (!isset($_GET["showall"])||$config["showall"]==0) {
		$pages=floor(($grandtotal+$config["numperpage"]-1)/$config["numperpage"]);

		if ($pages>1) {
			if ($page>0) {
				$nav.="<A HREF=\"".addpar($navurl,"page=".($page-1))."\"><-- Previous</A> [";
			} else {
				$nav.="<-- Previous [";
			}

			for ($i=1;$i<=$pages;$i++) {
				if ($page+1==$i) {
					$nav.=" <B>".$i."</B>";
				} else {
					$nav.=" <A HREF=\"".addpar($navurl,"page=".($i-1))."\">".$i."</A>";
				}
			}

			if (($page+1)*$config["numperpage"] < $grandtotal) {
				$nav.=" ] <A HREF=\"".addpar($navurl,"page=".($page+1))."\">Next --></A>\n";
			} else {
				$nav.=" ] Next -->\n";
			}
			if ($config["showall"]==1) $nav.=" (<A HREF=\"".addpar($navurl,"showall=1")."\">Show all</A>)\n";
		}

		// Set base url to current page.
		$url=addpar($url,"page=".$page);
	}


	$cells="";
	if (ckbit(1,$cols) && saneempty($_GET["userid"] ?? "")) $cells.=td("<A CLASS=\"tblhead\" HREF=\"".addpar($url,"order=name")."\">Owner</A>","","tablehead");
	if (ckbit(2,$cols)) $cells.=td("<A CLASS=\"tblhead\" HREF=\"".addpar($url,"order=title")."\">Title</A>","","tablehead");
	if (ckbit(4,$cols) && saneempty($_GET["year"] ?? "")) $cells.=td("<A CLASS=\"tblhead\" HREF=\"".addpar($url,"order=reldate")."\">Year</A>","","tablehead");
	if (ckbit(8,$cols)) $cells.=td("Genre(s)","","tablehead");
	if (ckbit(16,$cols)) $cells.=td("Comments","400","tablehead");
	if (ckbit(32,$cols) && saneempty($_GET["region"] ?? "")) $cells.=td("<A CLASS=\"tblhead\" HREF=\"".addpar($url,"order=region")."\">Region</A>","","tablehead");
	if (ckbit(64,$cols)) $cells.=td("<A CLASS=\"tblhead\" HREF=\"".addpar($url,"order=runtime")."\">Length</A>","","tablehead");
	if (ckbit(128,$cols) && saneempty($_GET["media"] ?? "")&&saneempty($_GET["region"] ?? "")) $cells.=td("<A CLASS=\"tblhead\" HREF=\"".addpar($url,"order=medianame")."\">Media</A>","","tablehead");
	if (ckbit(256,$cols)) $cells.=td("<A CLASS=\"tblhead\" HREF=\"".addpar($url,"order=rating")."\">Rating</A>","80","tablehead");
	if (ckbit(512,$cols)) $cells.=td("IMDb","","tablehead");
	if (ckbit(1024,$cols)) $cells.=td("Loan","","tablehead");
	if (ckbit(2048,$cols)) $cells.=td("Location","","tablehead");
	if (ckbit(4096,$cols)) $cells.=td("<A CLASS=\"tblhead\" HREF=\"".addpar($url,"order=album")."\">Album</A>","","tablehead");
	if (ckbit(8192,$cols)) $cells.=td("<A CLASS=\"tblhead\" HREF=\"".addpar($url,"order=albumpage")."\">Page</A>","","tablehead");
	if (ckbit(16384,$cols)) $cells.=td("<A CLASS=\"tblhead\" HREF=\"".addpar($url,"order=sleeve")."\">Sleeve</A>","","tablehead");

	$rows=tr($cells);
	$c=0;

	while ($row=mysqli_fetch_assoc($result)) {
		$cells="";
		if ($c++%2==0) $class="tablecell0"; else $class="tablecell1";
		if (ckbit(1,$cols) && saneempty($_GET["userid"] ?? "")) $cells.=td("<A HREF=\"mailto:".$row["email"]."\">".$row["name"]."</A>","",$class);
		if (ckbit(2,$cols)) $cells.=td("<A HREF=\"viewmovie.php?movieid=".$row["movieid"]."\">".htmlspecialchars($row["title"])."</A>","",$class);

		if (ckbit(4,$cols) && saneempty($_GET["year"] ?? "")) $cells.=td($row["reldate"],"",$class);
		if (ckbit(8,$cols)) {
			$genreout=array();
			$tmp=explode(",",$row["genreid"]);
			foreach ($tmp as $val) {
				$genreout[]=$genres[$val];
			}
			$cells.=td(implode(" / ",$genreout),"",$class);
		}
		if (ckbit(16,$cols)) {
			$comments=htmlspecialchars($row["comments"]);
			if ($row["comlen"]>$config["commentchars"]) $comments.=" <A HREF=\"viewmovie.php?movieid=".$row["movieid"]."\">(Read more..)</A>";
			$cells.=td($comments,"",$class);
		}
		if (ckbit(32,$cols)) {
			if (saneempty($_GET["region"] ?? "")) {
				$region=$row["region"];
				if (saneempty($row["region"])) $region="--";
				$cells.=td($region,"",$class);
			}
		}
		if (ckbit(64,$cols)) $cells.=td($row["runtime"],"",$class);
		if (ckbit(128,$cols) && saneempty($_GET["media"] ?? "") && saneempty($_GET["region"] ?? "")) $cells.=td($row["medianame"],"",$class);
		if (ckbit(256,$cols)) {
			$stars="";
			if ($row["rating"]==0) {
				$stars="Not rated";
			} else {
				if ($row["rating"] <=5) {
					$img="themes/".$config["theme"]."/images/blackstar.gif";
				} else {
					$img="themes/".$config["theme"]."/images/redstar.gif";
				}
				for ($i=1;$i<=$row["rating"];$i++) {
					if ($i<6) $stars.="<IMG SRC=\"".$img."\" ALT=\"+\">";
				}
			}
			$cells.=td($stars,"",$class);
		}
		if (ckbit(512,$cols)) $cells.=td("<A TARGET=\"_blank\" HREF=\"http://us.imdb.com/Tsearch?title=".urlencode($row["title"])."&type=substring&year=".$row["reldate"]."\">IMDb</A>","",$class);
		if (ckbit(1024,$cols)) {
			if (!saneempty($row["loanid"])) {
				$cells.=td("<A HREF=\"listloans.php\">On Loan</A>","",$class);
			} else if ($row["userid"]==$_COOKIE["userid"]) {
				$cells.=td("<A HREF=\"loan.php?movieid=".$row["movieid"]."\">Loan out</A>","",$class);
			} else {
				$cells.=td("<A HREF=\"mailto:".$row["email"]."?subject=Cool, you have '".$row["title"]."', Could I borrow it?\">Request</A>","",$class);
			}
		}

		$haslocation = !is_null($row["album"]);
		if (ckbit(2048,$cols)) {
			$location = $haslocation ? "A".$row["album"]." / P".$row["albumpage"]." / S".$row["sleeve"] : "";
			$cells.=td($location,"",$class);
		}
		if (ckbit(4096,$cols)) $cells.=td($haslocation ? $row["album"] : "","",$class);
		if (ckbit(8192,$cols)) $cells.=td($haslocation ? $row["albumpage"] : "","",$class);
		if (ckbit(16384,$cols)) $cells.=td($haslocation ? $row["sleeve"] : "","",$class);

		$rows.=tr($cells);
	}

	$output.="<BR />\n";

	if (($config["movienav"]&1)==1) $output.=table(tr(td($nowshowing)).tr(td($nav)),0,0,0,"","movienavtop");
	$output.=table($rows,0,1,0,"","datatable");
	if (($config["movienav"]&2)==2) $output.=table(tr(td($nav)).tr(td($nowshowing)),0,0,0,"","movienavbottom");
}

$content["body"] =& $output;
dopage($content);

?>
