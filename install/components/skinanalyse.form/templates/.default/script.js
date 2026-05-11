document.addEventListener('DOMContentLoaded', function () {
    const AJAX_URL = BX.message('SKIN_HISTORY_AJAX_URL');


    const form = document.getElementById('skinAnalyseForm');
    const fileInput = document.getElementById('photoInput');
    const AnalyseBtn = document.getElementById('AnalyseBtn');
    const resultDiv = document.getElementById('result');
    const statusBlock = document.getElementById('skinAnalyseStatus');

    if (!form || !AnalyseBtn || !statusBlock) {
        return;
    }

    const authBlocked = AnalyseBtn.disabled;

    let serverAvailable = false;
    let isLoading = false;

    function updateAnalyseButtonState() {
        AnalyseBtn.disabled = authBlocked || !serverAvailable || isLoading;
        AnalyseBtn.textContent = isLoading ? 'Обработка...' : 'Анализировать фото';
    }

    function renderStatusMessage() {
        if (authBlocked) {
            statusBlock.innerHTML = `
                <div class="alert alert-info mb-0">
                    Воспользоваться сервисом и отправить форму может только авторизованный пользователь.
                </div>
            `;
            return;
        }

        if (!serverAvailable) {
            statusBlock.innerHTML = `
                <div class="alert alert-warning mb-0">
                    Сервис временно недоступен.
                </div>
            `;
            return;
        }

        statusBlock.innerHTML = '';
    }

    async function checkServer() {
        try {
            const response = await fetch(AJAX_URL + '?action=/health_check');


            if (!response.ok) {
                return false;
            }

            const data = await response.json();

            return true;
        } catch (error) {
            return false;
        }
    }

    async function uploadPhoto(form) {
        const formData = new FormData(form);

        formData.set('action', 'skin_analysis');

        const response = await fetch(AJAX_URL + '?action=skin_analysis&endpoint=/upload', {
            method: 'POST',
            body: formData
        });

        const responseText = await response.text();
        
        let result;

        try {
            result = JSON.parse(responseText);
        } catch (e) {
            throw new Error('Сервер вернул неверный формат данных');
        }

        if (!result.success) {
            throw new Error(result.error || 'Unknown error from server');
        }

        return result;
    }

    async function AnalysePhoto() {
        if (authBlocked) {
            resultDiv.innerHTML = `
                <div class="result error">
                    ❌ Отправить форму может только авторизованный пользователь
                </div>
            `;
            return;
        }

        if (!serverAvailable) {
            resultDiv.innerHTML = `
                <div class="result error">
                    ⚠️ Сервис временно недоступен.
                </div>
            `;
            return;
        }

        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        if (!fileInput || !fileInput.files[0]) {
            alert('Выберите фото для анализа!');
            return;
        }

        const file = fileInput.files[0];

        if (file.size > 10 * 1024 * 1024) {
            alert('Файл слишком большой! Максимальный размер: 10MB');
            return;
        }

        const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];

        if (!allowedTypes.includes(file.type)) {
            alert('Неподдерживаемый формат файла! Разрешены: JPG, PNG, WebP');
            return;
        }

        isLoading = true;
        updateAnalyseButtonState();

        resultDiv.innerHTML = '<div style="text-align: center; padding: 30px;">⏳ Обработка изображения...</div>';

        try {
            const result = await uploadPhoto(form);

            if (!result.predictions || !Array.isArray(result.predictions)) {
                throw new Error('Некорректный ответ от сервера');
            }

            let html = '<div class="result success">';
            html += '<h3>✅ Результаты анализа:</h3>';

            result.predictions.forEach((pred, index) => {
                const color = index === 0 ? '#28a745' : '#6c757d';
                const emoji = index === 0 ? '🏆 ' : `${index + 1}. `;

                html += `
                    <div class="prediction" style="border-left: 4px solid ${color}; padding: 15px; margin: 10px 0; background: white; border-radius: 5px;">
                        <strong style="font-size: 1.1em;">${emoji}${pred.clean_name || pred.class_name}</strong><br>
                        <span style="color: ${color}; font-weight: bold; font-size: 1.2em;">
                            Уверенность: ${pred.confidence}%
                        </span>
                    </div>
                `;
            });

            if (result.file_info) {
                html += `
                    <div style="margin-top: 20px; padding-top: 15px; border-top: 1px solid #ddd; font-size: 0.9em; color: #666;">
                        <strong>Информация о файле:</strong><br>
                        Название: ${result.file_info.original_name}<br>
                        Разрешение: ${result.file_info.dimensions}
                    </div>
                `;
            }

            html += '</div>';
            resultDiv.innerHTML = html;

            try {
                await saveSkinHistory(file, result);
            } catch (saveError) {
                console.error('❌ Ошибка сохранения истории анализа кожи:', saveError);
            }

        } catch (error) {
            console.error('Ошибка:', error);

            resultDiv.innerHTML = `
                <div class="result error">
                    ❌ Ошибка при анализе фото<br>
                    <small>${error.message}</small>
                </div>
            `;
        } finally {
            isLoading = false;
            updateAnalyseButtonState();
        }
    }

    async function saveSkinHistory(file, result) {
        const formData = new FormData();

        formData.append('sessid', BX.bitrix_sessid());
        formData.append('action', 'save_skin_history');
        formData.append('photo', file);
        formData.append('response', JSON.stringify(result));
        formData.append('iblock_id', BX.message('SKIN_HISTORY_IBLOCK_ID'));

        const response = await fetch(AJAX_URL, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        });

        const rawText = await response.text();
        console.log('saveSkinHistory raw response:', rawText);

        let data;

        try {
            data = JSON.parse(rawText);
        } catch (e) {
            throw new Error('ajax.php вернул некорректный ответ: ' + rawText);
        }

        if (!response.ok || !data.success) {
            throw new Error(data.error || 'Не удалось сохранить историю анализа кожи');
        }

        return data;
    }

    form.addEventListener('submit', async function (event) {
        event.preventDefault();

        await AnalysePhoto();
    });

    window.addEventListener('load', async function () {
        serverAvailable = await checkServer();
        updateAnalyseButtonState();
        renderStatusMessage();
    });
});