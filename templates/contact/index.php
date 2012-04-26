<form action="<?= URLHelper::getLink('?cmd=search#anker') ?>" method="post">
    <?= CSRFProtection::tokenTag() ?>
    <table class="default">
        <tr>
            <td>
        <? if ($size_of_book): ?>
            <? if ($open === 'all'): ?>
                <a href="<?= URLHelper::getLink('', compact('view', 'filter') + array('close' => 'all')) ?>">
                    <?= Assets::img('icons/16/blue/arr_1up') ?>
                    <?= _('Alle zuklappen') ?>
            <? else: ?>
                <a href="<?= URLHelper::getLink('', compact('view', 'filter') + array('open' => 'all')) ?>">
                    <?= Assets::img('icons/16/blue/arr_1down') ?>
                    <?= _('Alle aufklappen') ?>
            <? endif; ?>
                    <?= sprintf($size_of_book == 1 ? _('(%d Eintrag)') : _('(%d Einträge)'), $size_of_book) ?>
                </a>
        <? endif; ?>
            </td>
            <td align="right">
            <? if (!$search_exp || !$search_results): ?>
                <label>
                    <?=  _('Person zum Eintrag in das Adressbuch suchen:') ?>
                    <input type="text" name="search_exp" value="<?= htmlReady($search_exp) ?>">
                </label>
                <input type="image" name="search" border="0"
                       src="<?= Assets::image_path('icons/16/blue/search') ?>"
                       value="<?= _('Personen suchen') ?>"
                       <?= tooltip(_('Person suchen')) ?>>
                &nbsp;
            <? elseif ($search_results): ?>
                <input type="image" name="addsearch"
                       src="<?= Assets::image_path('icons/16/yellow/arr_2down') ?>"
                       value="<?= _('In Adressbuch eintragen') ?>"
                       <?= tooltip(_('In Adressbuch eintragen')) ?>>
                <?= $search_results ?>
                <a href="<?= URLHelper::getLink() ?>">
                    <?= Assets::img('icons/16/blue/refresh', tooltip2(_('Neue Suche'))) ?>
                </a>
            <? endif; ?>
            </td>
        </tr>
    <? // TODO: Get rid of this.
        if ($_SESSION['sms_msg']):
            parse_msg ($_SESSION['sms_msg']);
            $_SESSION['sms_msg'] = '';
            $sess->unregister('sms_msg');
        endif;
    ?>
    </table>
</form>

<table align="center" class="default">
    <tr>
        <td align="middle" class="lightgrey">

        <? if ($contact['view'] == 'alpha'): ?>
            <?= $this->render_partial('contact/header-alpha') ?>
        <? elseif ($contact['view'] == 'gruppen'): ?>
            <?= $this->render_partial('contact/header-groups') ?>
        <? endif; ?>

    <? if ($edit_id): ?>
            <? PrintEditContact($edit_id); ?>
    <? else: ?>
            <? PrintAllContact($filter == 'all' ? '' : $filter); ?>
        <? if ($size_of_book): ?>
            <?= $this->render_partial('contact/legend') ?>
        <? endif; ?>
    <? endif; ?>

        </td>
    </tr>
</table>

