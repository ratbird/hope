<script>
    // for some reason jQuery(document).ready(...) is not always working...
    jQuery(function () {
        STUDIP.Forum.seminar_id = '<?= $seminar_id ?>';
        STUDIP.Forum.init();
    });
</script>

<?= $this->render_partial('index/_js_templates') ?>

<!-- set a CSS "namespace" for Forum -->
<div id="forum">
<?php

$sidebar = Sidebar::get();
$sidebar->setImage(Assets::image_path("sidebar/forum-sidebar.png"));

if (ForumPerm::has('search', $seminar_id)) {
    $search = new SearchWidget(PluginEngine::getLink('coreforum/index/search?backend=search'));
    $search->setId('tutorSearchInfobox');
    $search->addNeedle(_('Beitr�ge durchsuchen'), 'searchfor', true);
    $search->addFilter(_('Titel'), 'search_title');
    $search->addFilter(_('Inhalt'), 'search_content');
    $search->addFilter(_('AutorIn'), 'search_author');
    $sidebar->addWidget($search);
}

$actions = new ActionsWidget();

if ($constraint['depth'] == 0 && $section == 'index') {
    $actions->addLink(_('Tour starten'), "javascript:STUDIP.Forum.startTour();", 'icons/16/blue/info.png');
}

if ($section == 'index') {
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
        
        $actions->addLink($abo_text, $abo_url, 'icons/16/blue/link-intern.png');
    }

    if (ForumPerm::has('close_thread', $seminar_id) && $constraint['depth'] > 1) {
        if ($constraint['closed'] == 0) {
            $close_url = PluginEngine::getLink('coreforum/index/close_thread/' 
                            . $constraint['topic_id'] .'/'. $constraint['topic_id'] .'/'. ForumHelpers::getPage());
            $close = new LinkElement(
                _('Thema schlie�en'), 
                $close_url, 
                Assets::image_path('icons/16/blue/lock-locked.png'),
                array(
                    'onclick' => 'STUDIP.Forum.closeThreadFromThread(\'' . $constraint['topic_id'] . '\', '
                            . ForumHelpers::getPage() . '); return false;',
                    'class' => "closeButtons"
                )
            );
            $actions->addElement($close, 'closethread');
        } else {
            $open_url = PluginEngine::getLink('coreforum/index/open_thread/' 
                            . $constraint['topic_id'] .'/'. $constraint['topic_id'] .'/'. ForumHelpers::getPage());
            $open = new LinkElement(
                _('Thema �ffnen'),
                $open_url,
                Assets::image_path('icons/16/blue/lock-unlocked.png'),
                array(
                    'onclick' => 'STUDIP.Forum.openThreadFromThread(\'' . $constraint['topic_id'] . '\', '
                                . ForumHelpers::getPage() . '); return false;',
                    'class' => "closeButtons"
                )
            );
            $actions->addElement($open, 'closethread');
        }
    }
    
    if (ForumPerm::has('make_sticky', $seminar_id) && $constraint['depth'] > 1) {
        if ($constraint['sticky'] == 0) {
            $emphasize_url = PluginEngine::getLink('coreforum/index/make_sticky/' 
                                . $constraint['topic_id'] .'/'. $constraint['topic_id'] .'/'. ForumHelpers::getPage());
            $emphasize = new LinkElement(
                _('Thema hervorheben'),
                $emphasize_url,
                Assets::image_path('icons/16/blue/staple.png'),
                array(
                    'onclick' => 'STUDIP.Forum.makeThreadStickyFromThread(\'' . $constraint['topic_id'] . '\', '
                            . ForumHelpers::getPage() . '); return false;',
                    'id' => "stickyButton"
                )
            );
            $actions->addElement($emphasize, 'emphasize');
        } else {
            $unemphasize_url = PluginEngine::getLink('coreforum/index/make_unsticky/' 
                                . $constraint['topic_id'] .'/'. $constraint['topic_id'] .'/'. ForumHelpers::getPage());
            $emphasize = new LinkElement(
                _('Hervorhebung aufheben'),
                $unemphasize_url,
                Assets::image_path('icons/16/blue/staple.png'),
                array(
                    'onclick' => 'STUDIP.Forum.makeThreadStickyFromThread(\'' . $constraint['topic_id'] . '\', '
                            . ForumHelpers::getPage() . '); return false;',
                    'id' => "stickyButton"
                )
            );
            $actions->addElement($emphasize, 'emphasize');
        }
    }

    if ($constraint['depth'] == 0 && ForumPerm::has('add_category', $seminar_id)) {
        $actions->addLink(_('Neue Kategorie erstellen'), "#create", 'icons/16/blue/link-intern.png');
    }
}

$sidebar->addWidget($actions);

if ($section === 'index' && ForumPerm::has('pdfexport', $seminar_id)) {
    $export = new ExportWidget();
    $export->addLink(_('Beitr�ge als PDF exportieren'),
                     $controller->url_for('index/pdfexport/' . $constraint['topic_id']),
                     'icons/16/blue/file-pdf.png');
    $sidebar->addWidget($export);
}
?>

<!-- Breadcrumb navigation -->
<?= $this->render_partial('index/_breadcrumb') ?>

