<?php
# Lifter002: TODO
# Lifter003: TEST
# Lifter007: TODO
# Lifter010: TODO
/**
 * meine_seminare.php - Anzeige der eigenen Seminare (anhaengig vom Status)
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      Stefan Suchi <suchi@gmx.de>
 * @author      Ralf Stockmann <rstockm@gwdg.de>
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
 */
require_once '../lib/bootstrap.php';
header('Location:'.URLHelper::getURL('dispatch.php/my_courses'));
