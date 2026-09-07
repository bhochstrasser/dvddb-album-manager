<?php
require("inc/menu.php");
require("inc/html.php");
require("inc/common.php");
require("inc/controls.php");

$config=getconfig();

$output="";
$error="";
$cells="";
$rows="";

if (isset($_GET["userid"])) {
	$userid=intval($_GET["userid"]);
} else if (isset($_POST["userid"])) {
	$userid=intval($_POST["userid"]);
} else {
	$userid=intval($_COOKIE["userid"]);
}

$editconfig=getconfig($userid);

if (!$admin&&$userid!=$_COOKIE["userid"]) {
	header("Location: index.php");
	die();
} 

function updprefs ($item, $value, $userid, $current)
{
	if (in_array($item,$current)) {
		$query="update userprefs set value=\"".greatescape($value)."\" where item=\"".greatescape($item)."\" and userid=".$userid;
	} else {
		$query="insert userprefs values (".$userid.",\"".greatescape($item)."\", \"".greatescape($value)."\")";
	}
	doquery($query);
}

$action=$_POST["action"] ?? "";

if (!saneempty($action)) {
	switch ($action) {
		case "changeprefs":
			$current=array();
			$result=doquery("select item from userprefs where userid=".$userid);
			while ($row=mysqli_fetch_assoc($result)) $current[]=$row["item"];

			updprefs("movienav", $_POST["newnav"], $userid, $current);
			updprefs("theme", $_POST["newtheme"], $userid, $current);
			updprefs("defregion", $_POST["newregion"], $userid, $current);
			updprefs("defmedia", $_POST["newmedia"], $userid, $current);

			// Columns
			$total=0;
			foreach ($_POST["cols"] as $bit => $enabled) {
				if (strtolower($enabled)=="on") $total+=intval($bit);
			}
			updprefs("movcolumns", $total, $userid, $current);

			$output.="Settings saved.<BR />\n";
			$editconfig=getconfig($userid);
			if ($userid==$_COOKIE["userid"]) $config=$editconfig;

		break;
		case "changepass":
			if (saneempty($_POST["pass1"])) $error.="Must enter password.<BR />\n";
			if ($_POST["pass1"]!=$_POST["pass2"]) $error.="Passwords do not match.<BR />\n";
			if (strlen($_POST["pass1"])<6) $error.="Password must be at least 6 characters long.<BR />\n";

			if (!saneempty($error)) {
				$output.="The following errors occured:\n<P>\n";
				$output.=$error;
				$output.="<P>\nGo back and correct these errors to proceed";
			} else {
				$password=md5($_POST["pass1"]);
				doquery("update user set password=\"".$password."\" where id = ".$userid);
				if ($userid==$_COOKIE["userid"]) { // Only reset cookie if not admin changing pass
					setcookie("userpass",$password,time()+(3600*24*365));
				}
				$output.="Password changed.<BR />\n";
			}

		break;
		case "updateuser":
			if (saneempty($_POST["email"])) $error.="Email address must be entered.<BR />\n";
			if (saneempty($_POST["firstname"])) $error.="First name must be entered.<BR />\n";
			if (saneempty($_POST["lastname"])) $error.="Last name must be entered.<BR />\n";
			if (saneempty($_POST["city"])) $error.="City must be entered.<BR />\n";

			$result=doquery("select id from user where email = \"".$_POST["email"]."\" and id != ".$userid);
			if (mysqli_num_rows($result)>0) $error.="Email address in use by another account.<BR />\n";

			if (!saneempty($error)) {
				$output.="The following errors occured:\n<P>\n";
				$output.=$error;
				$output.="<P>\nGo back and correct these errors to proceed";
			} else {
				doquery("update user set email=\"".greatescape($_POST["email"])."\", fname=\"".greatescape($_POST["firstname"])."\", lname=\"".greatescape($_POST["lastname"])."\", city=\"".greatescape($_POST["city"])."\", regioncode=\"".greatescape($_POST["region"])."\", countrycode=\"".greatescape($_POST["country"])."\" where id = ".$userid);
				$output.="Account information saved.<BR />\n";
			}
		break;
	}
}

$regions=restoarray(doquery("select code, name from region order by name"));
$countries=restoarray(doquery("select code, name from country order by name"));

$result=doquery("select email, fname, lname, city, regioncode, countrycode from user where id = ".$userid);

