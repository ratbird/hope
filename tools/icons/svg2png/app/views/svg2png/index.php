<? $imagick = class_exists('IMagick'); ?>

<? if (!$imagick): ?>
<div class="messagebox messagebox_error">
    ImageMagick ist nicht als PHP-Modul verf�gbar. Dadurch k�nnen keine Zus�tze auf die Icons gerendert werden!
</div>
<? endif; ?>

<?
$available_colors = array(
    ''       => '',
    'black'  => '#000000',
    'blue'   => '#24437c',
    'green'  => '#00962d',
    'grey'   => '#6e6e6e',
    'red'    => '#cb1800',
    'white'  => '#ffffff',
    'yellow' => '#ffad00'
);
?>

<h1 class="table_header_bold">Icon-Generator</h1>
<form action="<?= $controller->url_for('svg2png/index') ?>" method="post">
    <fieldset>
        <h2>Einstellungen</h2>

        <div>
            <label for="input">Datei</label>
            <select name="input" id="input" required>
                <option value="">- Bitte ausw�hlen -</option>
            <? foreach ($inputs as $index => $input): ?>
                <option value="<?= $index ?>" <? if (Request::int('input') == $index) echo 'selected'; ?>>
                    <?= basename($input) ?>
                </option>
            <? endforeach; ?>
            </select>
        </div>

        <div>
            <label for="size">Gr��e</label>
            <input type="number" name="size" id="size" value="<?= @$size ?>">
        </div>

        <div>
            <label for="border">Rahmen</label>
            <input type="number" name="border" id="border" value="<?= @$border ?>">
        </div>

        <div>
            <label for="suffix">Suffix</label>
            <input type="text" name="suffix" id="suffix" value="<?= @$suffix ?>">
        </div>
    </fieldset>
    <fieldset>
        <h2>Farben</h2>
    <? foreach ($color['color'] as $index => $col): ?>
        <div>
            <input type="text" name="color[name][]" value="<?= $color['name'][$index] ?>">
            <input type="color" name="color[color][]" value="<?= @$col ?>">
            <a href="#" class="remove-color">entfernen</a>
        </div>
    <? endforeach; ?>
    </fieldset>

    <fieldset>
        <div>
            <?= Studip\Button::createAccept('Weiter', 'display') ?>

            <select name="new-color">
            <? foreach ($available_colors as $label => $col):
                  if ($col && in_array($col, $color['color'])) continue;
            ?>
                <option value="<?= $label ?>-<?= $col ?>">
                    <?= htmlReady($label) ?>
                <? if (!empty($col)): ?>
                    [<?= $col ?>]
                <? endif; ?>
                </option>
            <? endforeach; ?>
            </select>
            <?= Studip\Button::create('Weitere Farbe', 'add-color') ?>
        </div>
    </fieldset>
</form>

<? if (!empty($files)): ?>
<hr>

<h1 class="topic">
    Icons anzeigen
<? if ($imagick): ?>
    (klicken, um Zus�tze zu aktivieren)
<? endif; ?>
</h1>
<form action="<?= $controller->url_for('svg2png/download') ?>" method="post">
    <input type="hidden" name="input" value="<?= Request::int('input') ?>">
    <input type="hidden" name="size" value="<?= $size ?>">
    <input type="hidden" name="suffix" value="<?= $suffix ?>">
    <input type="hidden" name="border" value="<?= $border ?>">
<? foreach ($color['color'] as $index => $col): ?>
    <input type="hidden" name="color[color][<?= $index ?>]" value="<?= $col ?>">
    <input type="hidden" name="color[name][<?= $index ?>]" value="<?= $color['name'][$index] ?>">
<? endforeach; ?>

<? if ($imagick): ?>
    <fieldset>
        <div>
            <label for="extra-color">Zusatz-Farbe</label>
            <input type="color" name="extra-color" id="extra-color" value="<?= @$extra_color ?>">
        </div>
    
        <div>
            <label for="extra-offset-x">X-Offset</label>
            <input type="number" name="extra-offset[x]" id="extra-offset-x" value="<?= @$extra_offsets['x'] ?>">
            <small>(positiv = nach rechts, negativ = nach links)</small>
        </div>
    
        <div>
            <label for="extra-offset-y">Y-Offset</label>
            <input type="number" name="extra-offset[y]" id="extra-offset-y" value="<?= @$extra_offsets['y'] ?>">
            <small>(positiv = nach unten, negativ = nach oben)</small>
        </div>

        <div>
            <label for="extra-distance">Abstand</label>
            <input type="number" name="extra-distance" id="extra-distance" value="<?= @$extra_distance ?>">
    </fieldset>

    <div>
        <label>
            <input type="checkbox" id="all">
            Alle markieren
        </label>
    </div>
<? endif; ?>

    <div class="files">
    <? foreach ($files as $file => $png): ?>
        <label title="<?= basename($file) ?>">
        <? if ($imagick): ?>
            <input type="checkbox" name="extras[]" value="<?= urlencode($file) ?>">
        <? endif; ?>
            <img src="data:img/png;base64,<?= base64_encode($png) ?>" alt="<?= $file ?>">
        </label>
    <? endforeach; ?>
    </div>
    <div>
        <?= Studip\Button::createAccept('Herunterladen', 'download') ?>
        <?= Studip\Button::create('Markierte herunterladen', 'download_selected') ?>
        <?= Studip\Button::create('Markierte herunterladen (mit Zus�tzen)', 'download_selected_extras') ?>
    </div>
</form>
<? endif; ?>
