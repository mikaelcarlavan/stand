<?php
/* Copyright (C) 2003-2006	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2004-2015	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2005		Marc Barilley / Ocebo	<marc@ocebo.com>
 * Copyright (C) 2005-2015	Regis Houssin			<regis.houssin@inodbox.com>
 * Copyright (C) 2006		Andre Cianfarani		<acianfa@free.fr>
 * Copyright (C) 2010-2013	Juanjo Menent			<jmenent@2byte.es>
 * Copyright (C) 2011-2018	Philippe Grand			<philippe.grand@atoo-net.com>
 * Copyright (C) 2012-2013	Christophe Battarel		<christophe.battarel@altairis.fr>
 * Copyright (C) 2012-2016	Marcos García			<marcosgdf@gmail.com>
 * Copyright (C) 2012       Cedric Salvador      	<csalvador@gpcsolutions.fr>
 * Copyright (C) 2013		Florian Henry			<florian.henry@open-concept.pro>
 * Copyright (C) 2014       Ferran Marcet			<fmarcet@2byte.es>
 * Copyright (C) 2015       Jean-François Ferry		<jfefe@aternatik.fr>
 * Copyright (C) 2018       Frédéric France         <frederic.france@netlogic.fr>
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
 * \file 	htdocs/stand/card.php
 * \ingroup stand
 * \brief 	Page to show customer order
 */

$res=@include("../main.inc.php");                   // For root directory
if (! $res) $res=@include("../../main.inc.php");    // For "custom" directory

include_once DOL_DOCUMENT_ROOT . '/core/class/html.formmail.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/doleditor.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';

dol_include_once("/stand/class/stand.class.php");
dol_include_once("/stand/lib/stand.lib.php");

$langs->load("stand@stand");

$id = GETPOST('id', 'int');
$ref = GETPOST('ref', 'alpha');
$action = GETPOST('action', 'alpha');
$cancel = GETPOST('cancel', 'alpha');
$confirm = GETPOST('confirm', 'alpha');
$backtopage = GETPOST('backtopage','alpha');
$lineid = GETPOST('lineid', 'int');

$result = restrictedArea($user, 'stand', $id);

$object = new Stand($db);
$extrafields = new ExtraFields($db);

// fetch optionals attributes and labels
$extralabels = $extrafields->fetch_name_optionals_label($object->table_element);

// Load object
include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php';  // Must be include, not include_once

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('standcard','globalcard'));

$permissiondellink = $user->rights->stand->creer; 	// Used by the include of actions_dellink.inc.php

/*
 * Actions
 */
$error = 0;

