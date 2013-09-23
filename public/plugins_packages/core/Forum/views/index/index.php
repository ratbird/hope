<script>
    // for some reason jQuery(document).ready(...) is not always working...
    jQuery(function () {
        STUDIP.Forum.seminar_id = '<?= $seminar_id ?>';
        STUDIP.Forum.init();
    });
</script>

<?= $this->render_partial('index/_confirm_dialog') ?>

<!-- set a CSS "namespace" for Forum -->
<div id="forum">
<? 

$infobox_content[] = array(
    'kategorie' => _('Informationen'),
    'eintrag'   => array(
        array(
            'icon' => 'icons/16/black/info.png',
            'text' => sprintf(_('Sie befinden sich hier im Forum. Ausführliche Hilfe finden Sie in der %sDokumentation%s.'),
                '<a href="'. format_help_url(PageLayout::getHelpKeyword()) .'" target="_blank">', '</a>')
        )
    )
);

if (ForumPerm::has('search', $seminar_id)) :
    $infobox_content[] = array(
        'kategorie' => _('Suche'),
        'eintrag'   => array(
            array(
                'icon' => $section == 'search' ? 'icons/16/red/arr_1right.png' : 'icons/16/grey/arr_1right.png',
                'text' => $this->render_partial('index/_search', array('id' => 'tutorSearchInfobox'))
            )
        )
    );
endif;

if ($constraint['depth'] == 0 && $section == 'index') :
    $infobox_content[] = array(
        'kategorie' => _('Tour'),
        'eintrag'   => array(
            array(
                'icon' => 'icons/16/black/info.png',
                'text' => '<a href="javascript:STUDIP.Forum.startTour()">Tour starten</a>'
            )
        )
    );
endif;

$eintraege = array();
if (ForumPerm::has('abo', $seminar_id)) {
    if (ForumAbo::has($constraint['topic_id'])) :
        $abo_text = _('Nicht mehr abonnieren');
        $abo_url = PluginEngine::getLink('coreforum/index/remove_abo/' . $constraint['topic_id']);
    else :
        switch ($constraint['depth']) {
            case '0': $abo_text = _('Komplettes Forum abonnieren');break;
            case '1': $abo_text = _('Diesen Bereich abonnieren');break;
            default: $abo_text = _('Dieses Thema abonnieren');break;
        }
        
        $abo_url = PluginEngine::getLink('coreforum/index/abo/' . $constraint['topic_id']);
    endif;
    
    $eintraege[] = array(
        'icon' => 'icons/16/black/link-intern.png',
        'text' => '<a href="'. $abo_url .'">' . $abo_text .'</a>'
    );
}

if (ForumPerm::has('pdfexport', $seminar_id)) {
    $eintraege[] = array(
        'icon' => 'icons/16/black/export/file-pdf.png',
        'text' => '<a href="'. PluginEngine::getLink('coreforum/index/pdfexport/' . $constraint['topic_id']) .'">' . _('Beiträge als PDF exportieren') .'</a>'
    );
}
    
if (!empty($eintraege)) {
    $infobox_content[] = array(
        'kategorie' => _('Aktionen'),
        'eintrag'   => $eintraege
    );
}

// show the infobox only if it contains elements
if (!empty($infobox_content)) :
    $infobox = array('picture' => 'infobox/schedules.jpg', 'content' => $infobox_content);
endif;
?>

<!-- Breadcrumb navigation -->
<?= $this->render_partial('index/_breadcrumb') ?>

<!-- Seitenwähler (bei Bedarf) am oberen Rand anzeigen -->
<div style="float: right; padding-right: 10px;" data-type="page_chooser">
    <? if ($constraint['depth'] > 0 || !isset($constraint)) : ?>
    <?= $pagechooser = $GLOBALS['template_factory']->render('shared/pagechooser', array(
        'page'         => ForumHelpers::getPage() + 1,
        'num_postings' => $number_of_entries,
        'perPage'      => ForumEntry::POSTINGS_PER_PAGE,
        'pagelink'     => str_replace('%%s', '%s', str_replace('%', '%%', PluginEngine::getURL('coreforum/index/goto_page/'. $topic_id .'/'. $section 
            .'/%s/?searchfor=' . $searchfor . (!empty($options) ? '&'. http_build_query($options) : '' ))))
    )); ?>
    <? endif ?>
    <?= $link  ?>
