<input type="number" id="<?= $prefix ?>day" name="<?= $prefix ?>day" placeholder="<?= _('TT') ?>"
       value="<? if ($timestamp) echo date('d', $timestamp) ?>"
       size="2" maxlength="2" style="width: 4em;"
       <? if ($disabled) echo 'disabled'; ?>>.
<input type="number" name="<?= $prefix ?>month" placeholder="<?= _('MM') ?>"
       value="<? if ($timestamp) echo date('m', $timestamp) ?>"
       size="2" maxlength="2" style="width: 4em;"
       <? if ($disabled) echo 'disabled'; ?>>.
<input type="number" name="<?= $prefix ?>year" placeholder="<?= _('JJJJ') ?>"
       value="<? if ($timestamp) echo date('Y', $timestamp) ?>"
       size="4" maxlength="4" style="width: 5em;"
       <? if ($disabled) echo 'disabled'; ?>>
&nbsp; &nbsp;
<input type="number" name="<?= $prefix ?>hour" placeholder="<?= _('hh') ?>"
       value="<? if ($timestamp) echo date('G', $timestamp) ?>"
       size="2" maxlength="2" style="width: 4em;"
       <? if ($disabled) echo 'disabled'; ?>> :
<input type="number" name="<?= $prefix ?>minute" placeholder="<?= _('mm') ?>"
       value="<? if ($timestamp) echo date('i', $timestamp) ?>"
       size="2" maxlength="2" style="width: 4em;"
       <? if ($disabled) echo 'disabled'; ?>>
