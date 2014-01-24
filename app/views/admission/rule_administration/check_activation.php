<?php
    use Studip\Button, Studip\LinkButton;
?>
<form class="studip_form" id="rule_activation_form" action="<?= $controller->url_for('admission/ruleadministration/activate', $type) ?>" method="post">
    <div id="enabling">
        <label class="caption">
            <input type="checkbox" name="enabled" onclick="STUDIP.Admission.checkRuleActivation('rule_activation_form')"<?= $ruleTypes[$type]['active'] ? ' checked="checked"' : '' ?>/>&nbsp;<?= _('Regel ist aktiv') ?>
        </label>
    </div>
    <br/>
    <div id="activation">
        <label for="activated" class="caption">
            <?= _('Regel ist verf�gbar') ?>
        </label>
        <input type="radio" name="activated" value="studip" onclick="STUDIP.Admission.checkRuleActivation('rule_activation_form')"<?= $globally ? ' checked="checked"' : '' ?>/><?= _('systemweit') ?>
        <br/>
        <input type="radio" name="activated" value="inst" onclick="STUDIP.Admission.checkRuleActivation('rule_activation_form')"<?= $atInst ? ' checked="checked"' : '' ?>/><?=  _('an ausgew�hlten Einrichtungen') ?>
    </div>
    <br/>
    <div id="institutes_activation"<?= $globally ? ' style="display:none"' : '' ?>>
        <ul>
        <?php foreach (Institute::findBySQL("`fakultaets_id`=`Institut_id`") as $fak) { ?>
            <li id="<?= $fak->Institut_id ?>">
                <input type="checkbox" name="institutes[]" value="<?= $fak->Institut_id ?>"<?= $activated[$fak->Institut_id] ? ' checked="checked"' : ''?>/>
                <a href=""><?= htmlReady($fak->name) ?></a>
                <?php if ($fak->sub_institutes) { ?>
                <ul>
                    <?php
                    foreach ($fak->sub_institutes as $inst) {
                        if ($inst->Institut_id != $fak->Institut_id) {
                    ?>
                    <li id="<?= $inst->Institut_id ?>">
                        <input type="checkbox" name="institutes[]" value="<?= $inst->Institut_id ?>"<?= $activated[$inst->Institut_id] ? ' checked="checked"' : ''?>/>
                        <a href=""><?= htmlReady($inst->name) ?></a>
                    </li>
                    <?php
                        }
                    }
                    ?>
                </ul>
                <?php } ?>
            </li>
        <?php } ?>
        </ul>
        <script type="text/javascript">
            //<!--
            $(function() {
                $('#institutes_activation').bind('loaded.jstree', function (event, data) {
                    // Show checked checkboxes.
                    var checkedItems = $('#institutes_activation').find('.jstree-checked');
                    checkedItems.removeClass('jstree-unchecked');
                    // Open parent nodes of checked nodes.
                    checkedItems.parents().each(function () { data.inst.open_node(this, false, true); });
                }).bind('select_node.jstree', function(event, data) {
                    return data.inst.toggle_node(data.rslt.obj);
                }).jstree({
                    'core': {
                        'animation': 100,
                        'open_parents': true
                    },
                    'checkbox': {
                        'real_checkboxes': true,
                        'selected_parent_open': true,
                        'override_ui': false
                    },
                    'themes': {
                        'icons': false
                    },
                    'plugins': [ 'html_data', 'themes', 'checkbox', 'ui' ]
                });
            });
            //-->
        </script>
    </div>
    <br/>
    <div class="submit_wrapper">
        <?= CSRFProtection::tokenTag() ?>
        <?= Button::createAccept(_('Speichern'), 'submit') ?>
        <?= LinkButton::createCancel(_('Abbrechen'), '', array('rel' => 'close')) ?>
    </div>
</form>