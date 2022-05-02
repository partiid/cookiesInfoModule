<?php
/**
* 2007-2022 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author    PrestaShop SA <contact@prestashop.com>
*  @copyright 2007-2022 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

if (!defined('_PS_VERSION_')) {
    exit;
}

class Cookiesinfo extends Module
{
    protected $config_form = false;

    public function __construct()
    {
        $this->name = 'cookiesinfo';
        $this->tab = 'i18n_localization';
        $this->version = '1.0.0';
        $this->author = 'Supportal.pl';
        $this->need_instance = 1;
        $this->path = _MODULE_DIR_.$this->name;


        /**
         * Set $this->bootstrap to true if your module is compliant with bootstrap (PrestaShop 1.6)
         */
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Cookies info ');
        $this->description = $this->l('Displays a banner on the bottom of the store with cookies information ');

        $this->confirmUninstall = $this->l('Czy na pewno chcesz odinstalować?');

        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
    }

    /**
     * Don't forget to create update methods if needed:
     * http://doc.prestashop.com/display/PS16/Enabling+the+Auto-Update
     */
    public function install()
    {
        Configuration::updateValue('COOKIESINFO_LIVE_MODE', false);

        return parent::install() &&
            $this->registerHook('header') &&
            $this->registerHook('backOfficeHeader') &&
            $this->registerHook('displayTop') && 
            $this->registerHook('displayWrapperBottom') &&
            $this->registerHook('displayCookiesInfo');
    }

    public function uninstall()
    {
        Configuration::deleteByName('COOKIESINFO_TEXT');
        Configuration::deleteByName('COOKIESINFO_LINK');

        return parent::uninstall();
    }

    /**
     * Load the configuration form
     */
    public function getContent()
    {
        /**
         * If values have been submitted in the form, process.
         */

         $output = ''; 
        if (((bool)Tools::isSubmit('submitCookiesinfoModule')) == true) {
            $this->postProcess();

            $output .= $this->displayConfirmation($this->l('Settings updated'));

        }

        $this->context->smarty->assign('module_dir', $this->_path);

        $output .= $this->context->smarty->fetch($this->local_path.'views/templates/admin/configure.tpl');

        return $output.$this->renderForm();
    }

    /**
     * Create the form that will be displayed in the configuration of your module.
     */
    protected function renderForm()
    {
        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitCookiesinfoModule';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            .'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFormValues(), /* Add values for your inputs */
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );

        return $helper->generateForm(array($this->getConfigForm()));
    }

    /**
     * Create the structure of your form.
     */
    protected function getConfigForm()
    {
       

        return array(
            'form' => array(
                'legend' => array(
                'title' => $this->l('Settings'),
                'icon' => 'icon-cogs',
                ),
                'input' => array(
                   array(
                       'type' => 'textarea',
                       'label' => $this->l('Cookies information'),
                       'desc' => $this->l('Enter the text that will be displayed in the banner'),
                       'name' => 'COOKIESINFO_TEXT',
                       'autoload_rte' => 'rte'
                   ),
                   
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                ),
            ),
        );
    }

    /**
     * Set values for the inputs.
     */
    protected function getConfigFormValues()
    {
        

        return array(
                'COOKIESINFO_TEXT' => Configuration::get('COOKIESINFO_TEXT' ),
                'COOKIESINFO_LINK' => Configuration::get('COOKIESINFO_LINK')
        );
    }

    /**
     * Save form data.
     */
    protected function postProcess()
    {
        $form_values = $this->getConfigFormValues();

        foreach (array_keys($form_values) as $key) {
            if($key == 'COOKIESINFO_TEXT'){
                Configuration::updateValue($key, Tools::getValue($key), true);
            } else {
                Configuration::updateValue($key, Tools::getValue($key));

            }
        }
    }

    /**
    * Add the CSS & JavaScript files you want to be loaded in the BO.
    */
    public function hookBackOfficeHeader()
    {
        if (Tools::getValue('module_name') == $this->name) {
            $this->context->controller->addJS($this->_path.'views/js/back.js');
            $this->context->controller->addCSS($this->_path.'views/css/back.css');
        }
    }

    /**
     * Add the CSS & JavaScript files you want to be added on the FO.
     */
    public function hookHeader()
    {
        $this->context->controller->addJS($this->_path.'/views/js/front.js');
        $this->context->controller->addCSS($this->_path.'/views/css/front.css');
    }

    

    public function hookDisplayCookiesInfo()
    {
        $cookiesInfo = Configuration::get('COOKIESINFO_TEXT');
        $cookiesLink = Configuration::get('COOKIESINFO_LINK'); 

        $cmsPageLink = $this->getCmsPageLink($cookiesLink); 

        $this->context->smarty->assign(
            array(
                'text' => $cookiesInfo,
                'link' => $cmsPageLink
            )
        );
        //set the template for hook 
        Media::addJSDef(
            array(
                'cookiesLink' => $cmsPageLink
            )
        );

        return $this->display($this->path, '/views/templates/front/banner.tpl');
    }


    private function getCmsPageLink($id_cms)
    {
        $cms = new CMS($id_cms);
        return $this->context->link->getCMSLink($cms, $cms->link_rewrite, true);
    }
    private function getCmsPages()
    {
        $cmsPages = CMS::listCms($this->context->language->id);
        
        $pages = array(); 
        foreach($cmsPages as $page) {
            $cmsPage = array (
                'id' => $page['id_cms'],
                'value' => $page['id_cms'],
                'label' => $page['meta_title']
            );
            array_push($pages, $cmsPage);
            
        }
        return $pages; 

    }
    
    
}
