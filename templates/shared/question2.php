<?
# Lifter010: TODO
    use Studip\Button;
?>
<div class="modaloverlay">
    <div class="create-question-dialog ui-widget-content ui-dialog studip-confirmation">
        <div class="ui-dialog-titlebar ui-widget-header ui-corner-all ui-helper-clearfix">
            <span><?= _('Bitte bestätigen Sie die Aktion') ?></span>
            <a href="<?= $disapprovalLink ?>" class="ui-button ui-widget ui-state-default ui-corner-all ui-button-icon-only ui-dialog-titlebar-close">
                <span class="ui-button-icon-primary ui-icon ui-icon-closethick"></span>
                <span class="ui-button-text"><?= _('Schliessen') ?></span>
            </a>
        </div>
        <div class="content ui-widget-content ui-dialog-content studip-confirmation">
            <?= formatReady($question) ?>
        </div>
        <div class="buttons ui-widget-content ui-dialog-buttonpane">
            <div class="ui-dialog-buttonset">
                <form action="<?= $approvalLink ?>" method="post">
                    <?= CSRFProtection::tokenTag() ?> 
                    <?= $this->render_partial('shared/question2-parameters.php', array(
                            'parameters' => $approvParams
                    )) ?>
                    <?= Button::createAccept(_('JA!'), 'yes', array('style' => 'float: left')) ?>
                </form>
                <form action="<?= $approvalLink ?>" method="post">
                    <?= CSRFProtection::tokenTag() ?> 
                    <?= $this->render_partial('shared/question2-parameters.php', array(
                            'parameters' => $disapproveParams
                    )) ?>
                    <?= Button::createCancel(_('NEIN!'), 'no', array('style' => 'float: left')) ?>
                </form>
            </div>
        </div>
    </div>    
</div>
