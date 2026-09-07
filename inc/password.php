<?php
function genpass ($len=8)
{
	for ($i=1;$i<=$len;$i++) {
		switch(rand(1,3)) {
                        case 1: $pass.=chr(rand(48,57));  break; //0-9
                        case 2: $pass.=chr(rand(65,90));  break; //A-Z
			case 3: $pass.=chr(rand(97,122)); break; //a-z
		}
	}
	return $pass;
}
?>
