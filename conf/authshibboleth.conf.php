<?php
$conf['plugin']['authshibboleth'] = array(
    'use_dokuwiki_session' => false,
    
    'var_groups' => 'affiliation',
    
    'group_source_config' => array(
        
        'groups' => array(
            'type' => 'environment',
            'options' => array(
                'source_attribute' => 'affiliation',
                'prefix' => 'aff:'
            )
        ),
        
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
        
        'custom' => array(
            'type' => 'file',
            'options' => array(
                'path' => __DIR__ . '/custom_groups.php'
            )
        )
    ),
    
    'log_enabled' => true,
    'log_file' => '/data/var/log/dokuwiki/auth.log',
    
    'debug' => true
);