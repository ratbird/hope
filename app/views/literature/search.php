<? use Studip\Button, Studip\LinkButton; ?>
<? if ($msg) : ?>
    <table width="99%" border="0" cellpadding="2" cellspacing="0">
        <?= parse_msg($msg, "§", "blank", 1, false) ?>
    </table>
<? endif ?>
<? $attributes['search_plugin'] = $attributes['text']; ?>
<? $attributes['search_plugin']['onChange'] = 'document.' . $search->outer_form->form_name . '.submit()'; ?>
<?= $search->outer_form->getFormStart(URLHelper::getLink('dispatch.php/literature/search?return_range=' . $return_range), array('class' => 'studip-form')); ?>


    <section>
        <?= $search->outer_form->getFormFieldCaption('search_plugin', array('info' => true)); ?>
        <?= $search->outer_form->getFormField('search_plugin', $attributes['search_plugin']); ?>
        <footer>
            <?= $search->outer_form->getFormButton('change'); ?>
        </footer>
    </section>

    <h2><?= _("Ausgewählter Katalog:") ?></h2>
    <p><?= $search->search_plugin->description ?></p>

    <section>
        <? for ($i = 0; $i < $search->term_count; ++$i) : ?>
            <? if ($i > 0) : ?>
                <section>
                    <?= $search->inner_form->getFormFieldCaption("search_operator_" . $i, array('info' => true)) ?>
                    <?= $search->inner_form->getFormField("search_operator_" . $i, $attributes['radio']); ?>
                </section>
            <? endif ?>
            <section>
                <?= $search->inner_form->getFormFieldCaption("search_field_" . $i, array('info' => true)); ?>
                <?= $search->inner_form->getFormField("search_field_" . $i, $attributes['text']); ?>
            </section>
            <section>
                <?= $search->inner_form->getFormFieldCaption("search_truncate_" . $i, array('info' => true)); ?>
                <?= $search->inner_form->getFormField("search_truncate_" . $i, $attributes['text']); ?>
            </section>
            <section>
                <?= $search->inner_form->getFormFieldCaption("search_term_" . $i, array('info' => true)); ?>
                <?= $search->inner_form->getFormField("search_term_" . $i, $attributes['text']); ?>
            </section>
        <? endfor ?>
        <footer>
            <?= $search->outer_form->getFormButton('search', $attributes['button']); ?>

            <?= $search->outer_form->getFormButton('reset', $attributes['button']); ?>

            <?= $search->outer_form->getFormButton('search_add'); ?>
            <? if ($search->term_count > 1): ?>
                <?= $search->outer_form->getFormButton('search_sub'); ?>
            <? endif ?>
        </footer>

    </section>

<?= $search->outer_form->getFormEnd(); ?>

