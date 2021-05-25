<?php
/**
 * Web service init file.
 *
 * @package API
 *
 * @copyright YetiForce Sp. z o.o
 * @license   YetiForce Public License 3.0 (licenses/LicenseEN.txt or yetiforce.com)
 * @author    Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 */
require_once __DIR__ . '/include/main/WebUI.php';
require_once __DIR__ . '/include/RequirementsValidation.php';

\App\Process::$requestMode = 'API';
\App\Log::beginProfile(\App\Request::getRequestMethod() . '::' . $_SERVER['REQUEST_URI'], 'WebServiceAPI');
try {
	if (!\in_array('webservice', \App\Config::api('enabledServices'))) {
		throw new \App\Exceptions\NoPermittedToApi('Webservice - Service is not active', 403);
	}
	$controller = Api\Controller::getInstance();
	$process = $controller->preProcess();
	if ($process) {
		$controller->process();
	}
	$controller->postProcess();
} catch (\Api\Core\Exception $e) {
	\App\Log::error($e->getMessage() . PHP_EOL . $e->__toString());
	$e->handleError();
} catch (\App\Exceptions\NoPermittedToApi $e) {
	\App\Log::error($e->getMessage() . PHP_EOL . $e->__toString());
	$ex = new \Api\Core\Exception($e->getMessage(), $e->getCode(), $e);
	$ex->handleError();
} catch (\Throwable $e) {
	\App\Log::error($e->getMessage() . PHP_EOL . $e->__toString());
	$ex = new \Api\Core\Exception($e->getMessage(), $e->getCode(), $e);
	$ex->handleError();
}
\App\Log::endProfile(\App\Request::getRequestMethod() . '::' . $_SERVER['REQUEST_URI'], 'WebServiceAPI');
