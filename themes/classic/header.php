<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN"
    "http://www.w3.org/TR/REC-html40/loose.dtd">
<HTML>
<HEAD>
<TITLE><?php echo $config["sitename"]; ?></TITLE>
<LINK REL="StyleSheet" HREF="themes/classic/style.css" TYPE="text/css">
<?php echo $content["head"]; ?>
</HEAD>
<BODY BGCOLOR="#FFFFFF">
<TABLE BORDER="0" WIDTH="100%" CELLPADDING="2" CELLSPACING="0">
<TR>
<TD VALIGN="MIDDLE" ALIGN="RIGHT" CLASS="border"><A HREF="http://www.globalmegacorp.org/dvddb">DVDdb <?php echo $config["version"]; ?></A></TD></TR>
<TR><TD VALIGN="MIDDLE" ALIGN="CENTER" COLSPAN="3" CLASS="border">
<DIV CLASS="pageheading"><?php echo $config["sitename"]; ?></DIV><BR />
<?php
if (!empty($_COOKIE["userid"])) {
	echo ("<DIV CLASS=\"plain\">You are currently logged in as ".$_COOKIE["username"].". If this is incorrect click <A HREF=\"login.php\">here</A> to login.</DIV>\n");
}
?>
</TD>
</TR>
<TR>
<TD COLSPAN="3" ALIGN="CENTER" CLASS="border">
<?php
echo implode(" | ", $content["menu"])."\n";
?>
      </td>
  </tr>
<?php
if (!saneempty($content["adminmenu"])) {
	echo "<tr>\n";
	echo "<td class=\"admin\">";
	echo implode(" | ", $content["adminmenu"])."\n";
	echo "</td>\n</tr>\n";
}
?>
</TD>
</TR>
<TR>
<TD WIDTH="100%">
<BR />
