<!-- Dashboard / Tableau de bord -->
<section class="dashboard">
    <h2>Tableau de bord</h2>
    
    <!-- Statistiques globales -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">🎵</div>
            <div class="stat-content">
                <div class="stat-value"><?php echo $stats['total_tracks']; ?></div>
                <div class="stat-label">Musiques au total</div>
            </div>
        </div>
        
        <div class="stat-card stat-success">
            <div class="stat-icon">✓</div>
            <div class="stat-content">
                <div class="stat-value"><?php echo $stats['available_tracks']; ?></div>
                <div class="stat-label">Disponibles</div>
            </div>
        </div>
        
        <div class="stat-card stat-danger">
            <div class="stat-icon">✗</div>
            <div class="stat-content">
                <div class="stat-value"><?php echo $stats['deleted_tracks']; ?></div>
                <div class="stat-label">Supprimées</div>
            </div>
        </div>
        
        <div class="stat-card stat-info">
            <div class="stat-icon">🎧</div>
            <div class="stat-content">
                <div class="stat-value"><?php echo number_format($stats['total_listen_count'], 0, ',', ' '); ?></div>
                <div class="stat-label">Écoutes totales</div>
            </div>
        </div>
    </div>
    
    <!-- Dernière synchronisation -->
    <div class="sync-info">
        <?php if ($stats['last_sync']): ?>
            <p>
                <strong>Dernière synchronisation :</strong> 
                <?php echo date(DATE_FORMAT_DISPLAY, strtotime($stats['last_sync'])); ?>
            </p>
        <?php else: ?>
            <p class="no-sync">Aucune synchronisation effectuée</p>
        <?php endif; ?>
        
        <button id="syncBtn" class="btn btn-primary btn-lg sync-btn-hidden">
            <span class="btn-text">Synchroniser</span>
            <span class="btn-loading" style="display:none;">Synchronisation en cours...</span>
        </button>
        
        <button id="manualSyncBtn" class="btn btn-primary btn-lg sync-btn-hidden" style="margin-left: 10px;">
            <span class="btn-text">📋 Synchronisation manuelle</span>
        </button>
    </div>
    
    <!-- Progression de la synchronisation -->
    <div id="syncProgress" class="sync-progress" style="display:none;">
        <div class="progress-messages"></div>
    </div>
    
    <!-- Grille des tops -->
    <div class="tops-grid">
        <!-- Top des écoutes -->
        <div class="top-section">
            <h3>Top 5 des écoutes</h3>
            
            <?php if ($stats['top_listened'] && count($stats['top_listened']) > 0): ?>
            <div class="progression-list">
                <?php foreach ($stats['top_listened'] as $index => $track): ?>
                <div class="progression-item">
                    <div class="progression-rank"><?php echo $index + 1; ?></div>
                    
                    <?php if ($track->imageUrl): ?>
                        <img src="<?php echo htmlspecialchars($track->imageUrl); ?>" 
                             alt="<?php echo htmlspecialchars($track->title); ?>">
                    <?php endif; ?>
                    
                    <div class="progression-info">
                        <div class="progression-title"><?php echo htmlspecialchars($track->title); ?></div>
                        <div class="progression-value">
                            <?php echo number_format($track->currentListenCount, 0, ',', ' '); ?> écoutes
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <p>Aucune donnée disponible</p>
            <?php endif; ?>
        </div>
        
        <!-- Top des progressions -->
        <div class="top-section">
            <h3>Top 5 des progressions</h3>
            
            <div class="tabs">
                <button class="tab-btn" data-days="7">7 jours</button>
                <button class="tab-btn active" data-days="30">30 jours</button>
                <button class="tab-btn" data-days="90">90 jours</button>
            </div>
            
            <div id="topProgressionsList" class="progression-list">
                <!-- Chargé dynamiquement par JavaScript -->
            </div>
        </div>
    </div>
</section>
