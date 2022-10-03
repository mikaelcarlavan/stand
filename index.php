<?php
/* 
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more detaile.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *	\file       htdocs/stand/index.php
 *	\ingroup    stand
 *	\brief      Home page of stand module
 */


$res=@include("../main.inc.php");                   // For root directory
if (! $res) $res=@include("../../main.inc.php");    // For "custom" directory
require_once DOL_DOCUMENT_ROOT .'/core/class/notify.class.php';
include_once DOL_DOCUMENT_ROOT.'/core/class/dolgraph.class.php';

dol_include_once("/stand/class/stand.class.php");

if (!$user->rights->stand->lire) accessforbidden();

$langs->load("stand@stand");

/*
 * View
 */

$standstatic = new Stand($db);
$form = new Form($db);

llxHeader("", $langs->trans("Stands"));

print load_fiche_titre($langs->trans("StandsArea"));

print '<div class="fichecenter"><div class="fichethirdleft">';


/*
 * Statistics
 */

$sql = "SELECT COUNT(e.rowid) as total, MONTH(e.datec) as month";
$sql.= " FROM ".MAIN_DB_PREFIX."stand as e";
$sql.= " WHERE e.entity IN (".getEntity('stand').")";
$sql.= " GROUP BY MONTH(e.datec)";
$resql = $db->query($sql);
if ($resql)
{
    $num = $db->num_rows($resql);
    $i = 0;

    $total = 0;
    $dataseries = array();
    $vals = array();

	while ($i < $num)
    {
        $obj = $db->fetch_object($resql);
        if ($obj)
        {
			if (!isset($vars[$obj->month]))
			{
				$vars[$obj->month] = 0;
			}
			
			$vars[$obj->month] += $obj->total;
            $total += $obj->total;
        }
        $i++;
    }
	$db->free($resql);
	
    print '<table class="noborder nohover" width="100%">';
    print '<tr class="liste_titre"><th colspan="2">'.$langs->trans("Statistics").' - '.$langs->trans("Stands").'</th></tr>'."\n";
	
	$listofmonths = array(
		1 => $langs->transnoentities('Month01'),
		2 => $langs->transnoentities('Month02'),
		3 => $langs->transnoentities('Month03'),
		4 => $langs->transnoentities('Month04'),
		5 => $langs->transnoentities('Month05'),
		6 => $langs->transnoentities('Month06'),
		7 => $langs->transnoentities('Month07'),
		8 => $langs->transnoentities('Month08'),
		9 => $langs->transnoentities('Month09'),
		10 => $langs->transnoentities('Month10'),
		11 => $langs->transnoentities('Month11'),
		12 => $langs->transnoentities('Month12'),
	);

	foreach ($listofmonths as $id => $month)
    {
		$dataseries[] = array(
			$month,
			(isset($vars[$id]) ? (int) $vars[$id] : 0)
		);
	}
	
    if ($conf->use_javascript_ajax)
    {
        print '<tr class="impair"><td align="center" colspan="2">';

        $dolgraph = new DolGraph();
		$dolgraph->SetData($dataseries);
		$dolgraph->SetHeight(350);
        $dolgraph->setShowLegend(1);
        $dolgraph->setShowPercent(1);
        $dolgraph->SetType(array('pie'));
        $dolgraph->setWidth('100%');
		$dolgraph->draw('idgraphstatus');
		
        print $dolgraph->show($total?0:1);

		print '</td></tr>';
    }

	foreach ($listofmonths as $id => $month)
    {
        if (! $conf->use_javascript_ajax)
        {
            
            print '<tr class="oddeven">';
            print '<td>'.$month.'</td>';
            print '<td align="right">'.(isset($vars[$id]) ? (int) $vars[$id] : 0).' ';
            print $month;
            print '</a>';
            print '</td>';
            print "</tr>\n";
        }
    }
    print '<tr class="liste_total"><td>'.$langs->trans("Total").'</td><td align="right">'.$total.'</td></tr>';
    print "</table><br>";
}
else
{
    dol_print_error($db);
}

print '</div><div class="fichetwothirdright"><div class="ficheaddleft">';


$max = 15;

/*
 * Last modified stands
 */

$sql = "SELECT e.rowid, e.ref, e.tms as datem";
$sql.= " FROM ".MAIN_DB_PREFIX."stand as e";
$sql.= " WHERE e.entity IN (".getEntity('stand').")";
$sql.= " ORDER BY e.tms DESC";
$sql.= $db->plimit($max, 0);

$resql=$db->query($sql);
if ($resql)
{
	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre">';
	print '<th colspan="2">'.$langs->trans("LastModifiedStands",$max).'</th></tr>';

	$num = $db->num_rows($resql);
	if ($num)
	{
		$i = 0;
		while ($i < $num)
		{
			$obj = $db->fetch_object($resql);

			print '<tr class="oddeven">';
			print '<td width="40%" class="nowrap">';

			$standstatic->id = $obj->rowid;
			$standstatic->ref = $obj->ref;

			print $standstatic->getNomUrl(1);

			print '</td>';

			print '<td align="right">'.dol_print_date($db->jdate($obj->datem),'day').'</td>';
			print '</tr>';
			$i++;
		}
	}
	print "</table><br>";
}
else dol_print_error($db);


print '</div></div></div>';


llxFooter();

$db->close();
