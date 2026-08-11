/**
 * Application JavaScript principale
 * Gère la synchronisation, les graphiques et les interactions
 */

// État de l'application
var app = {
    currentPeriod: 30,
    currentSort: 'progression',
    currentSearch: '',
    tracks: [],
    charts: {}
};

/**
 * Initialisation au chargement de la page
 */
document.addEventListener('DOMContentLoaded', function() {
    initSyncButton();
    initTopProgressionsTabs();
    initFilters();
    loadTracks();
    loadTopProgressions(30);
});

/**
 * Initialise le bouton de synchronisation
 */
function initSyncButton() {
    var syncBtn = document.getElementById('syncBtn');
    if (!syncBtn) return;
    
    syncBtn.addEventListener('click', function() {
        startSync();
    });
}

/**
 * Lance la synchronisation
 */
function startSync() {
    var syncBtn = document.getElementById('syncBtn');
    var syncProgress = document.getElementById('syncProgress');
    var progressMessages = syncProgress.querySelector('.progress-messages');
    
    // Désactiver le bouton
    syncBtn.disabled = true;
    syncBtn.querySelector('.btn-text').style.display = 'none';
    syncBtn.querySelector('.btn-loading').style.display = 'inline';
    
    // Afficher la zone de progression
    syncProgress.style.display = 'block';
    progressMessages.innerHTML = '';
    
    // Créer un EventSource pour le streaming
    var eventSource = new EventSource('actions/sync.php');
    
    eventSource.onmessage = function(event) {
        var data = JSON.parse(event.data);
        
        if (data.message) {
            var messageDiv = document.createElement('div');
            messageDiv.className = 'progress-message';
            messageDiv.textContent = data.message;
            progressMessages.appendChild(messageDiv);
            
            // Scroller vers le bas
            progressMessages.scrollTop = progressMessages.scrollHeight;
        }
        
        if (data.done || data.error) {
            eventSource.close();
            
            // Réactiver le bouton
            syncBtn.disabled = false;
            syncBtn.querySelector('.btn-text').style.display = 'inline';
            syncBtn.querySelector('.btn-loading').style.display = 'none';
            
            // Recharger les données
            if (data.done) {
                setTimeout(function() {
                    location.reload();
                }, 2000);
            }
        }
    };
    
    eventSource.onerror = function() {
        eventSource.close();
        
        var messageDiv = document.createElement('div');
        messageDiv.className = 'progress-message';
        messageDiv.textContent = 'Erreur de connexion';
        progressMessages.appendChild(messageDiv);
        
        // Réactiver le bouton
        syncBtn.disabled = false;
        syncBtn.querySelector('.btn-text').style.display = 'inline';
        syncBtn.querySelector('.btn-loading').style.display = 'none';
    };
}

/**
 * Initialise les onglets des top progressions
 */
function initTopProgressionsTabs() {
    var tabs = document.querySelectorAll('.tab-btn');
    
    for (var i = 0; i < tabs.length; i++) {
        tabs[i].addEventListener('click', function() {
            // Retirer la classe active de tous les onglets
            var allTabs = document.querySelectorAll('.tab-btn');
            for (var j = 0; j < allTabs.length; j++) {
                allTabs[j].classList.remove('active');
            }
            
            // Ajouter la classe active à l'onglet cliqué
            this.classList.add('active');
            
            // Charger les progressions
            var days = parseInt(this.getAttribute('data-days'));
            loadTopProgressions(days);
        });
    }
}

/**
 * Charge le top des progressions
 */
