
<?php
class UserInactivityCronModuleFrontController extends ModuleFrontController
{
    /** @var bool If set to true, will be redirected to authentication page */
    public $auth = false;

    /** @var UserInactivity $module */
    public $module;


    /** @var bool */

    public function initContent()
    {
        parent::initContent();

        if(Tools::getValue('token') != Configuration::get('USERINACTIVITY_BOT_TOKEN')){
            Tools::redirect('pagenotfound');
        }
        $this->module->updateInactivity();
    }
}
