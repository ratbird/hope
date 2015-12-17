<?= Icon::create('arr_2down', 'sort', ['title' => _('Einrichtung hinzuf�gen')])->asImg(16, ["alt" => _('Einrichtung hinzuf�gen'), "onclick" => "STUDIP.Admission.updateInstitutes($('input[name=&quot;institute_id&quot;]').val(), '".$controller->url_for('admission/courseset/institutes',$courseset?$courseset->getId():'')."', '".$controller->url_for('admission/courseset/instcourses',$courseset?$courseset->getId():'')."', 'add')"]) ?>
<?= $instSearch ?>
<?= Icon::create('search', 'clickable', ['title' => _("Suche starten")])->asImg()?>

<ul>
    <?php foreach ($selectedInstitutes as $institute => $data) { ?>
    <li id="<?= $institute ?>">
        <input type="hidden" name="institutes[]" value="<?= $institute ?>" class="institute">
        <span class="hover_box">
            <?= htmlReady($data['Name']) ?>
            <span class="action_icons">
                <?= Icon::create('trash', 'clickable', ['title' => _('Einrichtung entfernen')])->asImg(16, ["alt" => _('Einrichtung entfernen'), "onclick" => "STUDIP.Admission.updateInstitutes('".$institute."', '".$controller->url_for('admission/courseset/institutes',$institute)."', '".$controller->url_for('admission/courseset/instcourses',$institute)."', 'delete')"]); ?>
            </span>
        </span>
    </li>
    <?php } ?>
</ul>