function loadTopProgressions(days) {
    var listContainer = document.getElementById('topProgressionsList');
    if (!listContainer) return;
    
    listContainer.innerHTML = '<div class="loading">Chargement...</div>';
    
    // Appel AJAX (compatible IE)
    var xhr = new XMLHttpRequest();
    xhr.open('GET', 'actions/top_progressions.php?days=' + days + '&limit=5', true);
    
    xhr.onload = function() {
        if (xhr.status === 200) {
            try {
                var data = JSON.parse(xhr.responseText);
                
                if (data.success && data.progressions && data.progressions.length > 0) {
                    var html = '';
                    
                    for (var i = 0; i < data.progressions.length; i++) {
                        var item = data.progressions[i];
                        var progression = parseFloat(item.progression) || 0;
                        var progressionClass = progression >= 0 ? '' : 'negative';
                        var progressionSign = progression >= 0 ? '+' : '';
                        
                        html += '<div class="progression-item">';
                        html += '<div class="progression-rank">' + (i + 1) + '</div>';
                        
                        if (item.image_url) {
                            html += '<img src="' + escapeHtml(item.image_url) + '" alt="">';
                        }
                        
                        html += '<div class="progression-info">';
                        html += '<div class="progression-title">' + escapeHtml(item.title) + '</div>';
                        html += '<div class="progression-value ' + progressionClass + '">';
                        html += progressionSign + progression.toFixed(2) + ' %';
                        html += '</div>';
                        html += '</div>';
                        html += '</div>';
                    }
                    
                    listContainer.innerHTML = html;
                } else {
                    listContainer.innerHTML = '<p>Aucune progression disponible</p>';
                }
            } catch (e) {
                console.error('Erreur lors du parsing JSON:', e);
                listContainer.innerHTML = '<p>Erreur de chargement</p>';
            }
        } else {
            console.error('Erreur HTTP:', xhr.status);
            listContainer.innerHTML = '<p>Erreur de chargement</p>';
        }
    };
    
    xhr.onerror = function() {
        console.error('Erreur réseau');
        listContainer.innerHTML = '<p>Erreur de connexion</p>';
    };
    
    xhr.send();
}

/**
 * Initialise les filtres
 */
function initFilters() {
    var searchInput = document.getElementById('searchInput');
    var sortSelect = document.getElementById('sortSelect');
    
    // Forcer la valeur par défaut (fix Firefox qui garde la valeur au reload)
    if (sortSelect) {
        sortSelect.value = app.currentSort;
    }
    
    if (searchInput) {
        // Debounce pour la recherche
        var searchTimeout;
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(function() {
                app.currentSearch = searchInput.value;
                loadTracks();
            }, 500);
        });
    }
    
    if (sortSelect) {
        sortSelect.addEventListener('change', function() {
            app.currentSort = this.value;
            loadTracks();
        });
    }
}

/**
 * Charge les musiques
 */
function loadTracks() {
    var tracksContainer = document.getElementById('tracksList');
    if (!tracksContainer) return;
    
    tracksContainer.innerHTML = '<div class="loading">Chargement des musiques...</div>';
    
    var url = 'actions/search.php?';
    url += 'search=' + encodeURIComponent(app.currentSearch);
    url += '&orderBy=' + encodeURIComponent(app.currentSort);
    
    var xhr = new XMLHttpRequest();
    xhr.open('GET', url, true);
    
    xhr.onload = function() {
        if (xhr.status === 200) {
            var data = JSON.parse(xhr.responseText);
            
            if (data.success) {
                app.tracks = data.tracks;
                displayTracks(data.tracks);
            } else {
                tracksContainer.innerHTML = '<p>Erreur lors du chargement des musiques</p>';
            }
        }
    };
    
    xhr.send();
}

/**
 * Affiche les musiques
 */
