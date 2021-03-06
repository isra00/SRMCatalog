<?php

require '../vendor/autoload.php';

use \Symfony\Component\HttpFoundation\Request;

error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT & ~E_NOTICE);

$app = new \Silex\Application;

$app['config'] = array_merge(
	require __DIR__ . '/../config.php',
	require __DIR__ . '/../parameters.php',
);

$app['debug']  = $app['config']['debug'];

/* 
 * SILEX PROVIDERS 
 */

$app->register(new \Silex\Provider\TwigServiceProvider(), array(
	'twig.path' => $app['config']['templates_dir']
));

$app->register(new \Silex\Provider\LocaleServiceProvider());
$app->register(new \Silex\Provider\TranslationServiceProvider());

$currentTranslation['messages'][$app['config']['locale']] = include $app['config']['translations'][$app['config']['locale']];
$app['translator.domains'] = $currentTranslation;
$app['locale'] = $app['config']['locale'];

/*
 * CUSTOM PROVIDERS
 */
$app['dbReader'] = function() use ($app) {
	return new \RMSCatalog\DbReader($app);
};
$app['classificationReader'] = function() use ($app) {
	return new \RMSCatalog\ClassificationReader($app);
};


/*
 * CONTROLLERS
 */

$app->get('/', function(Request $req) use ($app)
{
	return $app['twig']->render('home.twig', [
		'totalBooks'		 => count($app['dbReader']->readDb()),
		'classificationTree' => $app['classificationReader']->getHtmlTree($req->getBasePath())
	]);
});

$app->get('/record/{id}', function(Request $req, int $id) use ($app)
{
	$record = $app['dbReader']->cookRecord(
		$app['dbReader']->readDb()[$id]
	);

	$record['classTree'] = $app['classificationReader']->getParents($record['class']);

	return $app['twig']->render('record.twig', [
		'record' 				=> $record,
		'whereToFindTemplate' 	=> $app['config']['whereToFindTemplate'],
		'loadJSFramework'		=> $app['config']['fetchDataFromInternet']
	]);
});

$app->get('/ajaxExtraData/{id}', function(Request $req, int $id) use ($app)
{
	$record = $app['dbReader']->cookRecord(
		$app['dbReader']->readDb()[$id]
	);

	$gBooksDataReader = new \RMSCatalog\GBooksDataReader($app);

	return $app['twig']->render('ajaxExtraData.twig', [
		'gbooksData'		=> $gBooksDataReader->getBookData($record)
	]);
});

$app->get('/db',  "RMSCatalog\\Controllers\\DbManagement::get");
$app->post('/db', "RMSCatalog\\Controllers\\DbManagement::post");

$app->get('/match', "RMSCatalog\\Controllers\\Match::get");
$app->get('/search', "RMSCatalog\\Controllers\\Search::get");


$app->run();