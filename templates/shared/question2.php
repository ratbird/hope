<?
# Lifter010: TODO
    use Studip\Button;
?>
<div class="modaloverlay">
    <div class="messagebox">
        <div class="content">
            <?= formatReady($question) ?>
        </div>
        <div class="buttons">
            <form action="<?=$approvalLink?>" method="post">
                <? if(isset($approvParams)) :?>
                    <? foreach($approvParams as $key => $param) :?>
                        <? if(is_array($param)) :?>
                            <? foreach($param as $value) :?>
                                <input type="hidden" name="<?=$key?>[]" value="<?= $value?>" />
                            <? endforeach?>
                        <? else : ?>
                            <input type="hidden" name="<?=$key?>" value="<?=$param?>" />
                        <? endif ?>
                    <? endforeach?>
                <? endif?>
                <?= Button::createAccept(_('JA!'), 'yes', array('style' => 'float: left')) ?>
            </form>
            <form action="<?=$approvalLink?>" method="post">
                <?= Button::createCancel(_('NEIN!'), 'no', array('style' => 'float: left')) ?>
            </form>
        </div>
    </div>    
</div>