<? if (($num_hits = $search->getNumHits())) : ?>
    <? if ($search->start_result < 1 || $search->start_result > $num_hits) : ?>
        <? $search->start_result = 1; ?>
    <? endif ?>
    <? $end_result = (($search->start_result + 5 > $num_hits) ? $num_hits : $search->start_result + 4); ?>


    <h2><?= sprintf(_('%s Treffer in Ihrem Suchergebnis.'), $num_hits); ?></h2>
    <p style="text-align: right">
        <strong><?= _('Anzeige:') ?></strong>
        <? if ($search->start_result > 1) : ?>
            <a href="<?= URLHelper::getLink('', array('change_start_result' => ($search->start_result - 5))) ?>">
                <?= Assets::img('icons/16/blue/arr_2left.png', array('hspace' => 3)); ?>
            </a>
        <? endif ?>
        <?= $search->start_result . " - " . $end_result; ?>
        <? if ($search->start_result + 4 < $num_hits) : ?>
            <a href="<?= URLHelper::getLink('', array('change_start_result' => ($search->start_result + 5))) ?>">
                <?= Assets::img('icons/16/blue/arr_2right.png', array('hspace' => 3)); ?>
            </a>
        <? endif ?>
    </p>
    <? for ($i = $search->start_result; $i <= $end_result; ++$i) : ?>
        <? $element = $search->getSearchResult($i); ?>
        <? if ($element) : ?>
            <section class="contentbox">
                <header>
                    <h1>
                        <? $link = URLHelper::getLink('', array('cmd'        => 'add_to_clipboard',
                                                                'catalog_id' => $element->getValue("catalog_id")
                        )); ?>
                        <? if ($clipboard->isInClipboard($element->getValue("catalog_id"))) : ?>
                            <? $addon = tooltipIcon(_('Dieser Eintrag ist bereits in Ihrer Merkliste'), true); ?>
                        <? else : ?>
                            <? $addon = "<a href=\"$link\">"; ?>
                            <? $addon .= Assets::img('icons/16/blue/exclaim.png', tooltip2(_('Eintrag in Merkliste aufnehmen'))); ?>
                            <? $addon .= "</a>"; ?>
                        <? endif ?>
                        <?= htmlReady(my_substr($element->getShortName(), 0, 85)) ?>
                    </h1>
                    <span class="actions">
                        <?= $addon ?>
                    </span>
                </header>
                <section>
                    <dl>
                        <? if ($title = $element->getValue('dc_title')) : ?>
                            <dt><?= _('Titel:') ?></dt>
                            <dd><?= htmlReady($title, true, true) ?></dd>
                        <? endif ?>

                        <? if ($authors = $element->getValue('authors')) : ?>
                            <dt><?= _('Autor (weitere Beteiligte):') ?></dt>
                            <dd><?= htmlReady($authors, true, true) ?></dd>
                        <? endif ?>

                        <? if ($published = $element->getValue('published')): ?>
                            <dt><?= _('Erschienen:') ?></dt>
                            <dd> <?= htmlReady($published, true, true) ?></dd>
                        <? endif ?>

                        <? if ($identifier = $element->getValue('dc_identifier')) : ?>
                            <dt><?= _('Identifikation:') ?></dt>
                            <dd><?= htmlReady($identifier, true, true) ?></dd>
                        <? endif ?>

                        <? if ($subject = $element->getValue('dc_subject')) : ?>
                            <dt><?= _('Schlagwörter:') ?></dt>
                            <dd><?= htmlReady($subject, true, true) ?></dd>
                        <? endif ?>

                        <? if ($element->getValue("lit_plugin") != 'Studip') : ?>
                            <p><strong><?= _('Externer Link:') ?></strong>
                                <? if (($link = $element->getValue('external_link'))) : ?>
                                    <?= formatReady(' [' . $element->getValue('lit_plugin_display_name') . ']' . $link); ?>
                                <? else : ?>
                                    <?= _('(Kein Link zum Katalog vorhanden.)'); ?>
                                <? endif ?>
                            </p>
                        <? endif ?>
                    </dl>
                </section>
                <footer>
                    <? $link = URLHelper::getURL('dispatch.php/literature/edit_element.php', array('_catalog_id' => $element->getValue('catalog_id'))); ?>
                    <?= LinkButton::create(_('Details'), $link, array('data-dialog' => '')); ?>
                    <? $link = URLHelper::getURL('', array('cmd'        => 'add_to_clipboard',
                                                           'catalog_id' => $element->getValue('catalog_id')
                    )); ?>
                    <? if (!$clipboard->isInClipboard($element->getValue('catalog_id'))) : ?>
                        <?= LinkButton::create(_('In Merkliste'), $link); ?>
                    <? endif ?>
                </footer>
            </section>
        <? endif ?>
    <? endfor ?>
    <p style="text-align: right">
        <strong><?= _('Anzeige:') ?></strong>
        <? if ($search->start_result > 1) : ?>
            <a href="<?= URLHelper::getLink('', array('change_start_result' => ($search->start_result - 5))) ?>">
                <?= Assets::img('icons/16/blue/arr_2left.png', array('hspace' => 3)); ?>
            </a>
        <? endif ?>
        <?= $search->start_result . " - " . $end_result; ?>
        <? if ($search->start_result + 4 < $num_hits) : ?>
            <a href="<?= URLHelper::getLink('', array('change_start_result' => ($search->start_result + 5))) ?>">
                <?= Assets::img('icons/16/blue/arr_2right.png', array('hspace' => 3)); ?>
            </a>
        <? endif ?>
    </p>
<? endif ?>

<?php
$sidebar = Sidebar::get();
$sidebar->setImage('sidebar/literature-sidebar.png');
ob_start();
?>
<?= $clip_form->getFormStart(URLHelper::getLink('?_catalog_id=' . $catalog_id)); ?>
<?= $clip_form->getFormField('clip_content', array_merge(array('size' => $clipboard->getNumElements()), (array)$attributes['lit_select'])) ?>
<?= $clip_form->getFormField('clip_cmd', $attributes['lit_select']) ?>
    <div align="center">
        <?= $clip_form->getFormButton("clip_ok", array('style' => 'vertical-align:middle;margin:3px;')) ?>
    </div>
<?= $clip_form->getFormEnd(); ?>
<?
$content = ob_get_clean();
$widget = new SidebarWidget();
$widget->setTitle(_('Merkliste'));
$widget->addElement(new WidgetElement($content));
$sidebar->addWidget($widget);