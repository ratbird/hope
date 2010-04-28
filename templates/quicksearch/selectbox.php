<?

//Kein Javascript aktiviert, also über Select-Box arbeiten. Wir sind automatisch schon in Schritt 2 der 
				//non-javascript-Suche.
if ($withButton) : ?>
<div style="width: 233px; background-color: #ffffff; border: 1px #999999 solid; display:inline-block">
<? $input_style = " style=\"width: 210px; background-color:#ffffff; border: 0px;\""; ?>
<? endif ?>
<select<?= $input_style .($inputClass ? " class=\"".$inputClass."\"" : "") ?> name="<?= $name ?>">
<? foreach ($searchresults as $result) : ?>
  <option value="<?= $result[0] ?>"><?= $result[1] ?></option>
<? endforeach ?>
</select>
<? if ($withButton) : ?>
	<input style="vertical-align:middle" type="image" src="<?= Assets::image_path("suche2.gif") ?>">
	</div>
<? endif ?>