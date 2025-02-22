<?php

defined('MOODLE_INTERNAL') || die();


$capabilities = array(   
	    // 'block/'.NB_BLOQUE_I.':myaddinstance' => array(
			'block/blumod:myaddinstance' => array(
	        'captype' => 'write',
	        'contextlevel' => CONTEXT_SYSTEM,
	        'archetypes' => array(
	            'editingteacher' => CAP_ALLOW,
	            'manager' => CAP_ALLOW
	        ),
	        
	        'clonepermissionsfrom' => 'moodle/my:manageblocks'
	    ),
	    
	    // 'block/'.NB_BLOQUE_I.':addinstance' => array(
			'block/blumod:addinstance' => array(
	        'riskbitmask' => RISK_SPAM | RISK_XSS,
	        'captype' => 'write',
	        'contextlevel' => CONTEXT_COURSE,
	        'archetypes' => array(
	            'editingteacher' => CAP_ALLOW,
	            'manager' => CAP_ALLOW
	        ),
	        
	        'clonepermissionsfrom' => 'moodle/site:manageblocks'
	    ),
	    
	    // 'block/'.NB_BLOQUE_I.':view' => array(
			'block/blumod:view' => array(
	        'captype' => 'read',
	        'contextlevel' => CONTEXT_COURSE,
	        'archetypes' => array(
	            'coursecreator' => CAP_ALLOW,
	            'teacher' => CAP_ALLOW,
	            'editingteacher' => CAP_ALLOW,
	            'manager' => CAP_ALLOW
	        )
	    ),
        // 'block/'.NB_BLOQUE_I.':manageblus' => array(
		   'block/blumod:manageblus' => array(
           'captype' => 'write',
           'contextlevel' => CONTEXT_COURSE,
           'archetypes' => array(
               'coursecreator' => CAP_ALLOW,
               'teacher' => CAP_ALLOW,
               'editingteacher' => CAP_ALLOW,
               'manager' => CAP_ALLOW
       )
    )
    
);