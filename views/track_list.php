<!-- Liste des musiques -->
<section class="track-list-section">
    <h2>Liste des musiques</h2>
    
    <!-- Filtres et recherche -->
    <div class="filters">
        <div class="search-box">
            <input type="text" 
                   id="searchInput" 
                   placeholder="Rechercher par titre..." 
                   class="form-control">
        </div>
        
        <div class="sort-box">
            <label for="sortSelect">Trier par :</label>
            <select id="sortSelect" class="form-control">
                <option value="default">Ordre Audio.com (récent)</option>
                <option value="title_asc">Titre A → Z</option>
                <option value="title_desc">Titre Z → A</option>
                <option value="listen_count">Nombre d'écoutes</option>
                <option value="progression">Progression</option>
            </select>
        </div>
    </div>
    
    <!-- Liste des musiques -->
    <div id="tracksList" class="tracks-grid">
        <!-- Chargé dynamiquement par JavaScript -->
        <div class="loading">Chargement des musiques...</div>
    </div>
</section>

<!-- Template pour une musique -->
<template id="trackTemplate">
    <div class="track-card">
        <div class="track-header">
            <img src="" alt="" class="track-image">
            <div class="track-title-section">
                <h3 class="track-title"></h3>
                <span class="track-status"></span>
            </div>
        </div>
        
        <div class="track-stats">
            <div class="stat">
                <span class="stat-label">Écoutes actuelles</span>
                <span class="stat-value track-listens"></span>
            </div>
            <div class="stat">
                <span class="stat-label">Progression</span>
                <span class="stat-value track-progression"></span>
            </div>
        </div>
        
        <div class="track-chart-container">
            <div class="chart-controls">
                <select class="chart-period-select form-control-sm">
                    <option value="7">7 jours</option>
                    <option value="15">15 jours</option>
                    <option value="30" selected>30 jours</option>
                    <option value="60">60 jours</option>
                    <option value="90">90 jours</option>
                    <option value="0">Tout</option>
                </select>
            </div>
            <canvas class="track-chart"></canvas>
        </div>
        
        <div class="track-footer">
            <a href="" target="_blank" class="btn btn-sm btn-secondary track-link">
                Voir sur Audio.com
            </a>
        </div>
    </div>
</template>
