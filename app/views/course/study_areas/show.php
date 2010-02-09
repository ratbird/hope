<div class="white" style="padding: 0.5em;">

  <? if (isset($error)) : ?>
    <div id="error" style="background:white;margin:0;padding:1em;">
      <table border="0" cellspacing="0" cellpadding="2">
        <tr>
          <td align="center" width="50"><?= Assets::img('x.gif') ?></td>
          <td align="left"><font color="#FF2020"><?= $error ?></font></td>
        </tr>
      </table>
    </div>
  <? endif ?>

  <? if ($locked) : ?>

    <?= $this->render_partial('course/study_areas/locked_form') ?>

  <? elseif ($areas_not_allowed) : ?>

    <div class="effect_highlight" style="padding:1em;">
      <?= Assets::img('ausruf.gif') ?>
      <?= _("Für diesen Veranstaltungstyp ist die Zuordnung zu Studienbereichen nicht vorgesehen.") ?>
    </div>

  <? else : ?>

    <form method="POST" name="details"
          action="<?= $controller->url_for('course/study_areas/show/' . $course_id) ?>">

      <?= $this->render_partial('course/study_areas/form') ?>

    </form>

  <? endif ?>

</div>
