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
 *  \file       htdocs/stand/admin/setup.php
 *  \ingroup    stand
 *  \brief      Admin page
 */


$res=@include("../../main.inc.php");                   // For root directory
if (! $res) $res=@include("../../../main.inc.php");    // For "custom" directory

// Libraries
require_once DOL_DOCUMENT_ROOT . "/core/lib/admin.lib.php";
dol_include_once("/stand/lib/stand.lib.php");
dol_include_once("/stand/class/stand.class.php");

// Translations
$langs->load("stand@stand");
$langs->load("admin");

// Access control
if (! $user->admin) accessforbidden();

// Parameters
$action = GETPOST('action', 'alpha');
$value = GETPOST('value', 'alpha');

$reg = array();

/*
 * Actions
 */


include DOL_DOCUMENT_ROOT.'/core/actions_setmoduleoptions.inc.php';

$error=0;
if ($action == 'updateMask')
{
	$maskconststand=GETPOST('maskconststand','alpha');
	$maskstand=GETPOST('maskstand','alpha');
	if ($maskconststand) $res = dolibarr_set_const($db,$maskconststand,$maskstand,'chaine',0,'',$conf->entity);

	if (! $res > 0) $error++;

 	if (! $error)
	{
		setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	}
	else
	{
		setEventMessages($langs->trans("Error"), null, 'errors');
	}
}

if ($action == 'setmod')
{
	// TODO Verifier si module numerotation choisi peut etre active
	// par appel methode canBeActivated

	dolibarr_set_const($db, "STAND_ADDON",$value,'chaine',0,'',$conf->entity);
}

// Action mise a jour ou ajout d'une constante
if ($action == 'update')
{
	$constname=GETPOST('constname','alpha');
	$constvalue=(GETPOST('constvalue_'.$constname) ? GETPOST('constvalue_'.$constname) : GETPOST('constvalue'));


	$consttype=GETPOST('consttype','alpha');
	$constnote=GETPOST('constnote');
	$res = dolibarr_set_const($db,$constname,$constvalue,$type[$consttype],0,$constnote,$conf->entity);

	if (! $res > 0) $error++;

	if (! $error)
	{
		setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	}
	else
	{
		setEventMessages($langs->trans("Error"), null, 'errors');
	}
}

/*
 * View
 */

llxHeader('', $langs->trans('StandSetup'));

// Subheader
$linkback = '<a href="' . DOL_URL_ROOT . '/admin/modules.php">' . $langs->trans("BackToModuleList") . '</a>';

print load_fiche_titre($langs->trans('StandSetup'), $linkback);

// Configuration header
$head = stand_prepare_admin_head();
dol_fiche_head(
	$head,
	'settings',
	$langs->trans("ModuleStandName"),
	0,
	"stand2@stand"
);

$form = new Form($db);

/*
 *  Module numerotation
 */
print load_fiche_titre($langs->trans("StandsNumberingModules"),'','');

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Name")."</td>\n";
print '<td>'.$langs->trans("Description")."</td>\n";
print '<td class="nowrap">'.$langs->trans("Example")."</td>\n";
print '<td align="center" width="60">'.$langs->trans("Status").'</td>';
print '<td align="center" width="16">'.$langs->trans("ShortInfo").'</td>';
print '</tr>'."\n";

clearstatcache();

$dir = './../core/modules/stand/';
if (is_dir($dir))
{
	$handle = opendir($dir);
	if (is_resource($handle))
	{
		$var=true;

		while (($file = readdir($handle))!==false)
		{

			if (substr($file, 0, 10) == 'mod_stand_' && substr($file, dol_strlen($file)-3, 3) == 'php')
			{
				$file = substr($file, 0, dol_strlen($file)-4);

				require_once $dir.$file.'.php';

				$module = new $file;

				// Show modules according to features level
				if ($module->version == 'development'  && $conf->global->MAIN_FEATURES_LEVEL < 2) continue;
				if ($module->version == 'experimental' && $conf->global->MAIN_FEATURES_LEVEL < 1) continue;

				if ($module->isEnabled())
				{

					print '<tr class="oddeven"><td>'.$module->nom."</td><td>\n";
					print $module->info();
					print '</td>';

					// Show example of numbering module
					print '<td class="nowrap">';
					$tmp=$module->getExample();
					if (preg_match('/^Error/',$tmp)) print '<div class="error">'.$langs->trans($tmp).'</div>';
					elseif ($tmp=='NotConfigured') print $langs->trans($tmp);
					else print $tmp;
					print '</td>'."\n";

					print '<td align="center">';
					if ($conf->global->STAND_ADDON == "$file")
					{
						print img_picto($langs->trans("Activated"),'switch_on');
					}
					else
					{
						print '<a href="'.$_SERVER["PHP_SELF"].'?action=setmod&amp;value='.$file.'">';
						print img_picto($langs->trans("Disabled"),'switch_off');
						print '</a>';
					}
					print '</td>';

					$stand = new Stand($db);

					// Info
					$htmltooltip='';
					$htmltooltip.=''.$langs->trans("Version").': <b>'.$module->getVersion().'</b><br>';
					$nextval = $module->getNextValue($mysoc, $stand);
					if ("$nextval" != $langs->trans("NotAvailable")) {  // Keep " on nextval
						$htmltooltip.=''.$langs->trans("NextValue").': ';
						if ($nextval) {
							if (preg_match('/^Error/',$nextval) || $nextval=='NotConfigured')
								$nextval = $langs->trans($nextval);
							$htmltooltip.=$nextval.'<br>';
						} else {
							$htmltooltip.=$langs->trans($module->error).'<br>';
						}
					}

					print '<td align="center">';
					print $form->textwithpicto('',$htmltooltip,1,0);
					print '</td>';

					print "</tr>\n";
				}
			}
		}
		closedir($handle);
	}
}

print "</table><br>\n";


// Page end
dol_fiche_end();
llxFooter();
