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
        $this->table_name_products = 'productflags_products';
        $this->bootstrap = true;

        $this->ps_versions_compliancy = ['min' => '1.6', 'max' => '1.6.99.99'];

        // List of hooks
        $this->hooksList = [
            'displayBackOfficeHeader',
            'displayProductFlags',
            'displayAdminProductsExtra',
            'actionProductUpdate'
        ];

        parent::__construct();

        $this->displayName = $this->l('Product Flags');
        $this->description = $this->l('Add flags to your products');
    }


    /**
     * Hook for displaying on the store front
     */
    public function hookDisplayProductFlags($params)
    {
        $product = $params['product'];
        if (!isset($product) || !isset($product->id))
            return;

        $flags = $this->getProductFlagsForProduct($product->id);

        $this->context->smarty->assign([
            'product_flags' => $flags
        ]);

        return $this->display(__FILE__, 'views/front/productflags.tpl');
    }


    /**
     * Admin Product Page Tab
     */
    public function hookDisplayAdminProductsExtra($params)
    {
        $productId = Tools::getValue('id_product');
        if (!isset($productId))
            return "Could not load Product ID from Tools::getValue('id_product')";

        $allFlags = $this->getAllProductFlags();
        $flagsForProduct = $this->getProductFlagsForProduct($productId);

        foreach ($flagsForProduct as $myFlag) {
            foreach ($allFlags as $i => $globalFlag) {
                if ($globalFlag['id'] == $myFlag['id']) {
                    $allFlags[$i]['selected'] = true;
                    break;
                }
            }
        }

        $this->context->smarty->assign([
            'flag_list' => $allFlags,
            'selected_flags' => $flagsForProduct
        ]);
        return $this->display(__FILE__, 'views/admin/DisplayAdminProductsExtra.tpl');
    }


    /**
     * Admin Product Page Tab Post Process
     */
    public function hookActionProductUpdate()
    {
        if(Tools::isSubmit('submitAddproduct') || Tools::isSubmit('submitAddproductAndStay')){
            $product = Tools::getValue('id_product');
            $flags = Tools::getValue('selectedProductFlags');

            $table = _DB_PREFIX_.$this->table_name_products;

            if (isset($product) && isset($flags)) {
                // Delete all known flags for the product

                if (!Db::getInstance()->delete($this->table_name_products, 'id_product = '.$product)) {
                    error_log('Failed to remove all flags on Product.');
                }

                // Add new flags for the product
                foreach ($flags as $flag) {

                    if (!Db::getInstance()->insert($this->table_name_products, [
                        'id_flag' => $flag,
                        'id_product' => $product
                    ])) {
                        error_log("Failed to execute " . $sql);
                    }
                }
            }
        }

    }

    /**
     * Back Office Header
     */
    public function hookDisplayBackOfficeHeader()
    {
        $this->context->controller->addJS($this->_path . 'js/productflags.js', 'all');
    }



    /**
     * Find all of the product flags for a product
     */
    private function getProductFlagsForProduct($productId)
    {
        $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->ExecuteS('
            SELECT `t1`.`id_flag`, `t1`.`name`, `t2`.`id_lang`, `t2`.`html`, `t3`.`id_product`, `t1`.`active`
            FROM `'._DB_PREFIX_.$this->table_name.'` t1
            LEFT JOIN `'._DB_PREFIX_.$this->table_name_lang.'` t2 ON (t1.id_flag = t2.id_flag) AND (t2.id_lang = '.$this->context->language->id.')
            LEFT JOIN `'._DB_PREFIX_.$this->table_name_products.'` t3 ON (t3.id_flag = t1.id_flag)
            WHERE (t1.active = 1) AND (t3.id_product = '.$productId.')
        ');

        if (!$result)
            return [];

        $finalResult = [];

        foreach ($result as $flag) {
            $transformed = $this->transformResult($flag);
            $transformed['selected'] = true;
            array_push($finalResult, $transformed);
        }

        return $finalResult;
    }


    /**
     * Gets only the active product flags
     */
    public function getAllActiveProductFlags()
    {
        $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->ExecuteS('
            SELECT `t1`.`id_flag`, `t1`.`name`, `t2`.`id_lang`, `t2`.`html`, `t1`.`active`
            FROM `'._DB_PREFIX_.$this->table_name.'` t1
            LEFT JOIN `'._DB_PREFIX_.$this->table_name_lang.'` t2 ON (t1.id_flag = t2.id_flag) AND (t2.id_lang = '.$this->context->language->id.')
            WHERE `t1`.`active`=1
        ');

        if (!$result)
            return [];

        $finalResult = [];

        foreach ($result as $flag) {
            $finalFlag = $this->transformResult($flag);

            array_push($finalResult, $finalFlag);
        }

        return $finalResult;
    }


    /**
     * Gets all of the product flags
     */
    public function getAllProductFlags()
    {
        $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->ExecuteS('
            SELECT `t1`.`id_flag`, `t1`.`name`, `t2`.`id_lang`, `t2`.`html`, `t1`.`active`
            FROM `'._DB_PREFIX_.$this->table_name.'` t1
            LEFT JOIN `'._DB_PREFIX_.$this->table_name_lang.'` t2 ON (t1.id_flag = t2.id_flag) AND (t2.id_lang = '.$this->context->language->id.')
        ');

        if (!$result)
            return [];

        $finalResult = [];

        foreach ($result as $flag) {
            $finalFlag = $this->transformResult($flag);

            array_push($finalResult, $finalFlag);
        }

        return $finalResult;
    }

    /**
     * Returns a single product flag
     */
    public function getProductFlag($flagId)
    {
        if ($flagId == 'new') {
            return $this->transformResult(['id_flag' => 'new', 'name' => '', 'html' => '', 'active' => 1, 'id_lang' => $this->context->language->id]);
        }

        $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->ExecuteS('
            SELECT `t1`.`id_flag`, `t1`.`name`, `t2`.`id_lang`, `t2`.`html`, `t1`.`active`
            FROM `'._DB_PREFIX_.$this->table_name.'` t1
            LEFT JOIN `'._DB_PREFIX_.$this->table_name_lang.'` t2 ON (t1.id_flag = t2.id_flag) AND (t2.id_lang = '.$this->context->language->id.')
            WHERE (`t1`.`id_flag` = '.$flagId.')
        ');

        if (!$result)
            return false;

        $flag = $result[0];

        return $this->transformResult($flag);
    }


    /**
     * Returns the status of a product flag
     */
    public function getProductFlagStatus($flagId)
    {
        $flag = $this->getProductFlag($flagId);
        if (!$flag)
            return 1;

        return $flag['active'];
    }

    /**
     * Transform result into object
     */
    private function transformResult($result)
    {
        $tmp = [
            'id' => $result['id_flag'],
            'id_flag' => $result['id_flag'],
            'name' => $result['name'],
            'content' => $result['html'],
            'active' => $result['active'],
        ];

        $tmp['content_lang'] = [];
        $tmp['content_lang'][$result['id_lang']] = $result['html'];

        return $tmp;
    }


    public function install()
    {
        if ( ! parent::install()
            || ! $this->_createTabs()
            || ! $this->_createDatabases()
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
            || ! $this->_eraseDatabases()
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
    private function _createDatabases()
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
                `html` TEXT NOT NULL,
                PRIMARY KEY (  `id_flag`, `id_lang` )
                ) ENGINE =' ._MYSQL_ENGINE_;
        $sql3 = 'CREATE TABLE  `'._DB_PREFIX_.$this->table_name_products.'` (
                `id_flag` INT( 12 ),
                `id_product` INT( 12 ) NOT NULL,
                PRIMARY KEY (  `id_flag`,  `id_product`)
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
    private function _eraseDatabases()
    {
        if ( ! Db::getInstance()->Execute(
                'DROP TABLE `'._DB_PREFIX_.$this->table_name.'`'
            ) || ! Db::getInstance()->Execute(
                'DROP TABLE `'._DB_PREFIX_.$this->table_name_lang.'`'
            ) || ! Db::getInstance()->Execute(
                'DROP TABLE `'._DB_PREFIX_.$this->table_name_products.'`'
        )) {
            return false;
        }

        return true;
    }
}