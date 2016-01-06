/*jslint browser: true, white: true, undef: true, nomen: true, eqeqeq: true, plusplus: true, bitwise: true, newcap: true, immed: true, indent: 4, onevar: false */
/*global window, $, jQuery, _ */

/* ------------------------------------------------------------------------
 * Dateibereich
 * ------------------------------------------------------------------------ */

// hier ein paar "globale" Variablen, die nur in Funktionen des Filesystem-Namespace verwendet werden:
STUDIP.Filesystem = {
    hover_begin    : 0,             //erste Zeit, dass eine Datei �ber den Ordner ...hovered_folder bewegt wurde.
    hovered_folder : '',            //letzter Ordner, �ber den eine gezogene Datei bewegt wurde.
    movelock       : false,         //wenn auf true gesetzt, findet gerade eine Animation statt.
    sendstop       : false,         //wenn auf true gesetzt, wurde eine Datei in einen Ordner gedropped und die Seite l�dt sich gerade neu.
    getURL         : function (url) {
        return (url || document.URL).split("#", 1)[0];
    },
    /**
     * L�sst die gelben Pfeile verschwinden und ersetzt sie durch Anfassersymbole.
     * Wichtig f�r Javascript-Nichtjavascript Behandlung. Nutzer ohne Javascript
     * sehen nur die gelben Pfeile zum Sortieren.
     */
    unsetarrows     : function () {
        jQuery("span.move_arrows, span.updown_marker").hide();
        jQuery(".sortable").find(".handle").css("cursor", "move");
    }
};


/**
 * deklariert Ordner und Dateien als ziehbare Elemente bzw. macht sie sortierbar
 */
STUDIP.Filesystem.setdraggables = function () {
    jQuery("div.folder_container.sortable").each(function () {
        //wenn es einen Anfasser gibt, also wenn Nutzer verschieben darf
        jQuery(this).sortable({
            axis: "y",
            handle: ".handle",
            opacity: 0.6,
            revert: 300,
            scroll: true,
            update: function () {
                var id = this.getAttribute('id');
                var sorttype = (id.lastIndexOf('subfolders') !== -1 ? "folder" : "file");
                var md5_id = id.substr(id.lastIndexOf('_') + 1);
                var order = jQuery(this).sortable('serialize', {key: "order"}).split("&");
                order = jQuery.map(order, function (component) {
                    return component.substr(component.lastIndexOf('=') + 1);
                });
                var order_ids = jQuery.map(order, function (order_number) {
                    if (sorttype === "folder") {
                        // Unterordner:
                        return jQuery("#getmd5_fo" + md5_id + "_" + order_number).html();
                    } else {
                        // Dateien:
                        return jQuery("#getmd5_fi" + md5_id + "_"  + order_number).html();
                    }
                });
                jQuery.ajax({
                    url: STUDIP.Filesystem.getURL(),
                    data: {
                        sorttype: sorttype,
                        folder_sort: md5_id,
                        file_order: order_ids.join(",")
                    }
                });
            }
        });
    });
};

/**
 * deklariert Ordner als Objekte, in die Dateien gedropped werden k�nnen
 */
STUDIP.Filesystem.setdroppables = function () {
    jQuery("div.droppable").droppable({
        accept: '.draggable',
        hoverClass: 'hover',
        over: function () {
            var folder_md5_id = this.getAttribute('id');
            folder_md5_id = folder_md5_id.substr(folder_md5_id.lastIndexOf('_') + 1);
            STUDIP.Filesystem.openhoveredfolder(folder_md5_id);
        },
        drop: function (event, ui) {
            var id = ui.draggable.attr('id');
            var file_md5_id = id.substr(id.indexOf('_') + 1);
            file_md5_id = jQuery("#getmd5_fi" + file_md5_id).html();
            var folder_md5_id = jQuery(this).attr('id');
            folder_md5_id = folder_md5_id.substr(folder_md5_id.lastIndexOf('_') + 1);
            //alert("Drop "+file_md5_id+" on "+folder_md5_id);
            var adress = STUDIP.Filesystem.getURL();
            if ((event.keyCode === 17)  || (event.ctrlKey)) {
                jQuery.ajax({
                    url: adress,
                    data: {
                        copyintofolder: folder_md5_id,
                        copyfile: file_md5_id
                    },
                    success: function () {
                        window.location.href = adress + '&cmd=tree&open=' + folder_md5_id;
                    }
                });
            } else {
                jQuery.ajax({
                    url: adress,
                    data: {
                        moveintofolder: folder_md5_id,
                        movefile: file_md5_id
                    },
                    success: function () {
                        window.location.href = adress + '&cmd=tree&open=' + folder_md5_id;
                    }
                });
            }
            STUDIP.Filesystem.sendstop = true;
        }
    });
};

/**
 * �ffnet einen Dateiordner, wenn eine Datei lange genug dr�ber gehalten wird.
 */
STUDIP.Filesystem.openhoveredfolder = function (md5_id) {
    var zeit = new Date();
    if (md5_id === STUDIP.Filesystem.hovered_folder) {
        if (STUDIP.Filesystem.hover_begin < zeit.getTime() - 1000) {
            if (jQuery("#folder_" + md5_id + "_body").is(':hidden')) {
                STUDIP.Filesystem.changefolderbody(md5_id);
                STUDIP.Filesystem.hover_begin = zeit.getTime();
            }
        }
    } else {
        STUDIP.Filesystem.hovered_folder = md5_id;
        STUDIP.Filesystem.hover_begin = zeit.getTime();
    }
};