</div>
<br style="clear: both">

<div class="searchbar">
    <?= $this->render_partial('index/_search', array('id' => 'tutorSearch')); ?>
</div>

<!-- Message area -->
<div id="message_area">
    <?= $this->render_partial('messages') ?>
</div>

<? if ($no_entries) : ?>
    <?= MessageBox::info(_('In dieser Ansicht befinden sich zur Zeit keine Beiträge.')) ?>
<? endif ?>

<!-- Bereiche / Themen darstellen -->
<? if ($constraint['depth'] == 0) : ?>
    <?= $this->render_partial('index/_areas') ?>
<? elseif ($constraint['depth'] == 1) : ?>
    <?= $this->render_partial('index/_threads') ?>
<? endif ?>

<? if (!empty($postings)) : ?>
    <!-- Beiträge für das ausgewählte Thema darstellen -->
    <?= $this->render_partial('index/_postings') ?>
<? endif ?>

<!-- Seitenwähler (bei Bedarf) am unteren Rand anzeigen -->
<? if ($pagechooser) : ?>
<div style="float: right; padding-right: 10px;" data-type="page_chooser">
    <?= $pagechooser ?>
</div>
<? endif ?>

<!-- Erstellen eines neuen Elements (Kateogire, Thema, Beitrag) -->
<? if ($constraint['depth'] == 0) : ?>
    <? if (ForumPerm::has('add_category', $seminar_id)) : ?>
        <?= $this->render_partial('index/_new_category') ?>
    <? endif ?>

    <div style="text-align: center">
        <div class="button-group">
            <? if (ForumPerm::has('abo', $seminar_id) && $section == 'index') : ?>
            <span id="abolink">
                <?= $this->render_partial('index/_abo_link', compact('constraint')) ?>
            </span>
            <? endif ?>

            <? if (ForumPerm::has('pdfexport', $seminar_id) && $section == 'index') : ?>
                <?= Studip\LinkButton::create('Beiträge als PDF exportieren', PluginEngine::getLink('coreforum/index/pdfexport')) ?>
            <? endif ?>
        </div>
    </div>
    
<? else : ?>
    <? if (!$flash['edit_entry'] && ForumPerm::has('add_entry', $seminar_id)) : ?>
    <? $constraint['depth'] == 1 ? $button_face = _('Neues Thema erstellen') : $button_face = _('Antworten') ?>
    <div style="text-align: center">
        <div id="new_entry_button" <?= $this->flash['new_entry_title'] ? 'style="display: none"' : '' ?>>
            <div class="button-group">
                <?= Studip\LinkButton::create($button_face, PluginEngine::getLink('coreforum/index/new_entry/' . $topic_id),
                    array('onClick' => 'STUDIP.Forum.answerEntry(); return false;')) ?>
            
                <? if ($constraint['depth'] > 0 && ForumPerm::has('abo', $seminar_id)) : ?>
                <span id="abolink">
                    <?= $this->render_partial('index/_abo_link', compact('constraint')) ?>
                </span>
                <? endif ?>
                
                <? if (ForumPerm::has('pdfexport', $seminar_id)) : ?>
                <?= Studip\LinkButton::create('Beiträge als PDF exportieren', PluginEngine::getLink('coreforum/index/pdfexport/' . $topic_id)) ?>
                <? endif ?>
            </div>
        </div>

    </div>
    <? endif ?>

<? endif ?>

    <? if (ForumPerm::has('add_entry', $seminar_id)): ?>
        <?= $this->render_partial('index/_new_entry') ?>
    <? endif ?>
</div>

<!-- Mail-Notifikationen verschicken (soweit am Ende der Seite wie möglich!) -->
<? if ($flash['notify']) :
    ForumAbo::notify($flash['notify']);
endif ?>
