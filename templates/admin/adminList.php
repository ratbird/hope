<form action="<? URLHelper::getLink("?#admin_top_links", array('cid' => null)) ?>" method="get">
    <select class="text-top" aria-label="<?= _("W�hlen Sie ein Seminar aus Ihrer letzten Seminarsuche aus, um dieses zu bearbeiten.") ?>" name="cid" onKeyDown="if (event.keyCode === 13) { jQuery(this).closest('form')[0].submit(); }" onClick="jQuery(this).closest('form')[0].submit();" size="10" style="max-width: 200px;cursor:pointer">
    <? foreach ($adminList as $seminar) : ?>
        <option title="<?= htmlReady($seminar['VeranstaltungsNummer'] . ' ' . $seminar['Name']) ?>" value="<?= htmlReady($seminar['Seminar_id']) ?>"<?= ($seminar['Seminar_id'] === $course_id ? " selected" : "") ?>><?= htmlReady(my_substr($seminar['Name'],0,30)) ?></option>
    <? endforeach ?>
    </select>
    <?= Assets::input('icons/16/green/accept.png', array('type' => 'image', 'class' => 'text-top', 'title' => _('Ausw�hlen'))) ?>
</form>