/**
 * �ffnet/schlie�t einen Dateiordner entweder per AJAX oder nur per Animation,
 * wenn Inhalt schon geladen wurde.
 */
STUDIP.Filesystem.changefolderbody = function (md5_id) {
    if (!STUDIP.Filesystem.movelock) {
        STUDIP.Filesystem.movelock = true;
        window.setTimeout("STUDIP.Filesystem.movelock = false;", 410);
        if (jQuery("#folder_" + md5_id + "_body").is(':visible')) {
            jQuery("#folder_" + md5_id + "_header").css('fontWeight', 'normal');
            jQuery("#folder_" + md5_id + "_arrow_img").attr('src', STUDIP.ASSETS_URL + "images/forumgrau2.png");
            jQuery("#folder_" + md5_id + "_arrow_img").attr('title', "Objekt aufklappen".toLocaleString());
            jQuery("#folder_" + md5_id + "_arrow_td").addClass('printhead2')
                .removeClass('printhead3');
            jQuery("#folder_" + md5_id + "_body").slideUp(400);
        } else {
            if (jQuery("#folder_" + md5_id + "_body").html() === "") {
                var adress = STUDIP.Filesystem.getURL(jQuery("#folder_" + md5_id + "_arrow_img").parent()[0].href);
                jQuery("#folder_" + md5_id + "_body").load(adress, {getfolderbody: md5_id}, function () {
                    jQuery("#folder_" + md5_id + "_header").css('fontWeight', 'bold');
                    jQuery("#folder_" + md5_id + "_arrow_img").attr('src', STUDIP.ASSETS_URL + "images/forumgraurunt2.png");
                    jQuery("#folder_" + md5_id + "_arrow_img").attr('title', "Objekt zuklappen".toLocaleString());                    
                    jQuery("#folder_" + md5_id + "_arrow_td").addClass('printhead3')
                        .removeClass('printhead2');
                    STUDIP.Filesystem.unsetarrows();
                    STUDIP.Filesystem.setdraggables();
                    STUDIP.Filesystem.setdroppables();
                    jQuery("#folder_" + md5_id + "_body").slideDown(400);
                });
            } else {
                jQuery("#folder_" + md5_id + "_header").css('fontWeight', 'bold');
                jQuery("#folder_" + md5_id + "_arrow_img").attr('src', STUDIP.ASSETS_URL + "images/forumgraurunt2.png");
                jQuery("#folder_" + md5_id + "_arrow_img").attr('title', "Objekt zuklappen".toLocaleString());
                jQuery("#folder_" + md5_id + "_arrow_td").addClass('printhead3')
                    .removeClass('printhead2');
                STUDIP.Filesystem.unsetarrows();
                STUDIP.Filesystem.setdraggables();
                STUDIP.Filesystem.setdroppables();
                jQuery("#folder_" + md5_id + "_body").slideDown(400);
            }
        }
    }
    return false;
};

/**
 * �ffnet/schlie�t eine Datei entweder per AJAX oder nur per Animation,
 * wenn Inhalt schon geladen wurde.
 */

STUDIP.Filesystem.changefilebody = function (md5_id) {
    if (!STUDIP.Filesystem.movelock) {
        STUDIP.Filesystem.movelock = true;
        window.setTimeout("STUDIP.Filesystem.movelock = false;", 410);

        if (jQuery("#file_" + md5_id + "_body").is(':visible')) {
            jQuery("#file_" + md5_id + "_body").slideUp(400);
            jQuery("#file_" + md5_id + "_header").css("fontWeight", 'normal');
            jQuery("#file_" + md5_id + "_arrow_td").addClass('printhead2')
                .removeClass('printhead3');
            jQuery("#file_" + md5_id + "_arrow_img").attr('src', STUDIP.ASSETS_URL + "images/forumgrau2.png");
            jQuery("#file_" + md5_id + "_arrow_img").attr('title', "Objekt aufklappen".toLocaleString());
        } else {
            if (jQuery("#file_" + md5_id + "_body").html() === "") {
                var adress = STUDIP.Filesystem.getURL(jQuery("#file_" + md5_id + "_arrow_img").parent()[0].href);
                jQuery("#file_" + md5_id + "_body").load(adress, {getfilebody: md5_id}, function () {
                    jQuery("#file_" + md5_id + "_header").css('fontWeight', 'bold');
                    jQuery("#file_" + md5_id + "_arrow_img").attr('src', STUDIP.ASSETS_URL + "images/forumgraurunt2.png");
                    jQuery("#file_" + md5_id + "_arrow_img").attr('title', "Objekt zuklappen".toLocaleString());
                    jQuery("#file_" + md5_id + "_arrow_td").addClass('printhead3')
                        .removeClass('printhead2');
                    jQuery("#file_" + md5_id + "_body").slideDown(400);
                });
            } else {
                //Falls der Dateik�rper schon geladen ist.
                jQuery("#file_" + md5_id + "_body_row").show();
                jQuery("#file_" + md5_id + "_header").css('fontWeight', 'bold');
                jQuery("#file_" + md5_id + "_arrow_td").addClass('printhead3')
                    .removeClass('printhead2');
                jQuery("#file_" + md5_id + "_arrow_img").attr('src', STUDIP.ASSETS_URL + "images/forumgraurunt2.png");
                jQuery("#file_" + md5_id + "_arrow_img").attr('title', "Objekt zuklappen".toLocaleString());
                jQuery("#file_" + md5_id + "_body").slideDown(400);
            }
        }
    }
    return false;
};

