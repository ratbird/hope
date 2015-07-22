<form class="studip-form" action="<?= $controller->url_for('course/wizard/process', $stepnumber, $temp_id) ?>" method="post">
    <?= $content ?>
    <footer>
        <input type="hidden" name="step" value="<?= $stepnumber ?>"/>
        <?php if (!$first_step) { ?>
            <?= Studip\Button::create(_('Zur�ck'), 'back',
                $dialog ? array('data-dialog' => 'size=50%', 'data-dialog-button' => true) : array()) ?>
        <?php } ?>
        <?= Studip\Button::create(_('Weiter'), 'next',
            $dialog ? array('data-dialog' => 'size=50%', 'data-dialog-button' => true) : array()) ?>
    </footer>
</form>