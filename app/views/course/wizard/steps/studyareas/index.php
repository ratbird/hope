<h1><?= _('Studienbereiche') ?></h1>
<div id="assigned">
    <h2>
        <?= _('Bereits zugewiesen') ?>
        <span class="required">*</span>
    </h2>
    <ul class="css-tree">
        <li class="sem-tree-assigned-root keep-node" data-id="root"<?=
                $assigned ? ' class="hidden-no-js hidden-no-js"' : '' ?>>
            <?= $GLOBALS['UNI_NAME'] ?>
            <ul>
            <?php foreach ($assigned as $element) : ?>
            <?= $element->name ?>
            <?= $this->render_partial('studyareas/_assigned_node',
                    array('element' => $element, 'studyareas' => $values['studyareas'])) ?>
            <?php endforeach ?>
            </ul>
        </li>
    </ul>
</div>
<div id="studyareas" data-ajax-url="<?= $ajax_url ?>"
    data-forward-url="<?= $no_js_url ?>" data-no-search-result="<?=_('Es wurde kein Suchergebnis gefunden.') ?>">
    <h2><?= _('Alle Studienbereiche') ?></h2>
    <div>
        <input type="text" size="40" maxlength="255" name="search" id="sem-tree-search"
               value="<?= $values['searchterm'] ?>"/>
        <span id="sem-tree-search-start">
            <?= Assets::input('icons/blue/search.svg',
                array('name' => 'start_search',
                    'onclick' => "return STUDIP.CourseWizard.searchTree()",
                    'class' => $search_result ? 'hidden-no-js' : '')) ?>
        </span>
        <span id="sem-tree-search-reset" class="hidden-js">
            <?= Assets::input('icons/blue/refresh.svg',
                array('name' => 'reset_search',
                    'onclick' => "return STUDIP.CourseWizard.resetSearch()",
                    'class' => $search_result ? '' : ' hidden-no-js')) ?>
        </span>
    </div>
    <div id="sem-tree-assign-all" class="hidden-js hidden-no-js">
        <a href="" onclick="return STUDIP.CourseWizard.assignAllNodes()">
            <?= Assets::img('icons/yellow/arr_2left.svg') ?>
            <?= _('Alle Suchergebnisse zuweisen') ?>
        </a>
    </div>
    <ul class="collapsable css-tree">
        <li class="sem-tree-root tree-loaded keep-node">
            <input type="checkbox" id="root" checked="checked"/>
            <label for="root">
                <?= $GLOBALS['UNI_NAME'] ?>
            </label>
            <ul>
            <?php foreach ($tree as $node) : ?>
            <?= $this->render_partial('studyareas/_node',
                    array('node' => $node, 'stepnumber' => $stepnumber,
                        'temp_id' => $temp_id, 'values' => $values,
                        'open_nodes' => $open_nodes ?: array(),
                        'search_result' => $search_result ?: array())) ?>
            <?php endforeach ?>
            </ul>
        </li>
    </ul>
</div>
<?php if ($values['open_node']) : ?>
<input type="hidden" name="open_node" value="<?= $values['open_node'] ?>"/>
<?php endif ?>
<?php if ($values['searchterm']) : ?>
<input type="hidden" name="searchterm" value="<?= $values['searchterm'] ?>"/>
<?php endif ?>
<script type="text/javascript" language="JavaScript">
//<!--
$(function() {
    var element = $('#sem-tree-search');
    element.on('keypress', function(e) {
        if (e.keyCode == 13) {
            if (element.val() != '') {
                return STUDIP.CourseWizard.searchTree();
            } else {
                return STUDIP.CourseWizard.resetSearch();
            }
        }
    });
});
//-->
</script>