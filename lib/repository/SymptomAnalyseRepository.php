<?php

namespace med\custom\repository;


use Bitrix\Highloadblock\HighloadBlockTable;
use Bitrix\Main\SystemException;
use Bitrix\Main\Type\DateTime;
use Exception;


class SymptomAnalyseRepository {
	protected string $entityName = 'SymptomHistory';
	protected ?array $hlBlock = null;
	protected ?string $dataClass = null;

	protected function getHlBlock(): array {
		if ($this->hlBlock !== null) {
			return $this->hlBlock;
		}

		$this->hlBlock = HighloadBlockTable::getList(array(
			'filter' => array('=NAME' => $this->entityName),
			'limit' => 1,
		))->fetch();

		if (!$this->hlBlock) {
			throw new SystemException('HL-блок SymptomHistory не найден');
		}

		return $this->hlBlock;
	}

	protected function getDataClass(): string {
		if ($this->dataClass !== null) {
			return $this->dataClass;
		}

		$hlBlock = $this->getHlBlock();
		$entity = HighloadBlockTable::compileEntity($hlBlock);
		$this->dataClass = $entity->getDataClass();

		return $this->dataClass;
	}

	/**
	 * @throws Exception
	 */
	public function add(array $fields): int {
		if (empty($fields['UF_DATETIME'])) {
			$fields['UF_DATETIME'] = new DateTime();
		}

		$dataClass = $this->getDataClass();
		$result = $dataClass::add($fields);

		if (!$result->isSuccess()) {
			throw new Exception(implode(', ', $result->getErrorMessages()));
		}

		return (int)$result->getId();
	}

	/**
	 * @throws Exception
	 */
	public function addByData(int $userId, string $request, string $response, ?DateTime $dateTime = null): int {
		return $this->add(array(
			'UF_USER' => $userId,
			'UF_REQUEST' => $request,
			'UF_RESPONSE' => $response,
			'UF_DATETIME' => $dateTime ? $dateTime : new DateTime(),
		));
	}

	/**
	 * @throws SystemException
	 */
	public function getById(int $id): ?array {
		$dataClass = $this->getDataClass();

		$row = $dataClass::getList(array(
			'select' => array('ID', 'UF_USER', 'UF_DATETIME', 'UF_REQUEST', 'UF_RESPONSE'),
			'filter' => array('=ID' => $id),
			'limit' => 1,
		))->fetch();

		return $row ? $row : null;
	}

	/**
	 * @throws SystemException
	 */
	public function getByUserId(int $userId, int $limit = 20, int $offset = 0): array {
		$dataClass = $this->getDataClass();

		$result = $dataClass::getList(array(
			'select' => array('ID', 'UF_USER', 'UF_DATETIME', 'UF_REQUEST', 'UF_RESPONSE'),
			'filter' => array('=UF_USER' => $userId),
			'order' => array('UF_DATETIME' => 'DESC', 'ID' => 'DESC'),
			'limit' => $limit,
			'offset' => $offset,
		));

		$items = array();
		while ($row = $result->fetch()) {
			$items[] = $row;
		}

		return $items;
	}

	/**
	 * @throws SystemException
	 */
	public function getCountByUserId(int $userId): int {
		$dataClass = $this->getDataClass();

		return (int)$dataClass::getCount(array(
			'=UF_USER' => $userId,
		));
	}
}