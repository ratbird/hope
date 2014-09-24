<?
# Lifter010: TODO

use Studip\Button, Studip\LinkButton;

?>
<? if (isset($flash['error'])): ?>
    <?= MessageBox::error($flash['error'], $flash['error_detail']) ?>
<? elseif (isset($flash['message'])): ?>
    <?= MessageBox::info($flash['message']) ?>
<? elseif (isset($flash['success'])): ?>
    <?= MessageBox::info($flash['success']) ?>
<? endif; ?>

<? if (!empty($search_users)) : ?>
<h3>
    <?= _('Vorhandene Konfigurationsparameter f�r den Nutzer: ').htmlReady($search_users[0]['fullname']) ?>
</h3>
<table class="default">
    <tr>
        <th><?=_("Name")?></th>
        <th><?=_("Wert")?></th>
        <th><?=_("Typ")?></th>
        <th width="40%" ><?=_("Beschreibung")?></th>
        <th></th>
    </tr>
    <? foreach ($search_users as $search_user): ?>
    <tr class="<?= TextHelper::cycle('hover_odd', 'hover_even') ?>">
        <td>
            <?= htmlReady($search_user['field'])?>
        </td>
        <td>
            <? if ($search_user['type'] == 'string'): ''?><em><?= htmlReady($search_user['value'])?></em>
            <? elseif ($search_user['type'] == 'integer'): ''?> <?= htmlReady($search_user['value'])?>
            <? elseif ($search_user['type'] == 'boolean'): ''?>
                <?if ($search_user["value"] == '1'):?><?= Assets::img('icons/16/green/accept.png', array('title' => _('TRUE'))) ?>
                <? else :?> <?= Assets::img('icons/16/red/decline.png', array('title' => _('FALSE'))) ?>
                <? endif; ?>
            <? endif; ?>
        </td>
        <td>
            <?=$search_user['type']?>
        </td>
        <td>
            <?= htmlReady($search_user['description'])?>
        </td>
        <td align="right">
            <a class="load-in-new-row" href="<?=$controller->url_for('admin/configuration/edit_user_config/'.$user_id.'/'.$search_user['field'])?>">
            <?= Assets::img('icons/16/blue/edit.png', array('title' => 'Konfigurationsparameter bearbeiten')) ?>
            </a>
        </td>
    </tr>
    <? endforeach; ?>
</table>
<? endif;?>
<? if ($give_alls): ''?>
<h3>
    <?= _('Globale Konfigurationsparameter f�r alle Nutzer')?>
</h3>
<table class="default">
    <tr>
        <th><?=_("Name")?></th>
        <th><?=_("Wert")?></th>
        <th><?=_("Typ")?></th>
        <th width="40%" ><?=_("Beschreibung")?></th>
        <th></th>
    </tr>
    <? foreach ($give_alls as $give_all): ?>
    <tr class="<?= TextHelper::cycle('hover_odd', 'hover_even') ?>">
        <td>
            <?= htmlReady($give_all['field'])?>
        </td>
        <td>
            <? if ($give_all['type'] == 'string'): ''?><em><?= htmlReady($give_all['value'])?></em>
            <? elseif ($give_all['type'] == 'integer'): ''?> <?= htmlReady($give_all['value'])?>
            <? elseif ($give_all['type'] == 'boolean'): ''?>
                <?if ($give_all["value"]):?><?= Assets::img('icons/16/green/accept.png', array('title' => _('TRUE'))) ?>
                <? else :?> <?= Assets::img('icons/16/red/decline.png', array('title' => _('FALSE'))) ?>
                <? endif; ?>
            <? endif; ?>
        </td>
        <td>
            <?=$give_all['type']?>
        </td>
        <td>
            <?= htmlReady($give_all['description'])?>
        </td>
        <td align="right">
            <a class="load-in-new-row" href="<?=$controller->url_for('admin/configuration/edit_configuration/'.$give_all['config_id'])?>">
            <?= Assets::img('icons/16/blue/edit.png', array('title' => 'Konfigurationsparameter bearbeiten')) ?>
            </a>
        </td>
    </tr>
    <? endforeach; ?>
<? endif;?>
    <tr>
        <td>
            <?= LinkButton::createCancel(
                    _('Abbrechen'),
                    $controller->url_for('admin/configuration/configuration'),
                    array('title' => _('Zur�ck zur �bersicht'))) ?>
        </td>
    </tr>
</table>


<?
$sidebar = Sidebar::Get();
$sidebar->setTitle(PageLayout::getTitle() ? : _('Konfiguration'));
$sidebar->setImage('sidebar/admin-sidebar.png');

$actions = new ActionsWidget();
$actions->addLink(_('Globale Konfiguration'),$controller->url_for('admin/configuration/configuration'), 'icons/16/blue/admin.png');
$actions->addLink(_('Alle Nutzer-Einstellungen'),$controller->url_for('admin/configuration/user_configuration/giveAll'), 'icons/16/blue/person.png');
$sidebar->addWidget($actions);


$widget = new SidebarWidget();
$widget->setTitle(_('Eingabe'));
$widget->addElement(new WidgetElement($this->render_partial('admin/configuration/user_filter', compact('allconfigs', 'config_filter'))));
$sidebar->addWidget($widget);
