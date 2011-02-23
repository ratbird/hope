<div class="modalshadow">
    <div class="messagebox messagebox_modal">
        <?= formatReady(_("Möchten Sie folgende Inhaltselemente wirklich deaktivieren? Vorhandene Inhalte werden in der Regel dabei gelöscht")
                    . "\n" . $deactivate_modules_names) ?>
        <div style="margin-top: 1em;">
            <form action="<?= $controller->url_for('course/studygroup/update/'.$sem_id) ?>" method=post>
                <?= CSRFProtection::tokenTag() ?>
                <?
                    $modules = htmlspecialchars(serialize($this->flash['deactivate_modules']));
                    $plugins = htmlspecialchars(serialize($this->flash['deactivate_plugins']));
                ?>
                <input type="hidden" name="deactivate_modules" value="<?=$modules?>">
                <input type="hidden" name="deactivate_plugins" value="<?=$plugins?>">
                <input type="hidden" name="really_deactivate" value="<?=true?>">
                <?= makeButton('ja', 'input') ?>
                <a href="<?= $controller->url_for('course/studygroup/update/'.$sem_id . '?abort_deactivate=1') ?>" style="margin-left: 1em;">
                   <?= makebutton('nein') ?>
               </a>
            </form>
        </div>
    </div>
</div>
