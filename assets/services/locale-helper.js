export function getLocale() {
    const pathParts = window.location.pathname.split('/');
    const locale = pathParts[1];
    return ['fr', 'en'].includes(locale) ? locale : 'fr';
}

export function apiUrl(path) {
    const locale = getLocale();
    // Remove leading slash if present
    const cleanPath = path.startsWith('/') ? path.slice(1) : path;
    return `/${locale}/${cleanPath}`;
}
