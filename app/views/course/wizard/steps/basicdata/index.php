<header>
    <h1>
        <?= _('Grunddaten') ?>
    </h1>
</header>
<section>
    <label for="wizard-coursetype" class="required">
        <?= _('Typ') ?>
    </label>
    <select name="coursetype" id="wizard-coursetype">
        <?php foreach ($types as $class => $subtypes) { ?>
            <optgroup label="<?= htmlReady($class) ?>">
                <?php foreach ($subtypes as $type) { ?>
                    <option value="<?= $type['id'] ?>"<?= $type['id'] == $values['coursetype'] ? ' selected="selected"' : '' ?>>
                        <?= htmlReady($type['name']) ?>
                    </option>
                <?php } ?>
            </optgroup>
        <?php } ?>
    </select>
</section>
<section>
    <label for="wizard-start-time" class="required">
        <?= _('Semester') ?>
    </label>
    <select name="start_time" id="wizard-start-time" >
        <?php foreach ($semesters as $semester) { ?>
            <option value="<?= $semester->beginn ?>"<?= $semester->beginn == $values['start_time'] ? ' selected="selected"' : '' ?>>
                <?= htmlReady($semester->name) ?>
            </option>
        <?php } ?>
    </select>
</section>
<section>
    <label for="wizard-name" class="required">
        <?= _('Name') ?>
    </label>
    <input type="text" name="name" id="wizard-name" size="75" maxlength="254" value="<?= htmlReady($values['name']) ?>"/>
</section>
<section>
    <label for="wizard-number">
        <?= _('Veranstaltungsnummer') ?>
    </label>
    <input type="text" name="number" id="wizard-number" size="20" maxlength="99" value="<?= htmlReady($values['number']) ?>"/>
</section>
<section>
    <label for="wizard-description">
        <?= _('Beschreibung') ?>
    </label>
    <textarea name="description" id="wizard-description" cols="75" rows="4"><?= htmlReady($values['description']) ?></textarea>
</section>
<section>
    <label for="wizard-home-institute" class="required">
        <?= _('Heimateinrichtung') ?>
    </label>
    <select name="institute" id="wizard-home-institute"
            data-ajax-url="<?= URLHelper::getLink('dispatch.php/course/wizard/ajax') ?>">
        <?php
        $fak_id = '';
        foreach ($institutes as $inst) :
            if ($inst['is_fak']) {
                $fak_id = $inst['Institut_id'];
            }
            ?>
            <option value="<?= $inst['Institut_id'] ?>"<?=
            $inst['Institut_id'] == $values['institute'] ? ' selected="selected"' : '' ?> class="<?=
            $inst['is_fak'] ? 'faculty' : ($inst['fakultaets_id'] == $fak_id ? 'sub_institute' : 'institute') ?>">
                <?= htmlReady($inst['Name']) ?>
            </option>
        <?php endforeach ?>
    </select>
    <?= Icon::create('arr_2right', 'sort')->asInput(["name" => 'select_institute', "value" => '1', "class" => 'hidden-js']) ?>
</section>
<section>
    <label for="part_inst_id_1">
        <?= _('Beteiligte Einrichtungen') ?>
    </label>
    <div id="wizard-instsearch">
        <?= $instsearch ?>
    </div>
    <?php if ($values['part_inst_id_parameter']) : ?>
        <?= Icon::create('arr_2down', 'sort')->asInput(["name" => 'add_part_inst', "value" => '1']) ?>
    <?php endif ?>
    <div id="wizard-participating">
        <div class="description<?= count($values['participating']) ? '' : ' hidden-js' ?>">
            <?= _('bereits zugeordnet:') ?>
        </div>
        <?php foreach ($values['participating'] as $id => $assigned) : ?>
            <?php if ($inst = Institute::find($id)) : ?>
                <?= $this->render_partial('basicdata/_institute',
                    array('class' => 'institute', 'inst' => $inst)) ?>
            <?php endif ?>
        <?php endforeach ?>
    </div>
</section>
<section >
    <label for="lecturer_id_2" class="required">
        <?= _('Lehrende') ?>
    </label>
    <div id="wizard-lecturersearch">
        <?= $lsearch ?>
    </div>
    <?php if ($values['lecturer_id_parameter']) : ?>
        <?= Icon::create('arr_2down', 'sort')->asInput(["name" => 'add_lecturer', "value" => '1']) ?>
    <?php endif ?>
    <div id="wizard-lecturers">
        <div class="description<?= count($values['lecturers']) ? '' : ' hidden-js' ?>">
            <?= _('bereits zugeordnet:') ?>
        </div>
        <?php foreach ($values['lecturers'] as $id => $assigned) : ?>
            <?php if ($user = User::find($id)) : ?>
                <?= $this->render_partial('basicdata/_user',
                    array('class' => 'lecturer', 'inputname' => 'lecturers', 'user' => $user)) ?>
            <?php endif ?>
        <?php endforeach ?>
    </div>
</section>
<?php if ($dsearch) : ?>
<section>
    <label for="deputy_id_3">
        <?= _('Vertretungen') ?>
    </label>
    <div id="wizard-deputysearch">
        <?= $dsearch ?>
    </div>
    <?php if ($values['deputy_id_parameter']) : ?>
        <?= Icon::create('arr_2down', 'sort')->asInput(["name" => 'add_deputy', "value" => '1']) ?>
    <?php endif ?>
    <div id="wizard-deputies">
        <div class="description<?= count($values['deputies']) ? '' : ' hidden-js' ?>">
            <?= _('bereits zugeordnet:') ?>
        </div>
        <?php foreach ($values['deputies'] as $id => $assigned) : ?>
            <?php if ($user = User::find($id)) : ?>
                <?php if (!in_array($id, array_keys($values['lecturers']))) : ?>
                    <?= $this->render_partial('basicdata/_user',
                        array('class' => 'deputy', 'inputname' => 'deputies', 'user' => $user)) ?>
                <?php endif ?>
            <?php endif ?>
        <?php endforeach ?>
    </div>
</section>
<?php endif ?>
<section for="tutor_id_parameter">
    <label for="tutor_id_2">
        <?= _('Tutor/-in') ?>
    </label>
    <div id="wizard-tutorsearch">
        <?= $tsearch ?>
    </div>
    <?php if ($values['tutor_id_parameter']) : ?>
        <?= Icon::create('arr_2down', 'sort')->asInput(["name" => 'add_tutor', "value" => '1']) ?>
    <?php endif ?>
    <div id="wizard-tutors">
        <div class="description<?= count($values['tutors']) ? '' : ' hidden-js' ?>">
            <?= _('bereits zugeordnet:') ?>
        </div>
        <?php foreach ($values['tutors'] as $id => $assigned) : ?>
            <?php if ($user = User::find($id)) : ?>
                <?= $this->render_partial('basicdata/_user',
                    array('class' => 'tutor', 'inputname' => 'tutors', 'user' => $user)) ?>
            <?php endif ?>
        <?php endforeach ?>
    </div>
</section>
