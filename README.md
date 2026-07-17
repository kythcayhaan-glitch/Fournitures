# Gestion des Fournitures de Bureau

Application Symfony 7 complète pour la gestion des fournitures de bureau avec workflow de validation, gestion des stocks et contrôle d'accès par rôles.

---

## Prérequis

- PHP 8.2+
- Composer
- MySQL 8.0+ (ou MariaDB 10.5+)
- Node.js 18+ (optionnel, pour les assets)
- Symfony CLI (recommandé)

---

## Installation

### 1. Cloner le projet

```bash
git clone <url-du-repo> fournitures-bureau
cd fournitures-bureau
```

### 2. Installer les dépendances PHP

```bash
composer install
```

### 3. Configurer l'environnement

Copier et adapter le fichier `.env` :

```bash
cp .env .env.local
```

Éditer `.env.local` et renseigner :

```dotenv
DATABASE_URL="mysql://user:password@127.0.0.1:3306/fournitures_bureau?serverVersion=8.0&charset=utf8mb4"
APP_SECRET=votre_secret_unique_ici
```

### 4. Créer la base de données

```bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
```

### 5. Charger les données de test (Fixtures)

```bash
php bin/console doctrine:fixtures:load
```

Cette commande crée :
- 1 administrateur : `admin@example.com` / `Admin1234!`
- 2 managers : `manager1@example.com` / `Manager1234!`, `manager2@example.com` / `Manager1234!`
- 5 utilisateurs : `david.leroi@example.com`, `emma.blanc@example.com`, `felix.moreau@example.com`, `gaelle.simon@example.com`, `hugo.thomas@example.com` — mot de passe : `User1234!`
- 4 catégories : Papeterie, Informatique, Mobilier, Hygiène
- 20 fournitures
- 10 demandes avec statuts variés

### 6. Démarrer le serveur

```bash
symfony serve
# ou
php -S localhost:8000 -t public/
```

Accéder à : http://localhost:8000

---

## Structure du projet

```
src/
├── Command/
│   ├── StockAlertesCommand.php      # app:stock:alertes
│   └── DemandePurgerCommand.php     # app:demande:purger
├── Controller/
│   ├── SecurityController.php       # Login/Logout
│   ├── DashboardController.php      # Tableau de bord
│   ├── DemandeController.php        # Demandes (ROLE_USER)
│   ├── ManagerController.php        # Gestion (ROLE_MANAGER)
│   └── AdminController.php          # Administration (ROLE_ADMIN)
├── DataFixtures/
│   └── AppFixtures.php
├── Entity/
│   ├── User.php
│   ├── Category.php
│   ├── Fourniture.php
│   ├── DemandeMateriel.php
│   ├── LigneDemande.php
│   └── MouvementStock.php
├── Enum/
│   ├── StatutDemande.php
│   └── TypeMouvement.php
├── EventSubscriber/
│   └── WorkflowSubscriber.php       # Gestion transitions workflow
├── Form/
│   ├── DemandeType.php
│   ├── LigneDemandeType.php
│   ├── ProcessDemandeType.php
│   ├── LigneServieType.php
│   ├── FournitureType.php
│   ├── CategoryType.php
│   └── AjustementStockType.php
├── Repository/
│   ├── UserRepository.php
│   ├── CategoryRepository.php
│   ├── FournitureRepository.php
│   ├── DemandeMaterielRepository.php
│   ├── LigneDemandeRepository.php
│   └── MouvementStockRepository.php
├── Security/
│   └── Voter/
│       ├── DemandeVoter.php
│       └── FournitureVoter.php
└── Service/
    ├── StockService.php
    ├── DemandeService.php
    └── NotificationService.php

config/
├── packages/
│   ├── security.yaml                # Firewalls, roles, voters
│   ├── workflow.yaml                # State machine DemandeMateriel
│   ├── doctrine.yaml
│   ├── doctrine_migrations.yaml
│   ├── knp_paginator.yaml
│   ├── messenger.yaml
│   └── twig.yaml
└── services.yaml

templates/
├── base.html.twig                   # Layout Bootstrap 5 avec sidebar
├── security/login.html.twig
├── dashboard/index.html.twig
├── demande/{index,new,show}.html.twig
├── manager/{index,process,deliver,stock}.html.twig
├── admin/
│   ├── fournitures/{index,new,edit}.html.twig
│   ├── categories/{index,new,edit}.html.twig
│   ├── users/index.html.twig
│   ├── inventaire.html.twig
│   ├── inventaire_ajuster.html.twig
│   └── historique.html.twig
└── components/
    ├── badge_statut.html.twig
    └── ligne_stock.html.twig
```

