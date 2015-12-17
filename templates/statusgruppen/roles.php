<?
# Lifter010: TODO
?>
<?

if (!$indent) $indent = 0;
$pos = 1;
if (!isset($all_roles)) $all_roles = $roles;
if (is_array($roles)) foreach ($roles as $id => $role) :
?>
<tr>

    <? for($i = 0; $i < $indent - 1; $i++) : ?>
    <td class="blank" width="1%" align="right" nowrap><?= ($followers[$i+1]) ? Assets::img('forumstrich.gif') : '' ?></td>
    <? endfor; ?>

    <? if ($indent > 0) : ?>
    <td class="blank" width="1%" align="right" nowrap><?
        if (sizeof($roles) == $pos) :
            echo Assets::img('forumstrich2.gif');
        elseif ($pos < sizeof($roles)) :
            echo Assets::img('forumstrich3.gif');
        endif;
    ?></td>
    <? endif; ?>

    <td class="printhead" valign="bottom" colspan="<?= 19-$indent ?>" height="22" nowrap style="padding-left: 3px" width="<?= 99-$indent ?>%">
        <a name="<?= $id ?>">
        <? if ($open == $id) : ?>
        <a class="tree" href="<?= URLHelper::getLink('?list=true#'. $id) ?>"><?= Icon::create('arr_1down', 'clickable')->asImg(['class' => 'text-top']) ?></a>
        <? else : ?>
        <a class="tree" href="<?= URLHelper::getLink('?role_id='. $id .'#'. $id) ?>"><?= Icon::create('arr_1right', 'clickable')->asImg(['class' => 'text-top']); ?></a>
        <? endif; ?>

        <? if ($move) : ?>
        <a href="#"><?= Icon::create('arr_2right', 'sort')->asImg() ?></a>
        <? endif; ?>

        <? if ($sort) :
            if ($pos > 1) : ?>
        <a href="<?= URLHelper::getLink('?cmd=moveUp&view=sort&role_id='. $id) ?>"><?= Icon::create('arr_2up', 'sort')->asImg(); ?></a>
        <? endif; if ($pos < sizeof($roles)) : ?>
        <a href="<?= URLHelper::getLink('?cmd=moveDown&view=sort&role_id='. $id) ?>"><?= Icon::create('arr_2down', 'sort')->asImg(); ?></a>
        <? endif;
        endif;
        ?>
            <? if ($open == $id) : ?>
        <a class="tree" href="<?= URLHelper::getLink('?list=true#'. $id) ?>">
        <? else : ?>
        <a class="tree" href="<?= URLHelper::getLink('?role_id='. $id .'#'. $id) ?>">
        <? endif; ?>
            <?= htmlReady($role['role']->getName()) ?>
        </a>

    </td>
    <td width="1%" class="printhead" align="right" valign="bottom" nowrap>
        <? if ($role['role']->hasFolder()) :
            echo Icon::create('files', 'clickable')->asImg();
        endif; ?>

        &nbsp;
    </td>
</tr>
<?

    // if the current $role has followers, we need to display a straight line later
    $new_followers = $followers;
    $new_followers[$indent] = (sizeof($roles) > $pos);

    // if we have opened an entry, we show edit fields
    if ($open == $id) :
        $partial = LockRules::Check($range_id, 'groups') ?
                   'statusgruppen/role_administration_locked.php' :
                   'statusgruppen/role_administration.php';
        echo $this->render_partial($partial,
            array('indent' => $indent, 'followers' => $new_followers,
                'persons' => getPersonsForRole($id), 'role_id' => $id, 'editRole' => ($editRole == $id), 'role' => $role['role'],
                'role_size' => sizeof($roles), 'role_pos' => $pos, 'has_child' => ($role['child']) ? true : false, 'all_roles' => $all_roles)
        );
    endif;

    // if we have childs, we display them with the same template and some indention
    if($role['child']) {
        echo $this->render_partial('statusgruppen/roles.php',
            array('indent' => $indent + 1, 'roles' => $role['child'], 'followers' => $new_followers, 'all_roles' => $all_roles));
    }

    $pos++;
endforeach;
