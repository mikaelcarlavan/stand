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
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
/**
 * \file 	htdocs/stand/card.php
 * \ingroup stand
 * \brief 	Page to show stand
 */

$res=@include("../main.inc.php");                   // For root directory
if (! $res) $res=@include("../../main.inc.php");    // For "custom" directory

include_once DOL_DOCUMENT_ROOT . '/core/class/html.formmail.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/doleditor.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';
dol_include_once("/bike/class/bike.class.php");
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
    else if ($action == 'enable' && !GETPOST('cancel','alpha'))
    {
        $object->active = 1;
        $result = $object->update($user);

        if ($result < 0) setEventMessages($object->error, $object->errors, 'errors');
    }
    else if ($action == 'disable' && !GETPOST('cancel','alpha'))
    {
        $object->active = 0;
        $result = $object->update($user);

        if ($result < 0) setEventMessages($object->error, $object->errors, 'errors');
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
    else if ($action == 'setgps' && !GETPOST('cancel','alpha'))
    {
        $object->longitude = GETPOST('longitude', 'int');
        $object->latitude = GETPOST('latitude', 'int');

        $result = $object->update($user);

        if ($result < 0) setEventMessages($object->error, $object->errors, 'errors');
        else $object->fetch_thirdparty();
    }
    else if ($action == 'confirm_deletebike' && $confirm == 'yes' && $user->rights->stand->creer)
    {
        // Remove a product line
        $bike = new Bike($db);
        $result = $bike->fetch($lineid);
        if ($result > 0) {
            $bike->fk_stand = 0;
            $result = $bike->update($user);
        }

        if ($result > 0) {
            header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id);
            exit;
        } else {
            setEventMessages($object->error, $object->errors, 'errors');
        }
    }
    elseif ($action == 'addbike' && $user->rights->bike->creer) {		// Add a new line
        $langs->load('errors');
        $error = 0;

        // Set if we used free entry or predefined product
        $fk_bike = GETPOST('fk_bike', 'int');


        if (!$error) {

            $bike = new Bike($db);
            $result = $bike->fetch($fk_bike);
            if ($result > 0) {
                $bike->fk_stand = $object->id;
                $result = $bike->update($user);
            }

            if ($result > 0) {
                unset($_POST['fk_bike']);
                $ret = $object->fetch($object->id); // Reload to get new records
            } else {
                setEventMessages($object->error, $object->errors, 'errors');
            }

        }
    }
    else if ($action == 'confirm_deleteline' && $confirm == 'yes' && $user->rights->stand->creer)
    {
        // Remove a product line
        $result = $object->deleteline($user, $lineid);
        if ($result > 0) {
            header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id);
            exit;
        } else {
            setEventMessages($object->error, $object->errors, 'errors');
        }
    } elseif ($action == 'addline' && $user->rights->stand->creer) {		// Add a new line
        $langs->load('errors');
        $error = 0;

        // Set if we used free entry or predefined product
        $note = (GETPOSTISSET('note') ? GETPOST('note', 'restricthtml') : '');
        $fk_user = GETPOST('fk_user', 'int');

        if (!$error) {

            // Insert line
            $result = $object->addline($note, $fk_user);

            if ($result > 0) {
                $ret = $object->fetch($object->id); // Reload to get new records

                unset($_POST['note']);
                unset($_POST['fk_user']);

            } else {
                setEventMessages($object->error, $object->errors, 'errors');
            }

        }
    }
    elseif ($action == 'updateline' && $user->rights->stand->creer && !$cancel)
    {
        // Update a line
        $note = (GETPOSTISSET('note') ? GETPOST('note', 'restricthtml') : '');
        $fk_user = GETPOST('fk_user', 'int');

        $result = $object->updateline(GETPOST('lineid', 'int'), $note, $fk_user);

        if ($result >= 0) {
            unset($_POST['note']);
            unset($_POST['fk_user']);
            $ret = $object->fetch($object->id); // Reload to get new records
        } else {
            setEventMessages($object->error, $object->errors, 'errors');
        }

    }
    elseif ($action == 'updateline' && $user->rights->stand->creer && $cancel)
    {
        header('Location: '.$_SERVER['PHP_SELF'].'?id='.$object->id); // Pour reaffichage de la fiche en cours d'edition
        exit();
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
llxHeader('', $langs->trans('Stand'), '', '', 0, 0, array('/stand/js/leaflet.js'), array('/stand/css/leaflet.css'));

$form = new Form($db);
$formfile = new FormFile($db);
$formproject = new FormProjets($db);

// Mode creation
if ($action == 'create' && $user->rights->stand->creer)
{
	print load_fiche_titre($langs->trans('NewStand'),'','stand2@stand');


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

    // Latitude/Longitude
    print '<tr><td>' . $langs->trans('StandLatitudeLongitude') . '</td><td>';
    print '<input type="text" size="30" class="flat" name="latitude" value="'.GETPOST('latitude').'">';
    print '&nbsp;<input type="text"  size="60" class="flat" name="longitude" value="'.GETPOST('longitude').'">';
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
		
		dol_fiche_head($head, 'stand', $langs->trans("Stand"), -1, 'stand2@stand');

		$formconfirm = '';

		// Confirmation to delete
		if ($action == 'delete') {
			$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id, $langs->trans('DeleteStand'), $langs->trans('ConfirmDeleteStand'), 'confirm_delete', '', 0, 1);
		}

        if ($action == 'ask_deletebike') {
            $formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id.'&lineid='.$lineid, $langs->trans('DeleteStandBike'), $langs->trans('ConfirmDeleteStandBike'), 'confirm_deletebike', '', 0, 1);
        }

        if ($action == 'ask_deleteline') {
            $formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id.'&lineid='.$lineid, $langs->trans('DeleteStandLine'), $langs->trans('ConfirmDeleteStandLine'), 'confirm_deleteline', '', 0, 1);
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
        print $langs->trans('StandLongitudeLatitude');
        print '</td>';
        if ($action != 'editgps')
            print '<td align="right"><a href="' . $_SERVER["PHP_SELF"] . '?action=editgps&amp;id=' . $object->id . '">' . img_edit($langs->trans('SetLicencePlate'), 1) . '</a></td>';
        print '</tr></table>';
        print '</td><td>';
        if ($action == 'editgps') {
            print '<form name="setlatitude" action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '" method="post">';
            print '<input type="hidden" name="token" value="' . $_SESSION ['newtoken'] . '">';
            print '<input type="hidden" name="action" value="setgps">';
            print '<input type="text" class="flat" size="30" id="latitude" name="latitude" value="'.$object->latitude.'">';
            print '&nbsp;<input type="text" class="flat" size="30" id="longitude" name="longitude" value="'.$object->longitude.'">';
            print '<input type="submit" class="button" value="' . $langs->trans('Modify') . '">';
            print '</form>';
        } else {
            print $object->latitude && $object->longitude ? $object->latitude.', '.$object->longitude : '&nbsp;';

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

        print '<div id="stand-map" class="map" style="width:100%; height:400px;"></div>';

        print '</div>';

        print '</div>';

        print '</div>';

        print '<div class="clearboth"></div><br />';

        /*
                 * Lines
                 */
        $result = $object->getLinesArray();

        print '<form name="addbike" id="addbike" action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.(($action != 'editbike') ? '' : '#line_'.GETPOST('lineid', 'int')).'" method="POST">
		<input type="hidden" name="token" value="' . newToken().'">
		<input type="hidden" name="action" value="' . (($action != 'editbike') ? 'addbike' : 'updatebike').'">
		<input type="hidden" name="mode" value="">
		<input type="hidden" name="page_y" value="">
		<input type="hidden" name="id" value="' . $object->id.'">';

        print '<div class="div-table-responsive-no-min">';
        print '<table id="tablebikes" class="noborder noshadow" width="100%">';

        if (!empty($object->bikes)) {
            $ret = $object->printObjectBikes($action, $mysoc, $object->thirdparty, $lineid, 1);
        }

        $numlines = count($object->bikes);

        /*
         * Form to add new line
         */
        if ($user->rights->bike->creer && $action != 'selectbikes') {
            if ($action != 'editbike') {
                // Add products
                $parameters = array();
                // Note that $action and $object may be modified by hook
                $reshook = $hookmanager->executeHooks('formAddObjectBike', $parameters, $object, $action);
                if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
                if (empty($reshook))
                    $object->formAddObjectBike(1, $mysoc, $object->thirdparty);
            }
        }
        print '</table>';
        print '</div>';

        print "</form>";


        print '<form name="addline" id="addline" action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.(($action != 'editline') ? '' : '#line_'.GETPOST('lineid', 'int')).'" method="POST">
		<input type="hidden" name="token" value="' . newToken().'">
		<input type="hidden" name="action" value="' . (($action != 'editline') ? 'addline' : 'updateline').'">
		<input type="hidden" name="mode" value="">
		<input type="hidden" name="page_y" value="">
		<input type="hidden" name="id" value="' . $object->id.'">';

        print '<div class="div-table-responsive-no-min">';
        print '<table id="tablelines" class="noborder noshadow" width="100%">';

        // Show object lines
        if (!empty($object->lines)) {
            $ret = $object->printObjectLines($action, $mysoc, $object->thirdparty, $lineid, 1);
        }

        $numlines = count($object->lines);

        /*
         * Form to add new line
         */
        if ($user->rights->bike->creer && $action != 'selectlines') {
            if ($action != 'editline') {
                // Add products
                $parameters = array();
                // Note that $action and $object may be modified by hook
                $reshook = $hookmanager->executeHooks('formAddObjectLine', $parameters, $object, $action);
                if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
                if (empty($reshook))
                    $object->formAddObjectLine(1, $mysoc, $object->thirdparty);
            }
        }
        print '</table>';
        print '</div>';

        print "</form>";

		dol_fiche_end();

        $object->fetchObjectLinked();

		print '<div class="tabsAction">';

		$parameters = array();
		$reshook = $hookmanager->executeHooks('addMoreActionsButtons', $parameters, $object, $action); // Note that $action and $object may have been

		// modified by hook
		if (empty($reshook)) {

            // Activate
            if (!$object->active && $user->rights->stand->creer) {
                print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=enable">'.$langs->trans('StandActivate').'</a>';
            }
            // Deactivate
            if ($object->active && $user->rights->stand->creer) {
                print '<a class="butAction" href="card.php?id='.$object->id.'&amp;action=disable">'.$langs->trans('StandDeactivate').'</a>';
            }
            // Delete
            if ($user->rights->stand->supprimer) {
                print '<div class="inline-block divButAction"><a class="butActionDelete" href="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&amp;action=delete">' . $langs->trans('DeleteStand') . '</a></div>';
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

        ?>

        <script type="text/javascript">
            var lat = <?php echo $object->latitude ?: (!empty($conf->global->VELOMA_MAP_LATITUDE) ? $conf->global->VELOMA_MAP_LATITUDE : 48.852969); ?>;
            var lon = <?php echo $object->longitude ?: (!empty($conf->global->VELOMA_MAP_LONGITUDE) ? $conf->global->VELOMA_MAP_LONGITUDE : 2.349903); ?>;

            var map = null;
            var marker = null;

            function initMap() {
                map = L.map('stand-map').setView([lat, lon], <?php echo !empty($conf->global->VELOMA_MAP_ZOOM) ? $conf->global->VELOMA_MAP_ZOOM : 13; ?>);
                L.tileLayer('https://{s}.tile.openstreetmap.fr/osmfr/{z}/{x}/{y}.png', {
                   attribution: 'Données &copy; Contributeurs <a href="http://openstreetmap.org">OpenStreetMap</a> | <a href="https://creativecommons.org/licenses/by/2.0/">CC-BY</a>',
                   minZoom: 1,
                   maxZoom: 20
               }).addTo(map);

                <?php if ($action == 'editgps'): ?>
                map.on('click', onMapClick);
                <?php endif; ?>

                <?php if ($object->latitude && $object->longitude): ?>
                marker = L.marker([<?php echo $object->latitude; ?>, <?php echo $object->longitude; ?>]);
                marker.addTo(map);
                <?php endif; ?>
            }

            function onMapClick(e) {
                $("#latitude").val(e.latlng.lat);
                $("#longitude").val(e.latlng.lng);

                if (marker) {
                     marker.setLatLng(e.latlng);
                } else {
                     marker = L.marker(e.latlng);
                     marker.addTo(map);
                }
            }

            $(document).ready(function() {
                initMap();
            });
        </script>
        <?php
	}
}

// End of page
llxFooter();
$db->close();
