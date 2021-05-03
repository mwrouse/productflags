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

        parent::__construct();
    }

    public function initPageHeaderToolbar()
    {
        if (empty($this->display) || $this->display =='list') {
            $this->page_header_toolbar_btn['new_block'] = [
                'href' => static::$currentIndex.'&addproductflag&token='.$this->token,
                'desc' => $this->l('Add New Flag', null, null, false),
                'icon' => 'process-icon-new',
            ];
        }

        parent::initPageHeaderToolbar();
    }
}