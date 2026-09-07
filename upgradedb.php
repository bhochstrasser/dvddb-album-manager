<html>
<head>
<title>upgrading database</title>
</head>
<body>
<?php
function dosquery($query)
{
	// Actually do some error checking
	$result=doquery($query);
	if ($result==FALSE) {
		die("The following error occured: <BR /><BR />\n".db_error()."\n\n</body></html>\n");
	}
}
require("inc/db.php");

// 0.5 pre3

$test=@doquery("select count(*) from config");

if (db_errno()=="1146") {  // 1146 = Table not found
	// Config table does not exist. Create and populate.
	dosquery("create table config (item varchar(255), value varchar(255), comments varchar(255))");
	$settings=array();
	$settings[]=array("item" => "sitename", "value" => "An Unconfigured DVD Database", "comments" => "");
	$settings[]=array("item" => "webmaster", "value" => "your@email.address.here", "comments" => "");
	$settings[]=array("item" => "home", "value" => "http://your.site.com/dvddb", "comments" => "No trailing slash on the URL");
	$settings[]=array("item" => "approvalemail", "value" => "approvalmail@yourdomain.com", "comments" => "Address to which new user requests are sent");
	$settings[]=array("item" => "siteemail", "value" => "siteemail@yourdomain.com", "comments" => "Address from which site emails are sent");
	$settings[]=array("item" => "lowdate", "value" => "1950", "comments" => "Lowest date for date dropdowns");
	$settings[]=array("item" => "highdate", "value" => "2005", "comments" => "Highest date for date dropdowns");
	$settings[]=array("item" => "defregion", "value" => "1", "comments" => "Default region for new DVDs");
	$settings[]=array("item" => "showimdb", "value" => "1", "comments" => "Generate auto-IMDb link");
	$settings[]=array("item" => "numperpage", "value" => "20", "comments" => "Number of results per page");
	$settings[]=array("item" => "version", "value" => "0.5pre3", "comments" => "Database version - Do NOT change this value");

	foreach ($settings as $setting) {
		dosquery("insert config values (\"".$setting["item"]."\", \"".$setting["value"]."\", \"".$setting["comments"]."\")");
	}

	dosquery("alter table dvd add fulltext title (vchDVDTitle)");

	dosquery("update dvd set vchComments = replace(vchComments,\"&quot;\",\"\\\"\")");
	dosquery("update dvd set vchDVDTitle = replace(vchDVDTitle,\"&quot;\",\"\\\"\")");
	dosquery("update dvd set vchComments = replace(vchComments,\"&amp;\",\"&\")");
	dosquery("update dvd set vchDVDTitle = replace(vchDVDTitle,\"&amp;\",\"&\")");
	dosquery("update dvd set vchComments = replace(vchComments,\"&lt;\",\"<\")");
	dosquery("update dvd set vchDVDTitle = replace(vchDVDTitle,\"&lt;\",\"<\")");
	dosquery("update dvd set vchComments = replace(vchComments,\"&gt;\",\">\")");
	dosquery("update dvd set vchDVDTitle = replace(vchDVDTitle,\"&gt;\",\">\")");

	dosquery("alter table dvd add column iRuntime int(11) default '0'");
}

$test=db_result(doquery("select value from config where item = \"version\""),0);
if ($test=="0.5pre3") {
	dosquery("alter table loan add column loanee varchar(255) after iUserId");
	dosquery("alter table loan add column loaneeemail varchar(255) after loanee");
	dosquery("update config set value=\"0.5pre4\" where item=\"version\"");
	dosquery("insert config values (\"dateformat\", \"%W %D %M, %Y\", \"Format used to display date fields. See <A HREF=\\\"http://www.mysql.com/doc/en/Date_and_time_functions.html#IDX1333\\\">the mysql docs</A> for more options.\")");
	dosquery("insert config values (\"graphwidth\",\"600\",\"Width of stats graphs, in pixels\")");
}

$test=db_result(doquery("select value from config where item = \"version\""),0);
if ($test=="0.5pre4") {
	dosquery("update config set value=\"0.5\" where item=\"version\"");
}

