<?php
/**
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License version 3.0
* that is bundled with this package in the file LICENSE.txt
* It is also available through the world-wide-web at this URL:
* https://opensource.org/licenses/AFL-3.0
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade this module to a newer
* versions in the future. If you wish to customize this module for your needs
* please refer to CustomizationPolicy.txt file inside our module for more information.
*
* @author Adam DOUDEAU (Hotfirenet)
* @copyright Since 2016 Domadoo
*/

class DomadooInactivityMail extends ObjectModel
{
    public $id_inactivity;
    public $id_customer;
    public $nombre_relance;
    public $date_relance;

    public static $definition = array(
        'table'     => 'domadoo_inactivity_mail',
        'primary'   => 'id_inactivity',
        'fields'    => array(
            'id_inactivity'     => array('type' => self::TYPE_INT,    'validate' => 'isInt'),
            'id_customer'       => array('type' => self::TYPE_INT,    'validate' => 'isInt'),
            'nombre_relance'    => array('type' => self::TYPE_INT,    'validate' => 'isInt'),
            'date_relance'      => array('type' => self::TYPE_DATE,   'validate' => 'isDate'),
        ),
    );

    public function getInactivityByIdCustomer($id){
        return Db::getInstance()->getRow(' 
            SELECT *
	        FROM `' . _DB_PREFIX_ . 'domadoo_inactivity_mail` WHERE id_customer = ' . $id
        );
    }

    public static function deleteByIdCustomer($id){
        Db::getInstance()->execute(
            '
            DELETE
            FROM `' . _DB_PREFIX_ . 'domadoo_inactivity_mail`
            where id_customer = ' . $id
        );
    }

}