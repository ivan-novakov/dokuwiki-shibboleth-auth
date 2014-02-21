<?php

/**
 * DokuWiki Plugin authshibboleth (Auth Component).
 *
 * @author  Ivan Novakov http://novakov.cz/
 * @license http://debug.cz/license/bsd-3-clause BSD 3 Clause 
 * @link https://github.com/ivan-novakov/dokuwiki-shibboleth-auth
 */

// must be run within Dokuwiki
if (! defined('DOKU_INC'))
    die();


class auth_plugin_authshibboleth extends DokuWiki_Auth_Plugin
{

    const CONF_VAR_REMOTE_USER = 'var_remote_user';

    const CONF_VAR_DISPLAY_NAME = 'var_display_name';

    const CONF_VAR_MAIL = 'var_mail';

    const CONF_VAR_SHIB_SESSION_ID = 'var_shib_session_id';

    const CONF_LOGOUT_HANDLER = 'logout_handler';

    const CONF_LOGOUT_HANDLER_LOCATION = 'logout_handler_location';

    const CONF_LOGOUT_RETURN_URL = 'logout_return_url';

    const CONF_SHIBBOLETH_HANDLER_BASE = 'shibboleth_handler_base';

    const CONF_DISPLAY_NAME_TPL = 'display_name_tpl';

    const CONF_USE_DOKUWIKI_SESSION = 'use_dokuwiki_session';

    const CONF_GROUP_SOURCE_CONFIG = 'group_source_config';

    const CONF_LOG_ENABLED = 'log_enabled';

    const CONF_LOG_FILE = 'log_file';

    const CONF_LOG_TO_PHP = 'log_to_php';

    const CONF_LOG_PRIORITY = 'log_priority';

    const USER_UID = 'uid';

    const USER_NAME = 'name';

    const USER_MAIL = 'mail';

    const USER_GRPS = 'grps';

    const GROUP_SOURCE_TYPE_ENVIRONMENT = 'environment';

    const GROUP_SOURCE_TYPE_FILE = 'file';

    const LOG_DEBUG = 7;

    const LOG_INFO = 6;

    const LOG_ERR = 3;

    /**
     * Global configuration.
     * @var array
     */
    protected $globalConf = array();

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
        
        $this->setEnvironment($_SERVER);
        
        global $conf;
        $this->setGlobalConfiguration($conf);
        
