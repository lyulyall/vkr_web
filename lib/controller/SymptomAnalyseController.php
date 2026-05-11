<?php

namespace med\custom\controller;


use med\custom\service\SymptomAnalyseService;


class SymptomAnalyseController {
	public function __construct(protected SymptomAnalyseService $service) {
	}

	public function checkServer(): string {
		return $this->service->checkServer();
	}

	public function AnalyseSymptoms(string $symptoms): string {
		return $this->service->AnalyseSymptoms($symptoms);
	}


	public function saveRequestInHistory(int $userId, string $symptoms, string $responseJson): int {
		return $this->service->add(
			$userId,
			$symptoms,
			$responseJson
		);
	}
}