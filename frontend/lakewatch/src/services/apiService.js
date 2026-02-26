const urlBase = 'https://app.lakewatch.tech';

export async function login() {
    window.location.href = `${urlBase}/bff/auth/login`;
}

// Central fetch wrapper that handles 401 for every request
async function apiFetch(url, options = {}) {
    const response = await fetch(url, {
        method: 'GET',
        credentials: 'include',
        headers: {
            'Content-Type': 'application/json',
        },
        ...options,
    });

    if (response.status === 401) {
        localStorage.clear();
        login();
        return null;
    }

    if (!response.ok) {
        throw new Error(`Request failed: ${response.status}`);
    }

    return response.json();
}

export async function getMe() {
    try {
        const user = await apiFetch(`${urlBase}/bff/auth/me`);

        if (user) {
            localStorage.setItem('user', JSON.stringify(user));
        }

        return user;
    } catch (error) {
        console.error('Error fetching user:', error);
        localStorage.clear();
        login();
        return null;
    }
}

export async function getProbes(userId) {
    try {
        return await apiFetch(`${urlBase}/bff/api/users/${userId}/probes`);
    } catch (error) {
        console.error('getProbes error:', error);
        throw error;
    }
}

export async function getData(probeId) {
    try {
        return await apiFetch(`${urlBase}/bff/api/probes/${probeId}/data?hours=24`);
    } catch (error) {
        console.error('getData error:', error);
        throw error;
    }
}

export async function getNotifications(id) {
    try {
        return await apiFetch(`${urlBase}/bff/api/users/${id}/notifications`);
    } catch (error) {
        console.error('getNotifications error:', error);
        throw error;
    }
}

export async function deleteNotifications(id) {
    try {
        return await apiFetch(`${urlBase}/bff/api/notifications/${id}`, {
            method: 'DELETE',
        });
    } catch (error) {
        console.error('deleteNotifications error:', error);
        throw error;
    }
}

export async function getNews(id) {
    try {
        return await apiFetch(`${urlBase}/bff/api/users/${id}/news`);
    } catch (error) {
        console.error('getNews error:', error);
        throw error;
    }
}