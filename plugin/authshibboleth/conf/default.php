<?php
/**
 * Default settings for the authshibboleth plugin
 *
 * @author Ivan Novakov <ivan.novakov@debug.cz>
 * @link https://www.dokuwiki.org/devel:configuration#default_settings
 */
$conf = array(
    
    /*
     * auth plugin
     */
    
    // Rely on the Dokuwiki session
    'use_dokuwiki_session' => false,
    
    // The Shibboleth logout handler - /Shibboleth.sso/Logout
    'logout_handler' => 'Logout',
    
    // full URL
    'logout_handler_location' => '',
    
    // Logout return URL
    'logout_return_url' => '',
    
    // Shibboleth handler base
    'shibboleth_handler_base' => '/Shibboleth.sso/',
    
    // The variable, which contains the remote user identity
    'var_remote_user' => 'REMOTE_USER',
    
    // The variable, which contains user's display name
    'var_display_name' => 'cn',
    
    // The variable, which contains user's email
    'var_mail' => 'mail',
    
    // The variable, which contains user's groups
    //'var_groups' => '',
    
    // The variable, which contains user's entitlements
    'var_entitlement' => 'entitlement',
    
    'group_source_config' => array(),
    
    // Simple template for user display name construction
    'display_name_tpl' => null,
    
    //'superusers' => array(),
    // 'defaultgroup' => $conf['defaultgroup'],
    //'admingroup' => 'admin',
    
    //'customgroups' => false,
    //'customgroups_file' => DOKU_CONF . 'custom_groups.php',
    
    // Map entitlement to group name
    //'entitlement_groups' => array(),
    
    // Enable logging
    'log_enabled' => false,
    
    // Specify log file
    'log_file' => '',
    
    // Enable debugging - writes messages to the PHP's error log
    'debug' => false,
    
    
    /*
     * action plugin
     */
    'sso_handler' => 'Shibboleth.sso',
    'login_handler' => 'Login',
    'login_handler_location' => '',
    'target' => ''
);