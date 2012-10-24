<?
# Lifter010: TODO
?>
<div class="modaloverlay">
    <div class="messagebox">
        <span id="modalquestion">
            <?= formatReady($question) ?>
        </span>
        <div>
            <form action="<?= $action ?>" method="post">
                <?= CSRFProtection::tokenTag() ?>
                <?foreach($elements as $e) :?>
                    <div style="margin-top: 0.5em; text-align: left;">
                        <?= $e?>
                    </div>
                <?endforeach?>
                <div style="margin-top: 0.5em;">
                    <?= $approvalbutton ?>
                    <span style="margin-left: 1em;">
                        <?= $disapprovalbutton ?>
                    </span>
                </div>
            </form>
        </div>
    </div>
</div>