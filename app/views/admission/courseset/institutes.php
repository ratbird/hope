<?= Assets::img('icons/16/yellow/arr_2down.png', array(
    'alt' => _('Einrichtung hinzufügen'),
    'title' => _('Einrichtung hinzufügen'),
    'onclick' => "STUDIP.Admission.updateInstitutes($('#institute_id_1_realvalue').val(), '". 
        $controller->url_for('admission/courseset/institutes', $courseset ? $courseset->getId() : '')."', '".
        $controller->url_for('admission/courseset/instcourses', $courseset ? $courseset->getId() : '')."', 'add')")) ?>
<?= $instSearch ?>
<br/><br/>
<ul>
    <?php foreach ($selectedInstitutes as $institute => $data) { ?>
    <li id="<?= $institute ?>">
        <input type="hidden" name="institutes[]" value="<?= $institute ?>" class="institute">
        <span class="hover_box">
            <?= $via_ajax ? studip_utf8encode(htmlReady($data['Name'])) : htmlReady($data['Name']) ?>
            <span class="hover_symbols">
                <?= Assets::img('icons/16/blue/trash.png', array(
                            'alt' => _('Einrichtung entfernen'),
                            'title' => _('Einrichtung entfernen'),
                            'onclick'=> "STUDIP.Admission.updateInstitutes('".$institute."', '".
                                $controller->url_for('admission/courseset/institutes', $institute)."', '".
                                $controller->url_for('admission/courseset/instcourses', $institute)."', 'delete')"
                        )
                    ); ?>
            </span>
        </span>
    </li>
    <?php } ?>
</ul>