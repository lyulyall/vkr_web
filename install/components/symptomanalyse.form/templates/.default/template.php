<?php

use Bitrix\Main\Localization\Loc;


defined('B_PROLOG_INCLUDED') || die;

/**
 * @var $USER
 * @var $arParams
 * @var $arResult
 */

$userAuthorized = $arParams['USER_AUTHORIZED'];
$disabled = !$userAuthorized ? 'disabled' : '';
?>

<div class="symptomAnalyse container mb-5 mt-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h2 class="card-title mb-4">Анализ симптомов</h2>

                    <div class="info">
                        <strong>Как это работает:</strong><br>
                        Опишите ваши симптомы, и система подскажет:<br>
                        - К какому врачу стоит обратиться <br>
                        - Возможные причины <br>
                        - Срочность обращения
                    </div>

                    <form id="symptomAnalyseForm" class="mb-5">
                        <div class="mb-10">
                            <label for="symptomsInput" class="form-label fw-bold">
                                Опишите симптомы
                            </label>
                            <textarea
                                    class="form-control"
                                    id="symptomsInput"
                                    name="symptoms"
                                    rows="6"
                                    placeholder="Например: у меня болит сердце, испытываю колющие боли, иногда трудно дышать"
                                    required
                            ></textarea>
                            <div class="form-text">
                                Укажите симптомы как можно подробнее.
                            </div>
                        </div>

                        <div id="symptomAnalyseStatus" class="mb-3"></div>

                        <div class="form-check checkbox-box">
                            <input type="checkbox" class="form-check-input personal-data-read" name="personal-data-read" value="checked" required>
                            <label for="personal-data-read" class="form-check-label">
                                <?=Loc::getMessage('LABEL_PERSONAL_DATA_READ');?>
                                <a href="<?=SITE_DIR?>privacy-policy/" target="_blank">
                                    <?=Loc::getMessage('PERSONAL_DATA_READ');?>
                                </a>
                            </label>
                            <br>
                            <input type="checkbox" class="form-check-input personal-data-agreed" name="personal-data-agreed" value="checked" required>
                            <label for="personal-data-agreed" class="form-check-label">
                                <?=Loc::getMessage('LABEL_PERSONAL_DATA_AGREED');?>
                                <a href="<?=SITE_DIR?>include/licenses_detail.php" target="_blank">
                                    <?=Loc::getMessage('PERSONAL_DATA_AGREED');?>
                                </a>
                            </label>
                        </div>
                        <button type="submit" class="btn btn-primary mt-10" id="symptomAnalyseBtn" <?=$disabled?>>
                            Проанализировать симптомы
                        </button>
                    </form>

                    <div id="symptomResult" class="mt-5"></div>

                    <div class="disclaimer">
                        <strong>Важно:</strong> Данный анализ не заменяет консультацию врача и не является диагнозом.
                        Обратитесь за медицинской помощью!
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    BX.ready(function() {
        BX.message({
            'USER_ID': "<?=$USER->getId()?>",
            'SYMPTOM_HISTORY_AJAX_URL': "<?=CUtil::JSEscape($this->GetFolder())?>/ajax.php"
        });
    });
</script>
