<?
# Lifter010: TODO
?>
<? if (isset($flash['error'])): ?>
    <?= MessageBox::error($flash['error'], $flash['error_detail']) ?>
<? elseif (isset($flash['message'])): ?>
    <?= MessageBox::info($flash['message']) ?>
<? elseif (isset($flash['success'])): ?>
    <?= MessageBox::success($flash['success'], $flash['success_detail']) ?>
<? elseif (isset($flash['delete'])): ?>
    <?= createQuestion(sprintf(_('Wollen Sie das Datenfeld "%s" wirklich löschen? Bedenken Sie bitte, dass noch Einträge dazu existieren können'), $flash['delete']['name']), array('delete' => 1), array('back' => 1), $controller->url_for('admin/datafields/delete'.'/'.$flash['delete']['datafield_id'])); ?>
<? endif; ?>

<!-- Alle Datenfelder  -->
<table class="collapsable default" cellspacing="0" cellpadding="2">
<caption>
    <?= _('Verwaltung von generischen Datenfeldern') ?>
</caption>
<? foreach ($datafields_list as $key => $data): ?>
    <tbody class="<?= ((!is_null($current_class) && $current_class == $key) || !is_null($class_filter)) ? '': 'collapsed' ?> <? if (empty($datafields_list[$key])): ?>empty<? endif ?>">
        <tr class="table_header header-row">
            <td class="toggle-indicator" colspan="11">
            <? if (empty($datafields_list[$key])): ?>
                <?= sprintf(_('Datenfelder für %s'), $allclasses[$key]) ?>
            <? else: ?>
                <a name="<?= $key ?>" class="toggler" href="<?= $controller->url_for('admin/datafields/index/'.$key) ?>">
                    <?= sprintf(_('Datenfelder für %s'), $allclasses[$key]) ?>
                </a>
            <? endif; ?>
            </td>
        </tr>
        <tr class="table_footer" style="text-align: center;">
            <th style="text-align: left;"><?=_("Name")?></th>
            <th><?=_("Feldtyp")?></th>
            <th>
            <? if ($key == 'sem'): ?>
                <?= _('Veranstaltungskategorie') ?>
            <? elseif ($key == 'inst'): ?>
                <?= _('Einrichtungstyp') ?>
            <? else: ?>
                <?= _('Nutzerstatus') ?>
            <? endif; ?>
            </th>
            <th><?= _('benötigter Status') ?></th>
            <th><?= _('Sichtbarkeit') ?></th>
            <th><?= (in_array($key, array('sem'))? _('Pflichtfeld'):'') ?></th>
            <th><?= (in_array($key, array('sem'))? _('Beschreibung'):'') ?></th>
            <th><?= (in_array($key, array('user'))? _('Anmelderegel'):'') ?></th>
            <th><?= _('Reihenfolge') ?></th>
            <th><?= _('Einträge') ?></th>
            <th style="text-align: right;"><?= _('Aktionen') ?></th>
        </tr>
    <? foreach ($data as $input => $val): ?>
        <tr class="<?= TextHelper::cycle('hover_odd', 'hover_even') ?>">
            <td>
                <a name="item_<?= $val->getID() ?>"></a>
                <?= htmlReady($val->getName()) ?>
            </td>
            <td>
            <? if (in_array($val->getType(), array('selectbox', 'selectboxmultiple', 'radio', 'combo'))): ?>
                <a class="datafield_param_link" href="<?=$controller->url_for('admin/datafields/index/'. $current_class .'?edit_id='. $val->getID())?>">
                    <?= Assets::img('icons/16/blue/edit.png', array('class'=> 'text-top', 'title' => 'Einträge bearbeiten')) ?>
                </a>
            <? endif; ?>
             <span><?= htmlReady($val->getType()) ?></span>
            <? if (in_array($val->getType(), array('selectbox', 'selectboxmultiple','radio', 'combo'))): ?>
                   <?= $this->render_partial("admin/datafields/_param", array('datafield_id' => $val->getID(), 'typeparam' => $val->getTypeparam(), 'hidden' => $edit_id!=$val->getID() )) ?>
            <? endif; ?>
            </td>
            <td>
            <? if ($key == 'sem'): ?>
                <?= $val->getObjectClass() != null ? htmlReady($GLOBALS['SEM_CLASS'][$val->getObjectClass()]['name']) : _('alle')?>
            <? elseif ($key == 'inst'): ?>
                <?=  $val->getObjectClass() != null ? htmlReady($GLOBALS['INST_TYPE'][$val->getObjectClass()]['name']) : _('alle')?>
            <? else: ?>
                <?= $val->getObjectClass() != null ? DataFieldStructure::getReadableUserClass($val->getObjectClass()) : _('alle')?>
            <? endif; ?>
            </td>
            <td><?= $val->getEditPerms() ?></td>
            <td><?= $val->getViewPerms() ?></td>
            <td>
             <? if (in_array($key, array('sem'))): ?>
              <?= Assets::img('icons/16/grey/'.($val->getIsRequired()?'accept.png':'decline.png'))?>
             <? endif; ?>
            </td>
             <td>
             <? if (in_array($key, array('sem'))): ?>
              <?= Assets::img('icons/16/grey/'.(trim($val->getDescription())?'accept.png':'decline.png'))?>
             <? endif; ?>
            </td>
            <td>
            <? if (in_array($key, array('user'))): ?>
              <?= Assets::img('icons/16/grey/'.($val->getIsUserFilter()?'accept.png':'decline.png'))?>
             <? endif; ?>
            </td>
            <td><?= $val->getPriority() ?></td>
            <td><?= $val->getCachedNumEntries() ?></td>
            <td style="text-align: right;">
                <a class="load-in-new-row" href="<?=$controller->url_for('admin/datafields/edit/'.$val->getID())?>">
                    <?= Assets::img('icons/16/blue/edit.png', array('title' => 'Datenfeld ändern')) ?>
                </a>
                <a href="<?=$controller->url_for('admin/datafields/delete/'.$val->getID())?>">
                    <?= Assets::img('icons/16/blue/trash.png', array('title' => 'Datenfeld löschen')) ?>
                </a>
            </td>
        </tr>
    <? endforeach; ?>
    </tbody>
