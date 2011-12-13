<?php
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO

require_once("lib/wiki.inc.php");

class StmInstanceAssiVisualization{

    function showSelStmForm($form) {

        $styles = array(    'width'     => '100%',
//                          'border'        => '1',
                            'align'     => 'center'
        );

        $table = new Table($styles);

        echo $table->open(array('class' => 'steelgraulight'));
        $table->setCellColspan(1);
        echo $table->openRow();
            echo $table->cell(
            "<b>" . _("Wahl eines Konkreten Moduls") . "</b><br>
            " . _("Bitte w&auml;hlen Sie die entsprechende Aktion f&uuml;r ein vorhandenes Konkretes Modul aus. Sie k&ouml;nnen alternativ auch ein neues Modul instanziieren.")
            , array('align' => 'center', 'class' => 'blank'));
            echo $table->cell(Assets::img('infobox/archiv.jpg'));
        echo $table->close();

        echo $table->open(array('class' => 'steelgraulight'));
        echo $form->getFormStart();
        echo $table->blankRow();
        if($form->form_buttons['neuanlegen']){
            $table->setCellColspan(4);
            echo $table->cell($form->getFormButton("neuanlegen"), array('align' => 'center'));
            $table->setCellColspan(1);
        }
        echo $table->blankRow();
        //echo $table->cell($form->getFormButton("sel_$name"));
        echo $table->blankRow();
        foreach ($form->form_fields as $name => $field) {
            echo $table->openRow();
            echo $table->cell($form->getFormButton("sel_$name") . '&nbsp;', array('align' => 'center'));
            if($form->form_buttons['del_'.$name]) echo $table->cell($form->getFormButton("del_$name") . '&nbsp;', array('align' => 'center'));
            echo $table->cell($form->getFormButton("info_$name"). '&nbsp;', array('align' => 'center'));
            echo $table->cell($form->form_fields[$name]['info'], array('style' => 'width: 70%'));
            echo $table->closeRow();
        }
        echo $table->blankRow();
        echo $table->close();
        echo $table->open(array('class' => 'steelgraulight'));

        echo $form->getFormEnd();
        echo $table->close();
    }

    function showStgInputForm($form) {

        $stgs = AbstractStm::GetStg();
        $abschluesse = AbstractStm::GetAbschluesse();
        $types = AbstractStm::GetAbsStmTypes();

        $styles = array(    'width'     => '100%',
//                          'border'        => '2',
                            'align'     => 'center'

        );

        $table = new Table($styles);

        echo $table->open(array('class' => 'steelgraulight'));
        $table->setCellColspan(1);
        echo $table->openRow();
            echo $table->cell(
            "<b>" . _("Wahl des Allgemeinen Moduls") . "</b><br><br>
            " . _("Zuerst sollte man ein Allgemeines Modul ausw&auml;hlen, welches man instanziieren m&ouml;chte. Dazu muss eine Vorauswahl &uuml;ber den Studiengang getroffen werden.")
            , array('align' => 'center', 'class' => 'blank'));
            echo $table->cell('<img src="'.$GLOBALS['ASSETS_URL'].'images/hands01_04.jpg" border="0" align=right">', array('align' => 'right','class' => 'blank'));
        echo $table->close();

        echo $table->open(array('class' => 'steelgraulight'));
        $table->setCellColspan(3);
        echo $table->openRow(array('class' => 'steel1'));
            echo $table->cell("<font size=-1></font>");
        echo $form->getFormStart();

        $table->setCellColspan(1);
        echo $table->openRow(array('align' => 'center',  'class' => 'steelgraudunkel'));
            echo $table->cell($form->getFormFieldCaption('abschl_list') , array('style' =>  'font-weight: bold'));
            echo $table->cell($form->getFormFieldCaption('stg_list') , array('style' =>  'font-weight: bold'));
            echo $table->cell($form->getFormFieldCaption('abs_stm_list') , array('style' =>  'font-weight: bold'));
            echo $table->blankCell();
        echo $table->blankRow();
        echo $table->openRow();
            echo $table->cell($form->getFormField('abschl_list', array('onchange' => 'this.form.submit()', 'style' => 'width: 210')) , array('style' => 'text-align: center'));
            echo $table->cell($form->getFormField('stg_list', array('onchange' => 'this.form.submit()', 'style' => 'width: 210')) , array('style' => 'text-align: center'));
            echo $table->cell($form->getFormField('abs_stm_list', array('style' => 'width: 210')) , array('style' => 'text-align: center'));
            echo $table->blankCell();
            echo $table->blankRow();
        $table->setCellColspan(8);
//      echo $table->row(array($table->cell($form->getFormButton('add'), array('style' => 'text-align: center'))));
        echo $table->blankRow();
        echo $table->blankRow(array('class' => 'steel1'));
        echo $table->openRow(array('class' => 'steel1'));
        $table->setCellAlign('center');
        echo $table->openCell();
            echo $form->getFormButton('back') ;
            echo $form->getFormButton('continue') . "<br><br>";
        echo $table->closeCell();

        echo $form->getFormEnd();
        echo $table->close();

    }

