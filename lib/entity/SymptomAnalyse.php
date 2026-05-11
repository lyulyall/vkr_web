<?php

namespace med\custom\entity;


use Bitrix\Main\Type\DateTime;


class SymptomAnalyse{
	public function __construct(
		protected ?int $id = null,
		protected int $userId = 0,
		protected string $request = '',
		protected string $response = '',
		protected ?DateTime $dateTime = null,
	) { }
}