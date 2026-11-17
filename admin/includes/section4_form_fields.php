<?php
/**
 * Champs du formulaire admin section4 (deux bannières)
 * Variables attendues : $config (array)
 */
if (!isset($config) || !is_array($config)) {
    $config = section4_default_config();
}

function section4_form_value($config, $key) {
    if (isset($_POST[$key])) {
        return htmlspecialchars($_POST[$key]);
    }
    return htmlspecialchars($config[$key] ?? '');
}
?>

<input type="hidden" name="MAX_FILE_SIZE" value="52428800">

<div class="form-group">
    <label><i class="fas fa-toggle-on"></i> Afficher la section</label>
    <div class="toggle-section-wrap">
        <input type="hidden" name="statut" value="inactif">
        <label class="toggle-switch">
            <input type="checkbox" name="statut" value="actif" <?php echo ($config['statut'] ?? 'actif') === 'actif' ? 'checked' : ''; ?>>
            <span class="toggle-slider"></span>
        </label>
        <span class="toggle-label" id="statut-label"><?php echo ($config['statut'] ?? 'actif') === 'actif' ? 'Section activée' : 'Section désactivée'; ?></span>
    </div>
    <small>Désactivez pour masquer complètement cette section sur la page d'accueil</small>
</div>

<fieldset class="section4-fieldset">
    <legend><i class="fas fa-image"></i> Bannière 1 (gauche)</legend>

    <div class="form-group">
        <label for="texte">Surtitre <span class="optional">(optionnel)</span></label>
        <input type="text" id="texte" name="texte" value="<?php echo section4_form_value($config, 'texte'); ?>" placeholder="Ex: PROMOTION">
    </div>

    <div class="form-group">
        <label for="titre">Titre principal <span class="optional">(optionnel)</span></label>
        <input type="text" id="titre" name="titre" value="<?php echo section4_form_value($config, 'titre'); ?>" placeholder="Ex: Petits prix sur nos bestsellers">
    </div>

    <div class="form-row-2">
        <div class="form-group">
            <label for="bouton">Texte du bouton</label>
            <input type="text" id="bouton" name="bouton" value="<?php echo section4_form_value($config, 'bouton'); ?>" placeholder="Ex: Voir les promos">
        </div>
        <div class="form-group">
            <label for="lien">Lien du bouton</label>
            <input type="text" id="lien" name="lien" value="<?php echo section4_form_value($config, 'lien'); ?>" placeholder="Ex: promo.php">
        </div>
    </div>

    <div class="form-row-2">
        <div class="form-group">
            <label for="bg_color">Couleur de fond</label>
            <input type="color" id="bg_color" name="bg_color" value="<?php echo htmlspecialchars(preg_match('/^#[0-9a-fA-F]{3,8}$/', $config['bg_color'] ?? '') ? $config['bg_color'] : '#eef6f0'); ?>">
        </div>
        <div class="form-group">
            <label for="style_bouton">Style du bouton</label>
            <select id="style_bouton" name="style_bouton">
                <option value="solid" <?php echo ($config['style_bouton'] ?? 'solid') === 'solid' ? 'selected' : ''; ?>>Plein</option>
                <option value="outline" <?php echo ($config['style_bouton'] ?? '') === 'outline' ? 'selected' : ''; ?>>Contour</option>
            </select>
        </div>
    </div>

    <div class="form-group">
        <label for="image_fond">Image produit (droite) <span class="optional">(optionnel)</span></label>
        <small class="form-help">JPEG, PNG, GIF, WEBP — max 50 Mo</small>
        <?php if (!empty($config['image_fond'])): ?>
            <div class="current-image">
                <strong>Image actuelle :</strong>
                <img src="/upload/section4/<?php echo htmlspecialchars($config['image_fond']); ?>" alt="Bannière 1"
                    onerror="this.src='/image/<?php echo htmlspecialchars($config['image_fond']); ?>';">
            </div>
        <?php endif; ?>
        <div class="file-input-wrapper">
            <label for="image_fond" class="file-input-label">
                <i class="fas fa-cloud-upload-alt"></i>
                <span><?php echo !empty($config['image_fond']) ? 'Changer l\'image' : 'Choisir une image'; ?></span>
            </label>
            <input type="file" id="image_fond" name="image_fond" class="file-input" accept="image/jpeg,image/jpg,image/png,image/gif,image/webp">
        </div>
        <div id="imagePreview1" class="image-preview-wrap" style="display: none;">
            <img src="" alt="Aperçu" class="image-preview" id="previewImg1">
        </div>
    </div>
</fieldset>

