<? use Studip\Button, Studip\LinkButton; ?>
<div class="help_content_admin">
<h2><?= _('Verwalten von Hilfe-Texten') ?></h2>
<table cellspacing="0" cellpadding="0" width="100%">
    <tr>
        <td width="100%" class="blank"><p class="info">
        <form action="<?=$controller->url_for('help_content/admin_overview')?>" id="admin_help_content_form" method="POST">
        <input type="hidden" name="help_content_filter" value="set">
        <input type="hidden" name="help_content_filter_term" value="<?=htmlReady($help_content_searchterm)?>">
        <?=CSRFProtection::tokenTag(); ?>
        <table class="default">
            <thead>
            <tr><th colspan="2">
            <label><?= _("Suchbegriff:") ?>
            <input type="text" name="help_content_searchterm" aria-label="<?= _('Suchbegriff') ?>" value="<?= htmlReady($help_content_searchterm)?>"></label>
            &nbsp;&nbsp;
            <?=Button::create(_('Filter anwenden'), 'apply_help_content_filter', array('aria-label' => _('Liste mit Suchbegriff filtern')))?>
            </th></tr></thead>
            <? if ($filter_text) : ?>
                <tfoot><tr><td colspan="1">
                <?=$filter_text?>
                </td><td><div class="tour_reset_filter">
                <?=Button::create(_('Auswahl aufheben'), 'reset_filter')?>
                </div>
                </td></tr></tfoot>
            <? endif ?>
        </table>
        <? if (count($help_contents)) : ?>
            <table class="default">
                <caption>
                    <div class="help_content_list_title"><?=_('Hilfe-Texte')?></div>
                </caption>
                <colgroup>
                    <col width="20">
                    <col width="20%">
                    <col>
                    <col width="80">
                </colgroup>                   
                <thead><tr>
                    <th><?=_("Aktiv")?></th>
                    <th><?=_("Seite")?></th>
                    <th><?=_("Inhalt")?></th>
                    <th><?=_("Aktion")?></th>
                </tr></thead>
                <tbody>
                <? foreach ($help_contents as $help_content_id => $help_content) : ?>
                    <tr>
                    <td><input type="CHECKBOX" name="help_content_status_<?=$help_content_id?>" value="1" aria-label="<?= _('Status der Hilfe (aktiv oder inaktiv)')?>" <?=tooltip(_("Status der Hilfe (aktiv oder inaktiv)"),false)?><?=($help_content->visible) ? ' checked' : ''?>></td>
                    <td><?=htmlReady($help_content->route)?></td>
                    <td><?=formatReady($help_content->content)?></td>
                    <td>
                    <a href="<?=URLHelper::getURL('dispatch.php/help_content/edit/'.$help_content_id)?>" <?=tooltip(_('Hilfe-Text bearbeiten'))?> data-dialog="size=auto;reload-on-close">
                    <?= Assets::img('icons/16/blue/edit.png') ?></a>
                    <?= Assets::input('icons/16/blue/trash.png', tooltip2(_('Hilfe-Text löschen')) + array(
                            'name' => 'help_content_remove_' . $help_content_id,
                    )) ?>
                    </td>
                    </tr>
                <? endforeach ?>
                </tbody>
                <tfoot>
                <tr><td colspan="6">
                <?=Button::createAccept(_('Speichern'), 'save_help_content_settings') ?>
                </td></tr></tfoot>
            </table>
        <? else : ?>
            <?=_('Keine Hilfe-Texte vorhanden.')?>
        <? endif ?>
        </form><br><br></p></td>
    </tr>
</table>
</div>
