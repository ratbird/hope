<h1 class="hide-in-dialog"><?= PageLayout::getTitle() ?></h1>
<? if (!$GLOBALS['perm']->have_perm('root') && ($current_lock_rule['permission'] == 'admin' || $current_lock_rule['permission'] == 'root')) : ?>
    <?=MessageBox::info(sprintf(_('Die eingestellte Sperrebene "%s" d�rfen Sie nicht �ndern.'), htmlReady($current_lock_rule['name'])))?>
<? else : ?>
    <form action="<?= $controller->link_for('course/management/set_lock_rule') ?>" method="post" class="studip-form">
        <?= CSRFProtection::tokenTag() ?>
        <section>
            <select name="lock_sem" id="lock_sem" aria-labelledby="<?= _('Sperrebene ausw�hlen')?>">
                <? foreach ($all_lock_rules as $lock_rule) : ?>
                    <option
                        value="<?= $lock_rule['lock_id'] ?>" <?= $current_lock_rule->id == $lock_rule['lock_id'] ? 'selected' : '' ?>>
                        <?= htmlReady($lock_rule['name']) ?>
                    </option>
                <? endforeach ?>
            </select>
        </section>
        <footer data-dialog-button>
            <?= Studip\Button::createAccept(_('Speichern')) ?>
        </footer>
    </form>
<? endif ?>