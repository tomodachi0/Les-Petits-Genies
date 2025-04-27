<?php
require_once '../includes/header.php';
?>

<div class="hero">
    <div class="hero-content animate">
        <h1>Bienvenue les Petits Génies !</h1>
        <p>Découvre, joue et apprends chaque jour avec nous ✨</p>
    </div>
</div>

<!-- Rubriques -->
<div class="category-cards">

    <div class="category-card animate">
        <div class="card-icon" style="background-color: #ffe0b2;">
            <i class="fas fa-calculator"></i>
        </div>
        <h2>Jeux Mathématiques</h2>
        <p>Amuse-toi à additionner, soustraire et multiplier en t’amusant !</p>
        <a href="math.php" class="btn">Découvrir</a>
    </div>

    <div class="category-card animate">
        <div class="card-icon" style="background-color: #bbdefb;">
            <i class="fas fa-flag"></i>
        </div>
        <h2>Drapeaux du Monde</h2>
        <p>Pars en voyage et découvre les pays et leurs drapeaux colorés !</p>
        <a href="flags.php" class="btn">Explorer</a>
    </div>

    <div class="category-card animate">
        <div class="card-icon" style="background-color: #c8e6c9;">
            <i class="fas fa-paw"></i>
        </div>
        <h2>Animaux Magiques</h2>
        <p>Viens rencontrer des animaux extraordinaires et mignons !</p>
        <a href="animals.php" class="btn">Voir les Animaux</a>
    </div>

    <div class="category-card animate">
        <div class="card-icon" style="background-color: #d1c4e9;">
            <i class="fas fa-briefcase"></i>
        </div>
        <h2>Découvre les Métiers</h2>
        <p>Apprends ce que font les pompiers, médecins, professeurs...</p>
        <a href="jobs.php" class="btn">Explorer</a>
    </div>

    <div class="category-card animate">
        <div class="card-icon" style="background-color: #f8bbd0;">
            <i class="fas fa-book"></i>
        </div>
        <h2>Histoires Magiques</h2>
        <p>Lis ou écoute des histoires drôles, féériques et amusantes !</p>
        <a href="stories.php" class="btn">Lire une Histoire</a>
    </div>

</div>

<!-- Pourquoi aimer notre site -->
<div class="features-section">
    <h2>Pourquoi les enfants adorent notre site</h2>
    
    <div class="features">
        <div class="feature animate">
            <div class="feature-icon">
                <i class="fas fa-smile"></i>
            </div>
            <h3>Apprentissage Amusant</h3>
            <p>Des jeux interactifs et ludiques pour apprendre en s’amusant !</p>
        </div>
        
        <div class="feature animate">
            <div class="feature-icon">
                <i class="fas fa-brain"></i>
            </div>
            <h3>Contenu Éducatif</h3>
            <p>Du contenu pour grandir, apprendre et explorer en toute sécurité.</p>
        </div>
        
        <div class="feature animate">
            <div class="feature-icon">
                <i class="fas fa-child"></i>
            </div>
            <h3>Adapté aux Enfants</h3>
            <p>Un univers coloré et joyeux pensé spécialement pour toi !</p>
        </div>
        
        <div class="feature animate">
            <div class="feature-icon">
                <i class="fas fa-mobile-alt"></i>
            </div>
            <h3>Accessible Partout</h3>
            <p>Apprends sur ordinateur, tablette ou téléphone, où que tu sois !</p>
        </div>
    </div>
</div>

<?php
require_once '../includes/footer.php';
?>
