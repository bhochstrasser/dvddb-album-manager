<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<title><?php echo $config["sitename"]; ?></title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<link href="themes/indusiv/style.css" rel="stylesheet" type="text/css">
<?php echo $content["head"]; ?>
</head>

<BODY>
<table border="0" cellpadding="0" cellspacing="0" class="maintbl">
<tr> 
<td class="headerleft">&nbsp;_ <?php echo $config["sitename"]; ?><br>
<img src="themes/indusiv/images/null.gif" width="260" height="1"></td>
<td class="header" width="100%"><div align="right"><img src="themes/indusiv/images/headerend.gif" width="88" height="74"></div></td>
</tr>
<tr> 
<td colspan="2" valign="top">
<table width="100%" border="0" cellpadding="0" cellspacing="0" class="output">
<tr> 
<td rowspan="2" class="maintblleft">
<table border="0" cellpadding="0" cellspacing="0" class="navtbl">
<tr> 
<td class="navhead">//_Navigation</td>
</tr>
<tr> 
<td class="navbody" VALIGN="TOP">
<?php
echo implode("<BR />\n", $content["menu"]);
?>
<BR />
</tr>
</table>
<?php
if (!saneempty($content["adminmenu"])) {
	$rows=tr(td("//_Administration","","navhead"));
	$rows.=tr(td(implode("<BR />\n", $content["adminmenu"])."<BR />","","navbody"));
	echo table($rows,0,0,0,"","navtbl");
}
?>
</td>
<td valign="top" class="divline"><img src="themes/indusiv/images/divlinetop.gif" width="3" height="10"></td>
<td rowspan="2" class="bodyright">
