<?php
function datesel($name,$default,$low,$high,$step=1)
{
	$data=array();
	for ($i=$low;$i<=$high;$i+=$step) {
		$data[]=array($i,$i);
	}

	$r=input_select($name,$default,$data);
	return $r;
}

function rating($default)
{
	global $config;

	$rows="";
	$ratings=array("Unrated", "Dire", "Flawed", "Has its moments", "Very good", "Nearly there", "Excellent");
	$path="themes/".$config["theme"]."/images/";

	for ($i=0;$i<=6;$i++) {
		$c=FALSE;
		$cells="";
		$stars="";
		if ($i==$default) $c=TRUE;
		$cells.=td(input_radio("rating", $i, $c),"","","LEFT");
		$cells.=td($ratings[$i]);

		if ($i<6) $img=$path."blackstar.gif"; else $img=$path."redstar.gif";

		for ($j=1;$j<=$i;$j++) {
			if ($j<6) $stars.="<IMG BORDER=\"0\" SRC=\"".$img."\">\n";
		}
		$cells.=td($stars);
		$rows.=tr($cells);
	}
	return table($rows,0,2,2);
}
function columns ($mask)
{
	$columns[1]=array("title" => "Owner", "readonly" => TRUE);
	$columns[2]=array("title" => "Title", "readonly" => TRUE);
	$columns[4]=array("title" => "Year", "readonly" => FALSE);
	$columns[8]=array("title" => "Genre", "readonly" => FALSE);
	$columns[16]=array("title" => "Comments", "readonly" => FALSE);
	$columns[32]=array("title" => "Region", "readonly" => FALSE);
	$columns[64]=array("title" => "Length", "readonly" => FALSE);
	$columns[128]=array("title" => "Media", "readonly" => FALSE);
	$columns[256]=array("title" => "Rating", "readonly" => FALSE);
	$columns[512]=array("title" => "IMDb", "readonly" => FALSE);
	$columns[1024]=array("title" => "Loan", "readonly" => FALSE);
	$columns[2048]=array("title" => "Location (A/P/S)", "readonly" => FALSE);
	$columns[4096]=array("title" => "Album", "readonly" => FALSE);
	$columns[8192]=array("title" => "Page", "readonly" => FALSE);
	$columns[16384]=array("title" => "Sleeve", "readonly" => FALSE);

	$max=1<<(count($columns)-1);

	for ($i=1;$i<=$max;$i<<=1) {
		$r.=input_check("cols[".$i."]",ckbit($i,$mask),$columns[$i]["readonly"])." - ".$columns[$i]["title"]."<BR />\n";
	}

	return $r;
}
?>
