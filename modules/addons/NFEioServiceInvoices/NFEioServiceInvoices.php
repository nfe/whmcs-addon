<?php

/**
 * WHMCS SDK Sample Addon Module
 *
 * An addon module allows you to add additional functionality to WHMCS. It
 * can provide both client and admin facing user interfaces, as well as
 * utilise hook functionality within WHMCS.
 *
 * This sample file demonstrates how an addon module for WHMCS should be
 * structured and exercises all supported functionality.
 *
 * Addon Modules are stored in the /modules/addons/ directory. The module
 * name you choose must be unique, and should be all lowercase, containing
 * only letters & numbers, always starting with a letter.
 *
 * Within the module itself, all functions must be prefixed with the module
 * filename, followed by an underscore, and then the function name. For this
 * example file, the filename is "addonmodule" and therefore all functions
 * begin "addonmodule_".
 *
 * For more information, please refer to the online documentation.
 *
 * @see https://developers.whmcs.com/addon-modules/
 *
 * @copyright Copyright (c) WHMCS Limited 2017
 * @license   http://www.whmcs.com/license/ WHMCS Eula
 */

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

if (!defined('DS')) {
    define('DS', DIRECTORY_SEPARATOR);
}


use WHMCS\Database\Capsule;
use NFEioServiceInvoices\Admin\AdminDispatcher;
use NFEioServiceInvoices\Client\ClientDispatcher;

/**
 * Define addon module configuration parameters.
 *
 * Includes a number of required system fields including name, description,
 * author, language and version.
 *
 * Also allows you to define any configuration parameters that should be
 * presented to the user when activating and configuring the module. These
 * values are then made available in all module function calls.
 *
 * Examples of each and their possible configuration parameters are provided in
 * the fields parameter below.
 *
 * @return array
 */
function NFEioServiceInvoices_config()
{
    include_once __DIR__ . DS . 'Loader.php';
    new \NFEioServiceInvoices\Loader();
    return \NFEioServiceInvoices\Addon::config();
}

/**
 * Activate.
 *
 * Called upon activation of the module for the first time.
 * Use this function to perform any database and schema modifications
 * required by your module.
 *
 * This function is optional.
 *
 * @return array Optional success/failure message
 */
function NFEioServiceInvoices_activate()
{

    include_once __DIR__ . DS . 'Loader.php';
    new NFEioServiceInvoices\Loader();
    return \NFEioServiceInvoices\Addon::activate();
}

/**
 * Deactivate.
 *
 * Called upon deactivation of the module.
 * Use this function to undo any database and schema modifications
 * performed by your module.
 *
 * This function is optional.
 *
 * @return array Optional success/failure message
 */
function NFEioServiceInvoices_deactivate()
{
    include_once __DIR__ . DS . 'Loader.php';
    new NFEioServiceInvoices\Loader();
    return \NFEioServiceInvoices\Addon::deactivate();
}

/**
 * Upgrade.
 *
 * Called the first time the module is accessed following an update.
 * Use this function to perform any required database and schema modifications.
 *
 * This function is optional.
 *
 * @return void
 */
function NFEioServiceInvoices_upgrade($vars)
{
    include_once __DIR__ . DS . 'Loader.php';
    new NFEioServiceInvoices\Loader();
    \NFEioServiceInvoices\Addon::upgrade($vars);
}

/**
 * Admin Area Output.
 *
 * Called when the addon module is accessed via the admin area.
 * Should return HTML output for display to the admin user.
 *
 * This function is optional.
 *
 * @see NFEioServiceInvoices\Admin\Controller@index
 *
 * @return string
 */
function NFEioServiceInvoices_output($vars)
{
    include_once __DIR__ . DS . 'Loader.php';
    new \NFEioServiceInvoices\Loader();
    \NFEioServiceInvoices\Addon::I()->isAdmin(true);
    \NFEioServiceInvoices\Addon::I(false, $vars);
    //exit();

    // Dispatch and handle request here. What follows is a demonstration of one
    // possible way of handling this using a very basic dispatcher implementation.

    $action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';
    $dispatcher = new AdminDispatcher();
    $response = $dispatcher->dispatch($action, $vars);
    echo $response;
}

/**
 * Admin Area Sidebar Output.
 *
 * Used to render output in the admin area sidebar.
 * This function is optional.
 *
 * @param array $vars
 *
 * @return string
 */
/*function NFEioServiceInvoices_sidebar($vars)
{
    // Get common module parameters
    // $modulelink = $vars['modulelink'];
    // $version = $vars['version'];
    // $_lang = $vars['_lang'];
    //
    // // Get module configuration parameters
    // $configTextField = $vars['Text Field Name'];
    // $configPasswordField = $vars['Password Field Name'];
    // $configCheckboxField = $vars['Checkbox Field Name'];
    // $configDropdownField = $vars['Dropdown Field Name'];
    // $configRadioField = $vars['Radio Field Name'];
    // $configTextareaField = $vars['Textarea Field Name'];
    //
    // $sidebar = '<p>Sidebar output HTML goes here</p>';
    // return $sidebar;
}*/

/**
 * Client Area Output.
 *
 * Called when the addon module is accessed via the client area.
 * Should return an array of output parameters.
 *
 * This function is optional.
 *
 * @see NFEioServiceInvoices\Client\Controller@index
 *
 * @return array
 */
function NFEioServiceInvoices_clientarea($vars)
{
    // Get common module parameters
    $modulelink = $vars['modulelink']; // eg. index.php?m=addonmodule
    $version = $vars['version']; // eg. 1.0
    $_lang = $vars['_lang']; // an array of the currently loaded language variables

    // Dispatch and handle request here. What follows is a demonstration of one
    // possible way of handling this using a very basic dispatcher implementation.

    $action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';

    $dispatcher = new ClientDispatcher();
    return $dispatcher->dispatch($action, $vars);
}
