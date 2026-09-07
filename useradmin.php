<?php 
require("inc/menu.php");
require("inc/html.php");
require("inc/common.php");
require("inc/password.php");

$config=getconfig();

$output="";
$rows="";
$cells="";

if ($admin==FALSE) {
	header("Location: index.php");
	die();
}

$action=$_GET["action"] ?? "";
if (saneempty($action)) $action=$_POST["action"] ?? $action;
if (saneempty($action)) unset ($action);

if (isset($action)) {
	switch ($action) {
		case "delete":
			if (isset($_POST["sure"])) {
				if ($_POST["sure"]=="Yes") {
					// Might still have loans, so while we want to delete them, we should update the loan table first.
					$result=doquery("select concat(fname, \" \", lname) as name, email from user where id = ".intval($_POST["userid"]));
					$row=mysqli_fetch_assoc($result);
					doquery("update loan set userid=0, loanee=\"".$row["name"]."\", loaneeemail=\"".$row["email"]."\" where userid=".intval($_POST["userid"]));

					doquery("delete from loan using loan, movie where loan.movieid = movie.id and movie.userid = ".intval($_POST["userid"]));
					doquery("delete from movie where userid = ".intval($_POST["userid"]));
					doquery("delete from userprefs where userid = ".intval($_POST["userid"]));
					doquery("delete from user where id = ".intval($_POST["userid"]));
				}
				header("Location: useradmin.php");
				die();
			} else {
				$name=db_result(doquery("select concat(fname, \" \", lname) from user where id = ".intval($_GET["userid"])),0);
				$output.="<CENTER>Are you sure you want to delete <B>".$name."</B>?\n - This will delete all their DVD entries too!! (Loans TO this user will still be listed, but loans from this user to other people will be removed)<BR /><BR />\n";
				$output.=form_begin("useradmin.php","POST");
				$output.=input_hidden("userid",$_GET["userid"]);
				$output.=input_hidden("action","delete");
				$output.=submit("Yes","sure");
				$output.=submit("No","sure");
				$output.="</CENTER>\n";
				$output.=form_end();
			}
		break;
		case "enable":
			doquery("update user set enabled=1 where id = ".intval($_GET["userid"]));
			if ($_GET["email"]=="1") {
				$result=doquery("select fname, email from user where id = ".intval($_GET["userid"]));
				$row=mysqli_fetch_row($result);
				$to=$row[1];
				$subject=$config["sitename"]." login activated!";
				$message="Dear ".$row[0]."\n\n";
				$message.="Your \"".$config["sitename"]."\" login has now been activated.\n\n";
				$message.="Browse to ".$config["home"]." to login.\n";
				$headers="From: ".$config["siteemail"];
				mail("$to","$subject","$message","$headers");
			}
			header("Location: useradmin.php");
			die();
		break;
		case "disable":
			doquery("update user set enabled=0 where id = ".intval($_GET["userid"]));
			header("Location: useradmin.php");
			die();
		break;
		case "admin":
			doquery("update user set admin=1 where id = ".intval($_GET["userid"]));
			header("Location: useradmin.php");
			die();
		break;
		case "unadmin":
			doquery("update user set admin=0 where id = ".intval($_GET["userid"]));
			header("Location: useradmin.php");
			die();
		break;
		case "newuser":
			if (saneempty($_POST["email"])) $error.="Email address must be entered.<BR />\n";
			if (saneempty($_POST["firstname"])) $error.="First name must be entered.<BR />\n";
			if (saneempty($_POST["lastname"])) $error.="Last name must be entered.<BR />\n";
			if (saneempty($_POST["city"])) $error.="City must be entered.<BR />\n";

			if (!saneempty($error)) {
				$output.=$error;
				$output.="<P>\nPlease go back and correct these errors before proceeding.\n";
			} else {
				// Check to see if account already exists.
				$result=doquery("select * from user where email=\"".greatescape($_POST["email"])."\"");
				if (mysqli_num_rows($result)!=0) {
					$output.="<P>\nAccount appears to already exist.";
				} else {
					$password=genpass();
					$md5pass=md5($password);
					doquery("insert user values (NULL, \"".greatescape($_POST["email"])."\", \"".$md5pass."\", \"".greatescape($_POST["firstname"])."\", \"".greatescape($_POST["lastname"])."\",\"".greatescape($_POST["city"])."\", \"".greatescape($_POST["region"])."\", \"".greatescape($_POST["country"])."\", 1, 0, now(), now())");

					$subject=$config["sitename"]." login created";
					$headers="From: ".$config["siteemail"];
					$to=$_POST["email"];
					$body="A login has been created for you at ".$config["sitename"].".\n\n";
					$body.="You can visit the site here:\n";
					$body.="\t".$config["home"]."\n\n";
					$body.="Use the following details to log in:\n\n";
					$body.="\tLogin: ".$_POST["email"]."\n";
					$body.="\tPassword: ".$password."\n\n";
					$body.="Contact ".$config["webmaster"]." with any problems\n";

					mail($to,$subject,$body,$headers);

					header("Location: useradmin.php");
				}
			}

		break;
	}
} else {
	$result=doquery("select user.id, user.email, concat(fname, \" \", lname) as name, user.city, region.name as region, country.name as country, user.enabled, user.admin, date_format(user.lastvisit,\"".$config["dateformat"]."\") as lastvisit from user,region,country where user.regioncode=region.code and user.countrycode=country.code order by user.id");
	if (mysqli_num_rows($result)==0) {
		$output.="No users in database!\n";
	} else {
		$cells=td("User", "", "tablehead");
		$cells.=td("Email", "", "tablehead");
		$cells.=td("City", "", "tablehead");
		$cells.=td("Region", "", "tablehead");
		$cells.=td("Country", "", "tablehead");
		$cells.=td("Last Visit", "", "tablehead");
		$cells.=td("Action", "", "tablehead");

		$rows.=tr($cells);
		$c=0;

		while ($row=mysqli_fetch_assoc($result)) {
			if ($c++%2==0) $class="tablecell0"; else $class="tablecell1";
			$cells=td("<A HREF=\"settings.php?userid=".$row["id"]."\">".htmlspecialchars($row["name"])."</A>","",$class);
			$cells.=td("<A HREF=\"mailto:".htmlspecialchars($row["email"])."\">".htmlspecialchars($row["email"])."</A>", "", $class);
			$cells.=td(htmlspecialchars($row["city"]), "", $class);
			$cells.=td($row["region"], "", $class);
			$cells.=td($row["country"], "", $class);
			$cells.=td($row["lastvisit"], "", $class);
			$actions="<A HREF=\"useradmin.php?userid=".$row["id"]."&action=delete\">Delete</A> |\n";

			if ($row["enabled"]==1) {
				$actions.="<A HREF=\"useradmin.php?userid=".$row["id"]."&action=disable\">Disable</A> |\n";
			} else {
				$actions.="<A HREF=\"useradmin.php?userid=".$row["id"]."&action=enable&email=1\">Enable</A>\n";
				$actions.="<A HREF=\"useradmin.php?userid=".$row["id"]."&action=enable&email=0\">(No email)</A> |\n";
			}

			if ($row["admin"]==1) {
				$actions.="<A HREF=\"useradmin.php?userid=".$row["id"]."&action=unadmin\">Revoke admin</A>";
			} else {
				$actions.="<A HREF=\"useradmin.php?userid=".$row["id"]."&action=admin\">Make admin</A>";
			}

			$cells.=td($actions, "", $class);

			$rows.=tr($cells);
		}

		$output.=table($rows,0,1,0,"","datatable");
	}

	$regions=restoarray(doquery("select code, name from region order by name"));
	$countries=restoarray(doquery("select code, name from country order by name"));

	$output.=form_begin("useradmin.php","POST");
	$output.=input_hidden("action","newuser");

	$output.="<BR /><BR />\nUse this form to create a new login. A password will be auto-generated and sent to the email address entered below.<BR /><BR />\n";

	$rows=tr(td("Email address:","","","RIGHT").td(input_text("email",30,255)."  (This will be the login id)","","","LEFT"));
	$rows.=tr(td("First Name:","","","RIGHT").td(input_text("firstname",30,255),"","","LEFT"));
	$rows.=tr(td("Last Name:","","","RIGHT").td(input_text("lastname",30,255),"","","LEFT"));
	$rows.=tr(td("City:","","","RIGHT").td(input_text("city",30,255),"","","LEFT"));
	$rows.=tr(td("Region:","","","RIGHT").td(input_select("region","",$regions),"","","LEFT"));
	$rows.=tr(td("Country:","","","RIGHT").td(input_select("country","",$countries),"","","LEFT"));
	$rows.=tr(td().td());
	$rows.=tr(td(submit("Create Account"),"","","CENTER","","2"));

	$output.=table($rows,0,2,0);
	$output.=form_end();


}

$content["body"] =& $output;
dopage($content);
?>
