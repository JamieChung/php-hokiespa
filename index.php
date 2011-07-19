<?php
require 'config.php';
require_once ('multirequest/example/config.php');

define('COOKIE_FILE', tempnam("./cookies/", "COOKIES"));

/***************************************************************
  DEBUG METHODS
 **************************************************************/

function debug($message) {
	echo $message . '<br />';
	flush();
}

function debugRequestComplete(MultiRequest_Request $request, MultiRequest_Handler $handler) {
	debug('Request complete: ' . $request->getUrl() . ' Code: ' . $request->getCode() . ' Time: ' . $request->getTime());
	debug('Requests in waiting queue: ' . $handler->getRequestsInQueueCount());
	debug('Active requests: ' . $handler->getActiveRequestsCount());
}

function saveCompleteRequestToFile(MultiRequest_Request $request, MultiRequest_Handler $handler) {
	$filename = preg_replace('/[^\w\.]/', '', $request->getUrl());
	file_put_contents(DOWNLOADS_DIR . DIRECTORY_SEPARATOR . $filename, $request->getContent());
}

function prepareDownloadsDir() {
	$dirPath = DOWNLOADS_DIR;
	chmod($dirPath, 0777);
	$dirIterator = new RecursiveDirectoryIterator($dirPath);
	$recursiveIterator = new RecursiveIteratorIterator($dirIterator);
	foreach($recursiveIterator as $path) {
		if($path->isFile() && strpos($path->getFilename(), '.')) {
			unlink($path->getPathname());
		}
	}
}
prepareDownloadsDir(DOWNLOADS_DIR);

/***************************************************************
  MULTIREQUEST INIT
 **************************************************************/

$mrHandler = new MultiRequest_Handler();
$mrHandler->setConnectionsLimit(CONNECTIONS_LIMIT);
$mrHandler->onRequestComplete('debugRequestComplete');
$mrHandler->onRequestComplete('saveCompleteRequestToFile');

$headers = array();
$headers[] = 'Accept: text/xml,application/xml,application/xhtml+xml,text/html;q=0.9,text/plain;q=0.8,image/png,*/*;q=0.5';
$headers[] = 'Cache-Control: no-cache';
$headers[] = 'Connection: Keep-Alive';
$headers[] = 'Keep-Alive: 300';
$headers[] = 'Accept-Charset: UTF-8,Windows-1251,ISO-8859-1;q=0.7,*;q=0.7';
$headers[] = 'Accept-Language: ru,en-us,en;q=0.5';
$headers[] = 'Pragma:';
$mrHandler->requestsDefaults()->addHeaders($headers);

$options = array();
$options[CURLOPT_USERAGENT] = 'Mozilla/5.0 (Windows; U; Windows NT 6.1; en-US; rv:1.9.2.12) Gecko/20101026 Firefox/3.6.12';
$mrHandler->requestsDefaults()->addCurlOptions($options);


// Login
$request = new MultiRequest_Request('https://banweb.banner.vt.edu/ssb/prod/twbkwbis.P_ValLogin');
$request->setCookiesStorage(COOKIE_FILE);
$request->setPostVar('pid', PID);
$request->setPostVar('password', PASSWORD);
$request->onComplete('mainPage');
$mrHandler->pushRequestToQueue($request);

function mainPage (MultiRequest_Request $request, MultiRequest_Handler $handler)
{
	$headers = $request->getResponseHeaders();	
	$cookies = $request->getRespopnseCookies();
	$request = new MultiRequest_Request('https://banweb.banner.vt.edu/ssb/prod/twbkwbis.P_GenMenu?name=bmenu.P_MainMnu');
	$request->setCookiesStorage(COOKIE_FILE);
	$handler->pushRequestToQueue($request);
	
	$request->onComplete(function(MultiRequest_Request $request, MultiRequest_Handler $handler){
		try {
			$request->getFailException();
		} catch ( Exception $e ) {
			exit($e->getMessage());	
		}
		
		echo '<pre>';
		var_dump($request->getContent());
		exit;
	});
}

$startTime = time();
set_time_limit(300);
$mrHandler->start();

debug('Total time: ' . (time() - $startTime));


function get_meta_data($html) {

    preg_match_all( 
"/<meta[^>]+(http\-equiv|name)=\"([^\"]*)\"[^>]" . "+content=\"([^\"]*)\"[^>]*>/i", 
$html, $meta,PREG_PATTERN_ORDER);
    

return $meta;    
}

?>