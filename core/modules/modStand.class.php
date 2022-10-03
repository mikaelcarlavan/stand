<?php
/* Copyright (C) 2004-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2022 Mikael Carlavan <contact@mika-carl.fr>
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
 * 	\defgroup   stand     Module stand
 *  \brief      stand module descriptor.
 *
 *  \file       htdocs/stand/core/modules/modstand.class.php
 *  \ingroup    stand
 *  \brief      Description and activation file for module stand
 */
include_once DOL_DOCUMENT_ROOT .'/core/modules/DolibarrModules.class.php';


// The class name should start with a lower case mod for Dolibarr to pick it up
// so we ignore the Squiz.Classes.ValidClassName.NotCamelCaps rule.
// @codingStandardsIgnoreStart
/**
 *  Description and activation class for module stand
 */
class modStand extends DolibarrModules
{
	// @codingStandardsIgnoreEnd
	/**
	 * Constructor. Define names, constants, directories, boxes, permissions
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct($db)
	{
        global $langs,$conf;

        $this->db = $db;

		// Id for module (must be unique).
		// Use here a free id (See in Home -> System information -> Dolibarr for list of used modules id).
		$this->numero = 500010;		// TODO Go on page https://wiki.dolibarr.org/index.php/List_of_modules_id to reserve id number for your module
		// Key text used to identify module (for permissions, menus, etc...)
		$this->rights_class = 'stand';

		// Family can be 'crm','financial','hr','projects','products','ecm','technic','interface','other'
		// It is used to group modules by family in module setup page
		$this->family = "Véloma";
		// Module position in the family
		$this->module_position = 500;
		// Gives the possibility to the module, to provide his own family info and position of this family (Overwrite $this->family and $this->module_position. Avoid this)
		//$this->familyinfo = array('myownfamily' => array('position' => '001', 'label' => $langs->trans("MyOwnFamily")));

		// Module label (no space allowed), used if translation string 'ModulestandName' not found (MyModue is name of module).
		$this->name = preg_replace('/^mod/i','',get_class($this));
		// Module description, used if translation string 'ModulestandDesc' not found (MyModue is name of module).
		$this->description = "Stand";
		// Used only if file README.md and README-LL.md not found.
		$this->descriptionlong = "Module permettant de gérer les stations à vélo";

		$this->editor_name = 'Mikael Carlavan';
		$this->editor_url = 'http://www.mika-carl.fr';

		// Possible values for version are: 'development', 'experimental', 'dolibarr', 'dolibarr_deprecated' or a version string like 'x.y.z'
		$this->version = '1.0.0';
		// Key used in llx_const table to save module status enabled/disabled (where SITFAC is value of property name of module in uppercase)
		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		// Name of image file used for this module.
		// If file is in theme/yourtheme/img directory under name object_pictovalue.png, use this->picto='pictovalue'
		// If file is in module/img directory under name object_pictovalue.png, use this->picto='pictovalue@module'
		$this->picto='stand@stand';

		// Defined all module parts (triggers, login, substitutions, menus, css, etc...)
		// for default path (eg: /stand/core/xxxxx) (0=disable, 1=enable)
		// for specific path of parts (eg: /stand/core/modules/barcode)
		// for specific css file (eg: /stand/css/stand.css.php)
		$this->module_parts = array(
            'triggers' => 0,                                 	// Set this to 1 if module has its own trigger directory (core/triggers)
            'login' => 0,                                    	// Set this to 1 if module has its own login method directory (core/login)
            'substitutions' => 0,                            	// Set this to 1 if module has its own substitution function file (core/substitutions)
            'menus' => 0,                                    	// Set this to 1 if module has its own menus handler directory (core/menus)
            'theme' => 0,                                    	// Set this to 1 if module has its own theme directory (theme)
            'tpl' => 0,                                      	// Set this to 1 if module overwrite template dir (core/tpl)
            'barcode' => 0,                                  	// Set this to 1 if module has its own barcode directory (core/modules/barcode)
            'models' => 0,                                   	// Set this to 1 if module has its own models directory (core/modules/xxx)
            'css' => array(),	// Set this to relative path of css file if module has its own css file
            'js' => array(),          // Set this to relative path of js file if module must load a js on all pages
            'hooks' => array() 	// Set here all hooks context managed by module. You can also set hook context 'all'
        );

		// Data directories to create when module is enabled.
		// Example: this->dirs = array("/stand/temp","/stand/subdir");
		$this->dirs = array("/stand/temp");

		// Config pages. Put here list of php page, stored into stand/admin directory, to use to setup module.
		$this->config_page_url = array("setup.php@stand");

		// Dependencies
		$this->hidden = false;			// A condition to hide module
		$this->depends = array();		// List of module class names as string that must be enabled if this module is enabled
		$this->requiredby = array();	// List of module ids to disable if this one is disabled
		$this->conflictwith = array();	// List of module class names as string this module is in conflict with
		$this->phpmin = array(5,3);					// Minimum version of PHP required by module
		$this->need_dolibarr_version = array(4,0);	// Minimum version of Dolibarr required by module
		$this->langfiles = array("stand@stand");
		$this->warnings_activation = array();                     // Warning to show when we activate module. array('always'='text') or array('FR'='textfr','ES'='textes'...)
		$this->warnings_activation_ext = array();                 // Warning to show when we activate an external module. array('always'='text') or array('FR'='textfr','ES'='textes'...)

		$this->const = array();                                                 

		if (! isset($conf->stand) || ! isset($conf->stand->enabled))
        {
        	$conf->stand = new stdClass();
        	$conf->stand->enabled=0;
        }

		$this->tabs = array();

        // Dictionaries
        $this->dictionaries = array();

        // Boxes/Widgets
		// Add here list of php file(s) stored in stand/core/boxes that contains class to show a widget.
        $this->boxes = array();


		// Cronjobs (List of cron jobs entries to add when module is enabled)
		$this->cronjobs = array();


		// Permissions
		$this->rights = array();		// Permission array used by this module
		$r = 0;
		$this->rights[$r][0] = 5000101;
		$this->rights[$r][1] = 'Créer les stations';
		$this->rights[$r][2] = 'r';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'creer';
        $this->rights[$r][5] = '';

		$r++;
		$this->rights[$r][0] = 5000102;
		$this->rights[$r][1] = 'Voir les stations';
		$this->rights[$r][2] = 'w';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'lire';
        $this->rights[$r][5] = '';

		$r++;
		$this->rights[$r][0] = 5000103;
		$this->rights[$r][1] = 'Modifier les stations';
		$this->rights[$r][2] = 'w';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'modifier';
        $this->rights[$r][5] = '';

		$r++;
		$this->rights[$r][0] = 5000104;
		$this->rights[$r][1] = 'Supprimer les stations';
		$this->rights[$r][2] = 'w';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'supprimer';
		$this->rights[$r][5] = '';
	
		

		// Main menu entries
		$this->menu = array();
		$r = 0;

		$this->menu[$r]=array('fk_menu'=>0,			                // Put 0 if this is a top menu
			'type'=>'top',			                // This is a Top menu entry
			'titre'=>$langs->trans('Stands'),
			'mainmenu'=>'stand',
			'leftmenu'=>'',
			'url'=>'/stand/index.php',
			'langs'=> 'stand@stand',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position'=> 30,
			'enabled'=>'$conf->stand->enabled ', //&& (!(! empty($conf->comptabilite->enabled) || ! empty($conf->accounting->enabled) || ! empty($conf->facture->enabled) || ! empty($conf->deplacement->enabled) || ! empty($conf->don->enabled) || ! empty($conf->tax->enabled)))
			'perms'=>'$user->rights->stand->lire',			                // Use 'perms'=>'$user->rights->report->level1->level2' if you want your menu with a permission rules
			'target'=>'',
			'user'=>2
		);

		$r++;
        $this->menu[$r]=array(
            'fk_menu'=>'fk_mainmenu=stand',			// Put 0 if this is a top menu
        	'type'=> 'left',			// This is a Top menu entry
        	'titre'=> $langs->trans('Stands'),
        	'mainmenu'=> 'stand',
        	'leftmenu'=> 'stands',		// Use 1 if you also want to add left menu entries using this descriptor. Use 0 if left menu entries are defined in a file pre.inc.php (old school).
			'url'=> '/stand/index.php',
			'langs'=> 'stand@stand',	// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position'=> 101,
			'enabled'=> '1',			// Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled.
			'perms'=> '$user->rights->stand->lire',			// Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
			'target'=> '',
			'user'=> 2
		);
		
		$r++;
        $this->menu[$r]=array(
            'fk_menu'=>'fk_mainmenu=stand,fk_leftmenu=stands',			// Put 0 if this is a top menu
        	'type'=> 'left',			// This is a Top menu entry
        	'titre'=> $langs->trans('StandsNew'),
        	'mainmenu'=> 'stand',
        	'leftmenu'=> 'stands_nouveau',		// Use 1 if you also want to add left menu entries using this descriptor. Use 0 if left menu entries are defined in a file pre.inc.php (old school).
			'url'=> '/stand/card.php?action=create',
			'langs'=> 'stand@stand',	// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position'=> 101,
			'enabled'=> '1',			// Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled.
			'perms'=> '$user->rights->stand->lire',			// Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
			'target'=> '',
			'user'=> 2
		);
		
		$r++;
        $this->menu[$r]=array(
            'fk_menu'=>'fk_mainmenu=stand,fk_leftmenu=stands',			// Put 0 if this is a top menu
        	'type'=> 'left',			// This is a Top menu entry
        	'titre'=> $langs->trans('StandsList'),
        	'mainmenu'=> 'stand',
        	'leftmenu'=> 'stands_liste',		// Use 1 if you also want to add left menu entries using this descriptor. Use 0 if left menu entries are defined in a file pre.inc.php (old school).
			'url'=> '/stand/list.php',
			'langs'=> 'stand@stand',	// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position'=> 101,
			'enabled'=> '1',			// Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled.
			'perms'=> '$user->rights->stand->lire',			// Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
			'target'=> '',
			'user'=> 2
		);
	}

	/**
	 *		Function called when module is enabled.
	 *		The init function add constants, boxes, permissions and menus (defined in constructor) into Dolibarr database.
	 *		It also creates data directories
	 *
     *      @param      string	$options    Options when enabling module ('', 'noboxes')
	 *      @return     int             	1 if OK, 0 if KO
	 */
	public function init($options='')
	{
		$sql = array();

		$this->_load_tables('/stand/sql/');

		// Create extrafields
		include_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
		$extrafields = new ExtraFields($this->db);


        return $this->_init($sql, $options);
	}

	/**
	 * Function called when module is disabled.
	 * Remove from database constants, boxes and permissions from Dolibarr database.
	 * Data directories are not deleted
	 *
	 * @param      string	$options    Options when enabling module ('', 'noboxes')
	 * @return     int             	1 if OK, 0 if KO
	 */
	public function remove($options = '')
	{
		$sql = array();

		return $this->_remove($sql, $options);
	}

}
