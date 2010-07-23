<?php

/*
 * Copyright (C) 2009-2010 - Till Glöggler <tgloeggl@uos.de>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

class CalendarModel
{

    static function generateMatrix($grouped_entries)
    {
        $matrix = array();
        if (is_array($grouped_entries)) foreach ($grouped_entries as $day => $entries_for_day) {
            $group_matrix = array();
            foreach ($entries_for_day as $groups) {
                foreach ($groups as $group) {
                    if (is_array($group[0])) $data = $group[0]; else $data = $group;

                    for ($i = floor($data['start'] / 100); $i <= floor($data['end'] / 100); $i++) {
                        for ($j = 0; $j < 60; $j++) {
                            if (($i * 100) + $j >= $data['start'] && ($i * 100) + $j < $data['end']) {
                                $group_matrix[($i * 100) + $j]++;
                            }
                        }
                    } 
                }
            }

            $matrix[$day] = $group_matrix;
        }

        return $matrix;
    }


    static function sortAndGroupEntries($entries)
    {
        // optimize for each day
        if (is_array($entries)) foreach ($entries as $day => $entries_for_day) {

            $result = array();
            
            // 1st step - group all entries with the same duration
            foreach ($entries_for_day as $entry_id => $entry) {
                $new_entries[$entry['start'] .'_'. $entry['end']][] = $entry;
            }

            $column = 0;

            // 2nd step - optimize the groups
            while (sizeof($new_entries) > 0) {
                $lstart = 2399; $lend = 0;

                foreach ($new_entries as $time => $grouped_entries) {
                    list($start, $end) = explode('_', $time);
                    if ($start < $lstart /*&& ($end - $start) >= ($lend - $lstart)*/ )  { 
                        $lstart = $start;
                        $lend = $end;
                    }
                }

                $result['col_'. $column][] = $new_entries[$lstart .'_'. $lend];
                unset($new_entries[$lstart .'_'. $lend]);

                $hit = true;

                while ($hit) {
                    $hit = false;
                    $hstart = 2399; $hend = 2399;

                    // check, if there is something, that can be placed after
                    foreach ($new_entries as $time => $grouped_entries) {
                        list($start, $end) = explode('_', $time);

                        if ( ($start >= $lend) && ($start < $hstart) ) {
                            $hstart = $start;
                            $hend = $end;
                            $hit = true;
                        }
                    }

                    if ($hit) {
                        $lend = $hend;
                        $result['col_'. $column][] = $new_entries[$hstart .'_'. $hend];
                        unset($new_entries[$hstart .'_'. $hend]);
                    }
                }

                $column++;
            } // 2nd step

            $ret['day_'. $day] = $result;
        } // foreach - every day

        return $ret;

    }


    static function sortEntries($entries)
    {
        // optimize for each day
        if (is_array($entries)) foreach ($entries as $day => $entries_for_day) {

            $result = array();
            $column = 0;

            // 2nd step - optimize the groups
            while (sizeof($entries_for_day) > 0) {
                $lstart = 2399; $lend = 0; $lkey = null;

                foreach ($entries_for_day as $entry_key => $entry) {
                    if ($entry['start'] < $lstart /*&& ($end - $start) >= ($lend - $lstart)*/ )  { 
                        $lstart = $entry['start'];
                        $lend = $entry['end'];
                        $lkey = $entry_key;
                    }
                }

                $result['col_'. $column][] = $entries_for_day[$lkey];
                unset($entries_for_day[$lkey]);

                $hit = true;

                while ($hit) {
                    $hit = false;
                    $hstart = 2399; $hend = 2399; $hkey = null;

                    // check, if there is something, that can be placed after
                    foreach ($entries_for_day as $entry_key => $entry) {
                        if ( ($entry['start'] >= $lend) && ($entry['start'] < $hstart) ) {
                            // && (($end - $start) > ($hend - $hstart)) ) {
                            $hstart = $entry['start'];
                            $hend = $entry['end'];
                            $hkey = $entry_key;
                            $hit = true;
                        }
                    }

                    if ($hit) {
                        $lend = $hend;
                        $result['col_'. $column][] = $entries_for_day[$hkey];
                        unset($entries_for_day[$hkey]);
                    }
                }

                $column++;
            } // 2nd step

            $ret['day_'. $day] = $result;
        } // foreach - every day

        return $ret;
    }
}
