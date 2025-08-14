// Module pour gérer les tokens CSRF
document.addEventListener('DOMContentLoaded', function() {
    console.log('Module CSRF helper chargé');
    
    // Fonction pour obtenir le token CSRF depuis les méta-tags
    function getCsrfToken() {
        // Chercher le meta tag avec le token CSRF
        const metaToken = document.querySelector('meta[name="csrf-token"]');
        if (metaToken) {
            return metaToken.getAttribute('content');
        }
        
        // Chercher dans les formulaires existants
        const csrfInput = document.querySelector('input[name="_token"], input[name="_csrf_token"]');
        if (csrfInput) {
            return csrfInput.value;
        }
        
        // Aucun token trouvé
        console.warn('Aucun token CSRF trouvé sur la page');
        return null;
    }
    
    // Ajouter automatiquement le token CSRF à toutes les requêtes fetch POST
    const originalFetch = window.fetch;
    
    window.fetch = function(url, options = {}) {
        // Ne modifier que les requêtes POST
        if (options.method && options.method.toUpperCase() === 'POST') {
            // Obtenir le token CSRF
            const token = getCsrfToken();
            
            // Si un token existe, l'ajouter aux en-têtes
            if (token) {
                // S'assurer que headers existe
                if (!options.headers) {
                    options.headers = {};
                }
                
                // Ajouter le token CSRF à l'en-tête
                if (!(options.headers instanceof Headers)) {
                    options.headers = new Headers(options.headers);
                }
                
                // Ajouter l'en-tête CSRF
                if (!options.headers.has('X-CSRF-TOKEN')) {
                    options.headers.set('X-CSRF-TOKEN', token);
                }
                
                // Si c'est une requête JSON, ajouter le token au corps si possible
                if (options.headers.get('Content-Type') === 'application/json') {
                    try {
                        let body = {};
                        
                        // Si le corps existe, le parser
                        if (options.body) {
                            body = JSON.parse(options.body);
                        }
                        
                        // Ajouter le token au corps
                        body._token = token;
                        
                        // Mettre à jour le corps
                        options.body = JSON.stringify(body);
                    } catch (e) {
                        console.warn('Impossible d\'ajouter le token CSRF au corps JSON', e);
                    }
                }
            }
        }
        
        // Appeler la fonction fetch originale
        return originalFetch.call(this, url, options);
    };
    
    // Rendre la fonction disponible globalement
    window.getCsrfToken = getCsrfToken;
    
    console.log('CSRF helper initialisé');
});

export default {};
