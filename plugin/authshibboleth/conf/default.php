<?php
/**
 * Default settings for the authshibboleth plugin.
 *
 * @author  Ivan Novakov http://novakov.cz/
 * @license http://debug.cz/license/bsd-3-clause BSD 3 Clause 
 * @link https://github.com/ivan-novakov/dokuwiki-shibboleth-auth Homepage
 * 
 * @link https://www.dokuwiki.org/devel:configuration#default_settings Documentation
 */
$conf = array(
    
    /*
     * auth plugin (auth.php)
     */
    
    // Rely on the Dokuwiki session
    'use_dokuwiki_session' => true,
    
    // The Shibboleth logout handler - /Shibboleth.sso/Logout
    'logout_handler' => 'Logout',
    
    // full URL
    'logout_handler_location' => null,
    
    // Logout return URL
    'logout_return_url' => null,
    
    // Shibboleth handler base
    'shibboleth_handler_base' => '/Shibboleth.sso/',
    
    // The variable, which contains the remote user identity
    'var_remote_user' => 'REMOTE_USER',
    
    // The variable, which contains user's display name
    'var_display_name' => 'cn',
    
    // The variable, which contains user's email
    'var_mail' => 'mail',
    
    // The name of the variable containing the Shibboleth session ID
    'var_shib_session_id' => 'Shib-Session-ID',
    
    // Configuration of group sources
    'group_source_config' => array(),
    
    // Simple template for user display name construction
    'display_name_tpl' => null,
    
    // Enable logging
    'log_enabled' => false,
    
    // Specify log file
    'log_file' => null,
    
    // Set the logging priority - DEBUG=7, INFO=6, ERR=3
    'log_priority' => 7,
    
    // Enable debugging - writes messages to the PHP's error log
    'log_to_php' => false,
    
    
    /*
     * action plugin (action.php)
     */
    
    // The name of the login handler to be used.
    'login_handler' => 'Login',
    
    // Full URL specifying the login handler, for example: https://sp.example.org/Shibboleth.sso/Login
    'login_handler_location' => null,
    
    // Target page to redirect to, after successful login.
    'login_target' => null
);