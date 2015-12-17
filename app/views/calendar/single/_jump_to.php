<form action="<?= $action_url ?>" method="post" name="jump_to">
    <span style="font-size: small; color: #555555;"><?= _("Gehe zu:") ?> </span>
    <input type="hidden" name="action" value="<?= $action ?>">
    <input size="10" type="text" id="jmp_date" name="jmp_date" type="text" value="<?= strftime('%x', $atime)?>">
    <?= Icon::create('accept', 'clickable')->asImg(['class' => 'text-top']) ?>
</form>
<script>
    jQuery('#jmp_date').datepicker();
</script>