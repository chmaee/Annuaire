document.addEventListener("DOMContentLoaded", function() {
    const modifyPasswordBtn = document.getElementById('modify-password-btn');
    const passwordFields = document.getElementById('password-fields');

    if (modifyPasswordBtn) {
        modifyPasswordBtn.addEventListener('click', function() {
            if (passwordFields) {
                passwordFields.style.display = passwordFields.style.display === 'none' ? 'block' : 'none';
            }
        });
    }

    const codeInput = document.getElementById('utilisateur_code');
    const feedbackArea = document.getElementById('codeFeedback');

    if (codeInput) {
        codeInput.addEventListener("input", verifierCode);
    }
});

async function verifierCode() {
    const codeInput = document.getElementById('utilisateur_code');
    const feedbackArea = document.getElementById('codeFeedback');
    const login = codeInput.dataset.login || '';
    const code = codeInput.value;
    const url = Routing.generate('app_verifier_code');

    if (code === '') {
        feedbackArea.textContent = '';
        return;
    }

    try {
        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ code: code, login: login })
        });

        if (!response.ok) {
            throw new Error('Erreur réseau : ' + response.status);
        }

        const data = await response.json();
        if (data.available) {
            feedbackArea.textContent = 'Le code est disponible.';
            feedbackArea.style.color = 'green';
        } else {
            feedbackArea.textContent = 'Ce code est déjà pris.';
            feedbackArea.style.color = 'red';
        }
    } catch (error) {
        console.error('Error:', error);
        feedbackArea.textContent = 'Erreur lors de la vérification.';
        feedbackArea.style.color = 'red';
    }
}


document.addEventListener("DOMContentLoaded", function() {
    const getJsonButton = document.getElementById('getJsonData');

    if (getJsonButton) {
        getJsonButton.addEventListener('click', function() {
            const login = getJsonButton.getAttribute('data-login');
            const url = Routing.generate('app_utilisateur_json', { login: login });

            fetch(url)
                .then(response => response.json())
                .then(data => console.log(data))
                .catch(error => console.error('Erreur:', error));
        });
    }
});
