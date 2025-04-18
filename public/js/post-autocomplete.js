document.addEventListener('DOMContentLoaded', function() {
    const mentionInputs = document.querySelectorAll('.mention-input');
    
    mentionInputs.forEach(input => {
        let suggestionContainer = document.createElement('div');
        suggestionContainer.className = 'mention-suggestions';
        input.parentNode.insertBefore(suggestionContainer, input.nextSibling);
        
        let currentSuggestions = [];
        let selectedIndex = -1;
        
        input.addEventListener('input', async function() {
            const cursorPos = this.selectionStart;
            const content = this.value;
            const beforeCursor = content.slice(0, cursorPos);
            
            // Recherche du dernier @ ou # avant le curseur
            const lastMentionMatch = beforeCursor.match(/(@[^@\s]*)$/);
            const lastHashtagMatch = beforeCursor.match(/(#\w*)$/);
            
            if (lastMentionMatch) {
                const searchTerm = lastMentionMatch[1].slice(1); // Enlever le @
                if (searchTerm.length > 0) {
                    try {
                        const response = await fetch(`/api/mention-suggestions?q=${encodeURIComponent(searchTerm)}`);
                        const data = await response.json();
                        if (data.success) {
                            currentSuggestions = data.results;
                            showSuggestions(data.results.map(user => ({
                                text: `${user.firstName} ${user.lastName}`,
                                value: `${user.firstName} ${user.lastName}`,
                                prefix: '@',
                                displayText: `${user.firstName} ${user.lastName}${user.profilePicture ? ' 🖼️' : ''}`
                            })));
                        }
                    } catch (error) {
                        console.error('Erreur lors de la recherche des mentions:', error);
                    }
                } else {
                    hideSuggestions();
                }
            } else if (lastHashtagMatch) {
                const searchTerm = lastHashtagMatch[1].slice(1); // Enlever le #
                if (searchTerm.length > 0) {
                    try {
                        const response = await fetch(`/api/hashtag-suggestions?q=${encodeURIComponent(searchTerm)}`);
                        const data = await response.json();
                        if (data.success) {
                            showSuggestions(data.results.map(hashtag => ({
                                text: hashtag.name,
                                prefix: '#',
                                displayText: `#${hashtag.name} (${hashtag.usageCount} utilisations)`
                            })));
                        }
                    } catch (error) {
                        console.error('Erreur lors de la recherche des hashtags:', error);
                    }
                }
            } else {
                hideSuggestions();
            }
        });
        
        function showSuggestions(suggestions) {
            suggestionContainer.innerHTML = '';
            if (suggestions.length === 0) {
                hideSuggestions();
                return;
            }

            suggestions.forEach((suggestion, index) => {
                const div = document.createElement('div');
                div.className = 'mention-item';
                div.innerHTML = suggestion.displayText;
                div.addEventListener('click', () => {
                    insertSuggestion(suggestion);
                });
                if (index === selectedIndex) {
                    div.classList.add('selected');
                }
                suggestionContainer.appendChild(div);
            });

            suggestionContainer.style.display = 'block';
        }
        
        function hideSuggestions() {
            suggestionContainer.style.display = 'none';
            selectedIndex = -1;
            currentSuggestions = [];
        }
        
        function insertSuggestion(suggestion) {
            const cursorPos = input.selectionStart;
            const content = input.value;
            const beforeCursor = content.slice(0, cursorPos);
            const afterCursor = content.slice(cursorPos);
            
            const lastAtPos = beforeCursor.lastIndexOf('@');
            const insertText = `@${suggestion.value} `;
            
            input.value = content.slice(0, lastAtPos) + insertText + afterCursor;
            input.selectionStart = lastAtPos + insertText.length;
            input.selectionEnd = input.selectionStart;
            hideSuggestions();
            input.focus();
        }
        
        function updateSelection() {
            const items = suggestionContainer.getElementsByClassName('mention-item');
            Array.from(items).forEach((item, index) => {
                if (index === selectedIndex) {
                    item.classList.add('selected');
                    item.scrollIntoView({ block: 'nearest' });
                } else {
                    item.classList.remove('selected');
                }
            });
        }
        
        input.addEventListener('keydown', function(e) {
            const suggestions = suggestionContainer.getElementsByClassName('mention-item');
            
            if (suggestions.length === 0) return;

            if (e.key === 'ArrowDown') {
                e.preventDefault();
                selectedIndex = (selectedIndex + 1) % suggestions.length;
                updateSelection();
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                selectedIndex = selectedIndex <= 0 ? suggestions.length - 1 : selectedIndex - 1;
                updateSelection();
            } else if (e.key === 'Enter' && selectedIndex >= 0) {
                e.preventDefault();
                const suggestion = currentSuggestions[selectedIndex];
                insertSuggestion({
                    text: `${suggestion.firstName} ${suggestion.lastName}`,
                    value: `${suggestion.firstName} ${suggestion.lastName}`,
                    prefix: '@'
                });
            } else if (e.key === 'Escape') {
                hideSuggestions();
            }
        });
        
        // Fermer les suggestions en cliquant en dehors
        document.addEventListener('click', function(e) {
            if (!suggestionContainer.contains(e.target) && e.target !== input) {
                hideSuggestions();
            }
        });
    });
}); 