<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<HTML>
<HEAD>
<TITLE><?php echo $config["sitename"]; ?></TITLE>
<META HTTP-EQUIV="Content-Type" CONTENT="text/html; charset=iso-8859-1">
<LINK HREF="themes/emerald/style.css" REL="stylesheet" TYPE="text/css">
<?php echo $content["head"]; ?>
</HEAD>

<BODY>
<TABLE BORDER="0" CELLPADDING="0" CELLSPACING="0" CLASS="maintable">
<TR> 
<TD CLASS="sitename"><?php echo $config["sitename"]; ?></TD>
</TR>
<TR> 
<TD CLASS="menu">
<?php
echo implode(" | ", $content["menu"])."\n";
?>
</TD>
</TR>
<?php
if (!saneempty($content["adminmenu"])) {
	echo "<TR>\n";
	echo "<TD CLASS=\"adminmenu\">";
	echo implode(" | ", $content["adminmenu"])."\n";
	echo "</TD>\n</TR>\n";
}
?>
<TR> 
<TD CLASS="header">&nbsp;</TD>
</TR>
<TR> 
<TD CLASS="maincell">
