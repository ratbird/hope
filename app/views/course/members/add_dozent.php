<?

 use Studip\Button,
     Studip\LinkButton ?>

<h1><?= sprintf(_('%s hinzuf�gen'), htmlReady($decoratedStatusGroups['dozent'])) ?></h1>

<form action="<?= $controller->url_for('course/members/set') ?>" method="post">
<?= CSRFProtection::tokenTag() ?>
    <input type="hidden" name="studipticket" value="<?= $studipticket ?>">
    <table class="default zebra">
        <thead>
            <tr>
                <th colspan="2"><?= sprintf(_('%s suchen'), htmlReady($decoratedStatusGroups['dozent'])) ?></th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>
                    <?=
                        QuickSearch::get('new_dozent', $search)
                        ->withButton(array('reset_button_name' => 'reset_dozent', 
                            'search_button_name' => 'search_dozent'))
                        ->setAttributes(array('required' => 'required'))
                        ->render();
                    ?>  
                    <input type="hidden" name="seminar_id" value="<?= $course_id ?>">
                </td>

                <td>
<?= Button::create(_('Eintragen'), 'add_dozent', 
        array('title' => sprintf(_("als %s eintragen"),  htmlReady($decoratedStatusGroups['dozent'])))) ?>
<?= LinkButton::createCancel(_('Abbrechen'), $controller->url_for('dispatch.php/course/members/index')) ?>
                </td>
            </tr>
        </tbody>
    </table>
</form>