# BudgetCoop - Application de Gestion Collaborative de Budget Personnel

Application web de gestion collaborative de budget personnel développée avec PHP, MySQL, HTML, CSS et JavaScript pour XAMPP.

## 📋 Description

BudgetCoop est une application web permettant à plusieurs utilisateurs de suivre leurs revenus, dépenses, objectifs financiers et budgets partagés à travers une interface simple, sécurisée et accessible sur un réseau local.

## ✨ Fonctionnalités

### Gestion des utilisateurs
- ✅ Création de comptes utilisateurs sécurisés
- ✅ Connexion et déconnexion sécurisées
- ✅ Modification du profil utilisateur
- ✅ Gestion des mots de passe
- ✅ Attribution de rôles (Utilisateur / Administrateur)

### Gestion des revenus et dépenses
- ✅ Ajout de transactions (revenus ou dépenses)
- ✅ Choix du type, montant, date et description
- ✅ Affectation à une catégorie
- ✅ Modification et suppression de transactions

### Gestion des catégories
- ✅ Catégories par défaut (Alimentation, Transport, Logement, Santé, Loisirs, Études, etc.)
- ✅ Ajout de catégories personnalisées
- ✅ Modification et suppression des catégories personnalisées

### Gestion des budgets
- ✅ Création de budgets individuels ou partagés
- ✅ Définition de périodes budgétaires (mensuelle, hebdomadaire, personnalisée)
- ✅ Fixation de plafonds globaux
- ✅ Suivi de l'évolution du budget en temps réel

### Gestion collaborative
- ✅ Création de budgets partagés
- ✅ Ajout de membres à un budget
- ✅ Consultation commune des dépenses
- ✅ Identification de l'auteur de chaque transaction

### Alertes et suivi
- ✅ Alerte lorsque le budget atteint un certain seuil (80%)
- ✅ Alerte en cas de dépassement (100%)
- ✅ Indicateur d'état (maîtrisé, proche de la limite, dépassé)

### Tableau de bord
- ✅ Total des revenus
- ✅ Total des dépenses
- ✅ Solde disponible
- ✅ Pourcentage de budget consommé
- ✅ Répartition des dépenses par catégorie (graphique camembert)
- ✅ Évolution des dépenses dans le temps (graphique barres)

### Administration
- ✅ Validation des comptes utilisateurs
- ✅ Gestion des rôles
- ✅ Supervision de l'ensemble du système
- ✅ Consultation des statistiques globales

## 🛠️ Technologies utilisées

- **Frontend**: HTML5, CSS3, JavaScript, Chart.js
- **Backend**: PHP 8+
- **Base de données**: MySQL / MariaDB
- **Serveur**: Apache (via XAMPP)

## 📦 Structure du projet

```
projectpfa/
├── database/
│   └── schema.sql              # Script de création de la base de données
├── includes/
│   ├── auth.php               # Fonctions d'authentification
│   └── functions.php          # Fonctions utilitaires
├── admin.php                  # Panel d'administration
├── alerts.php                 # Gestion des alertes
├── budgets.php                # Gestion des budgets
├── categories.php             # Gestion des catégories
├── config.php                 # Configuration de la base de données
├── dashboard.php              # Tableau de bord principal
├── index.php                  # Page d'accueil
├── login.php                  # Page de connexion
├── logout.php                 # Déconnexion
├── profile.php                # Gestion du profil utilisateur
├── register.php               # Page d'inscription
├── style.css                  # Feuille de style principale
└── transactions.php           # Gestion des transactions
```

## 🚀 Installation

### Prérequis

- XAMPP (ou WAMP, MAMP) installé
- PHP 8.0 ou supérieur
- MySQL / MariaDB
- Navigateur web moderne

### Étapes d'installation

