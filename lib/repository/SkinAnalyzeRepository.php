<?php

namespace med\custom\repository;


use Bitrix\Iblock\ElementTable;
use CIBlockElement;
use Exception;


class SkinAnalyseRepository {
	public function __construct(protected int $iblockId = 94) { }

	/**
	 * @throws Exception
	 */
	public function add(array $fields): int {
		$element = new CIBlockElement();
		$id = $element->Add($fields);

		if (!$id) {
			throw new Exception($element->LAST_ERROR ?: 'Не удалось добавить элемент истории диагностики кожи');
		}

		return (int)$id;
	}

	/**
	 * @throws Exception
	 */
	public function addByData(int $userId, array $file, string $responseJson, string $name = ''): int {
		if ($this->iblockId <= 0) {
			throw new Exception('Не задан ID инфоблока для истории диагностики кожи');
		}

		if (empty($file) || empty($file['tmp_name'])) {
			throw new Exception('Не передан файл изображения');
		}

		$file['MODULE_ID'] = 'iblock';

		$fields = array(
			'IBLOCK_ID' => $this->iblockId,
			'ACTIVE' => 'Y',
			'NAME' => $name !== '' ? $name : ('Диагностика кожи ' . date('d.m.Y H:i:s')),
			'CREATED_BY' => $userId,
			'MODIFIED_BY' => $userId,
			'DETAIL_TEXT' => $responseJson,
			'DETAIL_TEXT_TYPE' => 'text',
			'DETAIL_PICTURE' => $file,
		);

		return $this->add($fields);
	}

	public function getById(int $id): ?array {
		$row = ElementTable::getList(array(
			'filter' => array(
				'=IBLOCK_ID' => $this->iblockId,
				'=ID' => $id,
			),
			'select' => array(
				'ID',
				'NAME',
				'DATE_CREATE',
				'DETAIL_TEXT',
				'DETAIL_TEXT_TYPE',
				'DETAIL_PICTURE',
				'CREATED_BY',
			),
			'limit' => 1,
		))->fetch();

		return $row ? $row : null;
	}

	public function getByUserId(int $userId, int $limit = 10, int $offset = 0): array {
		$result = ElementTable::getList(array(
			'filter' => array(
				'=IBLOCK_ID' => $this->iblockId,
				'=CREATED_BY' => $userId,
			),
			'select' => array(
				'ID',
				'NAME',
				'DATE_CREATE',
				'DETAIL_TEXT',
				'DETAIL_TEXT_TYPE',
				'DETAIL_PICTURE',
				'CREATED_BY',
			),
			'order' => array(
				'DATE_CREATE' => 'DESC',
				'ID' => 'DESC',
			),
			'limit' => $limit,
			'offset' => $offset,
		));

		$items = array();

		while ($row = $result->fetch()) {
			$items[] = $row;
		}

		return $items;
	}

	public function getCountByUserId(int $userId): int {
		return (int)ElementTable::getCount(array(
			'=IBLOCK_ID' => $this->iblockId,
			'=CREATED_BY' => $userId,
		));
	}
}