$parameters = array();
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook))
{
	include DOL_DOCUMENT_ROOT.'/core/actions_dellink.inc.php';		// Must be include, not include_once
	
	if ($action == 'confirm_delete' && $confirm == 'yes' && $user->rights->stand->supprimer)
	{
		$result = $object->delete($user);
		if ($result > 0)
		{
			header('Location: list.php?restore_lastsearch_values=1');
			exit;
		}
		else
		{
			setEventMessages($object->error, $object->errors, 'errors');
		}
	}
	// Add 
	else if ($action == 'add' && $user->rights->stand->creer)
	{
		$ref = $object->getNextNumRef($mysoc);

		$name = GETPOST('name', 'alpha');
		$description = GETPOST('description', 'restricthtml');
		$address = GETPOST('address', 'alpha');
        $zip = GETPOST('zip', 'alpha');
        $town = GETPOST('town', 'alpha');
        $longitude = GETPOST('longitude', 'alpha');
        $latitude = GETPOST('latitude', 'alpha');

		$ret = $extrafields->setOptionalsFromPost($extralabels, $object);
		if ($ret < 0) $error++;

		if (!$error)
		{
			$object->ref = $ref;
			$object->name 	= $name;
			$object->description = $description;
			$object->address 	= $address;
            $object->zip 	= $zip;
            $object->town 	= $town;
            $object->longitude 	= $longitude;
            $object->latitude 	= $latitude;

			$id = $object->create($user);
		}
		

		if ($id > 0 && ! $error)
		{
			header('Location: ' . $_SERVER["PHP_SELF"] . '?id=' . $id);
			exit;
		} else {
			$action = 'create';
			setEventMessages($object->error, $object->errors, 'errors');
		}
	}
	else if ($action == 'setname' && !GETPOST('cancel','alpha'))
	{
		$object->name = GETPOST('name', 'alpha');
		$result = $object->update($user);
		
		if ($result < 0) setEventMessages($object->error, $object->errors, 'errors');
	}
	else if ($action == 'setdescription' && !GETPOST('cancel','alpha'))
	{
		$object->description = GETPOST('description', 'restricthtml');
		$result = $object->update($user);
		
		if ($result < 0) setEventMessages($object->error, $object->errors, 'errors');
	}
    else if ($action == 'setaddress' && !GETPOST('cancel','alpha'))
    {
        $object->address = GETPOST('address', 'alpha');
        $result = $object->update($user);

        if ($result < 0) setEventMessages($object->error, $object->errors, 'errors');
    }
    else if ($action == 'setzip' && !GETPOST('cancel','alpha'))
    {
        $object->zip = GETPOST('zip', 'alpha');
        $result = $object->update($user);

        if ($result < 0) setEventMessages($object->error, $object->errors, 'errors');
        else $object->fetch_thirdparty();
    }
    else if ($action == 'settown' && !GETPOST('cancel','alpha'))
    {
        $object->town = GETPOST('town', 'alpha');
        $result = $object->update($user);

        if ($result < 0) setEventMessages($object->error, $object->errors, 'errors');
        else $object->fetch_thirdparty();
    }
    else if ($action == 'setlongitude' && !GETPOST('cancel','alpha'))
    {
        $object->longitude = GETPOST('longitude', 'alpha');
        $result = $object->update($user);

        if ($result < 0) setEventMessages($object->error, $object->errors, 'errors');
        else $object->fetch_thirdparty();
    }
    else if ($action == 'setlatitude' && !GETPOST('cancel','alpha'))
    {
        $object->latitude = GETPOST('latitude', 'alpha');
        $result = $object->update($user);

        if ($result < 0) setEventMessages($object->error, $object->errors, 'errors');
        else $object->fetch_thirdparty();
    }
    elseif ($action == 'confirm_validate' && $confirm == 'yes' && $user->rights->stand->creer)
    {

        $result = $object->valid($user);
        if ($result >= 0) {

        } else {
            setEventMessages($object->error, $object->errors, 'errors');
        }

    }
    elseif ($action == 'confirm_modif' && $user->rights->stand->creer)
    {
        $result = $object->setDraft($user);
        if ($result >= 0) {

        }
    }

	if ($action == 'update_extras')
	{
		$object->oldcopy = dol_clone($object);

		// Fill array 'array_options' with data from update form
		$extralabels = $extrafields->fetch_name_optionals_label($object->table_element);
		$ret = $extrafields->setOptionalsFromPost($extralabels, $object, GETPOST('attribute','none'));
		if ($ret < 0) $error++;

		if (! $error)
		{
			// Actions on extra fields
			$result = $object->insertExtraFields('STAND_MODIFY');
			if ($result < 0)
			{
				setEventMessages($object->error, $object->errors, 'errors');
				$error++;
			}
		}

		if ($error) $action = 'edit_extras';
	}

	// Actions to build doc
	$upload_dir = $conf->stand->multidir_output[$object->entity];
	$permissiontoadd = $user->rights->stand->creer;
	$permissiontoedit = $user->rights->stand->creer;

	include DOL_DOCUMENT_ROOT.'/core/actions_builddoc.inc.php';

	// Actions when printing a doc from card
	include DOL_DOCUMENT_ROOT.'/core/actions_printing.inc.php';

    // Action to move up and down lines of object
    include DOL_DOCUMENT_ROOT.'/core/actions_lineupdown.inc.php';
}


/*
 *	View
 */

llxHeader('', $langs->trans('Stand'));

$form = new Form($db);
$formfile = new FormFile($db);
$formproject = new FormProjets($db);

