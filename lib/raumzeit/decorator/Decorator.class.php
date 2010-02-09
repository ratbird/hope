<?
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
/*
 * Decorator.class.php
 *
 * Diese Klasse repraesentiert einen abstrakten Dekorator fuer die Termindaten einer Veranstaltung.
 * Um eine eigene Ausgabe zu erstellen muss die Methode toString ueberschrieben werden.
 *
 * Die Rohdaten liefert die Seminar-Klasse (lib/classes/Seminar.class.php) mittels der Funktion getUndecoratedData().
 *
 * (c) 2005 by Till Gloeggler (tgloeggl@uos.de)
 */

class Decorator {
	var $undecoratedData = NULL;
	
	function Decorator($data = '') {
		if ($data != '') {
			$this->undecoratedData = $data;
		}
	}

	function toString() {
		/*
		Das Array undecoratedData ist folgendermassen aufgebaut:
		Array
 		(
			[regular] => Array
        (
						[turnus_data] = Array
							(
		            [{metadate_id}] => Array
    		            (
		                    [metadate_id] => {metadate_id}
		                    [idx] => 
		                    [day] => 
		                    [start_hour] =>
		                    [start_minute] =>
		                    [end_hour] =>
		                    [end_minute] =>
		                )
								]

						[art] =>
            [start_woche] =>
            [turnus] =>

        )

    [irregular] => Array
        (
            [{termin_id}] => Array
                (
                    [termin_id] => {termin_id}
                    [date_typ] => 
                    [date] => 
                    [end_time] => 
                    [mkdate] =>
                    [chdate] =>
                    [ex_termin] => 
                    [orig_ex] => 
                    [range_id] =>
                    [author_id] => 
                    [resource_id] => 
                    [raum] => 
                )
				)
		)
		*/
	}
}
?>
