<?php
/**
* 2007-2024 PrestaShop
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
*  @copyright 2007-2024 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once dirname(__FILE__).'/classes/DomadooInactivityLog.php';
require_once dirname(__FILE__).'/classes/DomadooInactivityMail.php';

class UserInactivity extends Module
{
    protected $config_form = false;

    public function __construct()
    {
        $this->name = 'userinactivity';
        $this->tab = 'administration';
        $this->version = '1.0.0';
        $this->author = 'Domadoo';
        $this->need_instance = 0;

        /**
         * Set $this->bootstrap to true if your module is compliant with bootstrap (PrestaShop 1.6)
         */
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('userInactivity');
        $this->description = $this->l('Plugin pour supprimer les users inactifs');

        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => '8.0');
    }

    /**
     * Don't forget to create update methods if needed:
     * http://doc.prestashop.com/display/PS16/Enabling+the+Auto-Update
     */
    public function install()
    {
        include(dirname(__FILE__).'/sql/install.php');

        return parent::install() &&
            $this->registerHook('header') &&
            $this->registerHook('actionAuthentication') &&
            $this->registerHook('displayBackOfficeHeader');
    }

    public function uninstall()
    {
        Configuration::deleteByName('USERINACTIVITY_LIVE_MODE');

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
        if (((bool)Tools::isSubmit('submitUserInactivityModule')) == true) {
            $this->postProcess();
        }

        $this->context->smarty->assign('module_dir', $this->_path);

        $output = $this->context->smarty->fetch($this->local_path.'views/templates/admin/configure.tpl');

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
        $helper->submit_action = 'submitUserInactivityModule';
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
                        'type' => 'switch',
                        'label' => $this->l('Live mode'),
                        'name' => 'USERINACTIVITY_LIVE_MODE',
                        'is_bool' => true,
                        'desc' => $this->l('Use this module in live mode'),
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('Disabled')
                            )
                        ),
                    ),
                    array(
                        'col' => 3,
                        'type' => 'text',
                        'prefix' => '<i class="icon icon-envelope"></i>',
                        'desc' => $this->l('Enter a valid email address'),
                        'name' => 'USERINACTIVITY_ACCOUNT_EMAIL',
                        'label' => $this->l('Email'),
                    ),
                    array(
                        'type' => 'password',
                        'name' => 'USERINACTIVITY_ACCOUNT_PASSWORD',
                        'label' => $this->l('Password'),
                    ),
                    array(
                        'col' => 3,
                        'type' => 'text',
                        'prefix' => '<i class="icon icon-key"></i>',
                        'desc' => $this->l('Enter a private key, for exemple').' : ' .Tools::passwdGen(26),
                        'name' => 'USERINACTIVITY_BOT_TOKEN',
                        'label' => $this->l('Bot Token'),
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
            'USERINACTIVITY_LIVE_MODE' => Configuration::get('USERINACTIVITY_LIVE_MODE', true),
            'USERINACTIVITY_ACCOUNT_EMAIL' => Configuration::get('USERINACTIVITY_ACCOUNT_EMAIL', 'contact@prestashop.com'),
            'USERINACTIVITY_ACCOUNT_PASSWORD' => Configuration::get('USERINACTIVITY_ACCOUNT_PASSWORD', null),
        );
    }

    /**
     * Save form data.
     */
    protected function postProcess()
    {
        $form_values = $this->getConfigFormValues();

        foreach (array_keys($form_values) as $key) {
            Configuration::updateValue($key, Tools::getValue($key));
        }
    }

    /**
    * Add the CSS & JavaScript files you want to be loaded in the BO.
    */
    public function hookDisplayBackOfficeHeader()
    {
        if (Tools::getValue('configure') == $this->name) {
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

    public function hookActionAuthentication($params){
        //DomadooInactivityMail::getInactivityByIdCustomer($this->context->customer->id)
        if(true){
            Tools::redirect('https://installateurs.domadoo.fr/fr/module/userinactivity/inactivityconnection');
        }
    }

    public function updateInactivity(){
        $new_inactif = Db::getInstance()->executeS(
            'SELECT sub.* FROM
            (SELECT A.id_guest, B.id_customer, MAX(C.date_add) as last_connection FROM ps_guest A
            JOIN ps_customer B on A.id_customer = B.id_customer
            JOIN ps_connections C on A.id_guest = C.id_guest
            GROUP BY B.id_customer) sub
            WHERE DATEDIFF(now(), last_connection) >= 730
            AND NOT sub.id_customer IN (SELECT id_customer FROM ps_domadoo_inactivity_mail)'
        );
        foreach ($new_inactif as $inactif) {
            $row = new DomadooInactivityMail();
            $row->id_customer  = $inactif['id_customer'];
            $row->date_relance = date('Y-m-d H:m:s');
            $row->save();

            $log = new DomadooInactivityLog();
            $log->id_customer  = $inactif['id_customer'];
            $log->date_relance = date('Y-m-d H:m:s');
            $log->save();

            //$this->sendMail($inactif['id_customer']);
        }

        $list_delete = Db::getInstance()->executeS(
            'SELECT id_customer 
            FROM ps_domadoo_inactivity_mail
            WHERE nombre_relance = 3'
        );
        foreach ($list_delete as $delete) {
            $customer = new Customer($delete['id_customer']);
            $customer->delete();
            DomadooInactivityMail::deleteByIdCustomer($delete['id_customer']);
        }

        $list_update = Db::getInstance()->executeS(
            'SELECT *  FROM ps_domadoo_inactivity_mail
            WHERE (nombre_relance = 0 AND DATEDIFF(now(), date_relance) >=31)
            OR (nombre_relance = 1 AND DATEDIFF(now(), date_relance) >=7)
            OR (nombre_relance = 2 AND DATEDIFF(now(), date_relance) >=1)'
        );
        foreach ($list_update as $update) {
            $customer = new DomadooInactivityMail($update['id_inactivity']);
            $customer->nombre_relance = $update['nombre_relance'] + 1;
            $customer->date_relance = date('Y-m-d H:m:s');
            $customer->save();

            $log = new DomadooInactivityLog();
            $log->id_customer  = $update['id_customer'];
            $log->nombre_relance = $update['nombre_relance'] + 1;
            $log->date_relance = date('Y-m-d H:m:s');
            $log->save();
            //$this->sendMail($update['id_customer']);
        }
    }

    public function sendMail($id_customer){
        $customer = new Customer($id_customer);
        $date_first_mail = DomadooInactivityLog::getDateFirstMailByIdCustomer($id_customer);
        $date_suppresion = date('Y-m-d', strtotime($date_first_mail['date_relance']. ' + 40 days'));
        Mail::Send(
            (int)(Configuration::get('PS_LANG_DEFAULT')), // defaut language id
            'reply_msg', // email template file to be use
            'Compte inactif', // email subject
            array(
                '{email}'       => Configuration::get('PS_SHOP_EMAIL'), // sender email address
                '{firstname}'   => $customer->firstname,
                '{lastname}'    => $customer->lastname,
                '{reply}'       => 'Votre compte est inactif depuis plus de 2 ans, si vous ne voulez pas qu\'il soit supprimer, veuillez vous connectez avant le '.$date_suppresion, // email content
                '{link}'        => 'https://installateurs.domadoo.fr/fr/connexion?back=my-account',
            ),
            'adam.doudeau2@gmail.com', // receiver email address
            NULL, //receiver name
            NULL, //from email address
            NULL  //from name
        );
    }
}
