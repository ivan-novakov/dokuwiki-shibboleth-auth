<?php

/**
 * DokuWiki Plugin authshibboleth (Action Component)
 *
 * Intercepts the 'login' action and redirects the user to the Shibboleth Session Initiator Handler
 * instead of showing the login form.
 * 
 * @author  Ivan Novakov http://novakov.cz/
 * @license http://debug.cz/license/bsd-3-clause BSD 3 Clause 
 * @link https://github.com/ivan-novakov/dokuwiki-shibboleth-auth
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

    const CONF_SHIBBOLETH_HANDLER_BASE = 'shibboleth_handler_base';

    const CONF_LOGIN_HANDLER = 'login_handler';

    const CONF_LOGIN_TARGET = 'login_target';

    const CONF_LOGIN_HANDLER_LOCATION = 'login_handler_location';


    public function register(Doku_Event_Handler &$controller)
    {
        $controller->register_hook('ACTION_ACT_PREPROCESS', 'BEFORE', $this, 'redirectToLoginHandler');
    }


    public function redirectToLoginHandler($event, $param)
    {
        global $ACT;
        
        if ('login' == $ACT) {
            $loginHandlerLocation = $this->getConf(self::CONF_LOGIN_HANDLER_LOCATION);
            if (! $loginHandlerLocation) {
                $loginTarget = $this->getConf(self::CONF_LOGIN_TARGET);
                if (! $loginTarget) {
                    $loginTarget = $this->mkRefererUrl();
                }
                
                $loginHandlerLocation = $this->mkUrl($_SERVER['HTTP_HOST'], $this->mkShibHandler(), array(
                    'target' => $loginTarget
                ));
            }
            
            header("Location: " . $loginHandlerLocation);
            exit();
        }
    }


    protected function mkShibHandler()
    {
        return sprintf("%s%s", $this->getConf(self::CONF_SHIBBOLETH_HANDLER_BASE), $this->getConf(self::CONF_LOGIN_HANDLER));
    }


    protected function mkUrl($host, $path, $params = array(), $ssl = true)
    {
        return sprintf("%s://%s%s%s", $ssl ? 'https' : 'http', $host, $path, $this->mkQueryString($params));
    }


    protected function mkRefererUrl($ssl = true)
    {
        $urlParts = parse_url($_SERVER['HTTP_REFERER']);
        
        $host = $urlParts['host'];
        if ($urlParts['port'] && $urlParts['port'] != '80' && $urlParts['port'] != '443') {
            $host .= ':' . $urlParts['port'];
        }
        
        $query = array();
        parse_str($urlParts['query'], $query);
        
        return $this->mkUrl($host, $urlParts['path'], $query, $ssl);
    }


    protected function mkQueryString($params = array())
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