<!-- Seitenw�hler (bei Bedarf) am oberen Rand anzeigen -->
<div style="float: right; padding-right: 10px;" data-type="page_chooser">
    <? if ($constraint['depth'] > 0 || !isset($constraint)) : ?>
    <?= $pagechooser = $GLOBALS['template_factory']->render('shared/pagechooser', array(
        'page'         => ForumHelpers::getPage(),
        'num_postings' => $number_of_entries,
        'perPage'      => ForumEntry::POSTINGS_PER_PAGE,
        'pagelink'     => str_replace('%%s', '%s', str_replace('%', '%%', PluginEngine::getURL('coreforum/index/goto_page/'. $topic_id .'/'. $section 
            .'/%s/?searchfor=' . $searchfor . (!empty($options) ? '&'. http_build_query($options) : '' ))))
    )); ?>
    <? endif ?>
    <?= $link  ?>
</div>
<br style="clear: both">

<!-- Message area -->
<div id="message_area">
    <?= $this->render_partial('messages') ?>
</div>

<? if ($no_entries) : ?>
    <?= MessageBox::info(_('In dieser Ansicht befinden sich zur Zeit keine Beitr�ge.')) ?>
<? endif ?>

<!-- Bereiche / Themen darstellen -->
<? if ($constraint['depth'] == 0) : ?>
    <?= $this->render_partial('index/_areas') ?>
<? elseif ($constraint['depth'] == 1) : ?>
    <?= $this->render_partial('index/_threads') ?>
<? endif ?>

<? if (!empty($postings)) : ?>
    <!-- Beitr�ge f�r das ausgew�hlte Thema darstellen -->
    <?= $this->render_partial('index/_postings') ?>
<? endif ?>

<!-- Seitenw�hler (bei Bedarf) am unteren Rand anzeigen -->
<? if ($pagechooser) : ?>
<div style="float: right; padding-right: 10px;" data-type="page_chooser">
    <?= $pagechooser ?>
</div>
<? endif ?>

<!-- Erstellen eines neuen Elements (Kateogire, Thema, Beitrag) -->
<? if ($constraint['depth'] == 0) : ?>
    <div style="text-align: center">
        <div class="button-group">
            <? if (ForumPerm::has('abo', $seminar_id) && $section == 'index') : ?>
            <span id="abolink">
                <?= $this->render_partial('index/_abo_link', compact('constraint')) ?>
            </span>
            <? endif ?>

            <? if (ForumPerm::has('pdfexport', $seminar_id) && $section == 'index') : ?>
                <?= Studip\LinkButton::create(_('Beitr�ge als PDF exportieren'), PluginEngine::getLink('coreforum/index/pdfexport'), array('target' => '_blank')) ?>
            <? endif ?>
        </div>
    </div>

    <? if ($section == 'index' && $constraint['depth'] == 0 && ForumPerm::has('add_category', $seminar_id)) : ?>
        <?= $this->render_partial('index/_new_category') ?>
    <? endif ?>
<? else : ?>
    <? if (!$flash['edit_entry'] && ForumPerm::has('add_entry', $seminar_id)) : ?>
    <? $constraint['depth'] == 1 ? $button_face = _('Neues Thema erstellen') : $button_face = _('Antworten') ?>
    <div style="text-align: center">
        <div id="new_entry_button" <?= $this->flash['new_entry_title'] ? 'style="display: none"' : '' ?>>
            <div class="button-group">
                <? if ($constraint['depth'] <= 1 || ($constraint['closed'] == 0)) : ?>
                    <?= Studip\LinkButton::create($button_face, PluginEngine::getLink('coreforum/index/new_entry/' . $topic_id),
                        array('onClick' => 'STUDIP.Forum.answerEntry(); return false;',
                        'class' => 'hideWhenClosed',)) ?>
                <? endif ?>
                
                <? if ($constraint['depth'] > 1 && ($constraint['closed'] == 1)) : ?>
                    <?= Studip\LinkButton::create($button_face, PluginEngine::getLink('coreforum/index/new_entry/' . $topic_id),
                        array('onClick' => 'STUDIP.Forum.answerEntry(); return false;',
                            'class' => 'hideWhenClosed',
                            'style' => 'display:none;'
                        )) ?>
                <? endif ?>
                
                <? if (ForumPerm::has('close_thread', $seminar_id) && $constraint['depth'] > 1) : ?>
                    <? if ($constraint['closed'] == 0): ?>
                    <?= Studip\LinkButton::create(_('Thema schlie�en'), 
                            PluginEngine::getLink('coreforum/index/close_thread/' . $topic_id .'/'. $topic_id .'/'. ForumHelpers::getPage()), array(
                                'onClick' => 'STUDIP.Forum.closeThreadFromThread("'. $topic_id .'"); return false;',
                                'class' => 'closeButtons')
                        ) ?>
                    <? else: ?>
                    <?= Studip\LinkButton::create(_('Thema �ffnen'), 
                        PluginEngine::getLink('coreforum/index/open_thread/' . $topic_id .'/'. $topic_id .'/'. ForumHelpers::getPage()), array(
                            'onClick' => 'STUDIP.Forum.openThreadFromThread("'. $topic_id .'"); return false;',
                            'class' => 'closeButtons')
                        ) ?>
                    <? endif ?>
                <? endif ?>
                
                <? if ($constraint['depth'] > 0 && ForumPerm::has('abo', $seminar_id)) : ?>
                <span id="abolink">
                    <?= $this->render_partial('index/_abo_link', compact('constraint')) ?>
                </span>
                <? endif ?>
                
                <? if (ForumPerm::has('pdfexport', $seminar_id)) : ?>
                <?= Studip\LinkButton::create(_('Beitr�ge als PDF exportieren'), PluginEngine::getLink('coreforum/index/pdfexport/' . $topic_id), array('target' => '_blank')) ?>
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

<!-- Mail-Notifikationen verschicken (soweit am Ende der Seite wie m�glich!) -->
<? if ($flash['notify']) :
    ForumAbo::notify($flash['notify']);
endif ?>
