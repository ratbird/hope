<? $color = "#2222aa" ?>
<div id="schedule_entry__new" class="schedule_entry" style="width: 98%">
    <dl style="border: 1px solid <?= $color ?>;
        background-color: <?= $color ?>;
        ">
        <dt style="background-color: <?= $color ?>;">
            <?= sprintf("%s - %s", '<span class="empty_entry_start"></span>:00', '<span class="empty_entry_end"></span>:00') ?>
        </dt>
        <dd>
            <?= _("Neuer Eintrag") ?>
        </dd>
    </dl>
    <div class="snatch" style="display: none"><div> </div></div>
</div>