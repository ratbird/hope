<?

//Kein Javascript aktiviert, also über Select-Box arbeiten. Wir sind automatisch schon in Schritt 2 der
                //non-javascript-Suche.
if ($withButton) : ?>
<div class="quicksearch_frame" style="width: <?= $box_width ?>px;">
<? $withAttributes['style'] = "width: ".($box_width-23)."px;"; ?>
    <? if ($box_align === "left") : ?>
    <input class="text-bottom" type="image" src="<?= Assets::image_path("icons/16/blue/refresh.png") ?>">
    <? endif ?>
<? endif ?>
<select<? foreach ($withAttributes as $attr_name => $attr_value) {
              print ' '.$attr_name.'="'.$attr_value.'"';
          }
          ?> name="<?= $name ?>">
<? if (count($searchresults)) : ?>
  <? foreach ($searchresults as $result) : ?>
  <option value="<?= $result[0] ?>"><?= $result[1] ?></option>
  <? endforeach ?>
<? else : ?>
  <option value=""><?= _("Keine Treffer gefunden") ?></option> 
<? endif ?>
</select>
<? if ($withButton) : ?>
    <? if ($box_align !== "left") : ?>
    <input class="text-bottom" type="image" src="<?= Assets::image_path("icons/16/blue/refresh.png") ?>" title="<?= _("Suche zurücksetzen") ?>">
    <? endif ?>
    </div>
<? endif ?>