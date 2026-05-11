<?php

use Bitrix\Main\Loader;
use Bitrix\Main\Web\Json;
use Bitrix\Main\Web\Uri;
use med\custom\integration\AiDiagnostic\AiDiagnosticHelper;
use med\custom\repository\SkinAnalyseRepository;
use med\custom\service\SkinAnalyseService;


defined('B_PROLOG_INCLUDED') || die;

class SkinanalyseHistoryComponent extends CBitrixComponent {
	protected function checkModules() {
		if (!Loader::includeModule('iblock')) {
			throw new Exception('Не подключен модуль iblock');
		}

		if (!Loader::includeModule('med.appointment')) {
			throw new Exception('Не подключен модуль med.appointment');
		}
	}

	protected function getService(): SkinAnalyseService {
		$iblockId = (int)$this->arParams['IBLOCK_ID'];

		if ($iblockId <= 0) {
			throw new Exception('Не задан параметр IBLOCK_ID');
		}

		$repository = new SkinAnalyseRepository($iblockId);

		return new SkinAnalyseService($repository, new AiDiagnosticHelper());
	}

	protected function getPageSize() {
		$allowed = array(5, 10, 20);
		$pageSize = (int)$this->request->getQuery('page_size');

		if (!in_array($pageSize, $allowed, true)) {
			$pageSize = (int)$this->arParams['LIMIT'];
		}

		if (!in_array($pageSize, $allowed, true)) {
			$pageSize = 10;
		}

		return $pageSize;
	}

	protected function getPage() {
		$page = (int)$this->request->getQuery('page');

		if ($page < 1) {
			$page = 1;
		}

		return $page;
	}

	protected function getOffsetByPage($page, $pageSize) {
		return ($page - 1) * $pageSize;
	}

	protected function buildPageUrl($page, $pageSize) {
		$uri = new Uri($this->request->getRequestUri());
		$uri->deleteParams(array('page', 'page_size'));
		$uri->addParams(array(
			'page' => $page,
			'page_size' => $pageSize,
		));

		return $uri->getUri();
	}

	protected function prepareItems(array $items) {
		$result = array();

		foreach ($items as $item) {
			$responseData = array();

			if (!empty($item['DETAIL_TEXT'])) {
				try {
					$decoded = Json::decode((string)$item['DETAIL_TEXT']);
					$responseData = is_array($decoded) ? $decoded : array();
				} catch (Throwable $e) {
					$responseData = array();
				}
			}

			$picture = array();
			$pictureSrc = '';

			if (!empty($item['DETAIL_PICTURE'])) {
				$picture = CFile::GetFileArray((int)$item['DETAIL_PICTURE']);

				if (!is_array($picture)) {
					$picture = array();
				}

				if (!empty($picture['SRC'])) {
					$pictureSrc = (string)$picture['SRC'];
				}
			}

			$result[] = array(
				'ID' => (int)$item['ID'],
				'NAME' => (string)$item['NAME'],
				'DATE' => !empty($item['DATE_CREATE']) ? (string)$item['DATE_CREATE'] : '',
				'PHOTO' => $picture,
				'PHOTO_SRC' => $pictureSrc,
				'RESPONSE' => $responseData,
			);
		}

		return $result;
	}

	protected function buildPagination($page, $pageSize, $total) {
		$totalPages = $pageSize > 0 ? (int)ceil($total / $pageSize) : 1;

		if ($totalPages < 1) {
			$totalPages = 1;
		}

		$pagination = array(
			'PAGE' => $page,
			'PAGE_SIZE' => $pageSize,
			'TOTAL' => $total,
			'TOTAL_PAGES' => $totalPages,
			'HAS_PREV' => $page > 1,
			'HAS_NEXT' => $page < $totalPages,
			'PREV_URL' => $page > 1 ? $this->buildPageUrl($page - 1, $pageSize) : '',
			'NEXT_URL' => $page < $totalPages ? $this->buildPageUrl($page + 1, $pageSize) : '',
			'PAGE_SIZE_OPTIONS' => array(5, 10, 20),
			'PAGE_URLS' => array(),
		);

		if ($totalPages <= 7) {
			for ($i = 1; $i <= $totalPages; $i++) {
				$pagination['PAGE_URLS'][] = array(
					'TYPE' => 'page',
					'NUMBER' => $i,
					'URL' => $this->buildPageUrl($i, $pageSize),
					'IS_CURRENT' => ($i === $page),
				);
			}

			return $pagination;
		}

		$pages = array(1);

		for ($i = $page - 1; $i <= $page + 1; $i++) {
			if ($i > 1 && $i < $totalPages) {
				$pages[] = $i;
			}
		}

		$pages[] = $totalPages;
		$pages = array_unique($pages);
		sort($pages);

		$prevPageNumber = null;

		foreach ($pages as $pageNumber) {
			if ($prevPageNumber !== null && ($pageNumber - $prevPageNumber) > 1) {
				$pagination['PAGE_URLS'][] = array(
					'TYPE' => 'dots',
				);
			}

			$pagination['PAGE_URLS'][] = array(
				'TYPE' => 'page',
				'NUMBER' => $pageNumber,
				'URL' => $this->buildPageUrl($pageNumber, $pageSize),
				'IS_CURRENT' => ($pageNumber === $page),
			);

			$prevPageNumber = $pageNumber;
		}

		return $pagination;
	}

	public function executeComponent() {
		global $USER;

		try {
			$this->checkModules();

			$this->arResult['IS_AUTHORIZED'] = $USER->IsAuthorized();
			$this->arResult['ITEMS'] = array();
			$this->arResult['PAGINATION'] = array();

			if ($this->arResult['IS_AUTHORIZED']) {
				$service = $this->getService();
				$userId = (int)$USER->GetID();
				$pageSize = $this->getPageSize();
				$page = $this->getPage();
				$total = $service->getUserHistoryCount($userId);

				$totalPages = $pageSize > 0 ? (int)ceil($total / $pageSize) : 1;

				if ($totalPages < 1) {
					$totalPages = 1;
				}

				if ($page > $totalPages) {
					$page = $totalPages;
				}

				$offset = $this->getOffsetByPage($page, $pageSize);
				$items = $service->getUserHistory($userId, $pageSize, $offset);

				$this->arResult['ITEMS'] = $this->prepareItems($items);
				$this->arResult['PAGINATION'] = $this->buildPagination($page, $pageSize, $total);
			}

			$this->includeComponentTemplate();
		} catch (Throwable $e) {
			ShowError($e->getMessage());
		}
	}
}