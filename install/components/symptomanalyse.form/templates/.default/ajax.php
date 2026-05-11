<?php

define('NO_KEEP_STATISTIC', true);
define('NO_AGENT_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

use Bitrix\Main\Context;
use Bitrix\Main\Loader;
use Bitrix\Main\Web\Json;
use med\custom\controller\SymptomAnalyseController;
use med\custom\repository\SymptomAnalyseRepository;
use med\custom\service\SymptomAnalyzeService;

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Content-Type: application/json; charset=utf-8');

global $USER;


function sendJson(array $data, int $status = 200): void
{
	http_response_code($status);

	echo Json::encode(
		$data,
		JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
	);

	exit;
}


try {
	if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
		http_response_code(200);
		exit;
	}

	if (!check_bitrix_sessid()) {
		throw new Exception('Неверная сессия');
	}

	if (!$USER->IsAuthorized()) {
		throw new Exception('Пользователь не авторизован');
	}

	foreach ([
		'highloadblock',
		'med.appointment',
	] as $module) {
		if (!Loader::includeModule($module)) {
			throw new Exception(
				'Не удалось подключить модуль ' . $module
			);
		}
	}

	$request = Context::getCurrent()->getRequest();

	$action = $request->getQuery('action')
		?: $request->getPost('action');

	if (
		!in_array(
			$action,
			[
				'symptom_analysis',
				'save_symptom_history',
				'health_check',
			],
			true
		)
	) {
		throw new Exception('Неизвестное действие');
	}


	$controller = new SymptomAnalyseController(
		new SymptomAnalyzeService(
			new SymptomAnalyseRepository()
		)
	);


	switch ($action) {

		case 'health_check':

			if ($request->getRequestMethod() !== 'GET') {
				sendJson([
					'success' => false,
					'error' => 'Method not allowed',
				], 405);
			}

			echo $controller->checkServer();

			exit;


		case 'symptom_analysis':

			if ($request->getRequestMethod() !== 'POST') {
				sendJson([
					'success' => false,
					'error' => 'Method not allowed',
				], 405);
			}

			$symptoms = trim(
				(string) $request->getPost('symptoms')
			);

			if ($symptoms === '') {
				sendJson([
					'success' => false,
					'error' => 'Не переданы симптомы',
				], 400);
			}

			echo $controller->analyzeSymptoms(
				$symptoms
			);

			exit;


		case 'save_symptom_history':

			$symptoms = trim(
				(string) $request->getPost('symptoms')
			);

			$responseJson = trim(
				(string) $request->getPost('response')
			);

			if ($symptoms === '') {
				throw new Exception(
					'Пустое описание симптомов'
				);
			}

			if ($responseJson === '') {
				throw new Exception(
					'Пустой ответ нейросети'
				);
			}

			try {
				$decoded = Json::decode(
					$responseJson
				);

				$responseJson = Json::encode(
					$decoded,
					JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
				);

			} catch (Throwable $e) {
				throw new Exception(
					'Некорректный JSON ответа'
				);
			}

			$id = $controller->saveRequestInHistory(
				(int) $USER->GetID(),
				$symptoms,
				$responseJson
			);

			sendJson([
				'success' => true,
				'id' => $id,
			]);
	}

} catch (Throwable $e) {

	sendJson([
		'success' => false,
		'error' => $e->getMessage(),
	], 500);

}