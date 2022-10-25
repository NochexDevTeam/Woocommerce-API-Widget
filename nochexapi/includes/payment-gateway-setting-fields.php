<?php
use Nochexapi\WC_Nochexapi_Constants AS Nochexapi_CONSTANTS; 
return array(
    'general' => array(
        'title' => 'Nochex API',
        'description'=>'<p>General settings</p>',
        'fields'=> array(
            'enabled' => array(
                'title' => 'Enable/Disable',
                'label' => 'Enable Card Payments',
                'type' => 'checkbox',
                'description' => '',
                'default' => 'no',
                'class' => 'wppd-ui-toggle',
            ),
			'merchantId' => array(
                'title' => 'Merchant Id',
                'type' => 'text',
                'class' => '',
                'description' => '',
                'default' => '',
                'desc_tip' => true,
            ),
            'apikey' => array(
                'title' => 'Api Key',
                'type' => 'text',
                'class' => '',
                'description' => '',
                'default' => '',
                'desc_tip' => true,
            ),
            'title' => array(
                'title' => 'Title',
                'type' => 'text',
                'class' => '',
                'description' => 'This controls title text during checkout.',
                'default' => 'Pay with card',
                'desc_tip' => true,
            ),
            'description' => array(
                'title' => 'Description',
                'type' => 'text',
                'class' => '',
                'description' => 'This controls the description seen at checkout.',
                'default' => 'Checkout with Card',
                'desc_tip' => true
            ),
            'testMode' => array(
                'title' => 'Enable test mode',
                'type' => 'checkbox',
                'class' => 'wppd-ui-toggle',
				'description' => 'Select whether live or test transactions are made through our module.',
                'default' => 'no',
				'desc_tip' => true
            ),
            'jsLogging' => array(
                'title' => 'Enable console log? <small><em>Default:Off</em></small>',
                'label' => 'Console.log events',
                'type' => 'checkbox',
                'class' => 'wppd-ui-toggle',
                'description' => 'Only if requested to be activated by Nochex and private access is enabled should this be checked.',
                'default' => 'no',
                'desc_tip' => true,
            ),
            'serversidedebug' => array(
                'title' => 'Enable server side log? <small><em>Default:Off</em></small>',
                'label' => 'Debug log',
                'type' => 'checkbox',
                'class' => 'wppd-ui-toggle',
                'description' => 'Only if requested to be activated by Nochex and private access is enabled should this be checked.',
                'default' => 'no',
                'desc_tip' => true,
            ),
            'logLevels' => array(
                'title' => 'Logging level inclusion',
                'type' => 'multiselect',
                'class' => 'wc-enhanced-select-nostd',
                'default' => array(
                    'emergency',
                    'critical',
                    'error',
                    'warning',
                ),
                'options' => array(
                    'critical'=> 'Critical',
                    'debug' => 'Debugging',
                    'emergency' => 'Emergency',
                    'error' => 'Error',
                    'info' => 'Information',
                    'warning' => 'Warning'
                )
            ),
        )
    ),
);