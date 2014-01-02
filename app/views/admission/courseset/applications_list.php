<table class="default">
    <thead>
        <tr>
            <? foreach ($captions as $cap) :?>
            <th><?= $cap ?></th>
            <? endforeach ?>
        </tr>
    </thead>
    <tbody>
        <? foreach ($data as $row ) : ?>
        <tr>
            <? foreach ($row as $value ) : ?>
            <td><?= htmlReady($value) ?></td>
            <? endforeach ?>
        </tr>
        <? endforeach ?>
    </tbody>
</table>
<div style="text-align:center">
<?= Studip\LinkButton::create(_("Download"), $controller->url_for('admission/courseset/applications_list/' . $set_id .'/csv')) ?>
<?= Studip\LinkButton::create(_("Abbrechen"), '#', array('rel' => 'close'))?>
</div>