$test=db_result(doquery("select value from config where item = \"version\""),0);
if ($test=="0.5") {
	dosquery("alter table country change column chCountryCode code char(2) not null");
	dosquery("alter table country change column vchCountryDesc name varchar(255) not null");
	dosquery("alter table country drop index chCountryCode");
	dosquery("alter table country add index code (code)");

	dosquery("alter table genre change column iGenreId id int not null auto_increment");
	dosquery("alter table genre change column vchGenreDesc name varchar(64) not null");

	dosquery("alter table loan change column iLoanId id int not null auto_increment");
	dosquery("alter table loan change column iDVDId movieid int not null");
	dosquery("alter table loan change column iUserId userid int not null");
	dosquery("alter table loan change column dtLoanDate loandate date not null");
	dosquery("alter table loan drop tiReturned");
	dosquery("alter table loan drop index iDVDId");
	dosquery("alter table loan drop index iUserId");
	dosquery("alter table loan add index movieid (movieid)");
	dosquery("alter table loan add index userid (userid)");

	dosquery("alter table region change column chRegionCode code char(2) not null");
	dosquery("alter table region change column vchRegionDesc name varchar(255) not null");
	dosquery("alter table region drop index chRegionCode");
	dosquery("alter table region add index code (code)");

	dosquery("alter table user change column iUserId id int not null auto_increment");
	dosquery("alter table user change column vchEmail email varchar(255) not null");
	dosquery("alter table user change column vchPassword password varchar(32) not null");
	dosquery("alter table user change column vchFirstName fname varchar(255) not null");
	dosquery("alter table user change column vchLastName lname varchar(255) not null");
	dosquery("alter table user change column vchCity city varchar(255) not null");
	dosquery("alter table user change column chRegionCode regioncode char(2) not null");
	dosquery("alter table user change column chCountryCode countrycode char(2) not null");
	dosquery("alter table user change column tiEnabled enabled bit not null");
	dosquery("alter table user change column tiAdmin admin bit not null");
	dosquery("alter table user change column dtLastVisit lastvisit datetime not null");
	dosquery("alter table user change column dtLastCheck lastcheck datetime not null");

	dosquery("alter table dvd change column iDVDId id int not null auto_increment");
	dosquery("alter table dvd change column iUserId userid int not null");
	dosquery("alter table dvd change column vchDVDTitle title varchar(255) not null");
	dosquery("alter table dvd change column dtDVDDate reldate year not null");
	dosquery("alter table dvd change column vchComments comments text null");
	dosquery("alter table dvd change column irating rating int not null");
	dosquery("alter table dvd change column iGenreId genreid int not null");
	dosquery("alter table dvd change column iRegion region int null");
	dosquery("alter table dvd change column dtInsertDate insertdate datetime not null");
	dosquery("alter table dvd change column iRuntime runtime int null");
	dosquery("alter table dvd add column mediaid int not null");
	dosquery("alter table dvd drop index iUserId");
	dosquery("alter table dvd add index userid (userid)");
	dosquery("alter table dvd add index genreid (genreid)");
	dosquery("alter table dvd add index region (region)");
	dosquery("alter table dvd add index insertdate (insertdate)");
	dosquery("alter table dvd rename movie");
	dosquery("update movie set mediaid=1");

	dosquery("create table media (id int not null auto_increment, name varchar(32), primary key (id))");
	dosquery("insert media values (NULL, \"DVD\")");
	dosquery("insert media values (NULL, \"VHS\")");
	dosquery("insert media values (NULL, \"LD\")");

	dosquery("update config set value=\"0.6pre1\" where item=\"version\"");
	dosquery("insert config values (\"defmedia\", \"1\", \"Default media type. See <A HREF=\\\"tableedit.php\\\">table edit</A> for values\")");
	dosquery("insert config values (\"theme\", \"classic\", \"Default theme\")");
	dosquery("insert config values (\"yearwindow\", \"20\", \"Number of years back to display on stats page. Will be rounded down to the nearest decade\")");
	dosquery("update config set value=\"0.6pre1\" where item=\"version\"");
}

$test=db_result(doquery("select value from config where item = \"version\""),0);
if ($test=="0.6pre1") {
	dosquery("alter table config add column visible bit default \"1\"");
	dosquery("update config set visible=0 where item in (\"version\", \"theme\")");
	dosquery("update config set value=\"0.6pre2\" where item=\"version\"");
}

