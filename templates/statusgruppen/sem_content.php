<div align="right" style="margin-right: 10px" nowrap>
    <br>
    <!-- edit options -->
    <?= $this->render_partial('statusgruppen/sem_edit_role.php') ?>
</div>
<form action="<?= URLHelper::getLink('') ?>" method="post">
<table cellspacing="0" cellpadding="2" border="0" width="100%">
    <tr>
        <td width="30%" valign="top">
            <!-- the persons who can be added to a role -->
            <?= $this->render_partial('statusgruppen/sem_available_users.php') ?>
        </td>
        <td width="70%" valign="top" style="padding-left: 20px">
            <!-- the roles -->
            <?= $this->render_partial('statusgruppen/sem_roles') ?>
        </td>
    </tr>
</table>
</form>

