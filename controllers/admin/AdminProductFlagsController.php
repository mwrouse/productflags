<?php

if ( ! defined('_TB_VERSION_')) {
    exit;
}

class AdminProductFlagsController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        $this->show_toolbar = true;
        $this->identifier = 'id_flag';
        $this->table = 'productflags';
        $this->className = 'productflags';

        parent::__construct();
    }

    public function initPageHeaderToolbar()
    {
        if (empty($this->display) || $this->display =='list') {
            $this->page_header_toolbar_btn['new_flag'] = [
                'href' => static::$currentIndex.'&configure=&id_flag=new&updateproductflags&token='.$this->token,
                'desc' => $this->l('Add New Flag', null, null, false),
                'icon' => 'process-icon-new',
            ];
        }

        parent::initPageHeaderToolbar();
    }

    /**
     * List of all the product flags on the landing page
     */
    public function renderList()
    {
        $flags = $this->module->getAllProductFlags();
        $content = '';

        if (!$flags)
            return $content;

        $fieldsList = [
            'id_flag'  => [
                'title'   => 'ID',
                'align'   => 'center',
                'class'   => 'fixed-width-xs',
            ],
            'name'      => [
                'title'   => $this->l('Name'),
            ],
            'active'    => [
                'title'   => $this->l('Status'),
                'active'  => 'status',
                'type'    => 'bool',
            ]
        ];

        $helper = new HelperList();
        $helper->shopLinkType = '';
        $helper->simple_header = true;
        $helper->actions = ["edit", "delete"];
        $helper->show_toolbar = false;
        $helper->module = $this;
        $helper->listTotal = count($flags);
        $helper->identifier = 'id_flag';
        $helper->position_identifier = 'id_flag';
        $helper->title = "Product Flags";
        $helper->orderBy = 'name';
        $helper->orderWay = 'ASC';
        $helper->table = $this->table;
        $helper->token = Tools::getAdminTokenLite('AdminProductFlags');
        $helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;

        $content .= $helper->generateList($flags, $fieldsList);

        return $content;
    }


    /**
     * Form for editing a product flag
     */
    public function renderForm()
    {
        $inputs[] = [
            'type'   => 'switch',
            'label'  => $this->l("Active"),
            'name'   => 'active',
            'values' => [
                [
                    'id'    => 'active_on',
                    'value' => 1,
                ],
                [
                    'id'    => 'active_off',
                    'value' => 0,
                ],
            ]
        ];
        $inputs[] = [
            'type'  => 'text',
            'label' => $this->l('Flag Name'),
            'name'  => 'name',
        ];
        $inputs[] = [
            'type'  => 'textarea',
            'label' => $this->l('Content'),
            'name'  => 'content_lang',
            'lang'  => true,
            'autoload_rte' => true,
        ];
        $inputs[] = [
            'type'   => 'switch',
            'label'  => $this->l("Bottom of Description"),
            'name'   => 'bottom_of_desc',
            'values' => [
                [
                    'id'    => 'bottom_of_desc_on',
                    'value' => 1,
                ],
                [
                    'id'    => 'bottom_of_desc_off',
                    'value' => 0,
                ],
            ]
        ];

        if ($this->display == 'edit') {
            $inputs[] = [
                'type' => 'hidden',
                'name' => 'id_flag'
            ];
            $title = $this->l('Edit Product Flag');
            $action = 'submitEditProductFlag';
            $this->fields_value = $this->module->getProductFlag(Tools::getValue('id_flag'));
        }
        else {
            $title = $this->l('New Product Flag');
            $action = 'submitAddProductFlag';
        }

        $this->fields_form = [
            'legend' => [
                'title' => $title,
                'icon'  => 'icon-cogs',
            ],
            'input' => $inputs,
            'buttons' => [
                'save-and-stay' => [
                    'title' => $this->l('Save and Stay'),
                    'class' => 'btn btn-default pull-right',
                    'name' => $action.'AndStay',
                    'icon' => 'process-icon-save',
                    'type' => 'submit'
                ]

            ],
            'submit' => [
                'title' => $this->l('Save'),
                'class' => 'btn btn-default pull-right',
                'name'  => $action,
            ],

        ];

        return parent::renderForm();
    }

    /**
     * When the edit/new form is submitted
     */
    public function postProcess()
    {
        $flagId = Tools::getValue('id_flag');
        if (Tools::isSubmit('submitEditProductFlag') || Tools::isSubmit('submitEditProductFlagAndStay')) {
            if ($flagId == 'new')
                $this->processAdd();
            else
                $this->processUpdate();
        }
        else if (Tools::isSubmit('status'.$this->table)) {
            $this->toggleStatus();
        }
        else if (Tools::isSubmit('delete'.$this->table)) {
            $this->processDelete();
        }
    }


    /**
     * Save the updated values
     */
    public function processUpdate()
    {
        $flagId = Tools::getValue('id_flag');
        $saveAndStay = Tools::isSubmit('submitEditProductFlagAndStay');

        $name = Tools::getValue('name');

        if (!$name || !Validate::isGenericName($name)) {
            $this->_errors[] = $this->l('Invalid Name');
        }
        else {
            $active = Tools::getValue('active');
            $bottom_of_desc = Tools::getValue('bottom_of_desc');

            $result = Db::getInstance()->update($this->module->table_name,
                ['name' => pSQL($name),'active' => $active, 'bottom_of_desc' => $bottom_of_desc],
                'id_flag ='. (int)$flagId
            );

            if (!$result) {
                $this->_errors[] = $this->l('Error while updating Product Flag Name and Status');
            }
            else {
                foreach ($this->getLanguages() as $lang) {
                    $content = Tools::getValue('content_lang_' . $lang['id_lang']);

                    $isLangAdded = Db::getInstance()->getValue('SELECT id_flag FROM '._DB_PREFIX_.$this->module->table_name_lang.' WHERE (id_flag='.(int)$flagId.' AND id_lang='.$lang['id_lang'].')');
                    if (!$isLangAdded) {
                        Db::getInstance()->insert(
                            $this->module->table_name_lang,
                            [
                                'id_flag' => (int)$flagId,
                                'id_lang' => $lang['id_lang'],
                                'html' => pSQL($content, true)
                            ]
                        );
                    }
                    else {
                        $result = Db::getInstance()->update($this->module->table_name_lang,
                            ['html' => pSQL($content, true)],
                            'id_flag ='. (int)$flagId . ' AND id_lang =' . $lang['id_lang']
                        );

                        if (!$result) {
                            $this->_errors[] = $this->l('Error while updating Product Flag Content');
                        }
                    }
                }

            }

            if (empty($this->_errors)) {
                if (!$saveAndStay) {
                    $this->redirect_after = static::$currentIndex.'&conf=4&token='.$this->token;
                }
                else {
                    $this->redirect_after = static::$currentIndex . '&configure=&id_flag='. $flagId . '&updateproductflags&token=' . $this->token;
                }
            }
        }

    }


    /**
     * Save a new product flag
     */
    public function processAdd()
    {
        $name = Tools::getValue('name');
        $saveAndStay = Tools::isSubmit('submitEditProductFlagAndStay');

        $flagId = null;

        if (!$name || !Validate::isGenericName($name)) {
            $this->_errors[] = $this->l('Invalid Name');
        }
        else {
            $active = Tools::getValue('active');
            $bottom_of_desc = Tools::getValue('bottom_of_desc');

            $result = Db::getInstance()->insert(
                $this->module->table_name,
                ['name' => pSQL($name), 'active' => $active, 'bottom_of_desc' => $bottom_of_desc]
            );

            if (!result) {
                $this->_errors[] = $this->l("Error while adding new product flag, please try again.");
            }
            else {
                $flagId = Db::getInstance()->Insert_ID();

                foreach ($this->getLanguages() as $lang) {
                    $content = Tools::getValue('content_lang_' . $lang['id_lang']);

                    $result = Db::getInstance()->insert(
                        $this->module->table_name_lang,
                        ['id_flag' => $flagId, 'id_lang' => $lang['id_lang'], 'html' => pSQL($content, true)]
                    );

                    if (!$result) {
                        $this->_errors[] = $this->l('Error when adding new product flag content');
                    }
                }
            }
        }


        if (empty($this->_errors)) {
            if (!$saveAndStay && $flagId != null) {
                $this->redirect_after = static::$currentIndex.'&conf=4&token='.$this->token;
            }
            else {
                // Have to go to the edit page now
                $this->redirect_after = static::$currentIndex . '&configure=&id_flag='. $flagId . '&updateproductflags&token=' . $this->token;
            }
        }

    }

    /**
     * Toggle the status of a product flag from the main page
     */
    public function toggleStatus()
    {
        $flagId = (int)Tools::getValue('id_flag');

        Db::getInstance()->update(
            $this->module->table_name,
            ['active' => !$this->module->getProductFlagStatus($flagId)],
            'id_flag = '. $flagId
        );
    }

    // Remove a product flag
    public function processDelete()
    {
        $flagId = (int)Tools::getValue('id_flag');

        Db::getInstance()->delete($this->module->table_name, 'id_flag = '. $flagId);
        Db::getInstance()->delete($this->module->table_name_lang, 'id_flag ='.$flagId);
        Db::getInstance()->delete($this->module->table_name_products, 'id_flag='.$flagId);

        $this->redirect_after = static::$currentIndex.'&conf=1&token='.$this->token;
    }


    public function renderView()
    {
        $this->tpl_view_vars['object'] = $this->loadObject();

        return parent::renderView();
    }
}