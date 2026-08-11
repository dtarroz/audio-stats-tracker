    </main>
    
    <!-- Modal de synchronisation manuelle -->
    <div id="manualSyncModal" class="modal" style="display:none;">
        <div class="modal-content">
            <div class="modal-header">
                <h2>📋 Synchronisation manuelle</h2>
                <button class="modal-close">&times;</button>
            </div>
            
            <div class="modal-body">
                <div class="instructions">
                    <h3>📝 Instructions :</h3>
                    <ol>
                        <li>Ouvrez <a href="https://audio.com/dtarroz" target="_blank"><strong>audio.com/dtarroz</strong></a> dans un nouvel onglet</li>
                        <li>Appuyez sur <kbd>Ctrl+U</kbd> (ou <kbd>Cmd+Option+U</kbd> sur Mac) pour voir le code source</li>
                        <li>Sélectionnez tout avec <kbd>Ctrl+A</kbd> (ou <kbd>Cmd+A</kbd>)</li>
                        <li>Copiez avec <kbd>Ctrl+C</kbd> (ou <kbd>Cmd+C</kbd>)</li>
                        <li>Collez dans la zone ci-dessous</li>
                        <li>Si vous avez plusieurs pages, cliquez sur "Ajouter une page" et répétez</li>
                        <li>Cliquez sur "Traiter les données"</li>
                    </ol>
                </div>
                
                <div id="htmlPagesContainer">
                    <div class="html-page-group" data-page="1">
                        <h4>Page 1</h4>
                        <textarea class="html-input" placeholder="Collez le code source HTML ici..."></textarea>
                        <p class="char-count">0 caractères</p>
                    </div>
                </div>
                
                <button id="addPageBtn" class="btn btn-secondary">➕ Ajouter une page</button>
            </div>
            
            <div class="modal-footer">
                <button id="processManualSyncBtn" class="btn btn-primary btn-lg">
                    <span class="btn-text">✅ Traiter les données</span>
                    <span class="btn-loading" style="display:none;">Traitement en cours...</span>
                </button>
                <button class="btn btn-secondary modal-cancel">Annuler</button>
            </div>
            
            <div id="manualSyncResult" class="sync-result" style="display:none;"></div>
        </div>
    </div>
    
    <footer class="main-footer">
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?> - Suivi des statistiques Audio.com</p>
        </div>
    </footer>
    
    <script src="assets/js/app.js?v=<?php echo APP_VERSION; ?>"></script>
</body>
</html>
