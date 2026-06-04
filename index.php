<?php
require_once 'config.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>BudgetCoop - Gestion Collaborative de Budget Personnel</title>
    <meta name="description" content="Application web de gestion collaborative de budget personnel pour familles, colocataires et équipes.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css" />
</head>
<body>
    <header class="site-header">
        <nav class="nav-container">
            <a href="index.php" class="logo">BudgetCoop</a>
            <ul class="nav-links">
                <li><a href="#features">Fonctionnalités</a></li>
                <li><a href="#stats">Statistiques</a></li>
                <li><a href="login.php">Connexion</a></li>
                <li><a href="register.php" class="btn btn-primary">S'inscrire</a></li>
            </ul>
        </nav>
    </header>

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-container">
            <div class="hero-content">
                <div class="hero-badge">✨ Nouvelle version 2.0</div>
                <h1>Gérez vos finances ensemble, simplement</h1>
                <p>La solution collaborative pour suivre vos revenus, dépenses et budgets partagés. Parfait pour les familles, colocataires et équipes de projet.</p>
                <div class="hero-buttons">
                    <a href="register.php" class="btn-hero btn-hero-primary">Commencer gratuitement →</a>
                    <a href="#features" class="btn-hero btn-hero-secondary">En savoir plus</a>
                </div>
            </div>
            <div class="hero-visual">
                <div class="hero-card">
                    <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1.5rem;">
                        <div style="width: 48px; height: 48px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem;">📊</div>
                        <div>
                            <div style="font-weight: 700; color: #1f2937;">Budget Familial</div>
                            <div style="font-size: 0.875rem; color: #6b7280;">4 membres actifs</div>
                        </div>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.5rem;">
                        <div style="background: #f3f4f6; padding: 1rem; border-radius: 12px;">
                            <div style="font-size: 0.75rem; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em;">Revenus</div>
                            <div style="font-size: 1.5rem; font-weight: 800; color: #10b981;">+2,450 €</div>
                        </div>
                        <div style="background: #f3f4f6; padding: 1rem; border-radius: 12px;">
                            <div style="font-size: 0.75rem; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em;">Dépenses</div>
                            <div style="font-size: 1.5rem; font-weight: 800; color: #ef4444;">-1,890 €</div>
                        </div>
                    </div>
                    <div style="height: 8px; background: #f3f4f6; border-radius: 4px; overflow: hidden;">
                        <div style="width: 77%; height: 100%; background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);"></div>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-top: 0.5rem; font-size: 0.875rem;">
                        <span style="color: #6b7280;">Budget utilisé</span>
                        <span style="font-weight: 700; color: #6366f1;">77%</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features" id="features">
        <div class="features-container">
            <div class="section-header">
                <span class="section-badge">Fonctionnalités</span>
                <h2 class="section-title">Tout ce dont vous avez besoin</h2>
                <p class="section-subtitle">Une suite complète d'outils pour gérer vos finances personnelles et collaboratives en toute simplicité.</p>
            </div>
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">👥</div>
                    <h3>Gestion Collaborative</h3>
                    <p>Partagez vos budgets avec votre famille, colocataires ou équipe de projet. Chaque membre peut ajouter des transactions et consulter les statistiques.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">📊</div>
                    <h3>Tableau de Bord</h3>
                    <p>Visualisez vos finances en temps réel avec des graphiques interactifs. Suivez l'évolution de vos dépenses et revenus mois par mois.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">🎯</div>
                    <h3>Budgets Personnalisés</h3>
                    <p>Créez des budgets individuels ou partagés avec des limites par catégorie. Définissez des périodes budgétaires mensuelles ou hebdomadaires.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">🔔</div>
                    <h3>Alertes Intelligentes</h3>
                    <p>Recevez des notifications lorsque vous atteignez 80% de votre budget ou en cas de dépassement. Restez toujours informé de votre situation financière.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">🏷️</div>
                    <h3>Catégories Flexibles</h3>
                    <p>Organisez vos dépenses avec des catégories par défaut ou créez vos propres catégories personnalisées adaptées à votre style de vie.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">🔒</div>
                    <h3>Sécurité Avancée</h3>
                    <p>Vos données sont protégées avec un hachage des mots de passe, un contrôle d'accès basé sur les rôles et une validation des entrées.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="stats-section" id="stats">
        <div class="stats-container">
            <div class="stats-grid">
                <div class="stat-item">
                    <div class="stat-number">10K+</div>
                    <div class="stat-label">Utilisateurs actifs</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">50K+</div>
                    <div class="stat-label">Transactions traitées</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">5K+</div>
                    <div class="stat-label">Budgets créés</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">99%</div>
                    <div class="stat-label">Satisfaction client</div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section">
        <div class="cta-container">
            <div class="cta-card">
                <h2>Prêt à prendre le contrôle de vos finances ?</h2>
                <p>Rejoignez des milliers d'utilisateurs qui gèrent déjà leur budget avec BudgetCoop. Commencez gratuitement dès aujourd'hui.</p>
                <a href="register.php" class="btn-hero btn-hero-primary">Créer un compte gratuit →</a>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="site-footer">
        <div class="footer-container">
            <div class="footer-grid">
                <div class="footer-brand">
                    <h3>BudgetCoop</h3>
                    <p>La solution collaborative pour gérer vos finances personnelles en toute simplicité. Rejoignez notre communauté et prenez le contrôle de votre budget.</p>
                </div>
                <div class="footer-links">
                    <h4>Produit</h4>
                    <ul>
                        <li><a href="#features">Fonctionnalités</a></li>
                        <li><a href="#stats">Statistiques</a></li>
                        <li><a href="register.php">Tarifs</a></li>
                    </ul>
                </div>
                <div class="footer-links">
                    <h4>Ressources</h4>
                    <ul>
                        <li><a href="#">Documentation</a></li>
                        <li><a href="#">Guide</a></li>
                        <li><a href="#">Support</a></li>
                    </ul>
                </div>
                <div class="footer-links">
                    <h4>Légal</h4>
                    <ul>
                        <li><a href="#">Confidentialité</a></li>
                        <li><a href="#">Conditions</a></li>
                        <li><a href="#">Contact</a></li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2026 BudgetCoop – Tous droits réservés.</p>
            </div>
        </div>
    </footer>
</body>
</html>
