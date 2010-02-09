<ul>
  <? foreach ($courses as $course) : ?>
    <li><span class="informal">
    <? if (strlen($course['VeranstaltungsNummer'])) : ?>
      <span class="number"><?= htmlReady($course['VeranstaltungsNummer']) ?>: </span>
    <? endif ?>
    </span><?= htmlReady($course['Name']) ?><span class="informal">

    <? if (isset($semesters[$course['start_time']])) : ?>
      <span class="semester">(<?= htmlReady($semesters[$course['start_time']]) ?>)</span>
    <? endif ?>
    <br>

    <span class="lecturer"><?= htmlReady(text_excerpt($course['lecturer'], $search_term, 20, 60)) ?></span>
    <br>

    <span class="comment"><?= htmlReady(text_excerpt($course['Beschreibung'], $search_term, 20, 60)) ?></span>

    <span class="seminar_id"><?= $course['seminar_id'] ?></span>

    </span></li>
  <? endforeach ?>
</ul>
