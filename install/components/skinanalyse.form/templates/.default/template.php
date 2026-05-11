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

<div class="skinAnalyse mt-10">
    <h1>🔬 Анализ кожных заболеваний</h1>

    <div class="info">
        <strong>Как это работает:</strong><br>
        1. Загрузите фото кожного образования<br>
        2. Искусственный интеллект анализирует изображение<br>
        3. Получите вероятный диагноз с указанием уверенности
    </div>

    <form id="skinAnalyseForm" enctype="multipart/form-data">
        <?= bitrix_sessid_post() ?>

        <div class="form-group mb-10">
            <label for="photoInput" style="display: block; margin-bottom: 8px; font-weight: bold;">
                📸 Выберите фото для анализа:
            </label>

            <input
                    type="file"
                    id="photoInput"
                    name="photo"
                    accept="image/*"
                    capture="environment"
                    required
            >

            <small style="color: #666; display: block; margin-top: 5px;">
                Поддерживаемые форматы: JPG, PNG, JPEG, WEBP (до 10MB)
            </small>
        </div>

        <div class="form-check checkbox-box mt-10">
            <div>
                <input
                        type="checkbox"
                        id="personalDataRead"
                        class="form-check-input personal-data-read"
                        name="personal-data-read"
                        value="Y"
                        required
                >

                <label for="personalDataRead" class="form-check-label">
                    <?= Loc::getMessage('LABEL_PERSONAL_DATA_READ'); ?>
                    <a href="<?= SITE_DIR ?>privacy-policy/" target="_blank">
                        <?= Loc::getMessage('PERSONAL_DATA_READ'); ?>
                    </a>
                </label>
            </div>

            <div>
                <input
                        type="checkbox"
                        id="personalDataAgreed"
                        class="form-check-input personal-data-agreed"
                        name="personal-data-agreed"
                        value="Y"
                        required
                >

                <label for="personalDataAgreed" class="form-check-label">
                    <?= Loc::getMessage('LABEL_PERSONAL_DATA_AGREED'); ?>
                    <a href="<?= SITE_DIR ?>include/licenses_detail.php" target="_blank">
                        <?= Loc::getMessage('PERSONAL_DATA_AGREED'); ?>
                    </a>
                </label>
            </div>
        </div>

        <div id="skinAnalyseStatus" class="mb-3"></div>

        <div class="form-group">
            <button type="submit" class="btn btn-primary" id="AnalyseBtn" <?= $disabled ?>>
                Анализировать фото
            </button>
        </div>
    </form>

    <div id="result"></div>

    <div class="disclaimer">
        <strong>Важно:</strong> Данный анализ является предварительным и не заменяет консультацию врача.
        Для точной диагностики обратитесь к специалисту.
    </div>
</div>

<script>
    BX.ready(function() {
        BX.message({
            'USER_ID': "<?= $USER->getId() ?>",
            'SKIN_HISTORY_AJAX_URL': "<?= CUtil::JSEscape($this->GetFolder()) ?>/ajax.php",
            'SKIN_HISTORY_IBLOCK_ID': 94
        });
    });
</script>