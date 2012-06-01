<?
# Lifter010: TODO

use Studip\Button, Studip\LinkButton, Studip\ResetButton;

$options = array(
     'show_total_stats'            => _('Zeige Gesamtstatistik an'),
     'show_graphics'               => _('Zeige Grafiken an'),
     'show_questions'              => _('Zeige Fragen an'),
     'show_group_headline'         => _('Zeige Gruppenüberschriften an'),
     'show_questionblock_headline' => _('Zeige Fragenblocküberschriften an'),
);

$graphtypes = array(
    'polscale_gfx_type' => array(
        'title'   => _('Grafiktyp für Polskalen'),
        'options' => array(
            'bars'        => _('Balken'),
            'pie'         => _('Tortenstücke'),
            'lines'       => _('Linien'),
            'linepoints'  => _('Linienpunkte'),
            'area'        => _('Bereich'),
            'points'      => _('Punkte'),
            'thinbarline' => _('Linienbalken'),
        ),
    ),
    'likertscale_gfx_type' => array(
        'title'   => _('Grafiktyp für Likertskalen'),
        'options' => array(
            'bars'        => _('Balken'),
            'pie'         => _('Tortenstücke'),
            'lines'       => _('Linien'),
            'linepoints'  => _('Linienpunkte'),
            'area'        => _('Bereich'),
            'points'      => _('Punkte'),
            'thinbarline' => _('Linienbalken'),
        ),
    ),
    'mchoice_scale_gfx_type' => array(
        'title'   => _('Grafiktyp für Multiplechoice'),
        'options' => array(
            'bars'        => _('Balken'),
            'points'      => _('Punkte'),
            'thinbarline' => _('Linienbalken'),
        ),
    ),
);
?>

<form action="<?= URLHelper::getLink() ?>" method="post">
    <?= CSRFProtection::tokenTag() ?>

    <input type="hidden" name="template_id" value="<?= $templates['template_id'] ?>">
    <input type="hidden" name="eval_id" value="<?= $eval_id ?>">

    <table class="default zebra">
        <colgroup>
            <col width="50%">
            <col width="25%">
            <col width="25%">
        </colgroup>
        <thead>
            <tr>
                <th class="topic" colspan="3">
                    <?= Assets::img('icons/16/white/test.png') ?>
                    <?= _('Auswertungskonfiguration') ?>
                </th>
            </tr>
            <tr>
                <th><?= _('Optionen') ?></th>
                <th style="text-align: center;"><?= _('Ja') ?></th>
                <th style="text-align: center;"><?= _('Nein') ?></th>
            </tr>
        </thead>
        <tbody>
        <? foreach ($options as $option => $title): ?>
            <tr>
                <td><?= htmlReady($title) ?>:</td>
                <td style="text-align: center;">
                    <input type="radio" name="<?= $option ?>" value="1"
                           <? if ($templates[$option] || !$has_template) echo 'checked'; ?>>
                </td>
                <td style="text-align: center;">
                    <input type="radio" name="<?= $option ?>" value="0"
                           <? if ($has_template && !$templates[$option]) echo 'checked'; ?>
                </td>
            </tr>
        <? endforeach; ?>

        <? foreach ($graphtypes as $type => $data): ?>
            <tr>
                <td>
                    <label for="<?= $type ?>"><?= htmlReady($data['title']) ?>:</label>
                </td>
                <td style="text-align: center;" colspan="2">
                    <select id="<?= $type ?>" name="<?= $type ?>" style="120px">
                    <? foreach ($data['options'] as $k => $v): ?>
                        <option value="<?= htmlReady($k) ?>"
                                <? if ($templates[$type] == $k) echo "selected"; ?>>
                            <?= htmlReady($v) ?>
                        </option>
                    <? endforeach; ?>
                    </select>
                </td>
            </tr>
        <? endforeach; ?>
        </tbody>

        <tfoot>
            <tr>
                <td>
                    <?= LinkButton::create('<< ' . _('Zurück'), 
                                           URLHelper::getURL('eval_summary.php', compact('eval_id'))) ?>
                </td>
                <td colspan="2" style="text-align: right;">
                    <?= Button::createAccept(_('Speichern'), 'store') ?>
                    <?= ResetButton::createCancel(_('Zurücksetzen')) ?>
                </td>
            </tr>
        </tfoot>
    </table>
</form>

<?
$infobox = array(
    'picture' => 'infobox/evaluation.jpg',
    'content' => array(
        array(
            'kategorie' => _('Information:'),
            'eintrag'   => array(
                array(
                    'icon' => 'icons/16/black/vote.png',
                    'text' => _('Auf dieser Seite können Sie die Auswertung Ihrer Evaluation konfigurieren.')
                ),
                array(
                    'icon' => 'icons/16/black/info.png',
                    'text' => _('Wählen Sie Ihre Einstellungen und drücken Sie auf "Template speichern". '
                               .'Anschließend kommen Sie mit dem Button unten links zurück zu Ihrer Evaluation.')
                ),
            ),
        ),
    ),
);
?>
