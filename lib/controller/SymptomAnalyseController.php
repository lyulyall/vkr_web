<?php

namespace med\custom\controller;


use med\custom\service\SymptomAnalyzeService;


class SymptomAnalyseController {
	public function __construct(protected SymptomAnalyzeService $service) {
	}

	public function checkServer(): string {
		return $this->service->checkServer();
	}

	public function analyzeSymptoms(string $symptoms): string {
		return $this->service->analyzeSymptoms($symptoms);
	}


	public function saveRequestInHistory(int $userId, string $symptoms, string $responseJson): int {
		return $this->service->add(
			$userId,
			$symptoms,
			$responseJson
		);
	}
}