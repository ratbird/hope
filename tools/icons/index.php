<?php
/**
 * iconfactory
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      Michael Riehemann <michael.riehemann@uni-oldenburg.de>
 * @copyright   2010 Stud.IP Core-Group
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
 * @package     icons
 * @since       2.0
 */
?>
<html>
<head>
<style type="text/css">
label {
    width: 150px;
    float: left;
}
</style>
</head>
<body>
<h1>Stud.IP 2.0 Header-Navigation Icon-Factory</h1>
<?
    $path = dirname(__FILE__);
    $path_tmp = $path.'/tmp/';
?>
<? if (isset($_POST['action'])) : ?>
<? if (empty($_POST['name'])) : ?>
<p style="color: red;">Bitte einen Namen angeben</p>
<? endif ?>

<? if ($_FILES['icon_1']['error'] != 0 && $_FILES['icon_2']['error'] != 0) : ?>
<p style="color: red;">Bitte mindestens 2 Dateien hinzufügen</p>
<? else : ?>
    <? if ($_FILES['icon_1']['type'] != 'image/png' && $_FILES['icon_2']['type'] != 'image/png') : ?>
    <p style="color: red;">Die Dateien sind nicht im PNG-Format.</p>
    <? else : ?>
<?
    $name = str_replace('.png', '', trim(strip_tags($_POST['name'])));

    move_uploaded_file($_FILES['icon_1']['tmp_name'], $path_tmp.'icon1.png');
    move_uploaded_file($_FILES['icon_2']['tmp_name'], $path_tmp.'icon2.png');
    $im1 = imagecreatefrompng($path_tmp.'icon1.png');
    $im2 = imagecreatefrompng($path_tmp.'icon2.png');
    imagealphablending($im1, false);
    imagealphablending($im2, false);
    imagesavealpha($im1, true);
    imagesavealpha($im2, true);


    if ($_FILES['icon_3']['error'] == 0 && $_FILES['icon_4']['error'] == 0) {
        if ($_FILES['icon_1']['type'] != 'image/png' && $_FILES['icon_2']['type'] != 'image/png') {
            echo '<p style="color: red;">Die Dateien sind nicht im PNG-Format.</p>';
        } else {
            move_uploaded_file($_FILES['icon_3']['tmp_name'], $path_tmp.'icon3.png');
            move_uploaded_file($_FILES['icon_4']['tmp_name'], $path_tmp.'icon4.png');
            $im3 = imagecreatefrompng($path_tmp.'icon3.png');
            $im4 = imagecreatefrompng($path_tmp.'icon4.png');
            imagealphablending($im3, false);
            imagealphablending($im4, false);
            imagesavealpha($im3, true);
            imagesavealpha($im4, true);

            $im = imagecreatetruecolor(128, 32);
            imagealphablending($im, false);
            imagesavealpha($im, true);

            imagecopyresampled($im, $im1, 0, 0, 0, 0, 32, 32, 32, 32);
            imagecopyresampled($im, $im2, 32, 0, 0, 0, 32, 32, 32, 32);
            imagecopyresampled($im, $im3, 64, 0, 0, 0, 32, 32, 32, 32);
            imagecopyresampled($im, $im4, 96, 0, 0, 0, 32, 32, 32, 32);

            imagedestroy($im3);
            imagedestroy($im4);
        }
    } else {
        $im = imagecreatetruecolor(64, 32);
        imagealphablending($im, false);
        imagesavealpha($im, true);

        imagecopyresampled($im, $im1, 0, 0, 0, 0, 32, 32, 32, 32);
        imagecopyresampled($im, $im2, 32, 0, 0, 0, 32, 32, 32, 32);
    }
    imagepng($im, $path.'/'.$name.'.png');

    imagedestroy($im);
    imagedestroy($im1);
    imagedestroy($im2);
?>

<h2>Ergebnis:</h2>
<img src="<?= $name.'.png' ?>" style="background-color: #23427B">
<img src="<?= $name.'.png' ?>" style="margin-left: 20px;">
<img src="<?= $name.'.png' ?>" style="background-color: #000; margin-left: 20px;">
<br>
<p>(Es handelt sich in den oberen Beispielen um die gleiche transparente Grafik, nur der Hintergrund wurde per CSS verändert)</p>

<h2>Neue Datei erstellen</h2>
<? endif ?>
<? endif ?>
<? endif ?>
<form method="post" enctype="multipart/form-data">
<input type="hidden" name="action" value="create">

<label>Name des Icons:*</label>
<input type="text" name="name">.png

<br>

<label>Datei 1 (normal):*</label>
<input type="file" name="icon_1">

<br>

<label>Datei 2 (hover):*</label>
<input type="file" name="icon_2">

<br>

<label>Datei 3 (normal + neu):</label>
<input type="file" name="icon_3">

<br>

<label>Datei 4 (hover + neu):</label>
<input type="file" name="icon_4">

<br>

<input type="submit" name="submit" value="Datei erzeugen">
</form>

<p>* Pflichtfeld</p>

<h2>Hinweise:</h2>
<ul>
    <li>Bitte beim Dateinamen nur Kleinbuchstaben und keine Leer- oder Sonderzeichen verwenden. Bitte auch keine Endung angeben.</li>
    <li>Es werden nur Grafiken im Format <b>png</b> und in der Größe 32x32 unterstützt.</li>
</ul>

<? if (is_dir($path)) : ?>
<? if ($dh = opendir($path)) : ?>
<h2>Alle bereits erzeugten Grafiken: </h2>
<ul>
    <? while (($file = readdir($dh)) !== false) : ?>
    <? if (substr($file, -3) == 'png') : ?>
    <li><?= $file ?>:<br>
        <img src="<?= $file ?>" style="background-color: #23427B">
        <img src="<?= $file ?>" style="margin-left: 20px;">
        <img src="<?= $file ?>" style="background-color: #000; margin-left: 20px;">
    </li>
    <? endif ?>
    <? endwhile ?>
</ul>
<? closedir($dh) ?>
<? endif ?>
<? endif ?>
</body>
</html>