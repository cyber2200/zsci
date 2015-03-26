<?php
return array(
    'controllers' => array(
        'invokables' => array(
            'Ci' => 'Ci\Controller\CiController',
			'CiWebAPI-1_9' => 'Ci\Controller\CiWebAPIController',
        ),
    ),
    'view_manager' => array(
        'template_path_stack' => array(
            'Ci' => __DIR__ . '/../view',
        ),
    ),
	'webapi_routes' => array(
		'runCliJob' => array(
			'type' => 'Zend\Mvc\Router\Http\Literal',
			'options' => array(
				'route' => '/Api/runCiJob',
				'defaults' => array(
					'controller' => 'CiWebAPI',
					'action' => 'runJobCli',
					'versions' => array('1.9'),
				),
			)
		),
		'runCliJob1' => array(
			'type' => 'Zend\Mvc\Router\Http\Literal',
			'options' => array(
				'route' => '/Api/runCiJob1',
				'defaults' => array(
					'controller' => 'CiWebAPI',
					'action' => 'runJobCli1',
					'versions' => array('1.9'),
				),
			)
		)
	),
);