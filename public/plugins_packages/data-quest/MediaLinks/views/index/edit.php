<div id="MediaLinks">
    <form action="" method="POST">
        Name:<br/>
        <input type="text" name="name" value="<?= htmlReady($link->name) ?>"/><br/>
        Link:<br/>
        <input type="text" name="url" value="<?= htmlReady($link->url) ?>"/><br/>
        Beschreibung:<br/>
        <textarea name="description"><?= htmlReady($link->description) ?></textarea><br/>
        <input type="hidden" name="id" value="<?= htmlReady($link->id) ?>"/>
        <input type="submit" name="edit" value="Bearbeiten" />
    </form>
</div>