// Use relative URLs for development (Vite will proxy them)
// In production, URLs will go directly to app.lakewatch.com
const urlBase = '';

export async function login() {
    window.location.href = `${urlBase}/bff/auth/login`;
}

export async function getMe() {
    try {
        const response = await fetch(`${urlBase}/bff/auth/me`, {
            method: 'GET',
            credentials: 'include',
            headers: {
                'Content-Type': 'application/json',
            },
        });

        if (!response.ok) {
            if (response.status === 401) {
                localStorage.clear();
                login();
                return null;
            }
            throw new Error(`Failed to fetch user: ${response.status}`);
        }

        const user = await response.json();
        
        localStorage.setItem('user', JSON.stringify(user));
        
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
        const response = await fetch(`/bff/api/users/${userId}/probes`, {
            method: 'GET',
            credentials: 'include',
            headers: {
                'Content-Type': 'application/json',
            },
        });
        if (!response.ok) {
            throw new Error(`Failed to fetch probes: ${response.status}`);
        }

        return await response.json();
    } catch (error) {
        console.error('getProbes error:', error);
        throw error;
    }
}