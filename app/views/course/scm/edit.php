<? use Studip\Button, Studip\LinkButton; ?>

<p>
<?= _('Hier k�nnen Sie eine Seite mit Zusatzinformationen zu Ihrer '
     .'Veranstaltung gestalten. Sie k�nnen Links normal eingeben, diese '
     .'werden anschlie�end automatisch als Hyperlinks dargestellt.') ?>
</p>

<form action="<?= $controller->url_for('course/scm/edit/' . $scm->id) ?>" method="post">
    <?= CSRFProtection::tokenTag() ?>

    <table class="default scm">
        <colgroup>
            <col>
            <col width="25%">
        </colgroup>
        <thead class="content_title">
            <tr>
                <td>
                    <?= Assets::img('icons/16/grey/infopage.png', array('class' => 'text-top')) ?>
                    <input type="text" name="tab_name" value="<?= htmlReady($scm->tab_name) ?>"
                           placeholder="<?= _('Titel der Informationsseite') ?>">

                    <?= _('oder w�hlen Sie hier einen Namen aus:') ?>
                    <select name="tab_name_template" data-copy-to="input[name=tab_name]">
                        <option value="">- <?= _('Vorlagen') ?> -</option>
                    <? foreach ($GLOBALS['SCM_PRESET'] as $template): ?>
                        <option><?= htmlReady($template['name']) ?></option>
                    <? endforeach; ?>
                    </select>
                </td>
                <td>
                <? if (!$scm->isNew()): ?>
                    <?= sprintf(_('Zuletzt ge�ndert von %s am %s'),
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
                <td colspan="2">
                    <?= Button::createAccept(_('Speichern'), 'submit') ?>
                    <?= LinkButton::createCancel(_('Abbrechen'),
                                           $controller->url_for('course/scm/' . $scm->id)) ?>
                </td>
            </tr>
        </tfoot>

    </table>
</form>
