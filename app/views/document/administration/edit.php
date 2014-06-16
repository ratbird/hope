<? use Studip\Button, Studip\LinkButton; ?>

<form action="<?= $controller->url_for('document/administration/store/' . $config_id . '/' . $isGroupConfig) ?>" class="studip_form">
<? if(isset($head)): ?>
    <h3><?=$head?></h3>
<? endif; ?>
<? if($config_id == 0): ?>
    <? if ($isGroupConfig): ?>
        <fieldset>
            <legend><?= _('Nutzergruppe') ?></legend>
            <select name="group" id="group">
            <? foreach ($groups as $group): ?>
                <option><?= htmlReady($group) ?></option>
            <? endforeach; ?>
            </select>
        </fieldset>
    <? else: ?>
        <input type="hidden" name="group" id="group" value="<?=$user_id?>">
    <? endif; ?>
<?endif;?>

    <fieldset>
        <legend><?= _('Maximaler Upload') ?></legend>
        <div>
            <input type="number" name="upload_size" id="upload_size" 
                   value="<?= $config['upload_quota'] ?: 0 ?>">
            <select name="unitUpload">
             <? foreach(words('KB MB GB TB') as $unit) : ?>
                <option <? if ($unit === ($config['upload_unit'] ?: 'MB')) echo 'selected'; ?>>
                    <?= $unit ?>
                </option>
            <? endforeach ?>
            </select>
        </div>
    </fieldset>
    
    <fieldset>
        <legend><?=_('Maximales Quota')?></legend>
        <div>
            <input type="number" name="quota_size" id="quota_size"
                   value="<?= $config['quota'] ?: 0 ?>">
            <select name="unitQuota" id="unitQuota">
            <? foreach(words('KB MB GB TB') as $unit) : ?>
                <option <? if ($unit === ($config['quota_unit'] ?: 'MB')) echo 'selected'; ?>>
                    <?= $unit ?>
                </option>
            <? endforeach ?>
            </select>
        </div>
    </fieldset>
    
    <fieldset>
        <legend><?= _('Untersagte Dateitypen') ?></legend>
        <select id="datetype" multiple name="datetype[]" style="height: 200px; width:100%;">
        <? foreach ($types as $type): ?>
            <?
            foreach ($this->config['types'] as $forbiddenTypes) : ?>
                <? if ($forbiddenTypes['id'] == $type['id']) : ?>
                    <? $setAs = 'selected'; ?>
                <?endif;?>
            <? endforeach; ?>                  
            <option value="<?= $type['id'] ?>"<?= $setAs ?>><?= $type['type'] ?></option>
            <? $setAs = '' ?>
            <? endforeach ?>
            </select>
    </fieldset>

    <div data-dialog-button>
        <?= Button::createAccept(_('Speichern'), 'store') ?>
        <?= LinkButton::createCancel(_('Abbrechen'), $controller->url_for('document/administration/filter')) ?>
    </div>
</form>

<script type="text/javascript">
jQuery(function ($) {
    $('#datetype').multiselect({
        sortable: false,
        searchable: true,
        dividerLocation: 0.5
    });
});
</script>