1. **Copier le projet**
   - Placez le dossier `projectpfa` dans `C:\xampp\htdocs\`

2. **Démarrer XAMPP**
   - Lancez le XAMPP Control Panel
   - Démarrez Apache et MySQL

3. **Créer la base de données**
   - Ouvrez phpMyAdmin (http://localhost/phpmyadmin)
   - Importez le fichier `database/schema.sql`
   - Ou exécutez manuellement le script SQL

4. **Configurer la connexion**
   - Le fichier `config.php` est déjà configuré pour XAMPP par défaut :
     - Hôte: localhost
     - Utilisateur: root
     - Mot de passe: (vide)
     - Base de données: budget_app

5. **Accéder à l'application**
   - Ouvrez votre navigateur
   - Accédez à: http://localhost/projectpfa/

### Compte administrateur par défaut

- **Nom d'utilisateur**: admin
- **Mot de passe**: admin123
- **Email**: admin@budgetcoop.com

⚠️ **Important**: Changez ce mot de passe après la première connexion!

## 📖 Utilisation

### Premiers pas

1. **Créer un compte utilisateur**
   - Cliquez sur "Inscription"
   - Remplissez le formulaire
   - Le compte sera créé mais nécessitera une activation par l'administrateur

2. **Activer un compte (Administrateur)**
   - Connectez-vous en tant qu'administrateur
   - Allez dans le panel "Administration"
   - Activez le compte utilisateur

3. **Se connecter**
   - Utilisez vos identifiants pour vous connecter
   - Accédez au tableau de bord

### Gestion des transactions

1. Allez dans la section "Transactions"
2. Cliquez sur "Ajouter une transaction"
3. Remplissez les informations requises
4. Sélectionnez une catégorie et un budget (optionnel)
5. Validez pour enregistrer

### Création d'un budget

1. Allez dans la section "Budgets"
2. Cliquez sur "Créer un nouveau budget"
3. Définissez le nom, la limite et la période
4. Choisissez le type (individuel ou partagé)
5. Pour un budget partagé, ajoutez des membres

### Collaboration

1. Créez un budget de type "Partagé"
2. Ajoutez des membres par leur nom d'utilisateur
3. Chaque membre peut ajouter des transactions au budget
4. Tous les membres voient les mêmes statistiques

## 🔒 Sécurité

- ✅ Mots de passe hachés avec password_hash()
- ✅ Protection contre les injections SQL (PDO avec prepared statements)
- ✅ Validation des entrées utilisateur
- ✅ Contrôle des accès selon les rôles
- ✅ Protection contre les attaques XSS (htmlspecialchars)

## 📊 Base de données

### Tables principales

- **users**: Utilisateurs de l'application
- **categories**: Catégories de dépenses
- **budgets**: Budgets individuels et partagés
- **budget_members**: Membres des budgets partagés
- **budget_category_limits**: Limites par catégorie
- **transactions**: Transactions (revenus et dépenses)
- **transaction_comments**: Commentaires sur les transactions
- **alerts**: Alertes et notifications

## 🎨 Personnalisation

### Modifier les couleurs

Les couleurs sont définies dans `style.css` dans les variables CSS :
```css
:root {
    --primary-color: #2563eb;
    --secondary-color: #10b981;
    --danger-color: #ef4444;
    --warning-color: #f59e0b;
    /* ... */
}
```

### Ajouter des catégories par défaut

Modifiez le fichier `database/schema.sql` et ajoutez des INSERT dans la table `categories`.

## 🐛 Dépannage

### Erreur de connexion à la base de données

Vérifiez que :
- MySQL est démarré dans XAMPP
- Les identifiants dans `config.php` sont corrects
- La base de données `budget_app` existe

### Erreur "Table doesn't exist"

Assurez-vous d'avoir exécuté le script `database/schema.sql` dans phpMyAdmin.

### Problèmes de session

Vérifiez que :
- Le dossier `tmp` de XAMPP a les permissions d'écriture
- PHP est configuré correctement pour les sessions

## 📝 Documentation technique

### Architecture

L'application suit une architecture en 3 couches :
- **Couche présentation**: HTML, CSS, JavaScript
- **Couche métier**: PHP
- **Couche données**: MySQL

### Sécurité

- Authentification par session PHP
- Hachage des mots de passe (bcrypt)
- Prepared statements PDO
- Validation et sanitization des entrées
- Contrôle d'accès basé sur les rôles

## 📄 Licence

Ce projet est réalisé dans le cadre d'un projet semestriel pour le cours aménagés ING.

## 👥 Auteurs

Projet réalisé dans le cadre du cours "Projet Semestriel 1 ING (cours aménagés)"

## 🤝 Contribution

Pour améliorer l'application, vous pouvez :
- Ajouter des fonctionnalités bonus (export CSV, notifications, etc.)
- Améliorer l'interface utilisateur
- Optimiser les performances
- Ajouter des tests unitaires

## 📞 Support

Pour toute question ou problème, contactez l'administrateur du système.

---

**Version**: 1.0.0  
**Date**: 2026  
**Technologies**: PHP, MySQL, HTML, CSS, JavaScript
