<? use Studip\Button, Studip\LinkButton; ?>

<h3><?= _('Neues Banner anlegen') ?></h3>

<form action="<?= $controller->url_for('admin/banner/new') ?>" method="post" enctype="multipart/form-data">
    <table class="default">
        <tbody>
            <tr>
                <td>
                    <label for="imgfile"><?= _('Bilddatei auswählen:') ?></label>
                </td>
                <td>
                    <input id="imgfile" name="imgfile" type="file" accept="image/*">
                </td>
            </tr>
            <tr>
                <td>
                    <label for="description"><?= _('Beschreibung:') ?></label>
                </td>
                <td>
                    <input type="text" id="description" name="description"
                           value="<?= htmlReady($this->flash['request']['description']) ?>"
                           style="width: 240px;" maxlen="254">
                </td>
            </tr>
            <tr>
                <td>
                    <label for="alttext"><?= _('Alternativtext:') ?></label>
                </td>
                <td>
                    <input type="text" id="alttext" name="alttext"
                           value="<?= htmlReady($this->flash['request']['alttext']) ?>"
                           style="width: 240px;" maxlen="254">
                </td>
            </tr>
            <tr>
                <td>
                    <label for="target_type"><?= _('Verweis-Typ:') ?></label>
                </td>
                <td>
                    <select id="target_type" name="target_type">
                    <? foreach ($target_types as $key => $label): ?>
                        <option value="<?= $key ?>"><?= $label ?></option>
                    <? endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <td><?= _('Verweis-Ziel:') ?></td>
                <td>
                    <input type="url" class="target-url" name="target"
                           placeholder="<?= _('URL eingeben') ?>"
                           value="<?= htmlReady($this->flash['request']['target']) ?>"
                           style="width: 240px;" maxlen="254">

                    <?= QuickSearch::get('seminar', new StandardSearch('Seminar_id'))
                                   ->setInputStyle('width: 240px')
                                   ->setInputClass('target-seminar')
                                   ->render() ?>

                    <?= QuickSearch::get('institut', new StandardSearch('Institut_id'))
                                   ->setInputStyle('width: 240px')
                                   ->setInputClass('target-inst')
                                   ->render() ?>

                    <?= QuickSearch::get('user', new StandardSearch('username'))
                                   ->setInputStyle('width: 240px')
                                   ->setInputClass('target-user')
                                   ->render() ?>

                    <span class="target-none"><?= _('Kein Verweisziel') ?></span>
                </td>
            </tr>
            <tr>
                <td>
                    <label for="start_day"><?= _('Anzeigen ab:') ?></label>
                </td>
                <td>
                    <?= $this->render_partial('admin/banner/datetime-picker', array('prefix' => 'start_')) ?>
                </td>
            </tr>
            <tr>
                <td>
                    <label for="end_day"><?= _('Anzeigen bis:')?></label>
                </td>
                <td>
                    <?= $this->render_partial('admin/banner/datetime-picker', array('prefix' => 'end_')) ?>
                </td>
            </tr>
            <tr>
                <td>
                    <label for="priority"><?= _('Priorität:')?></label>
                </td>
                <td>
                    <select name="priority">
                    <? foreach ($priorities as $key => $label): ?>
                        <option value="<?= $key ?>"><?= $label ?></option>
                    <? endforeach; ?>
                    </select>
                </td>
            </tr>
        </tbody>
        <tfoot>
            <tr>
                <td>&nbsp;</td>
                <td>
                    <?= Button::createAccept(_('Anlegen'), 'anlegen', array('title' => _('Banner anlegen'))) ?>
                    <?= LinkButton::createCancel(_('Abbrechen'), $controller->url_for('admin/banner')) ?>
                </td>
            </tr>
        </tfoot>
    </table>
</form>

<script type="text/javascript">
jQuery(function ($) {
    $('#target_type').change(function () {
        var target = $(this).val();
        $(this).closest('tr').next().find('[class^="target"]').hide().filter('.target-' + target).show();
    }).change();
});
</script>

