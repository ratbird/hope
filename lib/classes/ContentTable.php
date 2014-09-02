<?php
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO

/*
Copyright (C) 2003 Tobias Thelen <tthelen@uni-osnabrueck.de>

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
*/

/**
 * Table Class for content areas of Stud.IP pages
 *
 * The content area usually contains:
 * - a 99%-width centred table with 2 columns
 * - the content
 * Content areas are normally part of a container table.
 *
 * Example:
 *
 * $container=new ContainerTable();
 * echo $container->headerRow("Name of the game...");
 * echo $container->openCell();
 * $content=new ContentTable();
 * echo $content->open();
 * ...the content...
 * echo $content->close();
 * echo $container->blankRow();
 * echo $container->close();
 *
 * @access public
 * @author Tobias Thelen <tthelen@uni-osnabrueck.de>
 *
 **/
class ContentTable extends Table {
    function ContentTable($styles="")
    {
        Table::Table($styles);
        if (!@$styles["width"]) {
            $this->table_width="99%";
        }
        if (!@$styles["align"]) {
            $this->table_align="center";
        }
    }
}
