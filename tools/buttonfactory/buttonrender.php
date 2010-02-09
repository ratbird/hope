<?php

/*
buttonrender.php - little tool to render the Stud.IP buttons
Copyright (C) 2007 Ralf Stockmann <rstockm@gwdg.de>

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
*/

require_once('button.class.php');

// Start of Output

?>
<body>
<?

// config

$buttonlabels = "buttonlabels.csv";  // csv file with labels: label-id, german, english, offset, alt-image
$folder_de = "./de/";  // folder for german version
$folder_en = "./en/";  // folder for englisch version
$presetbutton = "button.png";  // preset button image
$font = 'arialuni.ttf';

$fp = fopen($buttonlabels,"r");

while($zeile = fgetcsv($fp,4096,";")){
  $y++;
  echo "<b>Button $y:</b>&nbsp;";

  if (!$zeile[3]) $zeile[3] = $presetbutton;

  $button = new button($zeile[0], $zeile[1], $folder_de, $font, $zeile[3], (int)$zeile[4]);	
  $button->RenderButton();
  echo "<img src='".$folder_de.$zeile[0]."-button.png'>";

	echo "&nbsp; &nbsp;";

  $button = new button($zeile[0], $zeile[2], $folder_en, $font, $zeile[3], (int)$zeile[4]);	
  $button->RenderButton();
  echo "<img src='".$folder_en.$zeile[0]."-button.png'>";

  echo "<hr>";
}

fclose($fp);

?>
</body>
<?

?>