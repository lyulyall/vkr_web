<?php

namespace med\custom\helpers;


use Bitrix\Main\Application;
use Bitrix\Main\Loader;
use Bitrix\Highloadblock as HL;
use CIBlock;
use CIBlockType;
use CUserTypeEntity;
use Exception;


Loader::requireModule('highloadblock');
Loader::requireModule('iblock');


class TableDBHelper {
    public function __construct(private CUserTypeEntity $cUserTypeEntity) { }

    /**
     * @throws Exception
     */
    public function install(): void {
        try {
            $this->installHLDB();
            $this->installTypeIb();
        }
        catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @throws Exception
     */
    public function unInstall(): void {
        try {
            $this->unInstallHLDB();
            $this->unInstallTypeIb();
        }
        catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    private function createHLDB(array $langs, string $name, string $tableName): array|int {
        $id = 0;
        $result = HL\HighloadBlockTable::add(array(
            'NAME' => $name,
            'TABLE_NAME' => $tableName,
        ));
        if ($result->isSuccess()) {
            $id = $result->getId();
            foreach($langs as $lang_key => $lang_val){
                HL\HighloadBlockLangTable::add(array(
                    'ID' => $id,
                    'LID' => $lang_key,
                    'NAME' => $lang_val
                ));
            }
        } else {
            $errors = $result->getErrorMessages();
            var_dump($errors);
        }
        return $id;
    }

    public function installHLDB(): void {
        $langs = array(
            'ru' => 'История анализа симптомов',
            'en' => 'История анализа симптомов'
        );
        $keys_id = $this->createHLDB($langs, 'SymptomHistory', 'symptom_history');
        if ($keys_id) {
            $this->addSymptomAnalyseHLDBfields($keys_id);
        }
    }
    
    public function installTypeIb(): bool {
        $fields = array(
            'ID' => 'symptom_data',
            'SECTIONS' => 'N',
            'IN_RSS' => 'N',
            'SORT' => 100,
            'LANG' => array(
                'en' => array(
                    'NAME' => 'Анализ симптомов',
                    'SECTION_NAME' => 'Разделы',
                    'ELEMENT_NAME' => 'Элементы'
                ),
                'ru' => array(
                    'NAME' => 'Анализ симптомов',
                    'SECTION_NAME' => 'Разделы',
                    'ELEMENT_NAME' => 'Элементы'
                )
            )
        );
        $obBlocktype = new CIBlockType;
        $db = Application::getConnection();
        $db->StartTransaction();
        $res = $obBlocktype->Add($fields);

        if(!$res) {
            $db->rollbackTransaction();
            echo 'Error: '.$obBlocktype->LAST_ERROR.'<br>';
            return false;
        }
        else {
            $this->addIbSkinAnalyse();
            $db->commitTransaction();
        }

        return true;
    }

    public function addIbSkinAnalyse(): bool {
		$ib = new CIBlock();
		$fields = array(
			'VERSION' => 2,
		    'ACTIVE' => 'Y',
		    'NAME' => 'История запросов пациентов',
		    'CODE' => 'SkinAnalyseHistory',
		    'IBLOCK_TYPE_ID' => 'symptom_data',
		    'SITE_ID' => 's1',
		    'SORT' => 100,
		);

		$ibId = $ib->Add($fields);

		if (!$ibId) {
			echo '&mdash; Ошибка добавления ИБ ' . $fields['NAME'] . $ibId->LAST_ERROR . '<br />';
			return false;
		}

		return true;
	}

    private function addSymptomAnalyseHLDBfields(int $id): void {
        $UFObject = 'HLBLOCK_'.$id;

        $fields = $this->getHLDBfield($UFObject, 'UF_RESPONSE', 'string', '50', 'Ответ нейросети', 'Ответ нейросети');
        $this->cUserTypeEntity->Add($fields);

        $fields = $this->getHLDBfield($UFObject, 'UF_REQUEST', 'string', '100', 'Запрос клиента', 'Запрос клиента');
        $this->cUserTypeEntity->Add($fields);

        $fields = $this->getHLDBfield($UFObject, 'UF_DATETIME', 'datetime', '150', 'Дата и время запроса', 'Дата и время запроса');
        $this->cUserTypeEntity->Add($fields);

        $fields = $this->getHLDBfield($UFObject, 'UF_USER', 'user', '200', 'Клиент (пользователь)', 'Клиент (пользователь)');
        $this->cUserTypeEntity->Add($fields);
    }

    private function getHLDBfield($obj_id, $name, $type, $sort, $name_ru, $name_en, $filter = 'E') : array {
        $arLangFields = array('EDIT_FORM_LABEL','LIST_COLUMN_LABEL','LIST_FILTER_LABEL');
        $fields = array(
            'ENTITY_ID' => $obj_id,
            'FIELD_NAME' => $name,
            'USER_TYPE_ID' => $type,
            'XML_ID' => '',
            'SORT' => $sort,
            'MULTIPLE' => NULL,
            'MANDATORY' => 'Y',
            'SHOW_FILTER' => $filter,
            'SHOW_IN_LIST' => NULL,
            'EDIT_IN_LIST' => NULL,
            'IS_SEARCHABLE' => NULL,
        );
        foreach($arLangFields as $LANG_FIELD)
        {
            $fields[$LANG_FIELD]['ru'] = $name_ru;
            $fields[$LANG_FIELD]['en'] = $name_en;
        }
        return $fields;
    }

    private function getHLDB_id(string $name) : ?int {
        $hlBlock = HL\HighloadBlockTable::getList(
            array('filter' => array(
                'TABLE_NAME' => $name
            ))
        )->fetch();
        return $hlBlock['ID'];
    }

    private function unInstallHLDB(): void {
        $id = $this->getHLDB_id('SymptomHistory');
        if ($id) HL\HighloadBlockTable::delete($id);
    }

    function unInstallTypeIb(): void {
        $db = Application::getConnection();
        $db->StartTransaction();
        if(!CIBlockType::Delete('symptom_data')) {
            $db->rollbackTransaction();
        }

        $db->commitTransaction();
    }
}
