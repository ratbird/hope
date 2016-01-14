<? $allowed_to_add = ($range_id === $GLOBALS['user']->id && $range_type === "user") || ($range_id === "start" && $GLOBALS['perm']->have_perm("root")) || ($range_type === "course" && $GLOBALS['perm']->have_studip_perm("tutor", $range_id)) || ($range_type === "institute" && $GLOBALS['perm']->have_studip_perm("admin", $range_id)) ?>
<section class="contentbox questionnaire_widget" id="questionnaire_area">
    <header>
        <h1>
            <?= Icon::create("evaluation", "info")->asimg("16px", array('class' => "text-bottom")) ?>
            <?= _('Fragebögen') ?>
        </h1>
        <nav>
            <? if ($allowed_to_add) : ?>
                <a href="<?= URLHelper::getLink("dispatch.php/questionnaire/edit", array('range_id' => $range_id, 'range_type' => $range_type)) ?>" data-dialog title="<?= _('Fragebogen hinzufügen') ?>">
                    <?= Icon::create("add", "clickable")->asimg("16px", array('class' => "text-bottom")) ?>
                </a>
                <a href="<?= URLHelper::getLink("dispatch.php/questionnaire/overview") ?>" title="<?= _('Fragebögen verwalten') ?>">
                    <?= Icon::create("admin", "clickable")->asimg("16px", array('class' => "text-bottom")) ?>
                </a>
            <? endif ?>
        </nav>
    </header>

    <? if (!count($questionnaires)): ?>
        <section class="noquestionnaires">
            <?= _('Es sind keine Fragebögen vorhanden.') ?>
            <? if ($allowed_to_add) : ?>
                <?= _("Um neue Fragebögen zu erstellen, klicken Sie rechts auf das Plus.") ?>
            <? endif ?>
        </section>
    <? else: ?>
        <? foreach ($questionnaires as $questionnaire): ?>
            <?= $this->render_partial("questionnaire/_widget_questionnaire", array('questionnaire' => $questionnaire, 'range_type' => $range_type, 'range_id' => $range_id)) ?>
        <? endforeach; ?>
    <? endif; ?>
    <footer>
        <? if ($allowed_to_add) : ?>
            <? if (Request::get('questionnaire_showall')): ?>
                <a href="<?= URLHelper::getLink('#questionnaire_area', array('questionnaire_showall' => 0)) ?>"><?= _('Abgelaufene Fragebögen ausblenden') ?></a>
            <? else: ?>
                <a href="<?= URLHelper::getLink('#questionnaire_area', array('questionnaire_showall' => 1)) ?>"><?= _('Abgelaufene Fragebögen einblenden') ?></a>
            <? endif; ?>
        <? endif ?>
    </footer>
</section>