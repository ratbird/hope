<? use Studip\Button; ?>
STUDIP.Forum = {};

STUDIP.Forum.pruefe_name = function(){
    var re_nachname = /^([a-zA-ZÄÖÜ][^0-9"´'`\/\\\(\)\[\]]+)$/;
    var checked = true;
    if (re_nachname.test(document.forumwrite.nobodysname.value) == false) {
        alert('<?= _('Bitte geben Sie Ihren tatsächlichen Namen an.') ?>');
        document.forumwrite.nobodysname.focus();
        checked = false;
    }
    if (document.forumwrite.nobodysname.value=="unbekannt") {
        alert('<?= _('Bitte geben Sie Ihren Namen an.') ?>');
        document.forumwrite.nobodysname.focus();
        checked = false;
    }
    return checked;
}

STUDIP.Forum.rate_template = function (id) {
    var html = '<form method="post" action="<?= URLHelper::getLink('#anker', compact('view', 'open', 'flatviewstartposting')) ?>">\
    <?= CSRFProtection::tokenTag() ?>\
    <div style="text-align:center">\
        <?= _('Schulnote')?>\
        <br>\
        <span style="color:#009900;font-weight:bold;">1</span>\
    <?php foreach(range(1,5) as $r) :?>
        <input type="radio" name="rate[' + id + ']" value="<?=$r?>">\
    <?php endforeach?>
        <span style="color:#990000;font-weight:bold;">5</span>\
        <br>\
        <?= Button::create(_("Bewerten"), "sidebar") ?> \
    </div>\
    </form>';
    STUDIP.Dialogbox.openBox('Rating_for_<?= $open ?>', '<?= _('Bewertung des Beitrags') ?>', html, 'center');
}