        $this->success = true;
    }


    /**
     * Sets the environment variables.
     * 
     * @param array $environment
     */
    public function setEnvironment(array $environment)
    {
        $this->environment = $environment;
    }


    /**
     * Sets the global configuration variables.
     * 
     * @param array $globalConf
     */
    public function setGlobalConfiguration(array $globalConf)
    {
        $this->globalConf = $globalConf;
    }


    /**
     * {@inheritdoc}
     * @see DokuWiki_Auth_Plugin::trustExternal()
     */
    public function trustExternal()
    {
        $this->debug('Checking for DokuWiki session...');
        if ($this->getConf(self::CONF_USE_DOKUWIKI_SESSION) && ($userInfo = $this->loadUserInfoFromSession()) !== null) {
            $this->log('Loaded user from DokuWiki session');
            return;
        }
        
        $sessionVarName = $this->getConf(self::CONF_VAR_SHIB_SESSION_ID);
        $this->debug(sprintf("Checking for Shibboleth session [%s] ...", $sessionVarName));
        if ($this->getShibVar($sessionVarName)) {
            $this->log('Shibboleth session found, trying to authenticate user...');

            $userId = $this->getShibVar($this->getConf(self::CONF_VAR_REMOTE_USER));
            if ($userId) {
                
                $this->setUserId($userId);
                $this->setUserDisplayName($this->retrieveUserDisplayName());
                $this->setUserMail($this->retrieveUserMail());
                $this->setUserGroups($this->retrieveUserGroups());
                
                $this->saveUserInfoToSession();
                $this->saveGlobalUserInfo();
                
                $this->log('Loaded user from environment');
                
                return true;
            }
        }
        
        auth_logoff();
        return false;
    }


    /**
     * {@inheritdoc}
     * @see DokuWiki_Auth_Plugin::logOff()
     */
    public function logOff()
    {
        /*
         * Initiate a logout sequence only, if there is a Shibboleth identity
         */
        if ($this->retrieveUserId()) {
            $url = $this->getConf(self::CONF_LOGOUT_HANDLER_LOCATION);
            if (! $url) {
                $url = $this->createLogoutHandlerLocation();
            }
            
            $this->debug(sprintf("Logout redirect: %s", $url));
            
            header('Location: ' . $url);
            exit();
        }
    }


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
     * 
     * @return array|null
     */
    protected function loadUserInfoFromSession()
    {
        if (isset($_SESSION[DOKU_COOKIE]['auth']) && is_array($_SESSION[DOKU_COOKIE]['auth'])) {
            $authInfo = $_SESSION[DOKU_COOKIE]['auth'];
            
            if (isset($authInfo['user']) && isset($authInfo['info']) && is_array($authInfo['info'])) {
                $userInfo = $authInfo['info'];
                $username = $authInfo['user'];
                
                $this->setUserInfo($userInfo);
                $this->saveGlobalUserInfo();
                
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
        $_SERVER['REMOTE_USER'] = $userInfo['uid'];
    }


    /**
     * Returns the value of global configuration variable.
     * 
     * @param string $varName
     * @return mixed|null
     */
    protected function getGlobalConfVar($varName)
    {
        if (isset($this->globalConf[$varName])) {
            return $this->globalConf[$varName];
        }
        
        return null;
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
     * Extracts user's identity from the environment.
     * 
     * @return string|null
     */
    protected function retrieveUserId()
    {
        return $this->getShibVar($this->getConf(self::CONF_VAR_REMOTE_USER));
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
            $userDisplayName = $this->retrieveUserDisplayNameFromTpl($tplUserDisplayName);
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


    /**
     * Resolves the user's groups.
     * 
     * @return array
     */
    protected function retrieveUserGroups()
    {
        $groups = array();
        
        // default groups
        if (($defaultGroup = $this->getGlobalConfVar('defaultgroup')) !== null) {
            $groups[] = $defaultGroup;
        }
        
        $groupSourceConfig = $this->getConf(self::CONF_GROUP_SOURCE_CONFIG);
        if (is_array($groupSourceConfig)) {
            foreach ($groupSourceConfig as $sourceName => $config) {
                if (! isset($config['type'])) {
                    $this->log(sprintf("Group source '%s' without a type", $sourceName));
                    continue;
                }
                $sourceType = $config['type'];
                $sourceOptions = array();
                if (isset($config['options'])) {
                    $sourceOptions = $config['options'];
                }
                
                $sourceGroups = $this->retrieveUserGroupsFromSource($sourceName, $sourceType, $sourceOptions);
                if (is_array($sourceGroups)) {
                    $groups = array_merge($groups, $sourceGroups);
                }
            }
        } else {
            $this->log(sprintf("The value of '%s' must be an array", self::CONF_GROUP_SOURCE_CONFIG));
        }
        
        $this->log(sprintf("Resolved groups: %s", implode(', ', $groups)));
        
        return $groups;
    }


    /**
     * Resolves the user's groups from different sources.
     * 
     * @param string $sourceType
     * @param array $sourceOptions
     * @return array
     */
    protected function retrieveUserGroupsFromSource($sourceName, $sourceType, array $sourceOptions)
    {
        $groups = array();
        
        $this->debug(sprintf("Resolving groups from source '%s' (%s)", $sourceName, $sourceType));
        
        $handler = 'retrieveUserGroupsFrom' . ucfirst($sourceType);
        if (! method_exists($this, $handler)) {
            $this->log(sprintf("Non-existent group source handler '%s'", $handler));
            return $groups;
        }
        
        try {
            $sourceGroups = call_user_func_array(array(
                $this,
                $handler
            ), array(
                $sourceOptions
            ));
        } catch (Exception $e) {
            $this->log(sprintf("Error retrieving groups from source '%s' (%s): %s", $sourceName, $sourceType, $e->getMessage()));
            return $groups;
        }
        
        $this->debug(sprintf("Resolved groups from source '%s' (%s): %s", $sourceName, $sourceType, implode(', ', $sourceGroups)));
        
        /*
         * Groups "post-processing"
         */
        foreach ($sourceGroups as $group) {
            if (isset($sourceOptions['map'])) {
                $map = $sourceOptions['map'];
                if (isset($map[$group])) {
                    $group = $map[$group];
                }
            }
            
            if (isset($sourceOptions['prefix'])) {
                $group = $sourceOptions['prefix'] . $group;
            }
            
            $groups[] = $group;
        }
        
        return $groups;
    }


    /**
     * Resolves user's groups from the environment variables.
     * 
     * @param array $options
     * @throws RuntimeException
     * @return array
     */
    protected function retrieveUserGroupsFromEnvironment(array $options)
    {
        $groups = array();
        
        if (! isset($options['source_attribute'])) {
            throw new RuntimeException('The required "source_attribute" option not set');
        }
        $sourceAttributeName = $options['source_attribute'];
        
        $values = $this->getShibVar($sourceAttributeName, true);
        if (null !== $values) {
            foreach ($values as $value) {
                
                $groups[] = $value;
            }
        }
        
        return $groups;
    }


    /**
     * Resolves user's groups from a file.
     * 
     * @param array $options
     * @throws RuntimeException
     * @return array
     */
    protected function retrieveUserGroupsFromFile(array $options)
    {
        $groups = array();
        
        $userId = $this->getUserId();
        if (! $userId) {
            throw new RuntimeException('No user identity');
        }
        
        if (! isset($options['path'])) {
            throw new RuntimeException('The required "path" option not set');
        }
        
        $path = $options['path'];
        if (! file_exists($path)) {
            throw new RuntimeException(sprintf("Non-existent file '%s'", $path));
        }
        
        if (! is_readable($path)) {
            throw new RuntimeException(sprintf("File '%s' not readable", $path));
        }
        
        $sourceGroups = require $path;
        if (! is_array($sourceGroups)) {
            throw new RuntimeException(sprintf("Invalid group format in file '%s'", $path));
        }
        
        foreach ($sourceGroups as $groupName => $members) {
            if (in_array($userId, $members)) {
                $groups[] = $groupName;
            }
        }
        
        return $groups;
    }


    /**
     * Returns the user ID (user's identity value).
     * 
     * @return string|null
     */
    protected function getUserId()
    {
        return $this->getUserVar(self::USER_UID);
    }


    /**
     * Sets the user ID.
     * 
     * @param string $userId
     */
    protected function setUserId($userId)
    {
        $this->setUserVar(self::USER_UID, $userId);
    }


    /**
     * Returns the user's display name.
     * 
     * @return string|null
     */
    protected function getUserDisplayName()
    {
        return $this->getUserVar(self::USER_NAME);
    }


    /**
     * Sets the user's display name.
     * 
     * @param string $userDisplayName
     */
    protected function setUserDisplayName($userDisplayName)
    {
        $this->setUserVar(self::USER_NAME, $userDisplayName);
    }


    /**
     * Returns the user's mail.
     * 
     * @return string|null
     */
    protected function getUserMail()
    {
        return $this->getUserVar(self::USER_MAIL);
    }


    /**
     * Sets the user's mail.
     * 
     * @param string $mail
     */
    protected function setUserMail($mail)
    {
        $this->setUserVar(self::USER_MAIL, $mail);
    }


    /**
     * Returns the list of user's groups.
     * 
     * @return array
     */
    protected function getUserGroups()
    {
        return $this->getUserVar(self::USER_GRPS);
    }


    /**
     * Sets the user's groups.
     * 
     * @param array $groups
     */
    protected function setUserGroups(array $groups)
    {
        $this->setUserVar(self::USER_GRPS, $groups);
    }


    /**
     * Sets a specific user variable value.
     * 
     * @param string $varName
     * @param mixed $varValue
     */
    protected function setUserVar($varName, $varValue)
    {
        $this->userInfo[$varName] = $varValue;
    }


    /**
     * Returns a specific user variable value.
     * 
     * @param string $varName
     * @return mixed|null
     */
    protected function getUserVar($varName)
    {
        if (isset($this->userInfo[$varName])) {
            return $this->userInfo[$varName];
        }
        
        return null;
    }


    /**
     * Sets all the user info at once.
     * 
     * @param array $userInfo
     */
    protected function setUserInfo(array $userInfo)
    {
        $this->userInfo = $userInfo;
    }


    /**
     * Returns all the user info.
     * 
     * @return array
     */
    protected function getUserInfo()
    {
        return $this->userInfo;
    }


    /**
     * Build a logout handler URL.
     * 
     * @param string $returnUrl
     * @param string $handlerName
     * @return string
     */
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


    /**
     * Logs a debug message.
     * 
     * @param string $message
     */
    protected function debug($message)
    {
        $this->log($message, self::LOG_DEBUG);
    }


    /**
     * Logs an error message.
     * 
     * @param string $message
     */
    protected function err($message)
    {
        $this->log($message, self::LOG_ERR);
    }


    /**
     * Log a message.
     * 
     * @param mixed $message
     * @param integer $priority
     */
    protected function log($message, $priority = self::LOG_INFO)
    {
        $message = $this->logFormatMessage($message, $priority);

        if ($this->getConf(self::CONF_LOG_ENABLED) && $priority <= $this->getConf(self::CONF_LOG_PRIORITY)) {
            if ($this->getConf(self::CONF_LOG_TO_PHP)) {
                error_log($message);
            }
            
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
            } elseif (! $this->getConf(self::CONF_LOG_TO_PHP)) {
                $this->debug('Log enabled, but log file not set');
            }
        }
    }


    /**
     * Formats a log message.
     * 
     * @param mixed $message
     * @param integer $priority
     * @return string
     */
    protected function logFormatMessage($message, $priority)
    {
        if (! is_scalar($message)) {
            $message = print_r($message, true);
        }
        
        $userId = $this->getUserId();
        if (! $userId) {
            $userId = 'unknown';
        }
        return sprintf("(%d) [%s/%s] %s [%s]", $priority, $userId, $_SERVER['REMOTE_ADDR'], $message, $_SERVER['REQUEST_URI']);
    }
}
