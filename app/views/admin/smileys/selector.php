<?
use Studip\Button;

$groups = array(
    'all'   => _('Alle'),
    'top20' => _('Top 20'),
    'used'  => _('Benutzte'),
    'none'  => _('Nicht benutzte'),
    'short' => _('Nur mit Kürzel')
);
?>
<form class="smiley-select" action="<?= $controller->url_for('admin/smileys/index') ?>">
    <select name="view">
        <optgroup label="<?= _('Nach Buchstaben') ?>">
        <? foreach ($characters as $character => $count): ?>
            <option class="single" value="<?= $character ?>"
                    <?= $view == $character ? 'selected' : ''?>>
                <?= sprintf("%s (% 2u)", strtoupper($character), $count) ?>
            </option>
        <? endforeach; ?>
        </optgroup>
        <optgroup label="<?= _('Gruppiert') ?>">
        <? foreach ($groups as $key => $label): ?>
            <option value="<?= $key ?>" <?= $view == $key ? 'selected' : ''?>>
                <?= htmlReady($label) ?>
            </option>
        <? endforeach; ?>
        </optgroup>
    </select>
    <noscript><?= Button::create('Anzeigen') ?></noscript>
</form>