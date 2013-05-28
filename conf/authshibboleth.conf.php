<?php
/**
 * Sample configuration file.
 * 
 * @author  Ivan Novakov http://novakov.cz/
 * @license http://debug.cz/license/bsd-3-clause BSD 3 Clause 
 * @link https://github.com/ivan-novakov/dokuwiki-shibboleth-auth
 */
$conf['plugin']['authshibboleth'] = array(

    /*
     * auth plugin (auth.php)
     */

    /*
     * use_dokuwiki_session
     * 
     * If set to true, once the user is authenticated, the DokuWiki session will be used to persist the user's
     * identity. Otherwise, the authentication process is performed upon each request.
     */
    //'use_dokuwiki_session' => true,

    /*
     * var_remote_user
     * 
     * The server variable, which contains the remote user's identity (username, user ID etc.).
     */
    //'var_remote_user' => 'REMOTE_USER',

    /*
     * var_display_name
     * 
     * The server variable, which contains user's display name.
     */
    //'var_display_name' => 'cn',

    /*
     * var_mail
     * 
     * The server variable, which contains user's email.
     */
    //'var_mail' => 'mail',
    
    /*
     * display_name_tpl
     * 
     * Simple template for user display name construction. For example - "{givenName} {sn}".
     */
    //'display_name_tpl' => null,

    /*
     * group_source_config
     * 
     * Configures how user's groups are resolved. It is possible to define multiple sources of different
     * types. Currently these types are available:
     * 
     *   - type "environment" - data are extracted from the environment (the $_SERVER variable)
     *     - options:
     *       - "source_attribute" (required) - defines the name of the variable containing the groups
     *  
     *   - type "file" - data are read from a file (see the conf/custom_groups.php sample file for more info).
     *     - options:
     *       - "path" (required) - the full path to the group file
     *  
     *  Common options:
     *    - "map" (optional) - map values to custom group names
     *    - "prefix" (optional) - add a prefix for each group name from that source, applies after mapping
     *
     * 
     */
    'group_source_config' => array(
        
        /*
         * Example configuration
         */

        /*
         * The groups are taken from the "affiliation" attribute and are prefixed with "aff:".
         */
        /*
        'groups' => array(
            'type' => 'environment',
            'options' => array(
                'source_attribute' => 'affiliation',
                'prefix' => 'aff:'
            )
        ),
        */
        
        /*
         * The groups are taken from the entitlement attribute and the values are mapped to group names.
         * Theses group names are finally prefixed with "ent:".
         */
        /*
        'entitlement' => array(
            'type' => 'environment',
            'options' => array(
                'source_attribute' => 'entitlement',
                'map' => array(
                    'entitlement1' => 'group1',
                    'entitlement2' => 'group2'
                ),
                'prefix' => 'ent:'
            )
        ),
        */
        
        /*
         * The groups are read from the "custom_groups.php" file in the same directory.
         */
        /*
        'custom' => array(
            'type' => 'file',
            'options' => array(
                'path' => __DIR__ . '/custom_groups.php'
            )
        )
        */
    ),

    /*
     * shibboleth_handler_base
     * 
     * The base URL for Shibboleth handlers.
     */
    //'shibboleth_handler_base' => '/Shibboleth.sso/',
    
    /*
     * logout_handler
     * 
     * The name of the Shibboleth Logout handler.
     */
    //'logout_handler' => 'Logout',
    
    /*
     * logout_handler_location
     * 
     * Instead of specifying the handler base and the logout handler (see above), it is possible to specify
     * the full logout URL, for example - https://sp.example.org/Shibboleth.sso/Logout.
     */
    //'logout_handler_location' => null,
    
    /*
     * logout_return_url
     * 
     * The URL to redirect users after logout has been processed. If not specified, the current page
     * will be used.
     */
    //'logout_return_url' => null,


    /*
     * log_enabled
     * 
     * Enables logging. In order to actually log something, one of these options must be set - 
     * "log_file" or "log_to_php".
     */
    //'log_enabled' => false,

    /*
     * log_file
     * 
     * The full path to the log file.
     */
    //'log_file' => null,

    /*
     * log_to_php
     * 
     * Write log messages to the PHP error log.
     */
    //'log_to_php' => false,
    
    /*
     * log_priority
     * 
     * Set the log level: DEBUG=7, INFO=6, ERR=3
     */
    //'log_priority' => 7,

    
    /*
     * action plugin (action.php)
     */

    /*
     * login_handler
     * 
     * The name of the login handler to be used.
     */
    //'login_handler' => 'Login',

    /*
     * login_handler_location
     * 
     * Instead of specifying the handler base and the login handler (see above), it is possible to specify
     * the full login URL, for example - https://sp.example.org/Shibboleth.sso/Login.
     */
    //'login_handler_location' => null,

    /*
     * login_target
     * 
     * Target page to redirect to, after successful login. If not specified, the current page will be used.
     */
    //'login_target' => null
);