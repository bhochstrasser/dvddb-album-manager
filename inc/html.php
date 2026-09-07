<?php
// Common functions

function addatt ($name, $value) {
	if (saneempty($value)) {
		return;
	} else {
		return " ".$name."=\"".$value."\"";
	}
}

// Form functions

function form_begin ($action, $method, $name="", $enctype="", $extras="")
{
	$r="<FORM ACTION=\"".$action."\" METHOD=\"".$method."\"";
	$r.=addatt("ID",$name);
	$r.=addatt("ENCTYPE",$enctype);
	if ($extras) $r.=" ".$extras;
	$r.=">\n";

	return $r;
}

function form_end ()
{
	return "</FORM>\n";
}

function input_text ($name, $size, $maxlength, $value="", $class="plain", $extras="", $ro=FALSE)
{
	$r="<INPUT TYPE=\"TEXT\" NAME=\"".$name."\" SIZE=\"$size\" MAXLENGTH=\"$maxlength\" VALUE=\"".$value."\" CLASS=\"".$class."\"";
	if ($ro) $r.=" READONLY";
	if ($extras) $r.=" ".$extras;
	$r.=">\n";

	return $r;
}

function input_file ($name, $size, $class="plain", $extras="")
{
	$r.="<INPUT TYPE=\"FILE\" NAME=\"".$name."\" SIZE=\"$size\" CLASS=\"".$class."\"";
	if ($extras) $r.=" ".$extras;
	$r.=">\n";

	return $r;
}

function input_passwd ($name, $size, $maxlength, $value=NULL, $class="plain", $extras="")
{
	$r="<INPUT TYPE=\"PASSWORD\" NAME=\"".$name."\" SIZE=\"$size\" MAXLENGTH=\"$maxlength\" VALUE=\"".$value."\" CLASS=\"".$class."\"";
	if ($extras) $r.=" ".$extras;
	$r.=">\n";

	return $r;
}

function input_hidden ($name, $value)
{
	return "<INPUT TYPE=\"HIDDEN\" NAME=\"".$name."\" VALUE=\"".$value."\">\n";
}

function input_check ($name, $checked=FALSE, $disabled=FALSE, $class="plain", $extras="")
{
	$c="";
	$d="";

	if ($checked) $c=" CHECKED";
	if ($disabled) $d=" DISABLED";

	$r="<INPUT TYPE=\"CHECKBOX\" NAME=\"".$name."\" CLASS=\"".$class."\"".$c.$d;

	if ($extras) $r.=" ".$extras;
	$r.=">\n";

	// Insert nasty hack here.
	if ($disabled) $r.=input_hidden($name, "on");

	return $r;
}


function input_radio ($name, $value, $checked=FALSE, $class="plain", $extras="")
{
	if ($checked) {
		$r="<INPUT TYPE=\"RADIO\" NAME=\"".$name."\" VALUE=\"".$value."\" CLASS=\"".$class."\" CHECKED";
	} else {
		$r="<INPUT TYPE=\"RADIO\" NAME=\"".$name."\" VALUE=\"".$value."\" CLASS=\"".$class."\"";
	}
	if ($extras) $r.=" ".$extras;
	$r.=">\n";

	return $r;
}

function input_select ($name, $default, $data, $class="plain", $extras="")
{
	$r="<SELECT NAME=\"".$name."\" CLASS=\"".$class."\"";
	if ($extras) $r.=" ".$extras;
	$r.=">\n";

	foreach ($data as $value) {
		if ($value[0]==$default) {
			$r.="<OPTION SELECTED VALUE=\"".$value[0]."\">".$value[1]."</OPTION>\n";
		} else {
			$r.="<OPTION VALUE=\"".$value[0]."\">".$value[1]."</OPTION>\n";
		}
	}
	$r.="</SELECT>\n";

	return $r;
}

function input_multiselect ($name, $id, $default=array(0), $data, $rows, $class="plain", $extras="")
{
	$r="<SELECT MULTIPLE NAME=\"".$name."\" ID=\"".$id."\" SIZE=\"$rows\" CLASS=\"".$class."\"";
	if ($extras) $r.=" ".$extras;
	$r.=">\n";

	if (is_array($data)) {
		foreach ($data as $value) {
			if (in_array($value[0],$default)) {
				$r.="<OPTION VALUE=\"".$value[0]."\" SELECTED>".$value[1]."</OPTION>\n";
			} else {
				$r.="<OPTION VALUE=\"".$value[0]."\">".$value[1]."</OPTION>\n";
			}
		}
	}
	$r.="</SELECT>\n";

	return $r;
}

function textarea ($name, $text, $rows, $cols, $class="plain", $extras="")
{
	$r="<TEXTAREA NAME=\"$name\" ROWS=\"$rows\" COLS=\"$cols\" CLASS=\"$class\"";
	if ($extras) $r.=" ".$extras;
	$r.=">\n";

	$r.=$text;
	$r.="</TEXTAREA>\n";

	return $r;
}

function submit ($value, $name="", $class="plain", $extras="")
{
	$r="<INPUT TYPE=\"SUBMIT\" VALUE=\"".$value."\"";
	$r.=addatt("NAME",$name);
	$r.=addatt("CLASS",$class);
	if ($extras) $r.=" ".$extras;
	$r.=">\n";

	return $r;
}

function input_button ($name, $caption, $class="plain", $extras="")
{
	$r="<INPUT NAME=\"".$name."\" TYPE=\"BUTTON\" CLASS=\"".$class."\" VALUE=\"".$caption."\"";
	if ($extras) $r.=" ".$extras;
	$r.=">\n";

	return $r;
}

// Table functions

function table($rows, $border, $cellp, $cellsp, $width="", $class="")
{
	$r="<TABLE BORDER=\"".$border."\" CELLPADDING=\"".$cellp."\" CELLSPACING=\"".$cellsp."\"";
	$r.=addatt("WIDTH", $width);
	$r.=addatt("CLASS", $class);
	$r.=">\n";
	$r.=$rows;
	$r.="</TABLE>\n";

	return $r;
}

function td($content="", $width="", $class="", $align="",$valign="",$cspan="",$rspan="")
{
	if (saneempty($class)) $class="plain";
	if (saneempty($content)) $content="&nbsp;";
	$r="<TD";
	$r.=addatt("WIDTH", $width);
	$r.=addatt("ALIGN", $align);
	$r.=addatt("VALIGN", $valign);
	$r.=addatt("CLASS", $class);
	$r.=addatt("COLSPAN", $cspan);
	$r.=addatt("ROWSPAN", $rspan);
	$r.=">\n".$content."\n</TD>\n";
	return $r;
}

function tr($cells, $class="")
{
	$r="<TR";
	$r.=addatt("CLASS", $class, FALSE);
	$r.=">\n".$cells."</TR>\n";

	return $r;
}

?>
