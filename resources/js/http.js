/** Small fetch wrapper: JSON, CSRF token, user-friendly errors. */
const token = () => document.querySelector('meta[name="csrf-token"]')?.content;

export async function http(url, { method = 'GET', body = null } = {}) {
    const response = await fetch(url, {
        method,
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': token(),
            'X-Requested-With': 'XMLHttpRequest',
        },
        body: body ? JSON.stringify(body) : null,
        credentials: 'same-origin',
    });

    let data = {};
    try {
        data = await response.json();
    } catch {
        // Non-JSON response (should not happen): keep generic message below.
    }

    if (response.status === 401) {
        window.location.href = data.redirect ?? '/connexion';
        throw new Error('Veuillez vous connecter.');
    }

    if (!response.ok) {
        const firstError = data.errors ? Object.values(data.errors)[0]?.[0] : null;
        const message =
            firstError ??
            data.message ??
            (response.status === 419
                ? 'Votre session a expiré. Rechargez la page.'
                : response.status === 429
                  ? 'Trop de tentatives. Patientez quelques instants.'
                  : 'Une erreur est survenue. Veuillez réessayer.');
        throw new Error(message);
    }

    return data;
}
