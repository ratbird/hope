<input size="11" name="<?=$prefix?>date" id="<?=$prefix?>date" 
        value="<?if($timestamp) : ?><?=date('d.m.Y',$timestamp)?><?endif;?>"
        placeholder ="TT.MM.JJJJ"
        <?if($disabled) : ?>disabled<?endif;?>>
&nbsp; &nbsp;
<input type="number" name="<?= $prefix ?>hour" placeholder="<?= _('hh') ?>"
       value="<? if ($timestamp) echo date('G', $timestamp) ?>"
       size="2" maxlength="2" style="width: 4em;"
       <? if ($disabled) echo 'disabled'; ?>> :
<input type="number" name="<?= $prefix ?>minute" placeholder="<?= _('mm') ?>"
       value="<? if ($timestamp) echo date('i', $timestamp) ?>"
       size="2" maxlength="2" style="width: 4em;"
       <? if ($disabled) echo 'disabled'; ?>>
<script>
    jQuery('#<?=$prefix?>date').datepicker();
</script>