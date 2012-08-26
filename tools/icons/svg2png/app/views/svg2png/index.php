<h1 class="topic">Einstellungen</h1>
<form action="<?= $controller->url_for('svg2png/index') ?>" method="post">
    <fieldset>
        <div>
            <label for="input">Datei</label>
            <select name="input" id="input" required>
                <option value="">- Bitte auswählen -</option>
            <? foreach ($inputs as $index => $input): ?>
                <option value="<?= $index ?>" <? if (Request::int('input') == $index) echo 'selected'; ?>>
                    <?= basename($input) ?>
                </option>
            <? endforeach; ?>
            </select>
        </div>

        <div>
            <label for="size">Größe</label>
            <input type="number" name="size" id="size" value="<?= @$size ?>">
        </div>

        <div>
            <label for="color">Farbe</label>
            <input type="color" name="color" id="color" value="<?= @$color ?>">
            <label>
                <input type="checkbox" name="color-trigger" <? if (empty($color)) echo 'checked'; ?>>
                Keine Änderung
            </label>
        </div>

        <div>
            <?= Studip\Button::create('Anzeigen') ?>
        </div>
    </fieldset>
</form>

<? if (!empty($files)): ?>
<hr>

<h1 class="topic">Icons anzeigen (klicken, um Zusätze zu aktivieren)</h1>
<form action="<?= $controller->url_for('svg2png/download') ?>" method="post">
    <input type="hidden" name="input" value="<?= Request::int('input') ?>">
    <input type="hidden" name="size" value="<?= $size ?>">
    <input type="hidden" name="color" value="<?= $color ?>">

    <div>
        <label for="extra-color">Zusatz-Farbe</label>
        <input type="color" name="extra-color" id="extra-color" value="<?= @$extra_color ?>">
    </div>
    
    <div>
        <label>
            <input type="checkbox" id="all">
            Alle markieren
        </label>
    </div>

    <div class="files">
    <? foreach ($files as $file => $png): ?>
        <label>
            <input type="checkbox" name="extras[]" value="<?= urlencode($file) ?>">
            <img src="data:img/png;base64,<?= base64_encode($png) ?>" alt="<?= $file ?>">
        </label>
    <? endforeach; ?>
    </div>
    <div>
        <?= Studip\Button::create('Herunterladen', 'download') ?>
    </div>
</form>
<? endif; ?>
