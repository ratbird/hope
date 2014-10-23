<h3><?= $rule->getName() ?></h3>
<?= $tpl ?>
<input type="hidden" name="search_sem_qs_choose" value="title_lecturer_number">

<? if ($mandatory_course) : ?>
    <input type="hidden" name="mandatory_course_id_old" value="<?=$mandatory_course->id?>">
    <label class="caption">
        <?= _('Mitgliedschaft in folgender Veranstaltung ist notwendig') ?>:
    </label>
    <p>
        <?=htmlReady($mandatory_course->getFullName('number-name-semester'));?>
        <a href="<?=URLHelper::getScriptLink('dispatch.php/course/details/index/' . $mandatory_course->id) ?>"  data-dialog>
            <?= Assets::img('icons/16/grey/info-circle.png', array('title' =>_('Veranstaltungsdetails aufrufen')))?>
        </a>
    </p>
<? endif ?>
<label class="caption">
    <?= _('Veranstaltung suchen') ?>:
</label>
<div style="display:inline-block">

<?=
QuickSearch::get("mandatory_course_id", new SeminarSearch('number-name-lecturer'))
    ->render();
?>
<?= SemesterData::GetSemesterSelector(array('name' => 'search_sem_sem'), SemesterData::GetSemesterIndexById($_SESSION['_default_sem']), 'key', false)?>

</div>

<br><br>