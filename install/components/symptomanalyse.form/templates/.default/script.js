document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('symptomAnalyseForm');
    const symptomsInput = document.getElementById('symptomsInput');
    const resultBlock = document.getElementById('symptomResult');
    const submitBtn = document.getElementById('symptomAnalyseBtn');
    const statusBlock = document.getElementById('symptomAnalyseStatus');

    const PROXY_URL = 'https://nir--crt-guzenko.ivb24.ru/local/modules/med.appointment/src/entrypoint.php';

    if (!form || !submitBtn || !statusBlock) {
        return;
    }

    const authBlocked = submitBtn.disabled;
    let serverAvailable = false;
    let isLoading = false;

    function updateSubmitButtonState() {
        submitBtn.disabled = authBlocked || !serverAvailable || isLoading;
        submitBtn.textContent = isLoading ? 'Анализируем...' : 'Проанализировать симптомы';
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
            const response = await fetch(PROXY_URL + '?action=/health_check');

            if (!response.ok) {
                return false;
            }

            await response.json();
            return true;
        } catch (error) {
            console.error('Ошибка соединения:', error);
            return false;
        }
    }

    form.addEventListener('submit', async function (e) {
        e.preventDefault();

        if (authBlocked) {
            resultBlock.innerHTML = `
                <div class="alert alert-danger">
                    Отправить форму может только авторизованный пользователь.
                </div>
            `;
            return;
        }

        if (!serverAvailable) {
            resultBlock.innerHTML = `
                <div class="alert alert-warning">
                    Сервис временно недоступен.
                </div>
            `;
            return;
        }

        const symptoms = symptomsInput.value.trim();

        if (!symptoms) {
            resultBlock.innerHTML = `
                <div class="alert alert-danger">
                    Пожалуйста, опишите симптомы.
                </div>
            `;
            return;
        }

        isLoading = true;
        updateSubmitButtonState();

        resultBlock.innerHTML = `
            <div class="alert alert-secondary">
                Выполняется анализ симптомов...
            </div>
        `;

        try {
            const formData = new FormData();
            formData.append('action', 'symptom_analysis');
            formData.append('symptoms', symptoms);

            const response = await fetch(`${PROXY_URL}?action=symptom_analysis&endpoint=/symptom-analysis`, {
                method: 'POST',
                body: formData
            });

            const rawText = await response.text();

            let data;
            try {
                data = JSON.parse(rawText);
            } catch (e) {
                throw new Error('Сервер вернул некорректный ответ');
            }

            if (!response.ok || !data.success) {
                throw new Error(data.error || 'Ошибка при анализе симптомов');
            }

            resultBlock.innerHTML = renderSymptomResult(data);

            try {
                await saveSymptomHistory(symptoms, data);
            } catch (saveError) {
                console.error('Ошибка сохранения истории симптомов:', saveError);
            }

        } catch (error) {
            resultBlock.innerHTML = `
                <div class="alert alert-danger">
                    ${escapeHtml(error.message || 'Неизвестная ошибка')}
                </div>
            `;
        } finally {
            isLoading = false;
            updateSubmitButtonState();
        }
    });

    function renderSymptomResult(data) {
        const doctor = escapeHtml(data.doctor || 'Не указано');
        const urgency = escapeHtml(data.urgency || 'Не указано');
        const causes = Array.isArray(data.possible_causes) ? data.possible_causes : [];
        const recommendations = Array.isArray(data.recommendations) ? data.recommendations : [];

        return `
            <div class="card border-success">
                <div class="card-body">
                    <h4 class="card-title mt-5 mb-3">Результат анализа</h4>

                    <div class="mb-3">
                        <strong>К какому врачу обратиться:</strong><br>
                        ${doctor}
                    </div>

                    <div class="mb-3">
                        <strong>Срочность:</strong><br>
                        <span class="badge bg-warning text-dark">${urgency}</span>
                    </div>

                    <div class="mb-3">
                        <strong>Возможные причины:</strong>
                        <ul class="mt-2 mb-0">
                            ${causes.length
                ? causes.map(item => `<li>${escapeHtml(item)}</li>`).join('')
                : '<li>Не удалось определить</li>'
        }
                        </ul>
                    </div>

                    <div class="mb-3">
                        <strong>Рекомендации:</strong>
                        <ul class="mt-2 mb-0">
                            ${recommendations.length
                ? recommendations.map(item => `<li>${escapeHtml(item)}</li>`).join('')
                : '<li>Рекомендации отсутствуют</li>'
        }
                        </ul>
                    </div>
                </div>
            </div>
        `;
    }

    async function saveSymptomHistory(symptoms, aiResponse) {
        const formData = new FormData();
        formData.append('sessid', BX.bitrix_sessid());
        formData.append('action', 'save_symptom_history');
        formData.append('symptoms', symptoms);
        formData.append('response', JSON.stringify(aiResponse));

        const response = await fetch(BX.message('SYMPTOM_HISTORY_AJAX_URL'), {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        });

        const rawText = await response.text();

        let data;
        try {
            data = JSON.parse(rawText);
        } catch (e) {
            throw new Error('ajax.php вернул некорректный ответ');
        }

        if (!response.ok || !data.success) {
            throw new Error(data.error || 'Не удалось сохранить историю анализа');
        }

        return data;
    }

    function escapeHtml(str) {
        return String(str)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
    }

    window.addEventListener('load', async function () {
        serverAvailable = await checkServer();
        updateSubmitButtonState();
        renderStatusMessage();
    });
});