<? endforeach; ?>
</table>

<script type="text/javascript">
jQuery(function ($) {
    jQuery.fn.identify = function(prefix) {
        if (typeof jQuery.identify_count == 'undefined') {
            jQuery.identify_count = 0;
        }
        return this.each(function() {
            if($(this).attr('id')) return;
            do {
                jQuery.identify_count++;
                var id = prefix + '_' + jQuery.identify_count;
            } while($('#' + id).length > 0);
            $(this).attr('id', id);
        });
    };

    var preview_callbacks = {
            combo: function (items) {
                var result = $('<div/>'),
                    select = $('<select/>');
                result.append('<input type="radio" name="preview"/>');
                $.map(items, function (value, index) {
                    $('<option/>')
                        .text(value.label)
                        .val(value.value)
                        .appendTo(select);
                });
                result.append(select);
                result.append('<input type="radio" name="preview"/>');
                result.append('<input type="text" name="preview"/>');
                return result.children();
            },
            radio: function (items) {
                var result = $('<div/>');
                $.map(items, function (value, index) {
                    var radio = $('<input type="radio" name="preview" />').val(value.value).identify('preview'),
                        label = $('<label/>').attr('for', radio.attr('id')).text(value.label);
                    result.append(radio).append(label);
                });
                return result.children();
            },
            selectbox: function (items) {
                var select = $('<select/>');
                $.map(items, function (value) {
                    $('<option/>')
                        .text(value.label)
                        .val(value.value)
                        .appendTo(select);
                });
                return select;
            }
        };

    $('a.datafield_param_link, .datafield_param a.cancel').click(function (event) {
        $(this).closest('td').children(':not(span)').toggle();
        if ($(this).is('.cancel') && !$(this).is(':visible')) {
            var textarea = $(this).closest('form').find('textarea');
            textarea.val(textarea.attr('data-dev'));
        }
        event.preventDefault();
    });
    $('.datafield_param input[name=preview]').click(function (event) {
        var $select = $('<select />'),
            cancel_button = $(this).closest('td').find('.datafield_param_link').clone().addClass('cancel-preview').show(),
            input = $(this).closest('td').find('.datafield_param textarea').val(),
            type = $(this).closest('td').find('span').text(),
            items = [],
            element, elements;

        elements = $.map(input.split("\n"), function (value, index) {
            if (!$.trim(value).length) {
                return;
            }
            var parts = $.map(value.split("=>"), $.trim),
                value = parts.pop();
            return {
                label: value,
                value: parts.length ? parts.pop() : value
            };
        });
        element = preview_callbacks[type](elements)
                    .wrapAll('<div class="preview" />').parent();

        $(this).hide().next().hide().after(cancel_button);
        $(this).closest('form').find('textarea').hide().after(element);

        event.preventDefault();
    });

    $(document).on('click', '.datafield_param .cancel-preview', function (event) {
        $(this).prevAll().show();
        $(this).siblings('.preview').add(this).remove();

        event.preventDefault();
    });
});
</script>
<?
$sidebar = Sidebar::Get();
$sidebar->setImage('sidebar/admin-sidebar.png');
$sidebar->setTitle(_('Datenfelder'));

$actions = new ActionsWidget();
$actions->addLink(_('Neues Datenfeld anlegen'),$controller->url_for('admin/datafields/new/'.$class_filter), 'icons/16/blue/add.png');
$sidebar->addWidget($actions);


$widget = new SidebarWidget();
$widget->setTitle(_('Filter'));
$widget->addElement(new WidgetElement($this->render_partial('admin/datafields/class_filter', compact('allclasses', 'class_filter'))));
$sidebar->addWidget($widget);
