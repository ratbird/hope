<script>
    // for some reason jQuery(document).ready(...) is not always working...
    jQuery(function () {
        STUDIP.Forum.seminar_id = '<?= $seminar_id ?>';
        STUDIP.Forum.init();
    });
</script>

<!-- set a CSS "namespace" for Forum -->
<div id="forum">
<? 
if (ForumPerm::has('search', $seminar_id)) :
    $infobox_content[] = array(
        'kategorie' => _('Suche'),
        'eintrag'   => array(
            array(
                'icon' => $section == 'search' ? 'icons/16/red/arr_1right.png' : 'icons/16/grey/arr_1right.png',
                'text' => $this->render_partial('index/_search')
            )
        )
    );

    if ($constraint['depth'] == 0) :
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
endif;

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
    <?= $this->render_partial('index/_search'); ?>
</div>

<!-- Message area -->
<div id="message_area">
    <?= $this->render_partial('messages') ?>
</div>

<? if ($no_entries) : ?>
    <?= MessageBox::info(_('In dieser Ansicht befinden sich zur Zeit keine Beiträge.')) ?>
<? endif ?>

<!-- Bereiche / Themen / Beiträge -->
<? if (!empty($list)) : ?>
    <!-- Bereiche / Themen darstellen -->
    <? if ($constraint['depth'] == 0) : ?>
    <?= $this->render_partial('index/_areas') ?>
    <? else : ?>
    <?= $this->render_partial('index/_threads') ?>
    <? endif ?>
<? elseif ($constraint['depth'] == 0 && $section == 'forum') : ?>
    <?= MessageBox::info(_('Dieses Forum wurde noch nicht eingerichtet. '.
            'Es gibt bisher keine Bereiche, in denen man ein Thema erstellen könnte.')); ?>
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
            <? if (ForumPerm::has('abo', $seminar_id)) : ?>
            <span id="abolink">
                <?= $this->render_partial('index/_abo_link', compact('constraint')) ?>
            </span>
            <? endif ?>

            <? if (ForumPerm::has('pdfexport', $seminar_id)) : ?>
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
                <?= Studip\Button::create($button_face) ?>
            
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

        <div id="new_entry_box" <?= $this->flash['new_entry_title'] ? '' : 'style="display: none"' ?>>
            <br style="clear: both">
            <?= $this->render_partial('index/_new_entry') ?>
        </div>
    </div>
    <? endif ?>

<? endif ?>
</div>

<!-- Mail-Notifikationen verschicken (soweit am Ende der Seite wie möglich!) -->
<? if ($flash['notify']) :
    ForumAbo::notify($flash['notify']);
endif ?>
