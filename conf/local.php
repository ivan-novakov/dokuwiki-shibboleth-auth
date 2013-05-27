<?php
/*
 * Dokuwiki's Main Configuration File - Local Settings
 */

$conf['title'] = 'Test Wiki';
$conf['start'] = 'index';
$conf['license'] = 'cc-by-sa';
$conf['useacl'] = 1;
$conf['authtype'] = 'authshibboleth';
$conf['superuser'] = '@admin';
$conf['userewrite'] = '1';
$conf['useslash'] = 1;

// end auto-generated content

include __DIR__ . '/authshibboleth.conf.php';
