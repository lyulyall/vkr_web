<?php

namespace med\custom\service;


use Exception;
use med\custom\integration\AiDiagnostic\AiDiagnosticHelper;
use med\custom\repository\SymptomAnalyseRepository;


class SymptomAnalyzeService {
	public function __construct(protected SymptomAnalyseRepository $repository) {
	}

	/**
	 * @throws Exception
	 */
	public function add(int $userId, string $requestText, string $responseJson): int {
		return $this->repository->addByData(
			$userId,
			$requestText,
			$responseJson
		);
	}

	public function checkServer(): string {
		return (new AiDiagnosticHelper())->checkServer();
	}

	public function analyzeSymptoms(string $symptoms): string {
		return (new AiDiagnosticHelper())->analyzeSymptoms($symptoms);
	}

	public function getUserHistory(int $userId, int $limit = 20, int $offset = 0): array {
		return $this->repository->getByUserId($userId, $limit, $offset);
	}

	public function getUserHistoryCount(int $userId): int {
		return $this->repository->getCountByUserId($userId);
	}

	public function getById(int $id): ?array {
		return $this->repository->getById($id);
	}
}