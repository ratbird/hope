<? chat_get_javascript(); ?>

<p><?= _('Hier sehen Sie eine Übersicht aller aktiven Chaträume.') ?></p>

<table class="default">
    <tr>
        <td class="topic" >
            <?= _('Allgemeiner Chatraum') ?></b>
        </td>
    </tr>
    <tr>
        <td id="chat_studip">
            <? print_chat_info(array('studip')); ?>
        </td>
    </tr>
    <tr>
        <td class="blank">&nbsp;</td>
    </tr>
    <tr>
        <td class="topic" >
            <?= _('Persönlicher Chatraum') ?>
        </td>
    </tr>
    <tr>
        <td id="chat_own">
            <? print_chat_info(array($GLOBALS['user']->id)); ?>
        </td>
    </tr>
    <tr>
        <td class="blank">&nbsp;</td>
    </tr>

<? if (!empty($active_user_chats) || !empty($hidden_user_chats)): ?>
    <? SkipLinks::addIndex(_('Chaträume anderer NutzerInnen'), 'chat_user'); ?>
    <tr>
        <td class="topic" >
            <?= _('Chaträume anderer NutzerInnen') ?>
        </td>
    </tr>
    <tr>
        <td id="chat_user">
            <? print_chat_info($active_user_chats); ?>
        <? if (count($hidden_user_chats) == 1): ?>
            <?= _('+1 weiterer, unsichtbarer Chatraum.') ?>
        <? elseif (count($hidden_user_chats) > 0): ?>
            <?= sprintf(_('+%s weitere, unsichtbare Chaträume.'), count($hidden_user_chats)) ?>
        <? endif; ?>
        </td>
    </tr>
    <tr>
        <td class="blank">&nbsp;</td>
    </tr>
<? endif; ?>

<? if (!empty($active_sem_chats)): ?>
    <? SkipLinks::addIndex(_('Chaträume für Veranstaltungen'), 'chat_sem'); ?>
    <tr>
        <td class="topic" >
            <?= _('Chaträume für Veranstaltungen') ?>
        </td>
    </tr>
    <tr>
        <td id="chat_sem">
            <? print_chat_info($active_sem_chats); ?>
        </td>
    </tr>
    <tr>
        <td class="blank">&nbsp;</td>
    </tr>
<? endif; ?>

<? if (!empty($active_inst_chats)): ?>
    <? SkipLinks::addIndex(_('Chaträume für Einrichtungen'), 'chat_inst'); ?>
    <tr>
        <td class="topic" >
            <?= _('Chaträume für Einrichtungen') ?>
        </td>
    </tr>
    <tr>
        <td id="chat_inst">
            <? print_chat_info($active_inst_chats); ?>
        </td>
    </tr>
<? endif; ?>
</table>