$test=db_result(doquery("select value from config where item = \"version\""),0);
if ($test=="0.6pre2") {
	dosquery("create table userprefs (userid int not null, item varchar(255) not null, value varchar(255) not null, key userid (userid))");
	dosquery("update config set value=\"0.6pre3\" where item=\"version\"");
}

$test=db_result(doquery("select value from config where item = \"version\""),0);
if ($test=="0.6pre3") {
	dosquery("delete from userprefs where item=\"showimdb\"");
	dosquery("delete from config where item=\"showimdb\"");
	dosquery("insert config values (\"commentchars\",\"255\", \"Number of characters of comment to display on list movie page\", 1)");
	dosquery("insert config values (\"movcolumns\",\"2047\", \"Columns to display on list movies page.\", 0)");
	dosquery("insert config values (\"showall\",\"0\", \"Enable show all link on movie list page\", 1)");
	dosquery("update config set value=\"0.6pre4\" where item=\"version\"");
}

$test=db_result(doquery("select value from config where item = \"version\""),0);
if ($test=="0.6pre4") {
	dosquery("insert config values (\"allowadminedit\",\"1\", \"Allow admin to edit all movies in the system, regardless of owner.\", 1)");
	dosquery("insert config values (\"movienav\",\"3\", \"Show movie navigation at top/bottom or both. Bitmask value. 1=top, 2=bottom, 3=both\", 1)");
	dosquery("alter table movie add column director varchar(255)");
	dosquery("alter table movie add column sound varchar(255)");
	dosquery("alter table movie add column video varchar(255)");
	dosquery("alter table movie add column extra varchar(255)");
	dosquery("delete from config where item=\"highdate\"");
	dosquery("alter table movie change column genreid genreid varchar(255) not null");
	dosquery("update config set value=\"0.6pre5\" where item=\"version\"");
}

$test=db_result(doquery("select value from config where item = \"version\""),0);
if ($test=="0.6pre5") {
	dosquery("update config set value=\"0.6\" where item=\"version\"");
}

// DVDdb PHP 8.x fork additions.
// Keep these migrations idempotent so upgradedb.php can safely be re-run.

dosquery("create table if not exists movie_location (
	movieid int(11) not null,
	album int(11) not null,
	page int(11) not null,
	sleeve int(11) not null,
	disc_count int(11) not null default 1,
	primary key (movieid),
	unique key physical_location (album, page, sleeve)
)");

dosquery("create table if not exists album_config (
	album int(11) not null,
	pages int(11) not null,
	sleeves_per_page int(11) not null default 4,
	primary key (album)
)");

// Seed the one binder dimension confirmed during the 2026 restoration. This is
// ordinary editable configuration data; Album config can change or delete it.
dosquery("insert ignore into album_config (album,pages,sleeves_per_page) values (1,32,4)");

// Always update the mysql version. If you upgrade mysql, re-run upgradedb.php to
// make sure the right version number gets into the config.

// Parsing the version number is tricky at the best of times. MySQL returns
// different format version numbers, so it's possible this will end up
// wrong on some systems.

$version=db_server_info();
$spos=0;

// Make sure first char is numeric.

if (!is_numeric($version[0])) {
	for ($i=0;$i<=strlen($version);$i++) {
		if (is_numeric($version[$i])) {
			$spos=$i;
			break;
		}
	}
}

// Get the position of the first character that isn't numeric or a .
for ($i=0;$i<=strlen($version);$i++) {
	if (!is_numeric($version[$i])&&$version[$i]!=".") {
		$epos=$i;
		break;
	}
}

$version=substr($version,$spos,$epos-$spos);

// Now we should have a number in the format x.xx.xx - we only care about the first 2 digits, so lose the third.

$bits=explode(".",$version);
$version=$bits[0].".".$bits[1];

// See if it's already in the table..

$test=doquery("select * from config where item=\"mysqlversion\"");
if (mysqli_num_rows($test)==0) {
	$query="insert config values (\"mysqlversion\", \"".$version."\", \"Mysql version\", 0)";
} else {
	$query="update config set value=\"".$version."\" where item=\"mysqlversion\"";
}
dosquery($query);

echo "<BR /><BR />If no errors appear above, you may now <A HREF=\"index.php\">proceed to the site.</A><BR />\n";
echo "If errors appeared, contact <A HREF=\"mailto:james@globalmegacorp.org\">james@globalmegacorp.org</A><BR />\n";

?>
</body>
</html>
