document.addEventListener('DOMContentLoaded', function() {
    // Stocker les IDs des inputs déjà initialisés
    const initializedInputs = new Set();
    
    // Cache global pour les résultats des requêtes avec TTL
    const globalSuggestionCache = {
        mention: new Map(),
        hashtag: new Map()
    };

    // Map global pour stocker les contrôleurs de requêtes en cours
    const globalActiveRequests = new Map();

    // Map global pour stocker les timeouts de debounce
    const globalDebounceTimeouts = new Map();
    
    // Map global pour stocker la dernière requête
    const globalLastQueries = new Map();

    // Durée de validité du cache en millisecondes (5 minutes)
    const CACHE_DURATION = 5 * 60 * 1000;
    
    // Délai minimum entre les requêtes en millisecondes (300ms)
    const DEBOUNCE_DELAY = 300;
    
    // Nombre minimum de caractères avant de déclencher la recherche
    const MIN_CHARS_BEFORE_SEARCH = 2;

    // Fonction debounce
    function debounce(func, wait, inputId) {
        return function executedFunction(...args) {
            // Annuler le timeout précédent pour cet input spécifique
            const previousTimeout = globalDebounceTimeouts.get(inputId);
            if (previousTimeout) {
                clearTimeout(previousTimeout);
            }

            // Annuler la requête en cours pour cet input
            const activeRequest = globalActiveRequests.get(inputId);
            if (activeRequest) {
                activeRequest.abort();
                globalActiveRequests.delete(inputId);
            }

            const later = () => {
                globalDebounceTimeouts.delete(inputId);
                func.apply(this, args);
            };

            const timeout = setTimeout(later, wait);
            globalDebounceTimeouts.set(inputId, timeout);
        };
    }

    // Fonction pour vérifier si une entrée du cache est valide
    function isCacheValid(cacheEntry) {
        return cacheEntry && 
               (Date.now() - cacheEntry.timestamp) < CACHE_DURATION;
    }

    // Fonction pour nettoyer le cache périodiquement
    function cleanCache() {
        const now = Date.now();
        for (const type of ['mention', 'hashtag']) {
            for (const [key, value] of globalSuggestionCache[type]) {
                if (now - value.timestamp >= CACHE_DURATION) {
                    globalSuggestionCache[type].delete(key);
                }
            }
        }
    }
    
    function initializeMentionInput(input) {
        const inputId = input.id || `mention-input-${Math.random().toString(36).substr(2, 9)}`;
        
        // Vérifier si l'input a déjà été initialisé
        if (initializedInputs.has(inputId)) {
            return;
        }
        
        // Marquer l'input comme initialisé
        initializedInputs.add(inputId);

        let suggestionContainer = input.nextElementSibling;
        if (!suggestionContainer || !suggestionContainer.classList.contains('mention-suggestions')) {
            suggestionContainer = document.createElement('div');
            suggestionContainer.className = 'mention-suggestions dropdown-menu';
            input.parentNode.appendChild(suggestionContainer);
        }
        
        let selectedIndex = -1;
        let currentSuggestions = [];

        // Nettoyer le cache toutes les 5 minutes
        const cacheCleanupInterval = setInterval(cleanCache, CACHE_DURATION);

        async function fetchSuggestions(type, searchTerm) {
            // Vérifier la longueur minimale
            if (searchTerm.length < MIN_CHARS_BEFORE_SEARCH) {
                hideSuggestions();
                return;
            }

            // Vérifier si la recherche est identique à la précédente
            const lastQuery = globalLastQueries.get(inputId);
            if (searchTerm === lastQuery) {
                return;
            }
            globalLastQueries.set(inputId, searchTerm);

            // Vérifier le cache
            const cacheKey = `${type}-${searchTerm.toLowerCase()}`;
            const cachedResult = globalSuggestionCache[type].get(cacheKey);
            
            if (isCacheValid(cachedResult)) {
                showSuggestions(cachedResult.suggestions);
                return;
            }

            // Créer un nouveau controller pour cette requête
            const controller = new AbortController();
            globalActiveRequests.set(inputId, controller);

            try {
                const endpoint = type === 'mention' ? 'mention-suggestions' : 'hashtag-suggestions';
                const response = await fetch(
                    `/api/${endpoint}?q=${encodeURIComponent(searchTerm)}`,
                    { 
                        signal: controller.signal,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Cache-Control': 'no-cache'
                        }
                    }
                );

                // Supprimer le contrôleur de la map une fois la requête terminée
                globalActiveRequests.delete(inputId);

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                const data = await response.json();
                
                if (data.success) {
                    const suggestions = type === 'mention' 
                        ? data.results.map(user => ({
                            text: `${user.firstName} ${user.lastName}`,
                            prefix: '@',
                            displayText: `@${user.firstName} ${user.lastName}`,
                            profilePicture: user.profilePicture
                        }))
                        : data.results.map(hashtag => ({
                            text: hashtag.name,
                            prefix: '#',
                            displayText: `#${hashtag.name} (${hashtag.usageCount} utilisations)`
                        }));

                    // Mettre en cache les résultats avec timestamp
                    globalSuggestionCache[type].set(cacheKey, {
                        suggestions,
                        timestamp: Date.now()
                    });

                    // Vérifier si c'est toujours la dernière requête
                    if (globalLastQueries.get(inputId) === searchTerm) {
                        showSuggestions(suggestions);
                    }
                }
            } catch (error) {
                if (error.name === 'AbortError') {
                    console.log('Requête annulée');
                } else {
                    console.error(`Erreur lors de la recherche des ${type}s:`, error);
                    hideSuggestions();
                }
            }
        }
        
        const debouncedFetchSuggestions = debounce(fetchSuggestions, DEBOUNCE_DELAY, inputId);
        
        function handleInput() {
            const cursorPosition = this.selectionStart;
            const textBeforeCursor = this.value.substring(0, cursorPosition);
            
            const lastMentionMatch = textBeforeCursor.match(/@([a-zA-ZÀ-ÿ]+(?:\s+[a-zA-ZÀ-ÿ]+)*)$/);
            const lastHashtagMatch = textBeforeCursor.match(/#([a-zA-Z0-9_-]+)$/);
            
            if (lastMentionMatch && lastMentionMatch[1].length > 0) {
                debouncedFetchSuggestions('mention', lastMentionMatch[1]);
            } else if (lastHashtagMatch && lastHashtagMatch[1].length > 0) {
                debouncedFetchSuggestions('hashtag', lastHashtagMatch[1]);
            } else {
                hideSuggestions();
            }
        }

        function handleKeydown(e) {
            if (!suggestionContainer.style.display || suggestionContainer.style.display === 'none') {
                return;
            }

            switch(e.key) {
                case 'ArrowDown':
                    e.preventDefault();
                    selectedIndex = Math.min(selectedIndex + 1, currentSuggestions.length - 1);
                    updateSelection();
                    break;
                case 'ArrowUp':
                    e.preventDefault();
                    selectedIndex = Math.max(selectedIndex - 1, 0);
                    updateSelection();
                    break;
                case 'Enter':
                    e.preventDefault();
                    if (selectedIndex >= 0 && selectedIndex < currentSuggestions.length) {
                        insertSuggestion(currentSuggestions[selectedIndex]);
                    }
                    break;
                case 'Escape':
                    hideSuggestions();
                    break;
            }
        }

        function handleClickOutside(e) {
            if (!input.contains(e.target) && !suggestionContainer.contains(e.target)) {
                hideSuggestions();
            }
        }

        function showSuggestions(suggestions) {
            suggestionContainer.innerHTML = '';
            currentSuggestions = suggestions;
            selectedIndex = -1;

            if (suggestions.length === 0) {
                hideSuggestions();
                return;
            }

            suggestions.forEach((suggestion, index) => {
                const div = document.createElement('div');
                div.className = 'suggestion-item';
                div.innerHTML = suggestion.displayText;
                div.addEventListener('click', () => {
                    insertSuggestion(suggestion);
                });
                suggestionContainer.appendChild(div);
            });

            suggestionContainer.style.display = 'block';
            updateSelection();
        }

        function hideSuggestions() {
            suggestionContainer.style.display = 'none';
            selectedIndex = -1;
            currentSuggestions = [];
        }

        function updateSelection() {
            const items = suggestionContainer.querySelectorAll('.suggestion-item');
            items.forEach((item, index) => {
                item.classList.toggle('active', index === selectedIndex);
            });
        }

        function insertSuggestion(suggestion) {
            const cursorPosition = input.selectionStart;
            const textBeforeCursor = input.value.substring(0, cursorPosition);
            const textAfterCursor = input.value.substring(cursorPosition);
            
            const lastMentionIndex = textBeforeCursor.lastIndexOf('@');
            const lastHashtagIndex = textBeforeCursor.lastIndexOf('#');
            const lastIndex = Math.max(lastMentionIndex, lastHashtagIndex);
            
            if (lastIndex >= 0) {
                const newText = textBeforeCursor.substring(0, lastIndex) + 
                              suggestion.prefix + suggestion.text + ' ' + 
                              textAfterCursor;
                input.value = newText;
                input.selectionStart = input.selectionEnd = lastIndex + suggestion.prefix.length + suggestion.text.length + 1;
            }
            
            hideSuggestions();
            input.focus();
        }

        // Ajouter les event listeners
        input.addEventListener('input', handleInput);
        input.addEventListener('keydown', handleKeydown);
        document.addEventListener('click', handleClickOutside);

        // Nettoyer les event listeners lors de la suppression de l'input
        input._cleanup = function() {
            input.removeEventListener('input', handleInput);
            input.removeEventListener('keydown', handleKeydown);
            document.removeEventListener('click', handleClickOutside);
            clearInterval(cacheCleanupInterval);
            initializedInputs.delete(inputId);
        };
    }

    // Fonction pour initialiser tous les inputs de mention
    function initializeAllMentionInputs() {
        document.querySelectorAll('.mention-input').forEach(input => {
            initializeMentionInput(input);
        });
    }

    // Initialiser les inputs existants
    initializeAllMentionInputs();

    // Observer les changements dans le DOM pour initialiser les nouveaux inputs
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.addedNodes.length) {
                mutation.addedNodes.forEach(function(node) {
                    if (node.nodeType === 1 && node.matches('.mention-input')) {
                        initializeMentionInput(node);
                    }
                    if (node.nodeType === 1 && node.querySelectorAll) {
                        node.querySelectorAll('.mention-input').forEach(input => {
                            initializeMentionInput(input);
                        });
                    }
                });
            }
            if (mutation.removedNodes.length) {
                mutation.removedNodes.forEach(function(node) {
                    if (node.nodeType === 1 && node._cleanup) {
                        node._cleanup();
                    }
                });
            }
        });
    });

    observer.observe(document.body, {
        childList: true,
        subtree: true
    });
}); 