<?php

/**
 * DokuWiki Plugin authshibboleth (Action Component)
 *
 * Intercepts the 'login' action and redirects the user to the Shibboleth Session Initiator Handler
 * instead of showing the login form. Intended to work with the Shibboleth authentication backend with 
 * "lazy session" enabled.
 * 
 * @author  Ivan Novakov <ivan.novakov@debug.cz>
 */

// must be run within Dokuwiki
if (! defined('DOKU_INC'))
    die();

if (! defined('DOKU_LF'))
    define('DOKU_LF', "\n");
if (! defined('DOKU_TAB'))
    define('DOKU_TAB', "\t");
if (! defined('DOKU_PLUGIN'))
    define('DOKU_PLUGIN', DOKU_INC . 'lib/plugins/');

require_once DOKU_PLUGIN . 'action.php';


class action_plugin_authshibboleth extends DokuWiki_Action_Plugin
{


    public function register(Doku_Event_Handler &$controller)
    {
        $controller->register_hook('ACTION_ACT_PREPROCESS', 'BEFORE', $this, '_redirectToLoginHandler');
    }


    public function handle_action_act_preprocess(Doku_Event &$event, $param)
    {}


    function _redirectToLoginHandler($event, $param)
    {
        global $ACT;
        
        if ('login' == $ACT) {
            $loginHandlerLocation = $this->getConf('login_handler_location');
            if (! $loginHandlerLocation) {
                $target = $this->getConf('target');
                if (! $target) {
                    $target = $this->_mkRefererUrl();
                }
                
                $loginHandlerLocation = $this->_mkUrl($_SERVER['HTTP_HOST'], $this->_mkShibHandler(), array(
                    
                    'target' => $target
                ));
            }
            
            header("Location: " . $loginHandlerLocation);
            exit();
        }
    }


    function _mkShibHandler()
    {
        return sprintf("/%s/%s", $this->getConf('sso_handler'), $this->getConf('login_handler'));
    }


    function _mkUrl($host, $path, $params = array(), $ssl = true)
    {
        return sprintf("%s://%s%s%s", $ssl ? 'https' : 'http', $host, $path, $this->_mkQueryString($params));
    }


    function _mkRefererUrl($ssl = true)
    {
        $urlParts = parse_url($_SERVER['HTTP_REFERER']);
        
        $host = $urlParts['host'];
        if ($urlParts['port'] && $urlParts['port'] != '80' && $urlParts['port'] != '443') {
            $host .= ':' . $urlParts['port'];
        }
        
        $query = array();
        parse_str($urlParts['query'], $query);
        
        return $this->_mkUrl($host, $urlParts['path'], $query, $ssl);
    }


    function _mkQueryString($params = array())
    {
        if (empty($params)) {
            return '';
        }
        
        $queryParams = array();
        foreach ($params as $key => $value) {
            $queryParams[] = sprintf("%s=%s", $key, urlencode($value));
        }
        
        return '?' . implode('amp;', $queryParams);
    }
}