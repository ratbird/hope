<?
# Lifter010: TODO
use Studip\Button, Studip\LinkButton;

?>
<? if ($flash['delete']) : ?>
<?= $this->render_partial("admin/user/_delete", array('data' => $flash['delete'])) ?>
<? endif ?>

<form action="<?= $controller->url_for('admin/user/') ?>" method="post" class="default">
    <?= CSRFProtection::tokenTag() ?>

    <fieldset>
        <legend><?= _('Benutzerverwaltung') ?></legend>

        <label>
            <?= _('Benutzername') ?>
            <input name="username" type="text" value="<?= htmlReady($user['username']) ?>">
        </label>

        <label>
            <?= _('E-Mail') ?>
            <input name="email" type="text" value="<?= htmlReady($user['email']) ?>">
        </label>

        <label>
            <?= _('Status')?>

            <select name="perm">
            <? foreach(words('alle user autor tutor dozent admin root') as $one): ?>
                <option value="<?= $one ?>" <? if ($user['perm'] === $one) echo 'selected'; ?>>
                    <?= ($one === 'alle') ? _('alle') : $one ?>
                </option>
            <? endforeach; ?>
            </select>
        </label>

        <label>
            <input type="checkbox" name="locked" value="1" <? if ($user['locked']) echo 'checked'; ?>>
            <?= _('nur gesperrt') ?>
        </label>

        <label>
            <?= _('Vorname') ?>
            <input name="vorname" type="text" value="<?= htmlReady($user['vorname']) ?>">
        </label>

        <label>
            <?= _('Nachname') ?>
            <input name="nachname" type="text" value="<?= htmlReady($user['nachname']) ?>">
        </label>

        <label for="inactive">
            <?= _('inaktiv') ?>
        </label>
        <section class="hgroup size-m">
            <select name="inaktiv" class="size-s">
            <? foreach(array('<=' => '>=', '=' => '=', '>' => '<', 'nie' =>_('nie')) as $i => $one): ?>
                <option value="<?= htmlready($i) ?>" <? if ($user['inaktiv'] === $i) echo 'selected'; ?>>
                    <?= htmlReady($one) ?>
                </option>
            <? endforeach; ?>
            </select>

            <label>
                <input name="inaktiv_tage" type="number" id="inactive"
                       value="<?= htmlReady($user['inaktiv_tage']) ?>">
                <?= _('Tage') ?>
            </label>
        </section>

    </fieldset>

    <fieldset class="collapsable <? if (!$advanced) echo 'collapsed'; ?>">
        <legend><?= _('Erweiterte Suche') ?></legend>

        <label>
            <?= _('Nutzerdomäne') ?>

            <select name="userdomains">
                <option value=""><?= _('Alle') ?></option>
                <option value="null-domain" <? if ($user['userdomains'] === 'null-domain') echo 'selected'; ?>>
                    <?= _('Ohne Domäne') ?>
                </option>
            <? foreach ($userdomains as $one): ?>
                <option value="<?= htmlReady($one->getId()) ?>" <? if ($user['userdomains'] === $one->getId()) echo 'selected'; ?>>
                    <?= htmlReady($one->getName() ?: $one->getId()) ?>
                </option>
            <? endforeach; ?>
            </select>
        </label>

        <label>
            <?= _('Authentifizierung') ?>

            <select name="auth_plugins">
               <option value=""><?= _('Alle') ?></option>
               <option value="preliminary"><?= _('vorläufig')?></option>
           <? foreach ($available_auth_plugins as $one): ?>
                <option <? if ($user['auth_plugins'] === $one) echo 'selected'; ?>>
                    <?= htmlReady($one) ?>
                </option>
            <? endforeach; ?>
            </select>
        </label>
    
    <? foreach ($datafields as $datafield): ?>
        <label>
            <?= htmlReady($datafield->name) ?>

        <? if ($datafield->type === 'bool'): ?>
            <section class="hgroup size-m">
                <label>
                    <input type="radio" name="<?= $datafield->id ?>" value="" <? if (strlen($user[$datafield->id]) === 0) echo 'checked'; ?>>
                    <?= _('egal') ?>
                </label>
                <label>
                    <input type="radio" name="<?= $datafield->id ?>" value="1" <? if ($user[$datafield->id] === '1') echo 'checked'; ?>>
                    <?= _('ja') ?>
                </label>
                <label>
                    <input type="radio" name="<?= $datafield->id ?>" value="0" <? if ($user[$datafield->id] === '0') echo 'checked'; ?>>
                    <?= _('nein') ?>
                </label>
            </section>
        <? elseif ($datafield->type === 'selectbox' || $datafield->type === 'radio') : ?>
            <? $datafield_entry = DataFieldEntry::createDataFieldEntry($datafield);?>
            <select name="<?= $datafield->id ?>">
                <option value="---ignore---"><?= _('alle') ?></option>
            <? foreach ($datafield_entry->type_param as $pkey => $pval) :?>
                <? $value = $datafield_entry->is_assoc_param ? (string) $pkey : $pval; ?>
                <option value="<?= $value ?>" <?= ($user[$datafield->id] === $value) ? 'selected' : '' ?>>
                    <?= htmlReady($pval) ?>
                </option>
            <? endforeach ?>
            </select>
        <? else : ?>
            <input type="text" name="<?= $datafield->id ?>" value="<?= htmlReady($user[$datafield->id]) ?>">
        <? endif ?>
        </label>
    <? endforeach; ?>

    </fieldset>

    <footer>
        <?= Button::create(_('Suchen'), 'search')?>
        <?= Button::create(_('Zurücksetzen'), 'reset')?>
    </footer>
</form>

<? if (count($users) > 0 && $users != 0): ?>
    <?= $this->render_partial('admin/user/_results', compact('users')) ?>
<? endif; ?>
