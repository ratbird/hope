<?php
# Lifter001: TEST
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO
/**
 * details.php - Detail-Uebersicht und Statistik fuer ein Seminar
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      Cornelis Kater <ckater@gwdg.de>
 * @author      Stefan Suchi <suchi@gmx.de>
 * @author      Michael Riehemann <michael.riehemann@uni-oldenburg.de>
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
 */

require_once '../lib/bootstrap.php';

$sem_id = Request::option('sem_id');

header('Location: ' .URLHelper::getURL('dispatch.php/course/details', array('sem_id' => $sem_id)));