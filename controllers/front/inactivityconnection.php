<?php

class UserInactivityInactivityconnectionModuleFrontController extends ModuleFrontController{
    public function initContent(){
        parent::initContent();

        $this->setTemplate('module:userinactivity/views/templates/front/inactivityConnection.tpl');
    }

    public function postProcess(){
        $id_customer = $this->context->customer->id;
        if(Tools::getValue('supprimer')){
            $customer = new Customer($id_customer);
            $customer->delete();
            Tools::redirect('https://installateurs.domadoo.fr/fr/');
        }
        if(Tools::getValue('reactiver')){
            DomadooInactivityMail::deleteByIdCustomer($id_customer);
            Tools::redirect('https://installateurs.domadoo.fr/fr/mon-compte');
        }
    }
}