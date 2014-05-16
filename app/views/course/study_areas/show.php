<?php
# Lifter010: TODO

//Infobox:
$sidebar = Sidebar::get();
$sidebar->setImage(Assets::image_path("sidebar/admin-sidebar.png"));

if ($adminList) {
    $list = new SelectorWidget();
    $list->setUrl("?#admin_top_links");
    foreach ($adminList->adminList as $seminar) {
        $list->addElement(new SelectElement($seminar['Seminar_id'], $seminar['Name']), 'select-' . $seminar['Seminar_id']);
    }
    $list->setSelection($adminList->course_id);
    $sidebar->addWidget($list);
}

?>
<div class="white" style="padding: 0.5em;">

  <? if (isset($error)) : ?>
    <div id="error" style="background:white;margin:0;padding:1em;">
      <table border="0" cellspacing="0" cellpadding="2">
        <tr>
          <td align="center" width="50"><?= Assets::img('icons/16/red/decline.png') ?></td>
          <td align="left"><font color="#FF2020"><?= $error ?></font></td>
        </tr>
      </table>
    </div>
  <? endif ?>

  <? if ($locked) : ?>

    <?= $this->render_partial('course/study_areas/locked_form') ?>

  <? elseif ($areas_not_allowed) : ?>

    <?= MessageBox::info(_("Für diesen Veranstaltungstyp ist die Zuordnung zu Studienbereichen nicht vorgesehen.")) ?>

  <? else : ?>

    <form method="POST" name="details"
          action="<?= $controller->url_for('course/study_areas/show/' . $course_id) ?>">

      <?= CSRFProtection::tokenTag() ?>
      <?= $this->render_partial('course/study_areas/form') ?>

    </form>

  <? endif ?>

</div>
