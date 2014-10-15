<?

use Studip\Button,
    Studip\LinkButton;
?>

<p>
    <?=
    _('Hier können Sie eine Seite mit Zusatzinformationen zu Ihrer '
            . 'Veranstaltung gestalten. Sie können Links normal eingeben, diese '
            . 'werden anschließend automatisch als Hyperlinks dargestellt.')
    ?>
</p>

<form action="<?= $controller->url_for('course/scm/edit/' . $scm->id) ?>" method="post" class="studip_form">
    <?= CSRFProtection::tokenTag() ?>

    <label>
        <?= _('Titel') ?>
        <input id="tab_name" type="text" name="tab_name" value="<?= htmlReady($scm->tab_name) ?>"
               placeholder="<?= _('Titel der Informationsseite') ?>" maxlength="20"
               data-length-hint>
    </label>
    <label>
        <?= _('oder wählen Sie hier einen Namen aus:') ?>
        <select name="tab_name_template" data-copy-to="input[name=tab_name]">
            <option value="">- <?= _('Vorlagen') ?> -</option>
            <? foreach ($GLOBALS['SCM_PRESET'] as $template): ?>
                <option><?= htmlReady($template['name']) ?></option>
            <? endforeach; ?>
        </select>
    </label>
    <? if (!$scm->isNew()): ?>
        <p>
            <?=
            sprintf(_('Zuletzt geändert von %s am %s'), ObjectdisplayHelper::link($scm->user), strftime('%x, %X', $scm->chdate))
            ?>
        </p>
    <? endif; ?>
    <br>
    <fieldset>
        <legend>
            <?= _('Inhalt') ?>
        </legend>
        <textarea style="width: 90%;" name="content" data-secure="true"><?= htmlReady($scm->content) ?></textarea>
    </fieldset>
    <div data-dialog-button>
        <?= Button::createAccept(_('Speichern'), 'submit') ?>
        <? if ($first_entry): ?>
            <?= LinkButton::createCancel(_('Abbrechen'), URLHelper::getLink('seminar_main.php')) ?>
        <? else: ?>
            <?= LinkButton::createCancel(_('Abbrechen'), $controller->url_for('course/scm/' . $scm->id))
            ?>
        <? endif; ?>
    </div>
</form>
