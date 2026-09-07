<?php
require("inc/menu.php");
require("inc/html.php");
require("inc/common.php");

$config=getconfig();

$output="";
$rows="";

if (!isset($_GET["movieid"])||!is_numeric($_GET["movieid"])) {
	header("Location: listmovies.php");
}

$movieid=intval($_GET["movieid"]);

$genres=array();
$query="select id, name from genre order by id";
$genreres=doquery($query);
        
while ($genrerow=mysqli_fetch_assoc($genreres)) {
	$genres[$genrerow["id"]]=$genrerow["name"];
}

$fields ="user.email, user.fname, user.lname, ";
$fields.="movie.userid, movie.title, movie.reldate, movie.comments, movie.genreid, movie.rating, movie.runtime, movie.region, movie.director, movie.sound, movie.video, movie.extra, ";
$fields.="loan.userid as loanedto, loan.loanee, loan.loaneeemail, date_format(loan.loandate,\"".$config["dateformat"]."\") as loandate, ";
$fields.="media.name as medianame";

$from="movie, user, media";

$joins ="left join loan on (movie.id = loan.movieid";
$where="movie.userid = user.id and movie.mediaid = media.id and movie.id = ".$movieid;

$query="select ".$fields." from (".$from.") ".$joins.") where ".$where;

$mov=mysqli_fetch_assoc(doquery($query));

$genreout=array();
$tmp=explode(",",$mov["genreid"]);
foreach ($tmp as $val) {
	$genreout[]=$genres[$val];
}

$rows.=tr(td("<SPAN CLASS=\"movtitle\">".htmlspecialchars($mov["title"])."</SPAN>","100%", "movtitle","","","2"));
$rows.=tr(td("<B>Movie details</B>","40%").td("<B>User details</B>","60%"));


$r=tr(td("Owned by:","120","movdetails").td($mov["fname"]." ".$mov["lname"]." (<A HREF=\"mailto:".$mov["email"]."\">".$mov["email"]."</A>)","","movdetails"));
$r.=tr(td("Comments:","","movdetails","","TOP").td(htmlspecialchars($mov["comments"]),"","movdetails"));

if ($mov["loanedto"]==NULL) {
	$loaned="Not on loan";
} else if ($mov["loanedto"]==0) {
	$loaned="On loan to <A HREF=\"mailto:".$mov["loaneeemail"]."\">".$mov["loanee"]."</A> since ".$mov["loandate"];
} else {
	$result=doquery("select fname, lname, email from user where id = ".$mov["loanedto"]);
	$row=mysqli_fetch_assoc($result);
	$loaned="On loan to <A HREF=\"mailto:".$row["email"]."\">".$row["fname"]." ".$row["lname"]."</A> since ".$mov["loandate"];
}	

$r.=tr(td("Loan status:","","movdetails").td($loaned,"","movdetails"));

// Left column
                
if ($mov["rating"]==0) {
	$stars="Not rated";
} else {
	if ($mov["rating"] <=5) {
		$img="themes/".$config["theme"]."/images/blackstar.gif";
	} else {
		$img="themes/".$config["theme"]."/images/redstar.gif";
	}
	for ($i=1;$i<=$mov["rating"];$i++) {
		if ($i<6) $stars.="<IMG SRC=\"".$img."\" ALT=\"+\">";
	}
}

if (strtoupper($mov["medianame"])=="DVD") {
	$media=$mov["medianame"]." (Region ".$mov["region"].")";
} else {
	$media=$mov["medianame"];
}

$l=tr(td("Release date:","120","movdetails").td($mov["reldate"],"","movdetails"));
$l.=tr(td("Director:","","movdetails").td(htmlspecialchars($mov["director"]),"","movdetails"));
$l.=tr(td("Genre(s):","","movdetails").td(implode(" / ",$genreout),"","movdetails"));
$l.=tr(td("Media:","","movdetails").td($media,"","movdetails"));
$l.=tr(td("Sound:","","movdetails").td(htmlspecialchars($mov["sound"]),"","movdetails"));
$l.=tr(td("Video:","","movdetails").td(htmlspecialchars($mov["video"]),"","movdetails"));
$l.=tr(td("Extra info:","","movdetails").td(htmlspecialchars($mov["extra"]),"","movdetails"));
$l.=tr(td("Length (minutes):","","movdetails").td($mov["runtime"],"","movdetails"));
$l.=tr(td("User rating:","","movdetails").td($stars,"","movdetails"));

$rows.=tr(td(table($l,0,0,0),"","","","TOP").td(table($r,0,0,0),"","","","TOP"));

$output.=table($rows,0,1,4,"100%");

if ($mov["userid"]==$_COOKIE["userid"]||($config["allowadminedit"]==1&&$admin)) {
	$output.="<P>";
	$output.=form_begin("editmovie.php","GET");
	$output.=input_hidden("movieid",$movieid);
	$output.=submit("Edit this movie");
	$output.=form_end();
}

$content["body"] =& $output;
dopage($content);
?>
