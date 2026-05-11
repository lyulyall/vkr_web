<?php

namespace med\custom\integration\AiDiagnostic;


use Bitrix\Main\Config\Option;
use Bitrix\Main\Web\Json;
use Exception;


class AiDiagnosticHelper {
	protected string $serverUrl;

	protected array $headers = [
		'ngrok-skip-browser-warning: 1',
		'User-Agent: Mozilla/5.0 (compatible; MyApp/1.0)',
	];

	public function __construct() {
		$this->serverUrl = Option::get(
			'med.appointment',
			'PATH_TO_AI_DIAGNOSTIC_SERVER'
		);
	}

	public function checkServer(): string {
		return $this->sendRequest(
			$this->serverUrl . '/health'
		);
	}

	public function analyzeSymptoms(string $symptoms): string {
		$payload = Json::encode([
			'symptoms' => $symptoms,
		], JSON_UNESCAPED_UNICODE);

		return $this->sendRequest(
			$this->serverUrl . '/symptom-analysis',
			$payload,
			array_merge($this->headers, [
				'Content-Type: application/json',
				'Content-Length: ' . strlen($payload),
			])
		);
	}

	protected function sendRequest(string $url, ?string $payload = null, ?array $headers = null): string {
		$ch = curl_init();

		$options = [
			CURLOPT_URL => $url,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_SSL_VERIFYHOST => false,
			CURLOPT_HTTPHEADER => $headers ?? $this->headers,
		];

		if ($payload !== null) {
			$options[CURLOPT_POST] = true;
			$options[CURLOPT_POSTFIELDS] = $payload;
		}

		curl_setopt_array($ch, $options);

		$response = curl_exec($ch);
		$error = curl_error($ch);

		curl_close($ch);

		if ($error) {
			throw new Exception(
				'cURL error: ' . $error
			);
		}

		return $response;
	}
}