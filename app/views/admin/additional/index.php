<? if ($list): ?>
    <form class='studip_form' method='post'>
        <fieldset>
            <legend><?= _('Zusatzangaben') ?></legend>
            <label><?= _('Set') ?>
                <select name='aux_data'>
                    <option value='0'>
                        <?= '-- '._('Keine Zusatzdaten')." --" ?>
                    </option>
                    <? foreach ($list as $aux): ?>
                        <option value='<?= $aux->id ?>' <?= $course->aux_lock_rule && $course->aux->id == $aux->id ? "selected" : "" ?>>
                            <?= htmlReady($aux->name) ?>
                        </option>
                    <? endforeach ?>
                </select>
            </label>
            <label><?= _('Eingaben erzwingen') ?>
                <input type='checkbox' name='forced' value='1' <?= $course->aux_lock_rule_forced ? "checked" : "" ?>>
            </label>
            <? if($count): ?>
           <?= $count." "._('Datensätze vorhanden') ?>
                        <label><?= _('Löschen?') ?>
                <input type='checkbox' name='delete' value='1'>
            </label>
            <? endif; ?>
            <?= Studip\Button::create(_('Übernehmen'), 'save') ?>
        </fieldset>
    </form>
<? else: ?>
    <? _('Keine Zusatzangaben vorhanden') ?>
<? endif; ?>