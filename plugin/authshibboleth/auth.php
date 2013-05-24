<?php

/**
 * DokuWiki Plugin authshibboleth (Auth Component)
 *
 * @author  Ivan Novakov <ivan.novakov@debug.cz>
 */

// must be run within Dokuwiki
if (! defined('DOKU_INC'))
    die();


class auth_plugin_authshibboleth extends DokuWiki_Auth_Plugin
{

    const CONF_VAR_REMOTE_USER = 'var_remote_user';

    const CONF_VAR_DISPLAY_NAME = 'var_display_name';

    const CONF_VAR_MAIL = 'var_mail';

    const CONF_LOGOUT_HANDLER = 'logout_handler';

    const CONF_LOGOUT_HANDLER_LOCATION = 'logout_handler_location';

    const CONF_LOGOUT_RETURN_URL = 'logout_return_url';

    const CONF_SHIBBOLETH_HANDLER_BASE = 'shibboleth_handler_base';

    const CONF_DISPLAY_NAME_TPL = 'display_name_tpl';

    const CONF_USE_DOKUWIKI_SESSION = 'use_dokuwiki_session';

    const CONF_LOG_ENABLED = 'log_enabled';

    const CONF_LOG_FILE = 'log_file';

    const CONF_DEBUG = 'debug';

    const USER_UID = 'uid';

    const USER_NAME = 'name';

    const USER_MAIL = 'mail';

    /**
     * Environment variable values ($_SERVER).
     * @var array
     */
    protected $environment = array();

    /**
     * User information as gathered from the environment.
     * @var array
     */
    protected $userInfo = array();


    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->cando['external'] = true;
        $this->cando['logoff'] = true;
        
        $this->environment = $_SERVER;
        // $this->loadConfig();
        