<fieldset class="section4-fieldset">
    <legend><i class="fas fa-image"></i> Bannière 2 (droite)</legend>

    <div class="form-group">
        <label><i class="fas fa-toggle-on"></i> Afficher la bannière 2</label>
        <div class="toggle-section-wrap">
            <input type="hidden" name="actif2" value="inactif">
            <label class="toggle-switch">
                <input type="checkbox" name="actif2" value="actif" <?php echo ($config['actif2'] ?? 'actif') === 'actif' ? 'checked' : ''; ?>>
                <span class="toggle-slider"></span>
            </label>
            <span class="toggle-label" id="actif2-label"><?php echo ($config['actif2'] ?? 'actif') === 'actif' ? 'Bannière activée' : 'Bannière masquée'; ?></span>
        </div>
    </div>

    <div class="form-group">
        <label for="texte2">Surtitre <span class="optional">(optionnel)</span></label>
        <input type="text" id="texte2" name="texte2" value="<?php echo section4_form_value($config, 'texte2'); ?>" placeholder="Ex: NOUVEAUTÉS">
    </div>

    <div class="form-group">
        <label for="titre2">Titre principal <span class="optional">(optionnel)</span></label>
        <input type="text" id="titre2" name="titre2" value="<?php echo section4_form_value($config, 'titre2'); ?>" placeholder="Ex: Découvrez nos nouveautés">
    </div>

    <div class="form-row-2">
        <div class="form-group">
            <label for="bouton2">Texte du bouton</label>
            <input type="text" id="bouton2" name="bouton2" value="<?php echo section4_form_value($config, 'bouton2'); ?>" placeholder="Ex: Explorer">
        </div>
        <div class="form-group">
            <label for="lien2">Lien du bouton</label>
            <input type="text" id="lien2" name="lien2" value="<?php echo section4_form_value($config, 'lien2'); ?>" placeholder="Ex: nouveautes.php">
        </div>
    </div>

    <div class="form-row-2">
        <div class="form-group">
            <label for="bg_color2">Couleur de fond</label>
            <input type="color" id="bg_color2" name="bg_color2" value="<?php echo htmlspecialchars(preg_match('/^#[0-9a-fA-F]{3,8}$/', $config['bg_color2'] ?? '') ? $config['bg_color2'] : '#faf7f2'); ?>">
        </div>
        <div class="form-group">
            <label for="style_bouton2">Style du bouton</label>
            <select id="style_bouton2" name="style_bouton2">
                <option value="solid" <?php echo ($config['style_bouton2'] ?? '') === 'solid' ? 'selected' : ''; ?>>Plein</option>
                <option value="outline" <?php echo ($config['style_bouton2'] ?? 'outline') === 'outline' ? 'selected' : ''; ?>>Contour</option>
            </select>
        </div>
    </div>

    <div class="form-group">
        <label for="image2">Image produit (droite) <span class="optional">(optionnel)</span></label>
        <small class="form-help">JPEG, PNG, GIF, WEBP — max 50 Mo</small>
        <?php if (!empty($config['image2'])): ?>
            <div class="current-image">
                <strong>Image actuelle :</strong>
                <img src="/upload/section4/<?php echo htmlspecialchars($config['image2']); ?>" alt="Bannière 2">
            </div>
        <?php endif; ?>
        <div class="file-input-wrapper">
            <label for="image2" class="file-input-label">
                <i class="fas fa-cloud-upload-alt"></i>
                <span><?php echo !empty($config['image2']) ? 'Changer l\'image' : 'Choisir une image'; ?></span>
            </label>
            <input type="file" id="image2" name="image2" class="file-input" accept="image/jpeg,image/jpg,image/png,image/gif,image/webp">
        </div>
        <div id="imagePreview2" class="image-preview-wrap" style="display: none;">
            <img src="" alt="Aperçu" class="image-preview" id="previewImg2">
        </div>
    </div>
</fieldset>

<style>
    .section4-fieldset {
        border: 1px solid #e8e8e8;
        border-radius: 10px;
        padding: 20px;
        margin-bottom: 24px;
        background: #fafafa;
    }
    .section4-fieldset legend {
        font-weight: 700;
        color: #6b2f20;
        padding: 0 8px;
    }
    .form-row-2 {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 16px;
    }
    @media (max-width: 640px) {
        .form-row-2 { grid-template-columns: 1fr; }
    }
    .optional { font-weight: 400; color: #888; font-size: 12px; }
    .toggle-section-wrap { display: flex; align-items: center; gap: 15px; margin: 10px 0; }
    .toggle-switch { position: relative; display: inline-block; width: 54px; height: 28px; }
    .toggle-switch input { opacity: 0; width: 0; height: 0; }
    .toggle-slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background: #ccc; border-radius: 28px; transition: 0.3s; }
    .toggle-slider:before { position: absolute; content: ""; height: 20px; width: 20px; left: 4px; bottom: 4px; background: #fff; border-radius: 50%; transition: 0.3s; }
    .toggle-switch input:checked + .toggle-slider { background: var(--couleur-dominante, #c26638); }
    .toggle-switch input:checked + .toggle-slider:before { transform: translateX(26px); }
    .toggle-label { font-weight: 600; color: var(--titres, #000000); }
    .image-preview-wrap img { max-width: 100%; max-height: 200px; border-radius: 8px; margin-top: 12px; }
</style>

<script>
    document.querySelector('input[name="statut"][type="checkbox"]')?.addEventListener('change', function() {
        document.getElementById('statut-label').textContent = this.checked ? 'Section activée' : 'Section désactivée';
    });
    document.querySelector('input[name="actif2"][type="checkbox"]')?.addEventListener('change', function() {
        document.getElementById('actif2-label').textContent = this.checked ? 'Bannière activée' : 'Bannière masquée';
    });
    function bindImagePreview(inputId, previewWrapId, previewImgId) {
        const input = document.getElementById(inputId);
        if (!input) return;
        input.addEventListener('change', function(e) {
            const file = e.target.files[0];
            const wrap = document.getElementById(previewWrapId);
            const img = document.getElementById(previewImgId);
            if (file && wrap && img) {
                const reader = new FileReader();
                reader.onload = function(ev) {
                    img.src = ev.target.result;
                    wrap.style.display = 'block';
                };
                reader.readAsDataURL(file);
            } else if (wrap) {
                wrap.style.display = 'none';
            }
        });
    }
    bindImagePreview('image_fond', 'imagePreview1', 'previewImg1');
    bindImagePreview('image2', 'imagePreview2', 'previewImg2');
</script>
