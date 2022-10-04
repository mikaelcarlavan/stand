<?php
/* Copyright (C) 2022	Mikael Carlavan	    <contact@mika-carl.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * \file       htdocs/core/modules/stand/mod_stand_saphir.php
 * \ingroup    stand
 * \brief      File that contains the numbering module rules Saphir
 */

dol_include_once("/stand/core/modules/stand/modules_stand.php");


/**
 * Class of file that contains the numbering module rules Saphir
 */
class mod_stand_velov extends ModeleNumRefStands
{
		var $version='dolibarr';		// 'development', 'experimental', 'dolibarr'
		var $error = '';
		var $nom = 'Velov';


    /**
     *  Return description of module
     *
     *  @return     string      Texte descripif
     */
		function info()
    {
				global $conf,$langs;

				$langs->load("stand@stand");

				$form = new Form($this->db);

				$texte = $langs->trans('GenericNumRefModelDesc')."<br>\n";
				$texte.= '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
				$texte.= '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
				$texte.= '<input type="hidden" name="action" value="updateMask">';
				$texte.= '<input type="hidden" name="maskconststand" value="STAND_VELOV_MASK">';
				$texte.= '<table class="nobordernopadding" width="100%">';

				$tooltip=$langs->trans("GenericMaskCodes",$langs->transnoentities("Stand"),$langs->transnoentities("Stand"));
				$tooltip.=$langs->trans("GenericMaskCodes2");
				$tooltip.=$langs->trans("GenericMaskCodes3");
				$tooltip.=$langs->trans("GenericMaskCodes4a",$langs->transnoentities("Stand"),$langs->transnoentities("Stand"));
				$tooltip.=$langs->trans("GenericMaskCodes5");

				// Parametrage du prefix
				$texte.= '<tr><td>'.$langs->trans("Mask").':</td>';
				$texte.= '<td align="right">'.$form->textwithpicto('<input type="text" class="flat" size="24" name="maskstand" value="'.$conf->global->STAND_VELOV_MASK.'">',$tooltip,1,1).'</td>';

				$texte.= '<td align="left" rowspan="2">&nbsp; <input type="submit" class="button" value="'.$langs->trans("Modify").'" name="Button"></td>';

				$texte.= '</tr>';

				$texte.= '</table>';
				$texte.= '</form>';

				return $texte;
    }

    /**
     *  Rstand un exemple de numerotation
     *
     *  @return     string      Example
     */
    function getExample()
    {
        global $conf,$langs,$mysoc;

        $old_code_client=$mysoc->code_client;
        $old_code_type=$mysoc->typent_code;
        $mysoc->code_client='CCCCCCCCCC';
        $mysoc->typent_code='TTTTTTTTTT';
        $numExample = $this->getNextValue($mysoc,'');
        $mysoc->code_client=$old_code_client;
        $mysoc->typent_code=$old_code_type;

        if (! $numExample)
        {
            $numExample = 'NotConfigured';
        }
        return $numExample;
    }


    /**
     * Return next value
     *
     * @param	Societe		$objsoc     Object third party
     * @param   Facture		$stand	Object stand
     * @param   string		$mode       'next' for next value or 'last' for last value
     * @return  string      			Value if OK, 0 if KO
     */
    function getNextValue($objsoc,$stand,$mode='next')
    {
        global $db,$conf;

        require_once DOL_DOCUMENT_ROOT .'/core/lib/functions2.lib.php';

        // Get Mask value
        $mask=$conf->global->STAND_VELOV_MASK;

        if (! $mask)
        {
            $this->error='NotConfigured';
            return 0;
        }

        $where='';

        $numFinal=get_next_value($db, $mask,'stand','ref', $where, $objsoc, $stand->datec, $mode);
        if (! preg_match('/([0-9])+/',$numFinal)) $this->error = $numFinal;

        return  $numFinal;
    }


    /**
     * Return next free value
     *
     * @param	Societe		$objsoc     	Object third party
     * @param	string		$objforref		Object for number to search
     * @param   string		$mode       	'next' for next value or 'last' for last value
     * @return  string      				Next free value
     */
    function getNumRef($objsoc,$objforref,$mode='next')
    {
        return $this->getNextValue($objsoc,$objforref,$mode);
	}
		
}
