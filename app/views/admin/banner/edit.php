<? use Studip\Button, Studip\LinkButton; ?>
<h3><?= _('Banner editieren') ?></h3>

<form action="<?= $controller->url_for('admin/banner/edit', $banner['ad_id']) ?>" method="post" enctype="multipart/form-data">
    <?= CSRFProtection::tokenTag() ?>
    <table class="default">
        <tbody>
            <tr>
                <td rowspan="9" colspan="2" class="nohover" style="text-align: center;">
                <? if ($banner['banner_path']): ?>
                    <?= $banner->toImg() ?>
                <? else: ?>
                    <?= _('noch kein Bild hochgeladen') ?>
                <? endif; ?><br>
                    <label for="imgfile"><?= _('Bilddatei auswählen:') ?></label><br>
                    <input id="imgfile" name="imgfile" type="file" accept="image/*"><br>
                    <input type="hidden" name="banner_path" value="<?= $banner['banner_path'] ?>"><br>
                </td>
                <td>
                    <label for="description"><?= _('Beschreibung:') ?></label>
                </td>
                <td>
                    <input type="text" id="description" name="description"
                           value="<?= htmlReady($banner['description']) ?>"
                           size="40" maxlen="254">
                </td>
            </tr>
            <tr>
                <td>
                    <label for="alttext"><?= _('Alternativtext:') ?></label>
                </td>
                <td>
                    <input type="text" id="alttext" name="alttext"
                           value="<?= htmlReady($banner['alttext']) ?>"
                           size="40" maxlen="254">
                </td>
            </tr>
            <tr>
                <td><label for = "vtyp"><?= _("Verweis-Typ:") ?></label></td>
                <td>
                    <input name="target_type" type="hidden" size="8" value="<?=$banner['target_type']?>">
                    <select name="target_type" disabled="disabled">
                    <? foreach ($target_types as $key => $label): ?>
                        <option value="<?= $key ?>" <? if ($banner['target_type'] == $key) echo 'selected'; ?>>
                            <?= $label ?>
                        </option>
                    <? endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <td><label for = "vziel"><?= _("Verweis-Ziel:") ?></label></td>
                <td>
                <? if (in_array($banner['target_type'], words('none url'))): ?>
                    <input type="text" name="target" size="40" maxlen="254" value="<?= htmlReady($banner['target']) ?>">
                <? elseif ($banner['target_type'] == "seminar") :?>
                    <?= $seminar ?>
                <? elseif ($banner['target_type'] == "inst") :?>
                    <?= $institut ?>
                <? else: ?>
                    <?= $user ?>
                <? endif; ?>
                </td>
            </tr>
            <tr>
                <td>
                    <label for="start_day"><?= _('Anzeigen ab:') ?></label>
                </td>
                <td>
                    <?= $this->render_partial('admin/banner/datetime-picker', array(
                            'prefix'    => 'start_',
                            'timestamp' => $banner['startdate'])) ?>
                </td>
            </tr>
            <tr>
                <td>
                    <label for="end_day"><?= _('Anzeigen bis:') ?></label>
                </td>
                <td>
                    <?= $this->render_partial('admin/banner/datetime-picker', array(
                            'prefix'    => 'end_',
                            'timestamp' => $banner['enddate'])) ?>
                </td>
            </tr>
            <tr>
                <td>
                    <label for="priority"><?= _('Priorität:')?></label>
                </td>
                <td>
                    <select id="priority" name="priority">
                    <? foreach ($priorities as $key => $label): ?>
                        <option value="<?= $key ?>" <? if ($banner['priority'] == $key) echo 'selected'; ?>>
                            <?= $label ?>
                        </option>
                    <? endforeach; ?>
                    </select>
                </td>
            </tr>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="4" align="center">
                    <label for = "update">
                        <?= Button::create(_('Aktualisieren'), 'speichern', array('title' => _('Banner editieren')))?>
                    </label>
                </td>
            </tr>
        </tfoot>
    </table>
</form>

