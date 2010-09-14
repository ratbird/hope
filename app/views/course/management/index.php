<?
/* * * * * * * * * * * * *
 * * * I N F O B O X * * *
 * * * * * * * * * * * * */
$infobox['picture'] = 'infobox/administration.jpg';
if ($GLOBALS['perm']->have_perm('dozent')) {
    $infobox['content'] = array(
        array(
            'kategorie' => _("Aktionen"),
            'eintrag'   => array(
                array(
                    'text' => _("Diese Veranstaltung <a href=".URLHelper::getLink('copy_assi.php?list=TRUE&new_session=TRUE')."\>kopieren</a> und damit eine neue Veranstaltung mit gleichen Einstellungen erstellen."),
                    'icon' => 'icons/16/black/schedule.png'
                ),
            )
        )
    );
}

if (($GLOBALS['perm']->have_studip_perm('dozent', $GLOBALS['SessSemName'][1])
    && get_config('ALLOW_DOZENT_ARCHIV'))
    || $GLOBALS['perm']->have_perm('admin')) {

    $infobox['content'][0]['eintrag'][] = array(
        'text' => _("Diese Veranstaltung <a href=".URLHelper::getLink('archiv_assi.php?list=TRUE&new_session=TRUE')."\>archivieren</a> und damit beenden."),
        'icon' => 'icons/16/black/schedule.png'
    );

    if ($visible) {
        $text = sprintf(_('Diese Veranstaltung %sunsichtbar%s schalten'),
            '<a href="'.  $controller->url_for('course/management/visible/0') .'">',
            '</a>');
    } else {
        $text = sprintf(_('Diese Veranstaltung %ssichtbar%s schalten'),
            '<a href="'.  $controller->url_for('course/management/visible/1') .'">',
            '</a>');
    }
    $infobox['content'][0]['eintrag'][] = array(
        'text' => $text,
        'icon' => 'icons/16/black/schedule.png'
    );
}

$infobox['content'][] = array(
    'kategorie' => _("Information"),
    'eintrag'   => array(
        array(
            'text' => _("Sie können hier Ihre Veranstaltung in mehreren Kategorien anpassen. Informationen wie Grunddaten oder Termine und Einstellungen  Zugangsbeschränkungen und Funktionen können Sie hier administrieren."),
            "icon" => "icons/16/black/info.png"
         )
     )
);

?>

<div style="padding-left:0.5em; background-color: white; width: 100%">
  <h1 class="smashbox_kategorie"><?=_("Grundeinstellungen")?></h1>

  <div class="smashbox_stripe">
      <div style="margin-left: 1.5em;">

        <a class="click_me" href="<?= $controller->url_for('course/basicdata/view/'. $GLOBALS['SessSemName'][1]) ?>">
          <div>
              <span class="click_head"><?=_("Grunddaten")?></span>
              <p><?=_("Prüfen und Bearbeiten Sie in diesem Verwaltungsbereich die Grundeinstellungen dieser Veranstaltung.")?></p>
          </div>
          </a>

             <a class="click_me" href="<?= $controller->url_for('course/study_areas/show/'. $GLOBALS['SessSemName'][1]) ?>">
          <div>
              <span class="click_head"><?=_("Studienbereiche")?></span>
              <p><?=_("Legen Sie hier fest, in welchen Studienbereichen diese Veranstaltung im Verzeichnis aller Veranstaltungen erscheint.")?></p>
          </div>
          </a>

             <a class="click_me" href="<?= UrlHelper::getLink('raumzeit.php') ?>">
          <div>
              <span class="click_head"><?=_("Zeit und Ort")?></span>
              <p><?=_("Verändern Sie hier Angaben über regelmäßige Veranstaltungszeiten, Einzeltermine und Ortsangaben.")?></p>
          </div>
          </a>

          <a class="click_me" href="<?= UrlHelper::getLink('admin_admission.php') ?>">
          <div>
              <span class="click_head"><?=_("Zugangseinstellungen")?></span>
              <p><?=_("Richten Sie hier verschiedene Zugangsbeschränkungen, Anmeldeverfahren oder einen Passwortschutz für Ihre Veranstaltung ein.")?></p>
          </div>
          </a>

    </div>
         <br style="clear: both;">
  </div>

  <h1 class="smashbox_kategorie"><?=_("Weitere Inhaltselemente")?></h1>

  <div class="smashbox_stripe">
      <div style="margin-left: 1.5em;">

          <a class="click_me" href="<?= UrlHelper::getLink('admin_news.php?view=news_sem') ?>">
          <div>
              <span class="click_head"><?=_("Ankündigungen")?></span>
              <p><?=_("Erstellen Sie Ankündigungen für Ihre Veranstaltung und bearbeiten Sie laufende Ankündigungen.")?></p>
              </div>
          </a>

          <? if (get_config('VOTE_ENABLE')) : ?>
          <a class="click_me" href="<?= UrlHelper::getLink('admin_vote.php??view=vote_sem') ?>">
          <div>
              <span class="click_head"><?=_("Umfragen und Tests")?></span>
              <p><?=_("Erstellen Sie für Ihre Veranstaltung einfachen Umfragen und Tests.")?></p>
              </div>
          </a>

            <a class="click_me" href="<?= UrlHelper::getLink('admin_evaluation.php?view=eval_sem') ?>">
             <div>
              <span class="click_head"><?=_("Evaluationen")?></span>
              <p><?=_("Richten Sie für Ihre Veranstaltung fragebogenbasierte Umfragen und Lehr-Evaluationen ein.")?></p>
          </div>
          </a>
          <? endif ?>

        </div>
        <br style="clear: both;">
    </div>
</div>