        $this->success = true;
    }


    public function setEnvironment($environment)
    {
        $this->environment = $environment;
    }


    public function trustExternal()
    {
        global $USERINFO;
        global $conf;
        
        // _log($_SERVER);
        // _log($conf);
        $this->log('Checking...');
        
        if ($this->getConf(self::CONF_USE_DOKUWIKI_SESSION) && ($userInfo = $this->loadUserInfoFromSession()) !== null) {
            return;
        }
        
        $userId = $this->getShibVar($this->getConf(self::CONF_VAR_REMOTE_USER));
        if ($userId) {
            
            $this->setUserId($userId);
            $this->setUserDisplayName($this->retrieveUserDisplayName());
            $this->setUserMail($this->retrieveUserMail());
            
            $this->saveUserInfoToSession();
            $this->saveGlobalUserInfo();
            
            return true;
        }
        
        /* Is it necessary?
        if (! $this->_getOption('lazy_sessions')) {
            auth_logoff();
        }
        */
        
        return false;
    }


    public function logoff()
    {
        $url = $this->getConf(self::CONF_LOGOUT_HANDLER_LOCATION);
        if (! $url) {
            $url = $this->createLogoutHandlerLocation();
        }
        
        $this->debug(sprintf("Logout redirect: %s", $url));
        
        header('Location: ' . $url);
        exit();
    }
    
    /*
     * Protected methods
     */
    
    /**
     * Saves user info into the session.
     */
    protected function saveUserInfoToSession(array $userInfo = null)
    {
        if (! $userInfo) {
            $userInfo = $this->getUserInfo();
        }
        
        $_SESSION[DOKU_COOKIE]['auth']['user'] = $userInfo['uid'];
        $_SESSION[DOKU_COOKIE]['auth']['info'] = $userInfo;
    }


    /**
     * Loads user info from the session.
     */
    protected function loadUserInfoFromSession()
    {
        if (isset($_SESSION[DOKU_COOKIE]['auth']) && is_array($_SESSION[DOKU_COOKIE]['auth'])) {
            $authInfo = $_SESSION[DOKU_COOKIE]['auth'];
            
            if (isset($authInfo['user']) && isset($authInfo['info']) && is_array($authInfo['info'])) {
                $userInfo = $authInfo['info'];
                $username = $authInfo['user'];
                
                $this->setUserInfo($userInfo);
                
                return $userInfo;
            }
        }
        
        return null;
    }


    /**
     * Sets user info accordingly to the DokuWiki speifics.
     *
     * Sets the $USERINFO global variable. Sets the REMOTE_USER variable, if it is not populated with the
     * username from the Shibboleth environment. Despite having the $USERINFO global array, it seems that
     * DokuWiki still uses the REMOTE_USER value.
     *
     * @param array $userInfo
     */
    protected function saveGlobalUserInfo(array $userInfo = null)
    {
        global $USERINFO;
        
        if (! $userInfo) {
            $userInfo = $this->getUserInfo();
        }
        
        $USERINFO = $userInfo;
        
        if ($this->getConf(self::CONF_VAR_REMOTE_USER) != 'REMOTE_USER') {
            $_SERVER['REMOTE_USER'] = $userInfo['uid'];
        }
    }


    /**
     * Returns a Shibboleth variable.
     * 
     * @param string $varName
     * @param boolean $multivalue
     * @return string|array|null
     */
    protected function getShibVar($varName, $multivalue = false)
    {
        $value = $this->getEnvVar($varName);
        if ($value && $multivalue) {
            $value = explode(';', $value);
        }
        
        return $value;
    }


    /**
     * Returns the value of the required environment variable.
     * 
     * @param string $varName
     * @return string|null
     */
    protected function getEnvVar($varName)
    {
        if (isset($this->environment[$varName])) {
            return $this->environment[$varName];
        }
        
        return null;
    }


    /**
     * Extracts user's mail from the environment.
     * 
     * @return string|null
     */
    protected function retrieveUserMail()
    {
        $mails = $this->getShibVar($this->getConf(self::CONF_VAR_MAIL), true);
        if (count($mails)) {
            return $mails[0];
        }
        
        return null;
    }


    /**
     * Extracts user's display name from the environment.
     * 
     * @return string|null
     */
    protected function retrieveUserDisplayName()
    {
        $userDisplayName = null;
        $tplUserDisplayName = $this->getConf(self::CONF_DISPLAY_NAME_TPL);
        if ($tplUserDisplayName) {
            $userDisplayName = $this->getUserDisplayNameFromTpl($tplUserDisplayName);
        }
        
        $userDisplayNameVar = $this->getConf(self::CONF_VAR_DISPLAY_NAME);
        if ($userDisplayNameVar) {
            $userDisplayName = $this->getShibVar($userDisplayNameVar);
        }
        
        if (! $userDisplayName) {
            $userDisplayName = $this->getUserId();
        }
        
        return $userDisplayName;
    }


    /**
     * Resolves the template for the user's real name and returns it.
     *
     * @param string $tplUserDisplayName
     * @return string
     */
    protected function retrieveUserDisplayNameFromTpl($tplUserDisplayName)
    {
        $matches = array();
        if (preg_match_all('/({([^{}]+)})/', $tplUserDisplayName, $matches)) {
            $vars = $matches[2];
            
            $userName = $tplUserDisplayName;
            foreach ($vars as $var) {
                $value = $this->getShibVar($var);
                if (! $value) {
                    return '';
                }
                $userName = str_replace('{' . $var . '}', $value, $userName);
            }
            
            return $userName;
        }
        
        return '';
    }


    protected function getUserId()
    {
        return $this->getUserVar(self::USER_UID);
    }


    protected function setUserId($userId)
    {
        $this->setUserVar(self::USER_UID, $userId);
    }


    protected function getUserDisplayName()
    {
        return $this->getUserVar(self::USER_NAME);
    }


    protected function setUserDisplayName($userDisplayName)
    {
        $this->setUserVar(self::USER_NAME, $userDisplayName);
    }


    protected function getUserMail()
    {
        return $this->getUserVar(self::USER_MAIL);
    }


    protected function setUserMail($mail)
    {
        $this->setUserVar(self::USER_MAIL, $mail);
    }


    protected function setUserVar($varName, $varValue)
    {
        $this->userInfo[$varName] = $varValue;
    }


    protected function getUserVar($varName)
    {
        if (isset($this->userInfo[$varName])) {
            return $this->userInfo[$varName];
        }
        
        return null;
    }


    protected function setUserInfo(array $userInfo)
    {
        $this->userInfo = $userInfo;
    }


    protected function getUserInfo()
    {
        return $this->userInfo;
    }


    protected function createLogoutHandlerLocation($returnUrl = NULL, $handlerName = 'Logout')
    {
        if (! $returnUrl) {
            if (isset($_SERVER['HTTP_REFERER']) && isset($_SERVER['HTTP_REFERER'])) {
                $returnUrl = $_SERVER['HTTP_REFERER'];
            } else {
                $returnUrl = $this->getConf(self::CONF_LOGOUT_RETURN_URL);
            }
        }
        
        if (! $handlerName) {
            $handlerName = $this->getConf(self::CONF_LOGOUT_HANDLER);
        }
        
        return sprintf("https://%s%s%s?return=%s", $_SERVER['HTTP_HOST'], $this->getConf(self::CONF_SHIBBOLETH_HANDLER_BASE), $handlerName, $returnUrl);
    }


    protected function debug($value)
    {
        if ($this->getConf('debug')) {
            error_log('[DOKUWIKI DEBUG]: ' . print_r($value, true));
        }
    }


    protected function log($message)
    {
        $message = $this->logFormatMessage($message);
        $this->debug($message);
        
        if ($this->getConf(self::CONF_LOG_ENABLED)) {
            $logFile = $this->getConf(self::CONF_LOG_FILE);
            if ($logFile) {
                $flags = null;
                if (file_exists($logFile)) {
                    if (! is_writable($logFile)) {
                        $this->debug(sprintf("Log file '%s' not writable", $logFile));
                        return;
                    }
                    $flags = FILE_APPEND;
                }
                
                $message = sprintf("[%s]: %s\n", date('c', time()), $message);
                if (false === file_put_contents($logFile, $message, $flags)) {
                    $this->debug(sprintf("Error writing to log file '%s'", $logFile));
                }
            } else {
                $this->debug('Log enabled, but log file not set');
            }
        }
    }


    protected function logFormatMessage($message)
    {
        $userId = $this->getUserId();
        if (! $userId) {
            $userId = 'unknown';
        }
        return sprintf("[%s] [%s] %s", $_SERVER['REQUEST_URI'], $userId, $message);
    }
}


function _log($value)
{
    error_log(print_r($value, true));
}