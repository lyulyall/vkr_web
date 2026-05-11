<?php

namespace med\custom\controller;


use Exception;
use med\custom\service\SkinAnalyseService;


class SkinAnalyseController {
	public function __construct(protected SkinAnalyseService $service) {
	}

	public function checkServer(): string {
		return $this->service->checkServer();
	}

	public function AnalyseSkin(array $file): string {
		return $this->service->AnalyseSkin(
			$file
		);
	}

	public function saveRequestInHistory(int $userId, array $file, string $responseJson): int {
		return $this->service->add(
			$userId,
			$file,
			$responseJson
		);
	}
}