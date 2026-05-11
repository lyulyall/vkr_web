<?php

define('NO_KEEP_STATISTIC', true);
define('NO_AGENT_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

use Bitrix\Main\Context;
use Bitrix\Main\Loader;
use Bitrix\Main\Web\Json;
use med\custom\controller\SkinAnalyseController;
use med\custom\integration\AiDiagnostic\AiDiagnosticHelper;
use med\custom\repository\SkinAnalyseRepository;
use med\custom\service\SkinAnalyseService
;

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Content-Type: application/json; charset=utf-8');

global $USER;

function sendJson(array $data, int $status = 200): void {
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

	foreach (['iblock', 'med.appointment'] as $module) {
		if (!Loader::includeModule($module)) {
			throw new Exception('Не удалось подключить модуль ' . $module);
		}
	}

	$request = Context::getCurrent()->getRequest();

	$action = $request->getQuery('action')
		?: $request->getPost('action');

	$controller = new SkinAnalyseController(
		new SkinAnalyseService(
			new SkinAnalyseRepository(),
			new AiDiagnosticHelper()
		)
	);

	switch ($action) {
		case 'health_check':
			echo $controller->checkServer();
			exit;


		case 'skin_analysis':
			$file = $request->getFile('photo');

			if (empty($file)) {
				sendJson([
					'success' => false,
					'error' => 'Не передан файл',
				], 400);
			}

			echo $controller->AnalyseSkin(
				$file
			);

			exit;


		case 'save_skin_history':
			$file = $request->getFile('photo');

			$responseJson = trim(
				(string) $request->getPost('response')
			);

			$id = $controller->saveRequestInHistory(
				(int) $USER->GetID(),
				$file,
				$responseData = Json::decode($responseJson)
			);

			sendJson([
				'success' => true,
				'id' => $id,
			]);


		default:
			throw new Exception('Неизвестное действие');
	}

} catch (Throwable $e) {
	sendJson([
		'success' => false,
		'error' => $e->getMessage(),
	], 500);
}