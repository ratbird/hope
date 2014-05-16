<div>
<span style="font-weight:bold"><?php echo _("Betreff")?>:</span>
<span style="padding-left:5px"><?php echo htmlReady($msg['subject'])?></span>
</div>
<div>
<span style="font-weight:bold"><?php echo _("Datum")?>:</span>
<span style="padding-left:5px"><?php echo strftime('%x %X', $msg['mkdate'])?></span>
</div>
<div>
<span style="font-weight:bold"><?php echo _("Von")?>:</span>
<span style="padding-left:5px"><?php echo htmlReady($msg['from'])?></span>
</div>
<div>
<span style="font-weight:bold"><?php echo _("An")?>:</span>
<span style="padding-left:5px"><?php echo htmlReady($msg['to'])?></span>
</div>
<div style="margin-top:10px;margin-bottom:10px;">
<?php echo formatReady($msg['message'])?>
</div>
<?php if (count($msg['attachments'])) : ?>
    <hr>
    <div style="font-weight:bold">
    <?php echo _("Dateianh�nge:")?>
    </div>
    <?php foreach($msg['attachments'] as $one) : ?>
    <div>
    <?php echo htmlReady($one->filename) . ' (' . round($one->filesize/1024) . ' Kb)' ?>
    </div>
    <?php endforeach;?>
<?php endif;?>
