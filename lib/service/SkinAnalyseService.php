<?php

namespace med\custom\service;


use Exception;
use med\custom\integration\AiDiagnostic\AiDiagnosticHelper;
use med\custom\repository\SkinAnalyseRepository;


class SkinAnalyseService {
	public function __construct(
		protected SkinAnalyseRepository $repository,
		protected AiDiagnosticHelper $ai
	) { }

	/**
	 * @throws Exception
	 */
	public function add(int $userId, array $file, string $responseJson): int {
		$responseData = json_decode($responseJson, true);

		return $this->repository->addByData(
			$userId,
			$file,
			$responseJson,
			$this->buildElementName($responseData)
		);
	}

	public function checkServer(): string {
		return $this->ai->checkServer();
	}

	public function AnalyseSkin(array $file): string {
		return $this->ai->AnalyseSkin($file);
	}


	public function getUserHistory(int $userId, int $limit = 10, int $offset = 0): array {
		return $this->repository->getByUserId($userId, $limit, $offset);
	}

	public function getUserHistoryCount(int $userId): int {
		return $this->repository->getCountByUserId($userId);
	}

	public function getById(int $id): ?array {
		return $this->repository->getById($id);
	}

	protected function buildElementName(array $responseData): string {
		$title = 'Диагностика кожи';

		if (isset($responseData['predictions'][0]) && is_array($responseData['predictions'][0])) {
			$firstPrediction = $responseData['predictions'][0];
			$label = '';

			if (!empty($firstPrediction['clean_name'])) {
				$label = (string)$firstPrediction['clean_name'];
			} elseif (!empty($firstPrediction['class_name'])) {
				$label = (string)$firstPrediction['class_name'];
			}

			if ($label !== '') {
				$title .= ' - ' . $label;
			}
		}

		$title .= ' - ' . date('d.m.Y H:i:s');

		return $title;
	}
}