$row=mysqli_fetch_assoc($result);

$usertbl.=form_begin("settings.php","POST");
$usertbl.=input_hidden("action","updateuser");
$usertbl.=input_hidden("userid",$userid);

$rows.=tr(td("Email address:","35%","","RIGHT").td(input_text("email",30,255,htmlspecialchars($row["email"])),"","","LEFT"));
$rows.=tr(td("First Name:","35%","","RIGHT").td(input_text("firstname",30,255,htmlspecialchars($row["fname"])),"","","LEFT"));
$rows.=tr(td("Last Name:","35%","","RIGHT").td(input_text("lastname",30,255,htmlspecialchars($row["lname"])),"","","LEFT"));
$rows.=tr(td("City:","35%","","RIGHT").td(input_text("city",30,255,htmlspecialchars($row["city"])),"","","LEFT"));
$rows.=tr(td("Region:","35%","","RIGHT").td(input_select("region",$row["regioncode"],$regions),"","","LEFT"));
$rows.=tr(td("Country:","35%","","RIGHT").td(input_select("country",$row["countrycode"],$countries),"","","LEFT"));
$rows.=tr(td(submit("Update details"),"","","CENTER","","2"));

$usertbl.=table($rows,0,2,0,"100%");
$usertbl.=form_end();

$rows=tr(td("Account information","","tablehead"));
$rows.=tr(td($usertbl,"","tablecell0"));
$output.=table($rows,0,2,0,"600");

$output.="<BR />\n";

$passtbl.=form_begin("settings.php","POST");
$passtbl.=input_hidden("action","changepass");
$passtbl.=input_hidden("userid",$userid);

$rows=tr(td("New Password:","35%","","RIGHT").td(input_passwd("pass1",30,50),"","","LEFT"));
$rows.=tr(td("Confirm password:","35%","","RIGHT").td(input_passwd("pass2",30,50),"","","LEFT"));
$rows.=tr(td(submit("Change password"),"","","CENTER","","2"));
$passtbl.=table($rows,0,2,0,"100%");
$passtbl.=form_end();

$rows=tr(td("Change password","","tablehead"));
$rows.=tr(td($passtbl,"","tablecell0"));
$output.=table($rows,0,2,0,"600");

// Preferences table
$output.="<BR />\n";

$prefstbl.=form_begin("settings.php","POST");
$prefstbl.=input_hidden("action","changeprefs");
$prefstbl.=input_hidden("userid",$userid);

$handle=opendir("themes");

while (false !== ($file = readdir($handle))) {
	$c=FALSE;
	if ($file!="."&&$file!=".."&&$file!="CVS") {
		$themes[]=array($file,$file);
	}
}
closedir($handle);

$medias=restoarray(doquery("select id, name from media order by name"));

for ($i=0;$i<=6;$i++) {
	$c=FALSE;
	if ($i==$editconfig["defregion"]) $c=TRUE;
	$dvdregions.=input_radio("newregion",$i,$c)." ".$i;
}

$navs[]=array("1", "Top only");
$navs[]=array("2", "Bottom only");
$navs[]=array("3", "Top &amp; Bottom");

$rows=tr(td("Theme:","50%","","RIGHT").td(input_select("newtheme", $editconfig["theme"], $themes),"50%"));
$rows.=tr(td("Default DVD region:","","","RIGHT").td($dvdregions));
$rows.=tr(td("Default media:","","","RIGHT").td(input_select("newmedia",$editconfig["defmedia"],$medias)));
$rows.=tr(td("Movie columns:<BR /><SMALL>Location may be shown compactly as A/P/S or as separate Album, Page, and Sleeve columns.</SMALL>","","","RIGHT","TOP").td(columns($editconfig["movcolumns"])));
$rows.=tr(td("Display movie navigation:","","","RIGHT","TOP").td(input_select("newnav",$editconfig["movienav"],$navs)));
$rows.=tr(td(submit("Save preferences"),"","","CENTER","","2"));

$prefstbl.=table($rows,0,2,0,"100%");
$prefstbl.=form_end();

$rows=tr(td("User preferences","","tablehead"));
$rows.=tr(td($prefstbl,"","tablecell0"));
$output.=table($rows,0,2,0,"600");

$content["body"] =& $output;
dopage($content);

?>
