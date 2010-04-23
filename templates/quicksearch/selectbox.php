<?php

//Kein Javascript aktiviert, also über Select-Box arbeiten. Wir sind automatisch schon in Schritt 2 der 
				//non-javascript-Suche.
if ($withButton) {
	print "<div style=\"width: 233px; background-color: #ffffff; border: 1px #999999 solid;" .
	"\">&nbsp;";
	$input_style = " style=\"width: 210px; background-color:#ffffff; border: 0px;\"";
}
print "<select$input_style".($inputClass ? " class=\"".$inputClass."\"" : "")." name=\"".$name."\">";

foreach ($searchresults as $result) {
	print "<option value=\"".$result[0]."\">".$result[1]."</option>";
}
print "</select>";
if ($withButton) {
	print "<input style=\"vertical-align:middle\" type=\"image\" src=\"".$GLOBALS['ASSETS_URL']."/images/suche2.gif\">";
	print "</div>";
}