function displayTracks(tracks) {
    var tracksContainer = document.getElementById('tracksList');
    var template = document.getElementById('trackTemplate');
    
    if (!tracksContainer || !template) return;
    
    if (tracks.length === 0) {
        tracksContainer.innerHTML = '<p class="no-results">Aucune musique trouvée</p>';
        return;
    }
    
    tracksContainer.innerHTML = '';
    
    // Détecter les doublons
    var seenIds = [];
    var duplicates = [];
    
    for (var i = 0; i < tracks.length; i++) {
        var track = tracks[i];
        
        if (seenIds.indexOf(track.audio_id) !== -1) {
            console.warn('Doublon détecté:', track.title, 'audio_id:', track.audio_id);
            duplicates.push(track);
            continue; // Ignorer les doublons
        }
        seenIds.push(track.audio_id);
        
        var clone = template.content.cloneNode(true);
        
        // Image
        var img = clone.querySelector('.track-image');
        if (track.image_url) {
            img.src = track.image_url;
            img.alt = track.title;
        }
        
        // Titre
        clone.querySelector('.track-title').textContent = track.title;
        
        // Statut
        var statusSpan = clone.querySelector('.track-status');
        if (track.available) {
            statusSpan.textContent = 'Disponible';
            statusSpan.className = 'track-status available';
        } else {
            statusSpan.textContent = 'Supprimée sur Audio.com';
            statusSpan.className = 'track-status deleted';
        }
        
        // Nombre d'écoutes
        var listensSpan = clone.querySelector('.track-listens');
        listensSpan.textContent = formatNumber(track.current_listen_count);
        
        // Progression
        var progressionSpan = clone.querySelector('.track-progression');
        if (track.progression !== null && track.progression !== undefined) {
            var progressionClass = track.progression >= 0 ? 'positive' : 'negative';
            var progressionSign = track.progression >= 0 ? '+' : '';
            progressionSpan.textContent = progressionSign + track.progression.toFixed(2) + ' %';
            progressionSpan.classList.add(progressionClass);
        } else {
            progressionSpan.textContent = 'N/A';
        }
        
        // Lien
        var link = clone.querySelector('.track-link');
        link.href = track.track_url;
        
        // Canvas pour le graphique
        var canvas = clone.querySelector('.track-chart');
        canvas.id = 'chart-' + track.id;
        
        // Sélecteur de période
        var periodSelect = clone.querySelector('.chart-period-select');
        periodSelect.setAttribute('data-track-id', track.id);
        periodSelect.addEventListener('change', function() {
            var trackId = parseInt(this.getAttribute('data-track-id'));
            var days = parseInt(this.value);
            loadTrackChart(trackId, days);
        });
        
        tracksContainer.appendChild(clone);
        
        // Charger le graphique
        loadTrackChart(track.id, app.currentPeriod);
    }
    
    // Afficher un avertissement si des doublons ont été détectés
    if (duplicates.length > 0) {
        console.error('ATTENTION: ' + duplicates.length + ' doublon(s) détecté(s) et ignoré(s)');
        if (console.table) {
            console.table(duplicates.map(function(t) {
                return {title: t.title, audio_id: t.audio_id, id: t.id};
            }));
        } else {
            duplicates.forEach(function(t) {
                console.log('- ' + t.title + ' (audio_id: ' + t.audio_id + ', id: ' + t.id + ')');
            });
        }
    }
}

/**
 * Charge le graphique d'une musique
 */
function loadTrackChart(trackId, days) {
    var url = 'actions/history.php?track_id=' + trackId + '&days=' + days;
    
    var xhr = new XMLHttpRequest();
    xhr.open('GET', url, true);
    
    xhr.onload = function() {
        if (xhr.status === 200) {
            var data = JSON.parse(xhr.responseText);
            
            if (data.success) {
                renderChart(trackId, data.history);
            }
        }
    };
    
    xhr.send();
}

/**
 * Affiche un graphique
 */
function renderChart(trackId, history) {
    var canvas = document.getElementById('chart-' + trackId);
    if (!canvas) return;
    
    // Détruire le graphique existant
    if (app.charts[trackId]) {
        app.charts[trackId].destroy();
    }
    
    // Préparer les données
    var labels = [];
    var data = [];
    
    for (var i = 0; i < history.length; i++) {
        labels.push(history[i].date);
        data.push(history[i].listen_count);
    }
    
    // Créer le graphique
    var ctx = canvas.getContext('2d');
    
    app.charts[trackId] = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Écoutes',
                data: data,
                borderColor: 'rgb(102, 126, 234)',
                backgroundColor: 'rgba(102, 126, 234, 0.1)',
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return formatNumber(context.parsed.y) + ' écoutes';
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: false,
                    ticks: {
                        callback: function(value) {
                            return formatNumber(value);
                        }
                    }
                }
            }
        }
    });
}

/**
 * Formate un nombre avec des séparateurs de milliers
 */
function formatNumber(num) {
    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
}

/**
 * Échappe le HTML
 */
function escapeHtml(text) {
    var map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, function(m) { return map[m]; });
}

/**
 * Gestion de la synchronisation manuelle
 */
