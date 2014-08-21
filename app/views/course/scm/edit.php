<? use Studip\Button, Studip\LinkButton; ?>

<p>
<?= _('Hier können Sie eine Seite mit Zusatzinformationen zu Ihrer '
     .'Veranstaltung gestalten. Sie können Links normal eingeben, diese '
     .'werden anschließend automatisch als Hyperlinks dargestellt.') ?>
</p>

<form action="<?= $controller->url_for('course/scm/edit/' . $scm->id) ?>" method="post">
    <?= CSRFProtection::tokenTag() ?>

    <table class="default scm nohover">
        <colgroup>
            <col>
            <col width="25%">
        </colgroup>
        <thead class="content_title">
            <tr>
                <td>
                    <?= Assets::img('icons/16/grey/infopage.png', array('class' => 'text-top')) ?>
                    <input id="tab_name" type="text" name="tab_name" value="<?= htmlReady($scm->tab_name) ?>"
                           placeholder="<?= _('Titel der Informationsseite') ?>" maxlength="20"
                           data-length-hint>

                    <?= _('oder wählen Sie hier einen Namen aus:') ?>
                    <select name="tab_name_template" data-copy-to="input[name=tab_name]">
                        <option value="">- <?= _('Vorlagen') ?> -</option>
                    <? foreach ($GLOBALS['SCM_PRESET'] as $template): ?>
                        <option><?= htmlReady($template['name']) ?></option>
                    <? endforeach; ?>
                    </select>
                </td>
                <td>
                <? if (!$scm->isNew()): ?>
                    <?= sprintf(_('Zuletzt geändert von %s am %s'),
                                sprintf('<a href="%s">%s</a>',
                                        URLHelper::getLink('dispatch.php/profile?username=' . $scm->user->username),
                                        $scm->user->getFullName('full')),
                                strftime('%x, %X', $scm->chdate)) ?>
                <? endif; ?>
                </td>
            </tr>
        </thead>
        <tbody class="content_body">
            <tr>
                <td colspan="2">
                    <textarea class="add_toolbar" name="content" data-secure="true"><?= htmlReady($scm->content) ?></textarea>
                </td>
            </tr>
        </tbody>
        <tfoot class="table_footer">
            <tr>
                <td colspan="2" data-dialog-button>
                    <?= Button::createAccept(_('Speichern'), 'submit') ?>
                <? if ($first_entry): ?>
                    <?= LinkButton::createCancel(_('Abbrechen'), URLHelper::getLink('seminar_main.php')) ?>
                <? else: ?>
                    <?= LinkButton::createCancel(_('Abbrechen'),
                                                 $controller->url_for('course/scm/' . $scm->id)) ?>
                <? endif; ?>
                </td>
            </tr>
        </tfoot>

    </table>
</form>

<?php
$sidebar = Sidebar::get();
$sidebar->setImage(Assets::image_path("sidebar/info-sidebar.png"));
