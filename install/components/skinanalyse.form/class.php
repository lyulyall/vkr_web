<?php

defined('B_PROLOG_INCLUDED') || die;

class SkinAnalyse extends CBitrixComponent {
    public function executeComponent(): void {
		if (!CModule::IncludeModule('med.appointment')){
			http_response_code(500);
			exit;
		}

		$this->IncludeComponentTemplate();
	}
}
