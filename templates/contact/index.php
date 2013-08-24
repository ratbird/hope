<form action="<?= URLHelper::getLink('?cmd=search#anker') ?>" method="post">
    <?= CSRFProtection::tokenTag() ?>
    <table width="100%">
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
			<span class="actions">
            <? if (!$search_exp || !$search_results): ?>
                    <?=  _('Person zum Eintrag in das Adressbuch suchen:') ?>
                    <input type="text" name="search_exp" value="<?= htmlReady($search_exp) ?>">
                    <?= Assets::input('icons/16/blue/search',  array('type' => "image", 'style' => "vertical-align: middle;", 'name' => "search", 'title' => _('Neue Suche'))) ?>
                &nbsp;
            <? elseif ($search_results): ?>
                <?= Assets::input('icons/16/blue/arr_2down',  array('type' => "image", 'style' => "vertical-align: middle;", 'name' => "addsearch", 'title' => _('In Adressbuch eintragen'))) ?>
              <?= $search_results ?>
                <a href="<?= URLHelper::getLink() ?>">
                    <?= Assets::img('icons/16/blue/refresh', tooltip2(_('Neue Suche'))) ?>
                </a>
            <? endif; ?>
            </span>
            </td>
        </tr>
    <? // TODO: Get rid of this.
        if ($_SESSION['sms_msg']):
            parse_msg ($_SESSION['sms_msg']);
            $_SESSION['sms_msg'] = '';
        endif;
    ?>
    </table>
</form>

<table align="center">
    <tr>
        <td align="middle" >

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

