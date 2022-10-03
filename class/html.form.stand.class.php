<?php
/* Copyright (C) 2004-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2017 Mikael Carlavan <contact@mika-carl.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *  \file       htdocs/stand/class/html.form.stand.class.php
 *  \ingroup    stand
 *  \brief      File of class to manage form for stand
 */
require_once(DOL_DOCUMENT_ROOT."/core/lib/functions2.lib.php");
dol_include_once("/stand/class/stand.class.php");


class StandForm
{
    var $db;
    var $error;

    /**
     * Constructor
     * @param      $DB      Database handler
     */
    function __construct($DB)
    {
        $this->db = $DB;
    }

    /**
     *    Return combo list of status
     *    @param     selected         Id preselected status
     *    @param     htmlname         Name of html select object
     *    @param     htmloption       Options html on select object
     *    @return    string           HTML string with select
     */
    function select_status($selected='', $htmlname='fk_statut', $htmloption='', $empty = false)
    {
        global $conf, $langs;

        $stand = new Stand($this->db);

        $status = $stand->getStatus();
        
        //Build select
        $select = '<select class="flat" id = "'.$htmlname.'" name = "'.$htmlname.'" '.$htmloption.'>';
        if ($empty)
        {
            $select .= '<option value="-1" '.(empty($selected) ? 'selected="selected"' : '').'>&nbsp;</option>';
        }
        foreach ($status as $id => $s)
        {
            $select .= '<option value="'.$id.'" '.($id == $selected ? 'selected="selected"' : '').'>'.$s.'</option>';
        }
        
        $select .= '</select>';

        return $select;
    }

}