// Mode creation
if ($action == 'create' && $user->rights->stand->creer)
{
	print load_fiche_titre($langs->trans('NewStand'),'','stand@stand');


	print '<form name="crea_stand" action="' . $_SERVER["PHP_SELF"] . '" method="POST">';
	print '<input type="hidden" name="token" value="' . $_SESSION ['newtoken'] . '">';
	print '<input type="hidden" name="action" value="add">';

	dol_fiche_head('');

	print '<table class="border" width="100%">';

	// Reference
	print '<tr><td class="titlefieldcreate fieldrequired">' . $langs->trans('Ref') . '</td><td>';
    print  $object->getNextNumRef($mysoc);
    print '</td></tr>';

    // Licence plate
    print '<tr><td>' . $langs->trans('StandName') . '</td><td>';
    print '<input type="text" size="60"  class="flat" name="name" value="'.GETPOST('name').'">';
    print '</td></tr>';

    // Description
    print '<tr><td>' . $langs->trans('StandDescription') . '</td><td>';
    $doleditor = new DolEditor('description', GETPOST("description", 'restricthtml'), '', 90, 'dolibarr_notes', '', false, true, getDolGlobalString('FCKEDITOR_ENABLE_SOCIETE'), ROWS_3, '90%');
    $doleditor->Create();
    print '</td></tr>';

    // Address
    print '<tr><td>' . $langs->trans('StandAddress') . '</td><td>';
    print '<input type="text" size="60"  class="flat" name="address" value="'.GETPOST('address').'">';
    print '</td></tr>';

    // Zip/Postal code
    print '<tr><td>' . $langs->trans('StandZip') . '</td><td>';
    print '<input type="text"  size="60" class="flat" name="zip" value="'.GETPOST('zip').'">';
    print '</td></tr>';

    // Town
    print '<tr><td>' . $langs->trans('StandTown') . '</td><td>';
    print '<input type="text" size="60"  class="flat" name="town" value="'.GETPOST('town').'">';
    print '</td></tr>';

    // Longitude
    print '<tr><td>' . $langs->trans('StandLongitude') . '</td><td>';
    print '<input type="text"  size="60" class="flat" name="longitude" value="'.GETPOST('longitude').'">';
    print '</td></tr>';

    // Latitude
    print '<tr><td>' . $langs->trans('StandLatitude') . '</td><td>';
    print '<input type="text" size="60" class="flat" name="latitude" value="'.GETPOST('latitude').'">';
    print '</td></tr>';

	// Other attributes
	$parameters = array('objectsrc' => '', 'socid'=> '');
	$reshook = $hookmanager->executeHooks('formObjectOptions', $parameters, $object, $action); // Note that $action and $object may have been modified by
	print $hookmanager->resPrint;
	if (empty($reshook)) {
		print $object->showOptionals($extrafields, 'edit');
	}

	print '</table>';

	dol_fiche_end();

	print '<div class="center">';
	print '<input type="submit" class="button" name="bouton" value="' . $langs->trans('CreateStand') . '">';
	print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
	print '<input type="button" class="button" name="cancel" value="' . $langs->trans("Cancel") . '" onclick="javascript:history.go(-1)">';
	print '</div>';

	print '</form>';

} else {
	// Mode view
	$now = dol_now();

	if ($object->id > 0) 
	{

		$res = $object->fetch_optionals();

		$head = stand_prepare_head($object);
		
		dol_fiche_head($head, 'stand', $langs->trans("Stand"), -1, 'stand@stand');

		$formconfirm = '';

		// Confirmation to delete
		if ($action == 'delete') {
			$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id, $langs->trans('DeleteStand'), $langs->trans('ConfirmDeleteStand'), 'confirm_delete', '', 0, 1);
		}

		// Confirmation of validation
		if ($action == 'validate') {
			$formquestion = array();
			$text = $langs->trans('ConfirmValidateStand', $object->ref);
			$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('ValidateStand'), $text, 'confirm_validate', $formquestion, 0, 1, 220);
		}

		// Confirm back to draft status
		if ($action == 'modif') {
			$text = $langs->trans('ConfirmUnvalidateStand', $object->ref);
			$formquestion = array();
			$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('UnvalidateStand'), $text, 'confirm_modif', $formquestion, "yes", 1, 220);
		}


		// Call Hook formConfirm
		$parameters = array();
		$reshook = $hookmanager->executeHooks('formConfirm', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
		if (empty($reshook)) $formconfirm.=$hookmanager->resPrint;
		elseif ($reshook > 0) $formconfirm=$hookmanager->resPrint;

		// Print form confirm
		print $formconfirm;

		// Stand card
		$url = dol_buildpath('/stand/list.php', 1).'?restore_lastsearch_values=1';
		$linkback = '<a href="' . $url . '">' . $langs->trans("BackToList") . '</a>';

		dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref');

		print '<div class="fichecenter">';

		print '<div class="fichehalfleft">';
		print '<div class="underbanner clearboth"></div>';

        print '<table class="border" width="100%">';
        

        print '<tr><td>';
        print '<table class="nobordernopadding" width="100%"><tr><td>';
        print $langs->trans('StandName');
        print '</td>';
        if ($action != 'editname')
            print '<td align="right"><a href="' . $_SERVER["PHP_SELF"] . '?action=editname&amp;id=' . $object->id . '">' . img_edit($langs->trans('SetLicencePlate'), 1) . '</a></td>';
        print '</tr></table>';
        print '</td><td>';
        if ($action == 'editname') {
            print '<form name="setname" action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '" method="post">';
            print '<input type="hidden" name="token" value="' . $_SESSION ['newtoken'] . '">';
            print '<input type="hidden" name="action" value="setname">';
            print '<input type="text" class="flat" size="60" name="name" value="'.$object->name.'">';
            print '<input type="submit" class="button" value="' . $langs->trans('Modify') . '">';
            print '</form>';
        } else {
            print $object->name ? $object->name : '&nbsp;';
        }
        print '</td>';
        print '</tr>';

        print '<tr><td>';
        print '<table class="nobordernopadding" width="100%"><tr><td>';
        print $langs->trans('StandDescription');
        print '</td>';
        if ($action != 'editdescription')
            print '<td align="right"><a href="' . $_SERVER["PHP_SELF"] . '?action=editdescription&amp;id=' . $object->id . '">' . img_edit($langs->trans('SetLicencePlate'), 1) . '</a></td>';
        print '</tr></table>';
        print '</td><td>';
        if ($action == 'editdescription') {
            print '<form name="setdescription" action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '" method="post">';
            print '<input type="hidden" name="token" value="' . $_SESSION ['newtoken'] . '">';
            print '<input type="hidden" name="action" value="setdescription">';
            $doleditor = new DolEditor('description', $object->description, '', 90, 'dolibarr_notes', '', false, true, getDolGlobalString('FCKEDITOR_ENABLE_SOCIETE'), ROWS_3, '90%');
            $doleditor->Create();
            print '<input type="submit" class="button" value="' . $langs->trans('Modify') . '">';
            print '</form>';
        } else {
            print $object->description ? dol_nl2br($object->description) : '&nbsp;';
        }
        print '</td>';
        print '</tr>';

        print '<tr><td>';
        print '<table class="nobordernopadding" width="100%"><tr><td>';
        print $langs->trans('StandAddress');
        print '</td>';
        if ($action != 'editaddress')
            print '<td align="right"><a href="' . $_SERVER["PHP_SELF"] . '?action=editaddress&amp;id=' . $object->id . '">' . img_edit($langs->trans('SetLicencePlate'), 1) . '</a></td>';
        print '</tr></table>';
        print '</td><td>';
        if ($action == 'editaddress') {
            print '<form name="setname" action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '" method="post">';
            print '<input type="hidden" name="token" value="' . $_SESSION ['newtoken'] . '">';
            print '<input type="hidden" name="action" value="setaddress">';
            print '<input type="text" class="flat" size="60" name="address" value="'.$object->address.'">';
            print '<input type="submit" class="button" value="' . $langs->trans('Modify') . '">';
            print '</form>';
        } else {
            print $object->address ? $object->address : '&nbsp;';
        }
        print '</td>';
        print '</tr>';

        print '<tr><td>';
        print '<table class="nobordernopadding" width="100%"><tr><td>';
        print $langs->trans('StandZip');
        print '</td>';
        if ($action != 'editzip')
            print '<td align="right"><a href="' . $_SERVER["PHP_SELF"] . '?action=editzip&amp;id=' . $object->id . '">' . img_edit($langs->trans('SetLicencePlate'), 1) . '</a></td>';
        print '</tr></table>';
        print '</td><td>';
        if ($action == 'editzip') {
            print '<form name="setzip" action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '" method="post">';
            print '<input type="hidden" name="token" value="' . $_SESSION ['newtoken'] . '">';
            print '<input type="hidden" name="action" value="setzip">';
            print '<input type="text" class="flat" size="60" name="zip" value="'.$object->zip.'">';
            print '<input type="submit" class="button" value="' . $langs->trans('Modify') . '">';
            print '</form>';
        } else {
            print $object->zip ? $object->zip : '&nbsp;';
        }
        print '</td>';
        print '</tr>';

        print '<tr><td>';
        print '<table class="nobordernopadding" width="100%"><tr><td>';
        print $langs->trans('StandTown');
        print '</td>';
        if ($action != 'edittown')
            print '<td align="right"><a href="' . $_SERVER["PHP_SELF"] . '?action=edittown&amp;id=' . $object->id . '">' . img_edit($langs->trans('SetLicencePlate'), 1) . '</a></td>';
        print '</tr></table>';
        print '</td><td>';
        if ($action == 'edittown') {
            print '<form name="setname" action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '" method="post">';
            print '<input type="hidden" name="token" value="' . $_SESSION ['newtoken'] . '">';
            print '<input type="hidden" name="action" value="settown">';
            print '<input type="text" class="flat" size="60" name="town" value="'.$object->town.'">';
            print '<input type="submit" class="button" value="' . $langs->trans('Modify') . '">';
            print '</form>';
        } else {
            print $object->town ? $object->town : '&nbsp;';
        }
        print '</td>';
        print '</tr>';

        print '<tr><td>';
        print '<table class="nobordernopadding" width="100%"><tr><td>';
        print $langs->trans('StandLongitude');
        print '</td>';
        if ($action != 'editlongitude')
            print '<td align="right"><a href="' . $_SERVER["PHP_SELF"] . '?action=editlongitude&amp;id=' . $object->id . '">' . img_edit($langs->trans('SetLicencePlate'), 1) . '</a></td>';
        print '</tr></table>';
        print '</td><td>';
        if ($action == 'editlongitude') {
            print '<form name="setlongitude" action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '" method="post">';
            print '<input type="hidden" name="token" value="' . $_SESSION ['newtoken'] . '">';
            print '<input type="hidden" name="action" value="setlongitude">';
            print '<input type="text" class="flat" size="60" name="longitude" value="'.$object->longitude.'">';
            print '<input type="submit" class="button" value="' . $langs->trans('Modify') . '">';
            print '</form>';
        } else {
            print $object->longitude ? $object->longitude : '&nbsp;';
        }
        print '</td>';
        print '</tr>';

        print '<tr><td>';
        print '<table class="nobordernopadding" width="100%"><tr><td>';
        print $langs->trans('StandLatitude');
        print '</td>';
        if ($action != 'editlatitude')
            print '<td align="right"><a href="' . $_SERVER["PHP_SELF"] . '?action=editlatitude&amp;id=' . $object->id . '">' . img_edit($langs->trans('SetLicencePlate'), 1) . '</a></td>';
        print '</tr></table>';
        print '</td><td>';
        if ($action == 'editlatitude') {
            print '<form name="setlatitude" action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '" method="post">';
            print '<input type="hidden" name="token" value="' . $_SESSION ['newtoken'] . '">';
            print '<input type="hidden" name="action" value="setlatitude">';
            print '<input type="text" class="flat" size="60" name="latitude" value="'.$object->latitude.'">';
            print '<input type="submit" class="button" value="' . $langs->trans('Modify') . '">';
            print '</form>';
        } else {
            print $object->latitude ? $object->latitude : '&nbsp;';
        }
        print '</td>';
        print '</tr>';

		// Other attributes
		include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_view.tpl.php';

        print '</table>';

        print '</div>';

        print '<div class="fichehalfright">';

        print '<div class="ficheaddleft">';
        print '<div class="underbanner clearboth"></div>';
        print '</div>';

        print '</div>';

        print '</div>';

        print '<div class="clearboth"></div><br />';


		dol_fiche_end();

        $object->fetchObjectLinked();

		print '<div class="tabsAction">';

		$parameters = array();
		$reshook = $hookmanager->executeHooks('addMoreActionsButtons', $parameters, $object, $action); // Note that $action and $object may have been

		// modified by hook
		if (empty($reshook)) {


            // Valid
            if ($object->fk_statut == Stand::STATUS_DRAFT && $user->rights->stand->creer) {
                print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=validate">'.$langs->trans('Validate').'</a>';
            }
            // Edit
            if ($object->fk_statut == Stand::STATUS_VALIDATED && $user->rights->stand->creer) {
                print '<a class="butAction" href="card.php?id='.$object->id.'&amp;action=modif">'.$langs->trans('Modify').'</a>';
            }

		}

		print '</div>';

        print '<div class="fichecenter">';
        print '<div class="fichehalfleft">';
        print '<a name="builddoc"></a>'; // ancre


        print '</div>';
        print '<div class="fichehalfright">';
        print '<div class="ficheaddleft">';

        // List of actions on element
        include_once DOL_DOCUMENT_ROOT . '/core/class/html.formactions.class.php';
        $formactions = new FormActions($db);
        $somethingshown = $formactions->showactions($object, 'stand', '', 1);

        print '</div>';
        print '</div>';
        print '</div>';

	}
}

// End of page
llxFooter();
$db->close();