    function showAddInfoForm($form) {

        $styles = array(    'width'     => '100%',
                            //'border'      => '1',
                            'align'     => 'center'

        );

        $table = new Table($styles);

        $star = "";

        echo $table->open(array('class' => 'steelgraulight'));
        $table->setCellColspan(1);
        echo $table->openRow();
            echo $table->cell(
            "<b>" . _("Zus&auml;tzliche Daten des Moduls bearbeiten") . "</b><br><br>
            " . _("Hier werden die Daten eingetragen, die spezifisch f&uuml;r diese Instanz sind. Zur &Uuml;bersicht werden die entsprechenden Daten des Allgemeinen Moduls eingeblendet.")
            , array('align' => 'center', 'width' => '50%', 'class' => 'blank'));
            echo $table->cell('<img src="'.$GLOBALS['ASSETS_URL'].'images/hands02_04.jpg" border="0" align=right">', array('align' => 'right', 'class' => 'blank'));
        echo $table->openRow();
        echo $table->cell('<font size=-1></font>', array('align => right', 'colspan' => '2', 'class'=>'steel1'));
        echo $table->closeRow();
        echo $table->close();

        echo $table->open(array('class' => 'steelgraulight'));
        echo $form->getFormStart();
        $infobox = array ();
        $infobox[] = array("kategorie" => _("Information"), "eintrag" => array(array("icon"=>"icons/16/black/info.png",
        "text"=> sprintf(_("Die Textfelder <b>Inhalte</b> und <b>Hinweise</b> m&uuml;ssen nach den Konventionen
        des StudIPWiki formatiert werden. Beachten Sie dabei die %sFormatierungsm&ouml;glichkeiten%s. <br>
        Der Button <b>Vorschau</b> zeigt das Ergebnis unter dem jeweiligen Textfeld an.")
        ,'<a href="help/index.php?help_page=ix_forum6.htm" target="_blank">','</a>'))));

        echo $table->openRow();
        echo $table->cell('<b>' . $form->getFormFieldCaption('title') .'</b>' . $form->getFormFieldRequired('title') . "<br>" . $form->form_fields['title']['info']
        , array( 'align' => 'center', 'style' => 'width:40%'));
        echo $table->cell($form->getFormField('title', array('style' => 'width:95%')) .'<br><br>', array( 'align' => 'center', 'style' => 'width: 100%; vertical-align: middle'));
        echo $table->closeRow();
        echo $table->blankRow();

        echo $table->openRow();
        echo $table->cell('<b>' . $form->getFormFieldCaption('subtitle') .'</b>' . $form->getFormFieldRequired('subtitle') . "<br>" . $form->form_fields['subtitle']['info']
        , array( 'align' => 'center'));
        echo $table->cell($form->getFormField('subtitle', array('style' => 'width:95%')) .'<br><br>', array( 'align' => 'center', 'style' => 'width: 100%; vertical-align: middle'));
        echo $table->closeRow();
        echo $table->blankRow();

        echo $table->openRow();
        echo $table->cell('<b>' . $form->getFormFieldCaption('topics') .'</b>' . $form->getFormFieldRequired('topics') . "<br>" . $form->form_fields['topics']['info']
        , array( 'align' => 'center'));
        echo $table->cell($form->getFormField('topics', array('rows' => '25', 'style' => 'width: 95%; ')) , array( 'style' => 'width:100%; vertical-align:middle', 'align' => 'center'));
        echo $table->openCell(array('align' => 'center'));
        print_infobox ($infobox, "infobox/wiki.jpg");
        echo '<br>Vorschau&nbsp;' . $form->getFormButton('preview');
        echo $table->closeCell();
        echo $table->closeRow();
        if ($form->form_values['topics'] != '') {
            echo $table->openRow();
            echo $table->blankCell();
            echo $table->cell('<hr>');
            echo $table->openRow();
            echo $table->blankCell();
            echo $table->cell(wikiReady($form->form_values['topics']));
            echo $table->openRow();
            echo $table->blankCell();
            echo $table->cell('<hr>');
        }
        else
            echo $table->blankRow();

        echo $table->openRow();
        echo $table->cell('<b>' . $form->getFormFieldCaption('hints') .'</b>' . $form->getFormFieldRequired('hints') . "<br>" . $form->form_fields['hints']['info']
        , array( 'align' => 'center'));
        echo $table->cell($form->getFormField('hints', array('rows' => '10', 'style' => 'width: 95%; ')) , array( 'style' => 'width:100%; vertical-align:middle', 'align' => 'center'));
        echo $table->closeRow();
        if ($form->form_values['hints'] != '') {
            echo $table->openRow();
            echo $table->blankCell();
            echo $table->cell('<hr>');
            echo $table->openRow();
            echo $table->blankCell();
            echo $table->cell(wikiReady($form->form_values['hints']));
            echo $table->openRow();
            echo $table->blankCell();
            echo $table->cell('<hr>');
        }
        else
            echo $table->blankRow();

        echo $table->openRow();
        echo $table->cell('<b>' . $form->getFormFieldCaption('semester_id') .'</b>' . $form->getFormFieldRequired('semester_id') . "<br>" . $form->form_fields['semester_id']['info']
        , array( 'align' => 'center'));
        echo $table->cell($form->getFormField('semester_id', array('style' => 'width:95%')) .'<br><br>', array( 'align' => 'center', 'style' => 'width: 100%; vertical-align: middle'));
        echo $table->closeRow();
        echo $table->blankRow();

        echo $table->openRow();
        echo $table->cell('<b>' . $form->getFormFieldCaption('homeinst') .'</b>' . $form->getFormFieldRequired('homeinst') . "<br>" . $form->form_fields['homeinst']['info']
        , array( 'align' => 'center'));
        echo $table->cell($form->getFormField('homeinst', array('style' => 'width:95%')) .'<br><br>', array( 'align' => 'center', 'style' => 'width: 100%; vertical-align: middle'));
        echo $table->closeRow();
        echo $table->blankRow();

        echo $table->openRow();
        echo $table->cell('<b>' . $form->getFormFieldCaption('search_user') .'</b>' . $form->getFormFieldRequired('search_user') . "<br>" . $form->form_fields['search_user']['info']
        , array( 'align' => 'center'));
        echo $table->cell($form->getFormField('search_user', array('style' => 'width: 50%')) .'&nbsp;' . $form->getFormButton('search') . "<br><br>" . $form->getFormField('responsible', array('style' => 'width: 80%; vertical-align: middle')), array( 'align' => 'center'));
        echo $table->closeRow();

        echo $table->blankRow();
        echo $table->openRow();
        $table->setCellColspan(3);
        $table->setCellAlign('center');
        echo $table->openCell();
        foreach($form->form_buttons as $name => $value)
        {
            if ($name != 'search' && $name != 'preview')
                echo $form->getFormButton($name);
        }
        echo $table->openCell();
        echo $table->closeRow();

        echo $form->getFormEnd();
        echo $table->close();
    }

    function showSelElementgroupForm($form, $abs_stm, $inst_stm) {

        $styles = array(    'width'     => '100%',
//                          'border'        => '1',
                            'align'     => 'center'
        );

        $elem_types = AbstractStmElement::GetStmElementTypes();

        $table = new Table($styles);

        $star = "";

        echo $table->open(array('class' => 'steelgraulight'));
        $table->setCellColspan(1);
        echo $table->openRow();
            echo $table->cell(
            "<b>" . _("Modulauspr&auml;gung ausw&auml;hlen") . "</b><br><br>
            " . _("Hier wird die Kombination allgemeiner Veranstaltungen ausgew&auml;hlt, der jetzt konkrete Veranstaltungen zugewiesen werden sollen.")
            , array('align' => 'center', 'width' => '50%', 'class' => 'blank'));
            echo $table->cell('<img src="'.$GLOBALS['ASSETS_URL'].'images/hands03_04.jpg" border="0" align=right">', array('align' => 'right', 'class' => 'blank'));
        echo $table->openRow();
        echo $table->cell('<font size=-1></font>', array('align => right', 'colspan' => '2', 'class'=>'steel1'));
        echo $table->closeRow();
        echo $table->close();

        echo $table->open(array('class' => 'steelgraulight'));
        echo $table->blankRow();
        echo $form->getFormStart();

        $table->setCellColspan(1);
        echo $table->openRow(array('align' => 'center',  'class' => 'steelgraudunkel'));
        echo $table->cell("Lehr- und Lernformen" , array('style' =>  'font-weight: bold'));
        echo $table->cell("SWS" , array('style' =>  'font-weight: bold'));
        echo $table->cell("Studentische Arbeitszeit in Stunden" , array('style' =>  'font-weight: bold'));
        echo $table->cell("Semester" , array('style' =>  'font-weight: bold'));
        echo $table->cell("zugewiesen (mind. 1)" , array('style' =>  'font-weight: bold'));
        foreach($abs_stm->elements as $i => $elem_list) {
            foreach($elem_list as $j => $elem) {
                echo $table->openRow(array('style' => 'text-align: center'));
                echo $table->cell($elem_types[$elem->getElementTypeId()]['name']);
                echo $table->cell($elem->getSws());
                echo $table->cell($elem->getWorkload());
                echo $table->cell(($elem->getSemester()==0?"kein":($elem->getSemester()==1?"Sommersemester":"Wintersemester")));
                if ($inst_stm->isFilled($i,$j))
                    echo $table->cell(Assets::img('icons/16/green/accept.png'));
                else
                    echo $table->cell(Assets::img('icons/16/red/decline.png'));

            }
            echo $table->openRow(array('style' => 'text-align: center'));
            $table->setCellColspan(5);
            echo $table->cell($form->getFormButton("sel_$i"));
            echo $table->blankRow();
            $table->setCellColspan(1);
        }
        echo $table->blankRow();
        echo $table->openRow();
        $table->setCellColspan(5);
        $table->setCellAlign('center');
        echo $table->openCell();
        echo $form->getFormButton("back");
        echo $form->getFormButton("continue");
        echo $table->openCell();
        echo $table->closeRow();

        echo $form->getFormEnd();
        echo $table->close();
    }

    function showFillGroupForm($form, $group, $sem_browse_obj, $inst_stm, $group_pos, $cur_sem=null) {

        $styles = array(    'width'     => '100%',
//                          'border'        => '1',
                            'align'     => 'center'
        );

        $elem_types = AbstractStmElement::GetStmElementTypes();

        $table = new Table($styles);

        $star = "";

        echo $table->open(array('class' => 'steelgraulight'));
        $table->setCellColspan(1);
        echo $table->openRow();
            echo $table->cell(
            "<b>" . _("Kombination instanziieren") . "</b><br><br>
            " . _("Hier werden den Elementen der gew&auml;hlten Kombination konkrete Veranstaltungen zugewiesen. Es m&uuml;ssen nicht alle Felder belegt werden, allerdings gilt dieses Modul dann als unvollst&auml;ndig und muss zu einem sp&auml;teren Zeitpunkt vervollst&auml;ndigt werden. Es k&ouml;nnen einem Feld auch mehrere Veranstaltungen zugewiesen werden. Es sind dann alle Permutationen der einzelnen Felder erlaubt.")
            , array('align' => 'center', 'width' => '75%', 'class' => 'blank'));
            echo $table->cell('<img src="'.$GLOBALS['ASSETS_URL'].'images/hands03_04.jpg" border="0" align=right">', array('align' => 'right', 'class' => 'blank'));
        echo $table->openRow();
        echo $table->cell('<font size=-1></font>', array('align => right', 'colspan' => '2', 'class'=>'steel1'));
        echo $table->closeRow();
        echo $table->close();

        echo $table->open(array('class' => 'steelgraulight'));
        echo $table->blankRow();
        echo $form->getFormStart();

        $table->setCellColspan(1);
        echo $table->openRow(array('align' => 'center',  'class' => 'steelgraudunkel'));
        echo $table->cell("Lehr- und Lernformen" , array('style' =>  'font-weight: bold'));
        echo $table->cell("SWS" , array('style' =>  'font-weight: bold'));
        echo $table->cell("Studentische Arbeitszeit in Stunden" , array('style' =>  'font-weight: bold'));
        echo $table->cell("Semester" , array('style' =>  'font-weight: bold'));
        echo $table->cell("Zugewiesene Veranstaltungen" , array('style' =>  'font-weight: bold'));
        echo $table->blankCell();
        foreach($group as $j=> $elem) {
            echo $table->openRow(array('style' => 'text-align: center; vertical-align: top'));
            echo $table->cell($elem_types[$elem->getElementTypeId()]['name']);
            echo $table->cell($elem->getSws());
            echo $table->cell($elem->getWorkload());
            echo $table->cell(($elem->getSemester()==0?"kein":($elem->getSemester()==1?"Sommersemester":"Wintersemester")));
            if ($inst_stm->elements[$group_pos][$j]) {
                echo $table->openCell();
                foreach ($inst_stm->elements[$group_pos][$j] as $k => $element) {
                    $temp_sem = Seminar::GetInstance($element["sem_id"]);
                    echo htmlReady($temp_sem->getName()) . $form->getFormButton("remove_" . $j . "_" .$k) . "<br>";
                }
            }
            else
                echo $table->cell("keine");

            if ($cur_sem)
                echo $table->cell($form->getFormButton("fill_$j"));
            echo $table->blankRow();
        }
        echo $table->blankRow();

        if ($cur_sem) {
            echo $table->openRow();
            echo $table->cell("Veranstaltung im Fokus:" , array('style' =>  'font-weight: bold'));
            echo $table->openRow(array('align' => 'center',  'class' => 'steelgraudunkel'));
            $table->setCellColspan(2);
            echo $table->cell("Titel" , array('style' =>  'font-weight: bold'));
            echo $table->cell("Beginn" , array('style' =>  'font-weight: bold'));
            echo $table->cell("Turnus" , array('style' =>  'font-weight: bold'));
            echo $table->openRow(array('style' => 'text-align: center'));
            echo $table->cell(htmlReady($cur_sem->getName()));
            echo $table->cell(strftime("%d. %b. %Y", $cur_sem->getFirstDate()));
            echo $table->cell($cur_sem->getFormattedTurnus());
            echo $table->blankRow();
        }
        echo $form->getFormEnd();
        $table->setCellColspan(6);
        echo $table->openRow();
        echo $table->openCell();
        $sem_browse_obj->print_qs();
        if ($sem_browse_obj->search_obj->search_button_clicked)
            $sem_browse_obj->print_result();
        echo $form->getFormStart();
        echo $table->openRow();
        $table->setCellAlign('center');
        echo $table->blankRow();
        echo $table->openCell();
        echo $form->getFormButton("continue");
        echo $table->openCell();
        echo $table->closeRow();

        echo $form->getFormEnd();
        echo $table->close();
    }

    function showSummaryForm($form, $stm, $abs_stm) {

        $elem_types = AbstractStmElement::GetStmElementTypes();
        $stm_types = AbstractStm::GetAbsStmTypes();
        $stgs = AbstractStm::GetStg();
        $abschluesse = AbstractStm::GetAbschluesse();
        $is_new = is_string($form->getFormField('complete'));

        $styles = array(    'width'     => '100%',
                            //'border'  => '2',
                            'align'     => 'center'

        );

        $table = new Table($styles);

        echo $table->open(array('class' => 'blank'));
        $table->setCellColspan(1);
        echo $table->openRow();
            echo $table->cell(
            "<b>" . _("Zusammenfassung") . "</b><br><br>
            " . _("Hier werden alle Daten des Konkreten Moduls dargestellt.") .
            ( ($is_new)?
              _("Wenn Sie ein konkretes Modul als vollst&auml;ndig deklarieren, kann es danach nicht mehr bearbeitet oder gel&ouml;scht werden! Bitte &uuml;berpr&uuml;fen Sie noch einmal Ihre Angaben und speichern dann das Modul ab.")
             : "" )
            , array('align' => 'center', 'class' => 'blank'));
            if ($is_new)
                echo $table->cell('<img src="'.$GLOBALS['ASSETS_URL'].'images/hands04_04.jpg" border="0" align=right">', array('align' => 'right', 'class' => 'blank'));
            else
                echo $table->blankCell(array('class' => 'blank'));
        echo $form->getFormStart();
        echo $table->setCellColspan(2);
        echo $table->blankRow(array('class' => 'steel1'));
        echo $table->setCellColspan(1);
        echo $table->close();
        echo $table->open(array('class' => 'steelgraulight'));
        echo $table->blankRow();
        echo $table->openRow();
            if ($is_new) {
                echo $table->cell($form->getFormFieldCaption('complete') . "&nbsp;&nbsp;&nbsp;" . $form->getFormField('complete'), array('align' => 'center', 'colspan' =>'2'));
            }
        echo $table->blankRow();
        echo $table->openRow();
            echo $table->cell("&nbsp;&nbsp;<b> Titel </b>&nbsp;", array('align' => 'left'));
            echo $table->cell($stm->getTitle(), array('style' => 'width:80%'));
        echo $table->openRow();
            echo $table->cell("&nbsp;&nbsp;<b> Untertitel </b>&nbsp;", array('align' => 'left'));
            echo $table->cell($stm->getSubtitle(), array('style' => 'width:80%'));
        echo $table->closeRow();
        echo $table->row(array($table->cell("<hr>", array('colspan' => '2'))));
        echo $table->openRow();
            echo $table->cell("&nbsp;&nbsp;<b> Inhalte </b>&nbsp;", array('align' => 'left'));
            echo $table->cell(wikiReady($stm->getTopics()), array('style' => 'width:50%'));
        echo $table->closeRow();
        echo $table->row(array($table->cell("<hr>", array('colspan' => '2'))));
        echo $table->openRow();
            echo $table->cell("&nbsp;&nbsp;<b> Ziele </b>&nbsp;", array('align' => 'left'));
            echo $table->cell(wikiReady($abs_stm->getAims()), array('style' => 'width:50%'));
        echo $table->closeRow();
        echo $table->row(array($table->cell("<hr>", array('colspan' => '2'))));
        echo $table->openRow();
            echo $table->cell("&nbsp;&nbsp;<b> Hinweise </b>&nbsp;", array('align' => 'left'));
            echo $table->cell(wikiReady($stm->getHints()), array('style' => 'width:50%'));
        echo $table->closeRow();
        echo $table->row(array($table->cell("<hr>", array('colspan' => '2'))));
        echo $table->openRow();
            echo $table->cell("&nbsp;&nbsp;<b> Semester </b>&nbsp;", array('align' => 'left'));
            echo $table->cell($stm->getSemesterName(), array('style' => 'width:80%'));
        echo $table->openRow();
            echo $table->cell("&nbsp;&nbsp;<b> Heimat-Einrichtung </b>&nbsp;", array('align' => 'left'));
            echo $table->cell($stm->getHomeinstName(), array('style' => 'width:80%'));
        echo $table->openRow();
            echo $table->cell("&nbsp;&nbsp;<b> Modulverantwortliche(r) </b>&nbsp;", array('align' => 'left'));
            echo $table->cell($stm->getResponsibleName(), array('style' => 'width:80%'));
        echo $table->openRow();
            echo $table->cell("&nbsp;&nbsp;<b> Moduladministrator(in) </b>&nbsp;", array('align' => 'left'));
            echo $table->cell($stm->getCreatorName(), array('style' => 'width:80%'));
        echo $table->blankRow();
        echo $table->close();

        echo $table->open(array('class' => 'steelgraulight'));
        echo $table->openRow(array('align' => 'center',  'class' => 'steelgraudunkel'));
        echo $table->cell("Lehr- und Lernformen" , array('style' =>  'font-weight: bold'));
        echo $table->cell("SWS" , array('style' =>  'font-weight: bold'));
        echo $table->cell("Studentische Arbeitszeit in Stunden" , array('style' =>  'font-weight: bold'));
        echo $table->cell("Semester" , array('style' =>  'font-weight: bold'));
        echo $table->cell("Zugewiesene Veranstaltungen" , array('style' =>  'font-weight: bold'));
        foreach ($abs_stm->elements as $group_pos => $group) {
            foreach($group as $j=> $elem) {
                echo $table->openRow(array('style' => 'text-align: center; vertical-align: top'));
                echo $table->cell($elem_types[$elem->getElementTypeId()]['name']);
                echo $table->cell($elem->getSws());
                echo $table->cell($elem->getWorkload());
                echo $table->cell(($elem->getSemester()==0?"kein":($elem->getSemester()==1?"Sommersemester":"Wintersemester")));
                if ($stm->elements[$group_pos][$j]) {
                    echo $table->openCell();
                    foreach ($stm->elements[$group_pos][$j] as $k => $element) {
                        $temp_sem = Seminar::GetInstance($element["sem_id"]);
                        echo $temp_sem->getName() . "<br>";
                    }
                }
                else
                    echo $table->cell("keine");
            }
            echo $table->closeRow();
            echo $table->row(array($table->cell("<hr>", array('colspan' => '5'))));
        }
        echo $table->close();
        echo $table->open(array('class' => 'steel1'));
        echo $table->blankRow();
        echo $table->openRow();
        $table->setCellAlign('center');
        echo $table->openCell();
            echo $form->getFormButton('back');
            if($form->form_buttons['continue']) echo $form->getFormButton('continue');
        echo $table->openCell();
        echo $table->closeRow();

        echo $form->getFormEnd();
        echo $table->close();
    }

    function showAbsSummaryForm($form, $stm) {

        $elem_types = AbstractStmElement::GetStmElementTypes();
        $stm_types = AbstractStm::GetAbsStmTypes();
        $stgs = AbstractStm::GetStg();
        $abschluesse = AbstractStm::GetAbschluesse();

        $styles = array(    'width'     => '100%',
                            //'border'  => '2',
                            'align'     => 'center'

        );

        $table = new Table($styles);

        echo $table->open(array('class' => 'blank'));
        $table->setCellColspan(1);
        echo $table->openRow();
            echo $table->cell(
            "<b>" . _("Zusammenfassung") . "</b><br><br>
            " . _("Hier werden noch einmal alle Daten des Allgemeinen Moduls dargestellt. Bitte &uuml;berpr&uuml;fen Sie, ob Sie das richtige Modul gew&auml;hlt haben.")
            , array('align' => 'center', 'class' => 'blank'));
            echo $table->cell('<img src="'.$GLOBALS['ASSETS_URL'].'images/hands01_04.jpg" border="0" align=right">', array('align' => 'right', 'class' => 'blank'));
        echo $form->getFormStart();
        echo $table->setCellColspan(2);
        echo $table->openRow(array('class' => 'steel1'));
            echo $table->blankCell();
        echo $table->close();

        echo $table->open(array('class' => 'steelgraulight'));
        echo $table->blankRow();
        echo $table->openRow();
            echo $table->cell("&nbsp;&nbsp;<b> Titel </b>&nbsp;", array('align' => 'left', 'style' => 'width:12%'));
            echo $table->cell($stm->getTitle(), array('style' => 'width:35%'));
            echo $table->blankCell(array('style' => 'width:2%'));
            echo $table->cell("&nbsp;&nbsp;<b> ECTS-Punkte </b>&nbsp;", array('align' => 'left', 'style' => 'width:12%'));
            echo $table->cell($stm->getCredits(), array('style' => 'width:35%'));
        echo $table->openRow();
            echo $table->cell("&nbsp;&nbsp;<b> Untertitel </b>&nbsp;", array('align' => 'left', 'style' => 'width:12%'));
            echo $table->cell($stm->getSubtitle(), array('style' => 'width:35%'));
            echo $table->blankCell(array('style' => 'width:2%'));
            echo $table->cell("&nbsp;&nbsp;<b> Arbeitsaufwand </b>&nbsp;", array('align' => 'left', 'style' => 'width:12%'));
            echo $table->cell($stm->getWorkload(), array('style' => 'width:35%'));
        echo $table->openRow();
            echo $table->cell("&nbsp;&nbsp;<b>alphanumerische ID</b>&nbsp;", array('align' => 'left', 'style' => 'width:12%'));
            echo $table->cell($stm->getIdNumber(), array('style' => 'width:35%'));
            echo $table->blankCell(array('style' => 'width:2%'));
            echo $table->cell("&nbsp;&nbsp;<b> Turnus </b>&nbsp;", array('align' => 'left', 'style' => 'width:15%'));
            echo $table->cell(($stm->getTurnus()==0?"kein":($stm->getTurnus()==1?"Sommersemester":"Wintersemester")), array('style' => 'width:40%'));
        echo $table->openRow();
            echo $table->cell("&nbsp;&nbsp;<b> Dauer </b>&nbsp;", array('align' => 'left', 'style' => 'width:15%'));
            echo $table->cell($stm->getDuration(), array('style' => 'width:35%'));
        echo $table->blankRow();
        echo $table->openRow();
            echo $table->blankCell();
            $table->setCellColspan(9);
            echo $table->cell("<hr>");
            $table->setCellColspan(2);
        echo $table->openRow();
            echo $table->cell("&nbsp;&nbsp;<b> Inhalte </b>&nbsp;", array('align' => 'left', 'style' => 'width:15%'));
            echo $table->cell(wikiReady($stm->getTopics()), array('style' => 'width:35%'));
            echo $table->blankCell(array('style' => 'width:2%'));
            echo $table->cell("&nbsp;&nbsp;<b> Lernziele </b>&nbsp;", array('align' => 'left', 'style' => 'width:15%'));
            echo $table->cell(wikiReady($stm->getAims()), array('style' => 'width:35%'));
        echo $table->blankRow();
        echo $table->openRow();
            echo $table->blankCell();
            $table->setCellColspan(9);
            echo $table->cell("<hr>");
            $table->setCellColspan(2);
        echo $table->openRow();
            echo $table->cell("&nbsp;&nbsp;<b>Allgemeine Hinweise</b>&nbsp;", array('align' => 'left', 'style' => 'width:15%'));
            echo $table->cell(wikiReady($stm->getHints()), array('style' => 'width:35%'));
        echo $table->blankRow();
        echo $table->close();

        echo $table->open(array('class' => 'steelgraulight'));
        $table->setCellColspan(1);
        echo $table->openRow(array('align' => 'center',  'class' => 'steelgraudunkel'));
            echo $table->cell("Lehr- und Lernformen" , array('style' =>  'font-weight: bold'));
            echo $table->cell("SWS" , array('style' =>  'font-weight: bold'));
            echo $table->cell("Studentische Arbeitszeit" , array('style' =>  'font-weight: bold'));
            echo $table->cell("Semester" , array('style' =>  'font-weight: bold'));
        foreach($stm->elements as $i => $elem_list) {
            foreach($elem_list as $j => $elem) {
                echo $table->openRow(array('style' => 'text-align: center'));
                echo $table->cell($elem_types[$elem->getElementTypeId()]['name']);
                echo $table->cell($elem->getSws());
                echo $table->cell($elem->getWorkload());
                echo $table->cell(($elem->getSemester()==0?"kein":($elem->getSemester()==1?"Sommersemester":"Wintersemester")));
            }
            echo $table->openRow();
            echo $table->cell("<hr>", array('colspan' => '4'));
        }
        echo $table->blankRow();
        echo $table->close();
        echo $table->open(array('class' => 'steelgraulight'));
        $table->setCellColspan(1);
        echo $table->openRow(array('align' => 'center',  'class' => 'steelgraudunkel'));
            echo $table->cell("Studiengang" , array('style' =>  'font-weight: bold'));
            echo $table->cell("Studienprogramm" , array('style' =>  'font-weight: bold'));
            echo $table->cell("Modulart" , array('style' =>  'font-weight: bold'));
            echo $table->cell("Version der <br>Pr&uuml;fungsordnung" , array('style' =>  'font-weight: bold'));
            echo $table->cell("Fr&uuml;hestes<br>Studiensemester" , array('style' =>  'font-weight: bold'));
            echo $table->cell("Sp&auml;testes<br> Studiensemester" , array('style' =>  'font-weight: bold'));
            echo $table->cell("Empfohlenes<br> Studiensemester" , array('style' =>  'font-weight: bold'));
            echo $table->blankCell();
        foreach($stm->assigns as $index => $val) {
            echo $table->openRow(array('style' => 'text-align: center'));
            echo $table->cell($abschluesse[$val['abschl']]);
            echo $table->cell($stgs[$val['stg']]);
            echo $table->cell($stm_types[$val['stm_type_id']]['name']);
            echo $table->cell($val['pversion']);
            echo $table->cell($val['earliest']);
            echo $table->cell($val['latest']);
            echo $table->cell($val['recommed']);
        }

        echo $table->close();
        echo $table->open(array('class' => 'steel1'));
        echo $table->blankRow();
        echo $table->openRow();
        $table->setCellAlign('center');
        echo $table->openCell();
            echo $form->getFormButton('back');
            echo $form->getFormButton('continue');
        echo $table->openCell();
        echo $table->closeRow();

        echo $form->getFormEnd();
        echo $table->close();
    }

    function showError($err_msg) {

        if (count($err_msg)){
            echo "\n<table width=\"99%\" border=\"0\" cellpadding=\"2\" cellspacing=\"0\">";
            parse_msg_array($err_msg, "blank", 1 ,false);
            echo "\n</table>";
        }

    }


}
?>
