<?php
require("inc/menu.php");
require("inc/controls.php");
require("inc/html.php");
require("inc/common.php");

$config=getconfig();

$output="";
$cells="";
$rows="";
$imdb="";
$movextras="";

if (isset($_GET["movieid"])) {
	// This is an update. Need to retrieve data.
	$movieid=intval($_GET["movieid"]);
	$result=doquery("select id, userid, title, reldate, comments, rating, genreid, region, runtime, mediaid, director, sound, video, extra from movie where id = ".$movieid);
	$row=mysqli_fetch_assoc($result);

	if (($row["userid"]!=intval($_COOKIE["userid"])) && !($admin && $config["allowadminedit"]==1)) {
		// Can only edit your own records
		header("Location: index.php");
		die();
	} else {
		$movietitle=htmlspecialchars($row["title"]);
		$date=$row["reldate"];
		$curgenre=doquery("select id, name from genre where id in (".$row["genreid"].")");
		while ($grow=mysqli_fetch_row($curgenre)) {
			$unsortgenre[$grow[0]]=$grow[1];
		}
		foreach (explode(",",$row["genreid"]) as $val) {
			$genre[]=array($val, $unsortgenre[$val]);
		}
		$avgenres=restoarray(doquery("select id, name from genre where id not in (".$row["genreid"].") order by name"));
		$comments=htmlspecialchars($row["comments"]);
		$region=$row["region"];
		$rating=$row["rating"];
		$runtime=$row["runtime"];
		$media=$row["mediaid"];
		$director=htmlspecialchars($row["director"]);
		$sound=htmlspecialchars($row["sound"]);
		$video=htmlspecialchars($row["video"]);
		$extra=htmlspecialchars($row["extra"]);
	}
} else {
	$movieid=0;
	$movietitle="";
	$date=date("Y");
	$genre=array();
	$avgenres=restoarray(doquery("select id, name from genre order by name"));
	$comments="";
	$media=$config["defmedia"];
	$region=$config["defregion"];
	$rating=0;
	$runtime=0;
	$director="";
	$sound="";
	$video="";
	$extra="";
}

if (isset($_POST["movieid"])) {
	if (saneempty($_POST["movietitle"])) {
		$error="You must enter a title.\n";
	} else {
		// Ugly hack. DVD type must be 1 for this to work.
		$region=$_POST["region"];
		if ($_POST["media"]!=1) $region="NULL";
		if ($_POST["genres"]=="") $genres="-1"; else $genres=$_POST["genres"];
		if ($_POST["movieid"]==0) {
			$query="insert movie values (NULL, ".intval($_COOKIE["userid"]).", \"".greatescape($_POST["movietitle"])."\", \"".intval($_POST["year"])."\", \"".greatescape($_POST["comments"])."\", ".intval($_POST["rating"]).", \"".greatescape($genres)."\", ".intval($region).", now(), ".intval($_POST["runtime"]).", ".intval($_POST["media"]).", \"".greatescape($_POST["director"])."\", \"".greatescape($_POST["sound"])."\", \"".greatescape($_POST["video"])."\", \"".greatescape($_POST["extra"])."\")";
			doquery($query);
			$id=db_insert_id();
			$muserid=intval($_COOKIE["userid"]);
		} else {
			// This is an update.
			// Check user matches or admin
			$muserid=db_result(doquery("select userid from movie where id = ".intval($_POST["movieid"])),0);
			if ($muserid!=$_COOKIE["userid"] && !($admin && $config["allowadminedit"]==1)) {
				// Can only edit your own records
				header("Location: index.php");
				die();
			} else {
				$query="update movie set title=\"".greatescape($_POST["movietitle"])."\", reldate=\"".intval($_POST["year"])."\", comments=\"".greatescape($_POST["comments"])."\", rating=".intval($_POST["rating"]).", genreid=\"".greatescape($genres)."\", region=".intval($region).", runtime=".intval($_POST["runtime"]).", mediaid=".intval($_POST["media"]).", director=\"".greatescape($_POST["director"])."\", sound=\"".greatescape($_POST["sound"])."\", video=\"".greatescape($_POST["video"])."\", extra=\"".greatescape($_POST["extra"])."\" where id = ".intval($_POST["movieid"]);
				doquery($query);
				$id=intval($_POST["movieid"]);
			}
		}

		// Find position of this entry.
		$query="select id from movie where userid = ".$muserid." order by title";
		$result=doquery($query);

		$i=0;

		while ($row=mysqli_fetch_row($result)) {
			$data[$row[0]]=$i;
			$i++;
		}

		$page=floor($data[$id]/$config["numperpage"]);

		header("Location: listmovies.php?userid=".$muserid."&page=".$page);
	}
}

