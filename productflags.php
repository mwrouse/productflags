<?php
if ( ! defined('_TB_VERSION_')) {
    exit;
}



class ProductFlags extends Module
{
    /* @var boolean error */
    protected $hooksList = [];

    protected static $cachedHooksList;

    protected $_tabs = [
        'ProductFlags' => 'Flags', // class => label
    ];

    public function __construct()
    {
        $this->name = 'productflags';
        $this->tab = 'front_office_features';
        $this->version = '1.0.1';
        $this->author = 'Michael Rouse';
        $this->tb_min_version = '1.0.0';
        $this->tb_versions_compliancy = '> 1.0.0';
        $this->need_instance = 0;
        $this->table_name = 'productflags';
        $this->table_name_lang = 'productflags_lang';
        $this->table_name_hook = 'productflags_hook';
        $this->bootstrap = true;

        // List of hooks
        $this->hooksList = [
            'productActions'
        ];

        parent::__construct();

        $this->displayName = $this->l('Product Flags');
        $this->description = $this->l('Add flags to your products');
    }



    public function hookProductActions()
    {
        return "";
    }


    public function install()
    {
        if ( ! parent::install()
            || ! $this->_createTabs()
            || ! $this->_installTable()
        ) {
            return false;
        }

        foreach ($this->hooksList as $hook) {
            if ( ! $this->registerHook($hook)) {
                return false;
            }
        }

        return true;
    }

    public function uninstall()
    {
        if ( ! parent::uninstall()
            || ! $this->_eraseTable()
            || ! $this->_eraseTabs()
        ) {
            return false;
        }

        return true;
    }

    /**
     * Create tabs on the admin page
     */
    private function _createTabs()
    {
        /* This is the main tab, all others will be children of this */
        $allLangs = Language::getLanguages();
        $idTab = $this->_createSingleTab(9 /* Catalog Tab */, 'Admin'.ucfirst($this->name), $this->displayName, $allLangs);

        foreach ($this->_tabs as $class => $name) {
              $this->_createSingleTab($idTab, $class, $name, $allLangs);
        }

        return true;
    }

    /**
     * Creates a single tab
     */
    private function _createSingleTab($idParent, $class, $name, $allLangs)
    {
        $tab = new Tab();
        $tab->active = 1;

        foreach ($allLangs as $language) {
            $tab->name[$language['id_lang']] = $name;
        }

        $tab->class_name = $class;
        $tab->module = $this->name;
        $tab->id_parent = $idParent;

        if ($tab->add()) {
            return $tab->id;
        }

        return false;
    }

    /**
     * Get rid of all installed back office tabs
     */
    private function _eraseTabs()
    {
        $idTabm = (int)Tab::getIdFromClassName('Admin'.ucfirst($this->name));
        if ($idTabm) {
            $tabm = new Tab($idTabm);
            $tabm->delete();
        }

        foreach ($this->_tabs as $class => $name) {
            $idTab = (int)Tab::getIdFromClassName($class);
            if ($idTab) {
                $tab = new Tab($idTab);
                $tab->delete();
            }
        }

        return true;
    }

    /**
     * Create Database Tables
     */
    private function _installTable()
    {
        $sql = 'CREATE TABLE  `'._DB_PREFIX_.$this->table_name.'` (
                `id_flag` INT( 12 ) AUTO_INCREMENT,
                `name` VARCHAR( 64 ) NOT NULL,
                `active` TINYINT(1) NOT NULL,
                PRIMARY KEY (  `id_flag` )
                ) ENGINE =' ._MYSQL_ENGINE_;
        $sql2 = 'CREATE TABLE  `'._DB_PREFIX_.$this->table_name_lang.'` (
                `id_flag` INT( 12 ),
                `id_lang` INT( 12 ) NOT NULL,
                `content` TEXT NOT NULL,
                `bgcolor` TEXT NOT NULL,
                `fgColor` TEXT NOT NULL,
                PRIMARY KEY (  `id_flag`, `id_lang` )
                ) ENGINE =' ._MYSQL_ENGINE_;
        $sql3 = 'CREATE TABLE  `'._DB_PREFIX_.$this->table_name_hook.'` (
                `id_flag` INT( 12 ),
                `hook_name` VARCHAR( 64 ) NOT NULL,
                `position` INT( 12 ) NOT NULL,
                PRIMARY KEY (  `id_flag`,  `hook_name`)
                ) ENGINE =' ._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8';

        if ( ! Db::getInstance()->Execute($sql)
            || ! Db::getInstance()->Execute($sql2)
            || ! Db::getInstance()->Execute($sql3)
        ) {
            return false;
        }

        return true;
    }

    /**
     * Remove Database Tables
     */
    private function _eraseTable()
    {
        if ( ! Db::getInstance()->Execute(
                'DROP TABLE `'._DB_PREFIX_.$this->table_name.'`'
            ) || ! Db::getInstance()->Execute(
                'DROP TABLE `'._DB_PREFIX_.$this->table_name_lang.'`'
            ) || ! Db::getInstance()->Execute(
                'DROP TABLE `'._DB_PREFIX_.$this->table_name_hook.'`'
        )) {
            return false;
        }

        return true;
    }
}