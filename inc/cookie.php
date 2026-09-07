<?php
function checkcookies()
{
	// I used a global. Shoot me.
	global $admin;

	$admin=FALSE;
	$ok=FALSE;

	if (!empty($_COOKIE["userid"])&&!empty($_COOKIE["userpass"])) {
		$result=doquery("select password, admin from user where id = ".$_COOKIE["userid"]." and enabled=1");
		if (mysqli_num_rows($result)==1) {
			$row=mysqli_fetch_assoc($result);
			if ($row["password"]==$_COOKIE["userpass"]) {
				$ok=TRUE;
				if ($row["admin"]==1) $admin=TRUE;
				doquery("update user set lastvisit=now() where id = ".$_COOKIE["userid"]);
			}
		}
	}
	if (!$ok) {
		setcookie("userid");
		setcookie("userpass");
		setcookie("username");
		header("Location: login.php");
		die();
	}
}

?>
