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

<h3><?= _('Verwaltung von generischen Datenfeldern') ?></h3>

<!-- Datenfelder für Veranstaltungen -->
<table class="collapsable default" cellspacing="0" cellpadding="2">
<? foreach ($datafields_list as $key => $data): ?>
    <tbody class="<?= ((!is_null($current_class) && $current_class == $key) || !is_null($class_filter)) ? '': 'collapsed' ?> <? if (empty($datafields_list[$key])): ?>empty<? endif ?>">
        <tr class="steel header-row">
            <td class="toggle-indicator" colspan="8">
            <? if (empty($datafields_list[$key])): ?>
                <?= sprintf(_('Datenfelder für %s'), $allclasses[$key]) ?>
            <? else: ?>
                <a name="<?= $key ?>" class="toggler" href="<?= $controller->url_for('admin/datafields/index/'.$key) ?>">
                    <?= sprintf(_('Datenfelder für %s'), $allclasses[$key]) ?>
                </a>
            <? endif; ?>
            </td>
        </tr>
        <tr class="steel2" style="text-align: center;">
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
            <th><?= _('Reihenfolge') ?></th>
            <th><?= _('Einträge') ?></th>
            <th style="text-align: right;"><?= _('Aktionen') ?></th>
        </tr>
    <? foreach ($data as $input => $val): ?>
        <tr class="<?= TextHelper::cycle('cycle_odd', 'cycle_even') ?>">
            <td>
                <a name="item_<?= $val->getID() ?>"></a>
                <?= htmlReady($val->getName()) ?>
            </td>
            <td>
            <? if (in_array($val->getType(), array('selectbox', 'radio', 'combo'))): ?>
                <a class="datafield_param_link" href="<?=$controller->url_for('admin/datafields/_param/'.$val->getID())?>">
                    <?= Assets::img('icons/16/blue/edit.png', array('class'=> 'text-top', 'title' => 'Einträge bearbeiten')) ?>
                </a>
            <? endif; ?>
                <span><?= htmlReady($val->getType()) ?></span>
            <? if (in_array($val->getType(), array('selectbox', 'radio', 'combo'))): ?>
                <? if (true): ?>
                    <?= $this->render_partial("admin/datafields/_param", array('datafield_id' => $val->getID(), 'typeparam' => $val->getTypeparam(), 'hidden' => true))?>
                <? endif;?>
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
            <td><?= $val->getPriority() ?></td>
            <td><?= $val->getCachedNumEntries() ?></td>
            <td style="text-align: right;">
                <a class="load-in-new-row" href="<?=$controller->url_for('admin/datafields/edit/'.$val->getID())?>">
                    <?= Assets::img('icons/16/blue/edit.png', array('title' => 'Datenfeld ändern')) ?>
                </a>
                <a href="<?=$controller->url_for('admin/datafields/delete/'.$val->getID()).'/'.$val->getName()?>">
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
            textarea.val(textarea.attr('defaultValue'));
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

    $('.datafield_param .cancel-preview').live('click', function (event) {
        $(this).prevAll().show();
        $(this).siblings('.preview').add(this).remove();

        event.preventDefault();
    });
});
</script>
<?
$infobox = array(
    'picture' => 'infobox/administration.jpg',
    'content' => array(
    array(
        'kategorie' => _('Aktionen:'),
        'eintrag'   => array(
            array(
                'icon' => 'icons/16/black/arr_2right.png',
                'text' => $this->render_partial('admin/datafields/class_filter', compact('allclasses', 'class_filter'))
            ),
            array(
                'text' => '<a href="'.$controller->url_for('admin/datafields/new/'.$class_filter).'">'._('Neues Datenfeld anlegen').'</a>',
                'icon' => 'icons/16/black/plus.png',
            )
        )
    ),
    array(
        'kategorie' => _('Hinweise:'),
        'eintrag'   => array(
            array(
                'text' => _('Um neue Datenfelder anlegen zu können, wählen Sie bitte vorher im Anzeigefilter einen Datenfeldtyp aus.'),
                'icon' => 'icons/16/black/info.png'
            ),
            array(
                'text' => _('Die Metadaten von Veranstaltungen, Einrichtungen und Personen können um freie Datenfelder erweitert werden. Dabei kann der Name der Datenfelder festgelegt werden, sowie für welche Nutzer diese Felder sichtbar und bearbeitbar sind.'),
                'icon' => 'icons/16/black/info.png'
            )
        )
    )
));

