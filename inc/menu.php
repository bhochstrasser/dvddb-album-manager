<?php
require("inc/db.php");
require("inc/cookie.php");

$admin=FALSE;
$menu=array();
$adminmenu=array();

if (empty($nocheck)) checkcookies();

if (empty($_COOKIE["userid"])) {
	$menu[]="<A CLASS=\"nav\" HREF=\"login.php\">Login</A>";
	$menu[]="<A CLASS=\"nav\" HREF=\"newlogin.php\">Create Account</A>";
} else {
	$menu[]="<A CLASS=\"nav\" HREF=\"index.php\">Home</A>";
	$menu[]="<A CLASS=\"nav\" HREF=\"showusers.php\">Users</A>";
	$menu[]="<A CLASS=\"nav\" HREF=\"listmovies.php?userid=".$_COOKIE["userid"]."\">My movies</A>";
	$menu[]="<A CLASS=\"nav\" HREF=\"listmovies.php\">All movies</A>";
	$menu[]="<A CLASS=\"nav\" HREF=\"search.php\">Search</A>";
	$menu[]="<A CLASS=\"nav\" HREF=\"editmovie.php\">New movie</A>";
	$menu[]="<A CLASS=\"nav\" HREF=\"listloans.php\">Loans</A>";
	$menu[]="<A CLASS=\"nav\" HREF=\"stats.php\">Stats</A>";
	$menu[]="<A CLASS=\"nav\" HREF=\"settings.php\">Settings</A>";
	$menu[]="<A CLASS=\"nav\" HREF=\"logoff.php\">Logout</A>";
	if ($admin==TRUE) {
		$adminmenu[]="<A CLASS=\"nav\" HREF=\"useradmin.php\">User admin</A>";
		$adminmenu[]="<A CLASS=\"nav\" HREF=\"config.php\">Site config</A>";
		$adminmenu[]="<A CLASS=\"nav\" HREF=\"columnadmin.php\">Column admin</A>";
		$adminmenu[]="<A CLASS=\"nav\" HREF=\"themes.php\">Themes</A>";
		$adminmenu[]="<A CLASS=\"nav\" HREF=\"tableedit.php\">Table edit</A>";
		$adminmenu[]="<A CLASS=\"nav\" HREF=\"album.php\">Album manager</A>";
		$adminmenu[]="<A CLASS=\"nav\" HREF=\"mlist.php\">Mailout</A>";
	 }
}

$content["menu"] =& $menu;
$content["adminmenu"] =& $adminmenu;

?>