(function() {
    var modal = document.getElementById('manualSyncModal');
    var manualSyncBtn = document.getElementById('manualSyncBtn');
    var closeButtons = modal.querySelectorAll('.modal-close, .modal-cancel');
    var addPageBtn = document.getElementById('addPageBtn');
    var processBtn = document.getElementById('processManualSyncBtn');
    var container = document.getElementById('htmlPagesContainer');
    var resultDiv = document.getElementById('manualSyncResult');
    var pageCount = 1;
    
    // Ouvrir la modal
    manualSyncBtn.addEventListener('click', function() {
        modal.style.display = 'flex';
        resultDiv.style.display = 'none';
        resultDiv.className = 'sync-result';
    });
    
    // Fermer la modal
    closeButtons.forEach(function(btn) {
        btn.addEventListener('click', function() {
            modal.style.display = 'none';
        });
    });
    
    // Fermer en cliquant à l'extérieur
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            modal.style.display = 'none';
        }
    });
    
    // Ajouter une page
    addPageBtn.addEventListener('click', function() {
        pageCount++;
        var newPage = document.createElement('div');
        newPage.className = 'html-page-group';
        newPage.setAttribute('data-page', pageCount);
        newPage.innerHTML = 
            '<h4>Page ' + pageCount + '</h4>' +
            '<textarea class="html-input" placeholder="Collez le code source HTML ici..."></textarea>' +
            '<p class="char-count">0 caractères</p>' +
            '<button class="btn btn-danger btn-sm remove-page-btn">Supprimer cette page</button>';
        
        container.appendChild(newPage);
        
        // Ajouter l'événement de suppression
        var removeBtn = newPage.querySelector('.remove-page-btn');
        removeBtn.addEventListener('click', function() {
            container.removeChild(newPage);
        });
        
        // Ajouter l'événement de comptage
        var textarea = newPage.querySelector('.html-input');
        var charCount = newPage.querySelector('.char-count');
        textarea.addEventListener('input', function() {
            charCount.textContent = textarea.value.length + ' caractères';
        });
    });
    
    // Comptage de caractères pour la première page
    var firstTextarea = container.querySelector('.html-input');
    var firstCharCount = container.querySelector('.char-count');
    firstTextarea.addEventListener('input', function() {
        firstCharCount.textContent = firstTextarea.value.length + ' caractères';
    });
    
    // Traiter les données
    processBtn.addEventListener('click', function() {
        var textareas = container.querySelectorAll('.html-input');
        var htmlContents = [];
        
        // Récupérer tous les contenus HTML
        textareas.forEach(function(textarea) {
            var content = textarea.value.trim();
            if (content) {
                htmlContents.push(content);
            }
        });
        
        if (htmlContents.length === 0) {
            alert('Veuillez coller au moins une page HTML');
            return;
        }
        
        // Afficher le chargement
        var btnText = processBtn.querySelector('.btn-text');
        var btnLoading = processBtn.querySelector('.btn-loading');
        btnText.style.display = 'none';
        btnLoading.style.display = 'inline';
        processBtn.disabled = true;
        
        resultDiv.style.display = 'none';
        
        // Envoyer les données
        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'actions/manual_sync.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        
        xhr.onload = function() {
            btnText.style.display = 'inline';
            btnLoading.style.display = 'none';
            processBtn.disabled = false;
            
            if (xhr.status === 200) {
                try {
                    var response = JSON.parse(xhr.responseText);
                    
                    resultDiv.style.display = 'block';
                    
                    if (response.success) {
                        resultDiv.className = 'sync-result success';
                        resultDiv.innerHTML = 
                            '<h3>✅ ' + response.message + '</h3>' +
                            '<p><strong>Statistiques :</strong></p>' +
                            '<ul>' +
                            '<li>Pages traitées : ' + response.stats.pages + '</li>' +
                            '<li>Musiques trouvées : ' + response.stats.total + '</li>' +
                            '<li>Nouvelles musiques : ' + response.stats.created + '</li>' +
                            '<li>Musiques mises à jour : ' + response.stats.updated + '</li>' +
                            '</ul>' +
                            '<p style="margin-top:15px;"><a href="index.php" class="btn btn-primary">Voir les résultats</a></p>';
                    } else {
                        resultDiv.className = 'sync-result error';
                        resultDiv.innerHTML = '<h3>❌ Erreur</h3><p>' + response.message + '</p>';
                    }
                } catch (e) {
                    resultDiv.className = 'sync-result error';
                    resultDiv.style.display = 'block';
                    resultDiv.innerHTML = '<h3>❌ Erreur</h3><p>Erreur lors du traitement de la réponse</p>';
                }
            } else {
                resultDiv.className = 'sync-result error';
                resultDiv.style.display = 'block';
                resultDiv.innerHTML = '<h3>❌ Erreur</h3><p>Erreur serveur (' + xhr.status + ')</p>';
            }
        };
        
        xhr.onerror = function() {
            btnText.style.display = 'inline';
            btnLoading.style.display = 'none';
            processBtn.disabled = false;
            
            resultDiv.className = 'sync-result error';
            resultDiv.style.display = 'block';
            resultDiv.innerHTML = '<h3>❌ Erreur</h3><p>Impossible de contacter le serveur</p>';
        };
        
        // Encoder les données
        var formData = 'html_content=' + encodeURIComponent(JSON.stringify(htmlContents));
        xhr.send(formData);
    });
})();
