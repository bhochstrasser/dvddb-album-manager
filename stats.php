<?php
require("inc/menu.php");
require("inc/html.php");
require("inc/common.php");

$config=getconfig();

$output="";

$barwidth=$config["graphwidth"]-150;

//////////////////////////////////////////////////////////////////////////////////
// Movies by year
$endyear=date("Y");
$startyear=floor(($endyear-$config["yearwindow"])/10)*10;

$moviesbyyear=doquery("select count(reldate) as cnt, reldate from movie where reldate >=".$startyear." group by reldate order by reldate");
$moviesyearmaxcnt=mysqli_fetch_assoc(doquery("select count(reldate) as cnt from movie where reldate >=".$startyear." group by reldate order by cnt desc limit 1"));
if ($moviesyearmaxcnt["cnt"]==0) {
	$numrows=1;
} else {
	$numrows=$moviesyearmaxcnt["cnt"];
}
$scl=$barwidth/$numrows;

while($row=mysqli_fetch_assoc($moviesbyyear)) {
	$data[$row["reldate"]]=$row["cnt"];
}

$yeartr="";

for ($i=$startyear;$i<=$endyear;$i++) {
	if (saneempty($data[$i])) {
		$tr=tr(td("&nbsp;",$barwidth));
		$cnt=0;
	} else {
		$tr=tr(td("&nbsp;",round($data[$i]*$scl),"statsbar").td("&nbsp;",$barwidth-round($data[$i]*$scl),"emptybar"));
		$cnt=$data[$i];
	}
	$yeartd=td("<A HREF=\"listmovies.php?year=".$i."\">".$i."</A>&nbsp;&nbsp;","","stats");
	$yeartd.=td(table($tr,0,0,0),$barwidth,"stats");
	$yeartd.=td($cnt,"","stats");
	$yeartr.=tr($yeartd);
}
$yeargraph=table($yeartr,0,2,2,$config["graphwidth"]);
//////////////////////////////////////////////////////////////////////////////////


//////////////////////////////////////////////////////////////////////////////////
// DVDs by region

$dvdsbyregion=doquery("select count(id) as cnt, region from movie where mediaid=1 group by region order by region");
$dvdregionmaxcnt=mysqli_fetch_assoc(doquery("select count(id) as cnt from movie group by region order by cnt desc limit 1"));
if ($dvdregionmaxcnt["cnt"]==0) {
	$numrows=1;
} else {
	$numrows=$dvdregionmaxcnt["cnt"];
}
$scl=$barwidth/$numrows;

$data="";

while($row=mysqli_fetch_assoc($dvdsbyregion)) {
	$data[$row["region"]]=$row["cnt"];
}

$regiontr="";

for ($i=0;$i<=6;$i++) {
	if (saneempty($data[$i])) {
		$tr=tr(td("&nbsp;",$barwidth));
		$cnt=0;
	} else {
		$tr=tr(td("&nbsp;",round($data[$i]*$scl),"statsbar").td("&nbsp;",$barwidth-round($data[$i]*$scl),"emptybar"));
		$cnt=$data[$i];
	}
	$regiontd=td("<A HREF=\"listmovies.php?region=".$i."\">Region ".$i."</A>&nbsp;&nbsp;","","stats");
	$regiontd.=td(table($tr,0,0,0),$barwidth,"stats");
	$regiontd.=td($cnt,"","stats");
	$regiontr.=tr($regiontd);
}
$regiongraph=table($regiontr,0,2,2,$config["graphwidth"]);
//////////////////////////////////////////////////////////////////////////////////

//////////////////////////////////////////////////////////////////////////////////
// Movies by Genre
$moviesbygenre=doquery("select genre.id, count(movie.genreid) as cnt, genre.name from genre left join movie on find_in_set(genre.id,movie.genreid) group by genre.id order by cnt desc, genre.name");
$row=mysqli_fetch_assoc($moviesbygenre);
if ($row["cnt"]==0) {
	$numrows=1;
} else {
	$numrows=$row["cnt"];
}
$scl=$barwidth/$numrows;
mysqli_data_seek($moviesbygenre,0);

$genretr="";

