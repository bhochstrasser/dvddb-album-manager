<?php 
require("inc/menu.php");
require("inc/html.php");
require("inc/common.php");

$config=getconfig();

$action=greatescape($_POST["action"]);
if (saneempty($action)) $action=greatescape($_GET["action"]);
if (saneempty($action)) unset($action);

if (isset($action)) {
	switch ($action) {
		case "loan":
			if ($_POST["type"]==0) {
				doquery("insert loan values (NULL, ".intval($_POST["movieid"]).", ".intval($_POST["lentto"]).", \"\", \"\", now())");
				header("Location: listloans.php");
				die();
			} else {
				if (saneempty($_POST["olentto"])) {
					$output.="Error - must enter name\n";
				} else {
					doquery("insert loan values (NULL, ".intval($_POST["movieid"]).", 0, \"".greatescape($_POST["olentto"])."\", \"".greatescape($_POST["email"])."\", now())");
					header("Location: listloans.php");
					die();
				}
			}
		break;
		case "return":
			doquery("delete from loan where id = ".intval($_GET["loanid"])); // Could use a join to the DVD table to check userid too.
			header("Location: listloans.php");
			die();
		break;
	}
} else {
	$users=restoarray(doquery("select id, concat(fname, \" \", lname) as name from user where id != ".intval($_COOKIE["userid"])." and enabled=1 order by name"));
	$movietitle=db_result(doquery("select title from movie where id = ".intval($_GET["movieid"])." and userid = ".intval($_COOKIE["userid"])),0);

	$output.="<P>\nChoose or enter the person who has borrowed the movie <B>&quot;".htmlspecialchars($movietitle)."&quot;</B>:\n<P>\n";
	$output.=form_begin("loan.php","POST");
	$output.=input_hidden("movieid",intval($_GET["movieid"]));
	$output.=input_hidden("action","loan");

	$rows=tr(td("User:").td(input_radio("type", 0, TRUE)).td(input_select("lentto","",$users)));
	$rows.=tr(td("Other:","","","","TOP").td(input_radio("type", 1, FALSE),"","","","TOP").td("Name:<BR />\n".input_text("olentto",30,255)."<BR />\n(Optional) Email:<BR />".input_text("email",30,255)));

	$output.=table($rows,0,2,2);
	$output.="<P>\n";

	$output.=submit("Go");
	$output.=form_end();
}

$content["body"] =& $output;
dopage($content);

?>
