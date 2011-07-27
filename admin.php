<?php

/**
 * file containing administrator function for the jfusion plugin
 *
 * PHP version 5
 *
 * @category   JFusion
 * @package    JFusionPlugins
 * @subpackage PrestaShop
 * @author     JFusion Team <webmaster@jfusion.org>
 * @copyright  2008 JFusion. All rights reserved.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU/GPL
 * @link       http://www.jfusion.org
 */


// no direct access
defined('_JEXEC') or die('Restricted access');


/**
 * JFusion Admin Class for PrestaShop
 * For detailed descriptions on these functions please check the model.abstractadmin.php
 *
 * @category   JFusion
 * @package    JFusionPlugins
 * @subpackage PrestaShop
 * @author     JFusion Team <webmaster@jfusion.org>
 * @copyright  2008 JFusion. All rights reserved.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU/GPL
 * @link       http://www.jfusion.org
 */


class JFusionAdmin_prestashop extends JFusionAdmin
{
    /**
     * returns the name of this JFusion plugin
     * @return string name of current JFusion plugin
     */
    function getJname()
    {
        return 'prestashop';
    }
    function getTablename() {
        return 'customer';
    }
    function loadSetup($storePath) {
        //check for trailing slash and generate file path
        if (substr($storePath, -1) == DS) {
            $myfile = $storePath . 'config/settings.inc.php';
        } else {
            $myfile = $storePath . DS . 'config/settings.inc.php';
        }
        if (($file_handle = @fopen($myfile, 'r')) === false) {
            JError::raiseWarning(500, JText::_('WIZARD_FAILURE') . ": $myfile " . JText::_('WIZARD_MANUAL'));
            $result = false;
            return $result;
        } else {
            //parse the file line by line to get only the config variables
			$config = array();
            $file_handle = fopen($myfile, 'r');
            while (!feof($file_handle)) {
                $line = fgets($file_handle);
                if (strpos($line, 'define(') === 0 && count($config) <= 8) {
                    /* extract the name and value, it was coded to avoid the use of eval() function */
                    // name
                    $vars_strt[0] = strpos($line, "'");
                    $vars_end[0] = strpos($line, "',");
                    $name = trim(substr($line, $vars_strt[0], $vars_end[0] - $vars_strt[0]), "'");     
                    // value
                    $vars_strt[1] = strpos($line, " '");
                    $vars_strt[1]++;
                    $vars_end[1] = strpos($line, "')");
                    $value = trim(substr($line, $vars_strt[1], $vars_end[1] - $vars_strt[1]), "'");
                    if($name == "_DB_TYPE_")
                    {
                     $value = strtolower($value);
                    }    
                    $config[$name] = $value;

                }
            }
	        fclose($file_handle);
	    }
        return $config;
	}
	
	function setupFromPath($storePath) {
	    $config = JFusionAdmin_prestashop::loadSetup($storePath);
        if (!empty($config)) {
            //save the parameters into array
            $params = array();
            $params['database_host'] = $config['_DB_SERVER_'];
            $params['database_name'] = $config['_DB_NAME_'];
            $params['database_user'] = $config['_DB_USER_'];
            $params['database_password'] = $config['_DB_PASSWD_'];
            $params['database_prefix'] = $config['_DB_PREFIX_'];
            $params['database_type'] = $config['_DB_TYPE_'];
            $params['source_path'] = $storePath;
            $params['cookie_key'] = $config['_COOKIE_KEY_'];
			$params['usergroup'] = 1;
			//return the parameters so it can be saved permanently
            return $params;
        }
    }
    function getUserList() {
        //getting the connection to the db
        $db = JFusionFactory::getDatabase($this->getJname());
		$params = JFusionFactory::getParams($this->getJname());
		$tbp = $params->get('database_prefix');
        $query = "SELECT email as email, id_customer as userid from " . $tbp . "customer WHERE email NOT LIKE '' and email IS NOT null";
        $db->setQuery($query);
        //getting the results
        $userlist = $db->loadObjectList();
        return $userlist;
    }
    function getUserCount() {
        //getting the connection to the db
        $db = JFusionFactory::getDatabase($this->getJname());
		$params = JFusionFactory::getParams($this->getJname());
		$tbp = $params->get('database_prefix');
        $query = "SELECT count(*) from " . $tbp . "customer WHERE email NOT LIKE '' and email IS NOT null";
        $db->setQuery($query);
        //getting the results
        $no_users = $db->loadResult();
        return $no_users;
    }
    function getUsergroupList() {
        //get the connection to the db
        $db = JFusionFactory::getDatabase($this->getJname());
		$params = JFusionFactory::getParams($this->getJname());
		$tbp = $params->get('database_prefix');
        $query = "SELECT value FROM " . $tbp . "configuration WHERE name IN ('PS_LANG_DEFAULT');";
        $db->setQuery($query);
        //getting the default language to load groups
        $default_language = $db->loadResult();
        //prestashop uses two group categories which are employees and customers, each have there own groups to access either the front or back end
        /*
          Customers only for this plugin
        */
        $query = "SELECT id_group as id, name as name from " . $tbp . "group_lang WHERE id_lang IN ('" . $default_language . "');";
        $db->setQuery($query);
        //getting the results
		$result = $db->loadObjectList();
        return $result;
    }
    function getDefaultUsergroup() {
	    $db = JFusionFactory::getDatabase($this->getJname());
	    $params = JFusionFactory::getParams($this->getJname());
		$tbp = $params->get('database_prefix');
        //we want to output the usergroup name
        $query = "SELECT value FROM " . $tbp . "configuration WHERE name IN ('PS_LANG_DEFAULT');";
        $db->setQuery($query);
        //getting the default language to load groups
        $default_language = $db->loadResult();
        $query = "SELECT name as name from " . $tbp . "group_lang WHERE id_lang IN ('" . $default_language . "') AND id_group IN ('1')";
        $db->setQuery($query);
		return $db->loadResult();
    }
    function allowRegistration() {
        //you cannot disable registration
            $result = true;
            return $result;
    }
	function allowEmptyCookiePath(){
		return true;
	}
	function allowEmptyCookieDomain(){
		return true;
	}
}