$medias=restoarray(doquery("select id, name from media order by name"));

for ($i=0;$i<=6;$i++) {
	$c=FALSE;
	if ($i==$region) $c=TRUE;
	$regions.=input_radio("region",$i,$c)." ".$i;
}

$content["head"]="<SCRIPT TYPE=\"text/javascript\" SRC=\"js/genre.js\"></SCRIPT>\n";

if ($movieid==0) {
	$content["head"].="<SCRIPT TYPE=\"text/javascript\" SRC=\"js/imdb.js\"></SCRIPT>\n";
	$imdb="&nbsp;".input_button("imdbbut", "Search IMDb", "plain", "onClick=\"javascript:imdbsearch(this.form.movietitle.value);\"");
}
$output.=$error;

$numlines="6";

$right="Available:<BR />".input_multiselect("avgenre","avgenre",array(),$avgenres,$numlines,"genre");
$mid=input_button("add", "&lt;-- Add", "button", "onClick=\"javascript:move(this.form.avgenre, this.form.genre,0);\"");
$mid.=input_button("remove", "Remove --&gt;", "button", "onClick=\"javascript:move(this.form.genre, this.form.avgenre,1);\"");
$left="Selected:<BR />".input_multiselect("genre","genre",array(),$genre,$numlines,"genre");

$genreedit=table(tr(td($left).td($mid,"140","","CENTER","MIDDLE").td($right)),0,0,0);

$output.=form_begin("editmovie.php","POST");
$output.=input_hidden("movieid",$movieid);
$output.=input_hidden("genres","");

$rows=tr(td("Movie Title:","","","RIGHT").td(input_text("movietitle",80,255,$movietitle).$imdb,"","","LEFT").td(rating($rating),"","","","TOP","","11"));
$rows.=tr(td("Year of release:","","","RIGHT").td(datesel("year",$date,$config["lowdate"],date("Y")),"","","LEFT"));
$rows.=tr(td("Director:","","","RIGHT").td(input_text("director",80,255,$director),"","","LEFT"));
$rows.=tr(td("Genres:","","","RIGHT","TOP").td($genreedit,"","","LEFT"));
$rows.=tr(td("Media:","","","RIGHT").td(input_select("media",$media,$medias),"","","LEFT"));
$rows.=tr(td("Soundtracks:","","","RIGHT").td(input_text("sound",80,255,$sound),"","","LEFT"));
$rows.=tr(td("Video format:","","","RIGHT").td(input_text("video",80,255,$video),"","","LEFT"));
$rows.=tr(td("Comments:","","","RIGHT","TOP").td(textarea("comments",$comments,6,80),"","","LEFT"));
$rows.=tr(td("Extra info (Box set, Special edition, etc.):","","","RIGHT").td(input_text("extra",80,255,$extra),"","","LEFT"));
$rows.=tr(td("Length (minutes):","","","RIGHT").td(input_text("runtime",4,4,$runtime),"","","LEFT"));
$rows.=tr(td("Region:","","","RIGHT").td($regions,"","","LEFT"));
$rows.=tr(td(input_button("save", "Save movie","plain","onClick=\"javascript:this.form.genres.value = makeStringFromSelect(this.form.genre); this.form.submit();\""),"","","CENTER","","3"));

$output.=table($rows,0,2,2,"100%");

$output.=form_end();
if ($movieid!=0) {
	$output.=form_begin("delete.php","POST");
	$output.=input_hidden("movieid",$movieid);
	$rows=tr(td(submit("Delete Movie"),"","","CENTER"));
	$output.=table($rows,0,2,2,"100%");
	$output.=form_end();
}

$content["body"] =& $output;
dopage($content);

?>