while ($row=mysqli_fetch_assoc($moviesbygenre)) {
	if ($row["cnt"]==0) {
		$tr=tr(td("&nbsp;",$barwidth));
	} else {
		$tr=tr(td("&nbsp;",round($row["cnt"]*$scl),"statsbar").td("&nbsp;",$barwidth-round($row["cnt"]*$scl),"emptybar"));
	}
	$genretd=td("<A HREF=\"listmovies.php?genreid=".$row["id"]."\">".$row["name"]."</A>&nbsp;&nbsp;","","stats");
	$genretd.=td(table($tr,0,0,0),$barwidth,"stats");
	$genretd.=td($row["cnt"],"","stats");
	$genretr.=tr($genretd);
}
$genregraph=table($genretr,0,2,2,$config["graphwidth"]);
//////////////////////////////////////////////////////////////////////////////////


//////////////////////////////////////////////////////////////////////////////////
// Number of Movies by User.
$countbyuser=doquery("select user.id, user.fname, user.lname, count(movie.id) as cnt from user left join movie on user.id=movie.userid where user.enabled=1 group by user.id, movie.userid order by cnt desc");
$row=mysqli_fetch_assoc($countbyuser);
if ($row["cnt"]==0) {
	$numrows=1;
} else {
	$numrows=$row["cnt"];
}
$scl=$barwidth/$numrows;
mysqli_data_seek($countbyuser,0);

$usertr="";

while ($row=mysqli_fetch_assoc($countbyuser)) {
	if ($row["cnt"]==0) {
		$tr=tr(td("&nbsp;",$barwidth));
	} else {
		$tr=tr(td("&nbsp;",round($row["cnt"]*$scl),"statsbar").td("&nbsp;",$barwidth-round($row["cnt"]*$scl),"emptybar"));
	}
	$usertd=td("<A HREF=\"listmovies.php?userid=".$row["id"]."\">".$row["fname"]." ".$row["lname"]."</A>&nbsp;&nbsp;","","stats");
	$usertd.=td(table($tr,0,0,0),$barwidth,"stats");
	$usertd.=td($row["cnt"],"","stats");
	$usertr.=tr($usertd);
}
$usergraph=table($usertr,0,2,2,$config["graphwidth"]);
//////////////////////////////////////////////////////////////////////////////////

//////////////////////////////////////////////////////////////////////////////////
// Movies by media
$moviesbymedia=doquery("select media.id, count(movie.mediaid) as cnt, media.name from media left join movie on movie.mediaid = media.id group by movie.mediaid, media.id order by cnt desc, media.name");
$row=mysqli_fetch_assoc($moviesbymedia);
if ($row["cnt"]==0) {
	$numrows=1;
} else {
	$numrows=$row["cnt"];
}
$scl=$barwidth/$numrows;
mysqli_data_seek($moviesbymedia,0);

$mediatr="";

while ($row=mysqli_fetch_assoc($moviesbymedia)) {
	if ($row["cnt"]==0) {
		$tr=tr(td("&nbsp;",$barwidth));
	} else {
		$tr=tr(td("&nbsp;",round($row["cnt"]*$scl),"statsbar").td("&nbsp;",$barwidth-round($row["cnt"]*$scl),"emptybar"));
	}
	$mediatd=td("<A HREF=\"listmovies.php?media=".$row["id"]."\">".$row["name"]."</A>&nbsp;&nbsp;","","stats");
	$mediatd.=td(table($tr,0,0,0),$barwidth,"stats");
	$mediatd.=td($row["cnt"],"","stats");
	$mediatr.=tr($mediatd);
}
$mediagraph=table($mediatr,0,2,2,$config["graphwidth"]);
//////////////////////////////////////////////////////////////////////////////////

$output.="Number of Movies by year: (from ".$startyear." to ".$endyear.")<BR /><BR />\n";
$output.=$yeargraph;

$output.="<BR /><HR><BR />\n";

$output.="Number of DVDs by region:<BR /><BR />\n";
$output.=$regiongraph;

$output.="<BR /><HR><BR />\n";

$output.="Number of Movies by user:<BR /><BR />\n";
$output.=$usergraph;

$output.="<BR /><HR><BR />\n";

$output.="Number of Movies by genre:<BR /><BR />\n";
$output.=$genregraph;

$output.="<BR /><HR><BR />\n";

$output.="Number of Movies by media:<BR /><BR />\n";
$output.=$mediagraph;

$content["body"] =& $output;
dopage($content);

?>
