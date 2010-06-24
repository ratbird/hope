<?

//Kein Javascript aktiviert, also über Select-Box arbeiten. Wir sind automatisch schon in Schritt 2 der 
                //non-javascript-Suche.
if ($withButton) : ?>
<div style="width: <?= $box_width ?>px; background-color: #ffffff; border: 1px #999999 solid; display:inline-block">
<? $input_style = " style=\"width: ".($box_width-23)."px; background-color:#ffffff; border: 0px;\""; ?>
    <? if ($box_align === "left") : ?>
    <input style="vertical-align:middle; width: 19px" type="image" src="<?= Assets::image_path("suchen.gif") ?>">
    <? endif ?>
<? endif ?>
<select<?= $input_style .($inputClass ? " class=\"".$inputClass."\"" : "") ?> name="<?= $name ?>">
<? foreach ($searchresults as $result) : ?>
  <option value="<?= $result[0] ?>"><?= $result[1] ?></option>
<? endforeach ?>
</select>
<? if ($withButton) : ?>
    <? if ($box_align !== "left") : ?>
    <input style="vertical-align:middle" type="image" src="<?= Assets::image_path("suche2.gif") ?>">
    <? endif ?>
    </div>
<? endif ?>