---

## Rôles et permissions

| Rôle | Accès |
|------|-------|
| `ROLE_USER` | Tableau de bord, créer/voir ses propres demandes |
| `ROLE_MANAGER` | Tout ROLE_USER + traiter toutes les demandes, voir le stock |
| `ROLE_ADMIN` | Tout ROLE_MANAGER + CRUD fournitures/catégories, gestion utilisateurs, inventaire |

---

## Workflow des demandes

```
                      ┌─────────┐
            submit    │         │ approve   ┌──────────┐  deliver  ┌───────────┐
 (création) ────────► │ pending │──────────►│ approved │──────────►│ delivered │
                      │         │           └──────────┘           └───────────┘
                      │         │ reject
                      └────┬────┘
                           │
                           ▼
                      ┌──────────┐
                      │ rejected │
                      └──────────┘
```

Seuls les `ROLE_MANAGER` peuvent effectuer les transitions `approve`, `reject` et `deliver`.

---

## Commandes console

```bash
# Afficher les fournitures en stock bas
php bin/console app:stock:alertes

# Purger les demandes rejetées de plus de 90 jours (simulation)
php bin/console app:demande:purger --statut=rejected --jours=90 --dry-run

# Purger réellement
php bin/console app:demande:purger --statut=rejected --jours=90

# Purger les demandes livrées de plus de 180 jours
php bin/console app:demande:purger --statut=delivered --jours=180
```

---

## Opérations de maintenance

```bash
# Vider le cache
php bin/console cache:clear

# Vérifier le schéma Doctrine
php bin/console doctrine:schema:validate

# Créer une nouvelle migration après modification d'entité
php bin/console make:migration

# Appliquer les migrations
php bin/console doctrine:migrations:migrate
```

---

## Variables d'environnement

| Variable | Description | Défaut |
|----------|-------------|--------|
| `DATABASE_URL` | DSN de connexion à la base de données | `mysql://root:root@127.0.0.1:3306/fournitures_bureau` |
| `APP_SECRET` | Clé secrète Symfony (32 chars min) | À définir |
| `APP_ENV` | Environnement (`dev`, `prod`, `test`) | `dev` |
| `MAILER_DSN` | DSN pour les emails | `null://null` |
| `MESSENGER_TRANSPORT_DSN` | Transport Messenger | `doctrine://default` |

---

## Sécurité

- Mots de passe hashés avec `auto` (bcrypt/argon2)
- Tokens CSRF sur tous les formulaires et actions destructives
- Voters Symfony pour chaque permission métier
- Validation côté serveur avec Symfony Validator
- Role hierarchy : `ROLE_ADMIN > ROLE_MANAGER > ROLE_USER`

---

## Technologies utilisées

| Technologie | Version | Usage |
|-------------|---------|-------|
| PHP | 8.2+ | Langage principal, enums, attributs |
| Symfony | 7.1 | Framework |
| Doctrine ORM | 3.x | Persistance, migrations |
| Symfony Workflow | 7.1 | State machine des demandes |
| Symfony Security | 7.1 | Auth, voters, CSRF |
| Bootstrap | 5.3 | Interface responsive |
| KnpPaginatorBundle | 6.x | Pagination |
| Symfony Messenger | 7.1 | Notifications asynchrones |
