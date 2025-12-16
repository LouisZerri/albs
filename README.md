# 🚇 À la bonne station

> Application de suivi et gamification du métro parisien

![Version](https://img.shields.io/badge/version-1.0.0-blue.svg)
![Symfony](https://img.shields.io/badge/Symfony-7.2-000000?logo=symfony)
![PHP](https://img.shields.io/badge/PHP-8.3-777BB4?logo=php)
![License](https://img.shields.io/badge/license-MIT-green.svg)


## 🎯 À propos

**À la bonne station** est une application web (et bientôt mobile) permettant aux utilisateurs de suivre leur progression dans les 16 lignes du métro parisien. Transformez vos déplacements quotidiens en aventure ludique grâce à un système de badges et de statistiques !

### 🌟 Concept

- **Explorez** : Marquez les stations que vous visitez
- **Progressez** : Suivez votre avancement ligne par ligne
- **Débloquez** : Obtenez des badges en accomplissant des défis
- **Partagez** : Échangez avec la communauté (à venir)

---

## ✨ Fonctionnalités

### 🔐 Authentification
- ✅ Inscription / Connexion sécurisée
- ✅ Remember me (7 jours)
- ✅ Gestion de session
- ✅ Protection CSRF

### 🚇 Suivi des lignes
- ✅ 16 lignes du métro parisien
- ✅ 309 stations au total
- ✅ Marquage "Passé" / "Visité"
- ✅ Gestion des branches (Lignes 7 et 13)
- ✅ Statistiques par ligne
- ✅ Calcul de progression en temps réel

### 🏆 Système de badges (16 badges)

#### Badges de progression
- 🌱 **Parisien en herbe** : Première station visitée
- 🗼 **Touriste averti** : 10 stations passées
- 🚇 **Habitué du métro** : 5 stations visitées
- 🥐 **Vrai Parisien** : 25 stations visitées
- 🐀 **Rat des quais** : 50 stations passées
- 🗺️ **Explorateur urbain** : 50 stations visitées
- 🌍 **Globe-trotter parisien** : 100 stations visitées
- 🏆 **Légende du métro** : Toutes les stations visitées

#### Badges de lignes
- 👑 **Maître de ligne** : 1 ligne complétée
- 🎯 **Collectionneur de lignes** : 3 lignes complétées
- 👨‍✈️ **Seigneur du métro** : 5 lignes complétées
- 💙 **Fidèle de la ligne** : 20 stations passées sur une même ligne

#### Badges spéciaux
- 🌙 **Noctambule** : Visite après minuit (00h-06h)
- 🌅 **Lève-tôt** : Visite avant 6h du matin
- 🏃 **Marathonien du RER** : 10 stations en une journée
- 🎉 **Nouveau départ** : Création du compte

#### Système avancé
- ✅ Calcul automatique de progression (0-100%)
- ✅ Notifications en temps réel des nouveaux badges
- ✅ Sélection de 3 badges à afficher sur le profil
- ✅ Suivi temporel (date de première visite/passage)

### 👤 Profil utilisateur
- ✅ Statistiques personnelles
- ✅ Upload de photo de profil
- ✅ Édition des informations
- ✅ Badges affichés (max 3)
- ✅ Progression par ligne
- ✅ Suppression de compte

### 📊 Statistiques
- ✅ Nombre de stations passées
- ✅ Nombre de stations visitées
- ✅ Progression par ligne (%)
- ✅ Badges débloqués
- ✅ Historique de progression

---

## 🛠️ Stack technique

### Backend (Web)
- **Framework** : Symfony 7.3
- **PHP** : 8.3
- **Base de données** : MySQL 8.0
- **ORM** : Doctrine
- **Template Engine** : Twig
- **Authentification** : Symfony Security

### Frontend (Web)
- **CSS Framework** : TailwindCSS 4
- **JavaScript** : Stimulus.js (Hotwired)
- **Build tool** : Webpack Encore
- **Icons** : Emoji natifs

### Infrastructure
- **Containerisation** : Docker + Docker Compose
- **Serveur web** : Apache 2.4
- **PHP-FPM** : 8.3
- **Volumes persistants** : MySQL data

---

## 📋 Prérequis

- Docker 20.10+
- Docker Compose 2.0+
- Git

---

## 🚀 Installation

### 1. Cloner le repository

```bash
git clone https://github.com/votre-username/albs.git
cd albs
```

### 2. Lancer Docker

```bash
docker compose up -d --build
```

### 3. Installer les dépendances

```bash
docker compose exec php composer install
docker compose exec php npm install
```

### 4. Configurer l'environnement

```bash
docker compose exec php cp .env .env.local
# Éditer .env.local avec vos configurations
```

### 5. Créer la base de données

```bash
docker compose exec php php bin/console doctrine:database:create
docker compose exec php php bin/console doctrine:migrations:migrate
```

### 6. Charger les données initiales

```bash
# Lignes et stations du métro
docker compose exec php php bin/console doctrine:fixtures:load --append

# Badges
docker compose exec php php bin/console doctrine:fixtures:load --group=BadgeFixtures --append
```

### 7. Compiler les assets

```bash
docker compose exec php npm run build
# Ou en mode watch pour le développement
docker compose exec php npm run watch
```

### 8. Accéder à l'application

- **Web** : http://localhost:8080
- **Base de données** : localhost:3307
  - User: `root`
  - Password: `root`
  - Database: `albs`

---

## ⚙️ Configuration

### Variables d'environnement

Créez un fichier `.env.local` :

```env
# Base de données
DATABASE_URL="mysql://root:root@database:3306/albs?serverVersion=8.0"

# Secret Symfony
APP_SECRET=votre_secret_unique_ici

# Environnement
APP_ENV=dev
APP_DEBUG=true

# Mailer (optionnel)
MAILER_DSN=smtp://mailhog:1025
```

### Structure Docker

```yaml
services:
  php:
    build: ./docker/php
    ports:
      - "8080:80"
    volumes:
      - .:/var/www/html
    networks:
      - station_network

  database:
    image: mysql:8.0
    ports:
      - "3307:3306"
    environment:
      MYSQL_ROOT_PASSWORD: root
      MYSQL_DATABASE: bonne_station
    volumes:
      - db_data:/var/lib/mysql
    networks:
      - station_network
```

---

## 💻 Utilisation

### Commandes principales

```bash
# Démarrer l'application
docker compose up -d

# Arrêter l'application
docker compose down

# Voir les logs
docker compose logs -f php

# Accéder au conteneur PHP
docker compose exec php bash

# Vider le cache
docker compose exec php php bin/console cache:clear

# Créer une migration
docker compose exec php php bin/console make:migration

# Exécuter les migrations
docker compose exec php php bin/console doctrine:migrations:migrate

# Charger les fixtures
docker compose exec php php bin/console doctrine:fixtures:load --append

# Mettre à jour les dates des anciennes stations
docker compose exec php php bin/console app:update-user-station-dates
```

---

## 🏗️ Architecture

### Structure du projet

```
.
├── assets/                      # Frontend assets
│   ├── controllers/             # Stimulus controllers
│   ├── styles/                  # CSS/TailwindCSS
│   └── app.js                   # Entry point JS
├── config/                      # Configuration Symfony
├── docker/                      # Configuration Docker
│   ├── apache/
│   │   └── vhost.conf
│   ├── mysql/
│   │   └── init.sql
│   └── php/
│       └── Dockerfile
├── public/                      # Point d'entrée web
│   ├── uploads/                 # Fichiers uploadés
│   │   └── avatars/
│   └── index.php
├── src/
│   ├── Command/                 # Commandes Symfony
│   ├── Controller/              # Contrôleurs
│   │   ├── Api/                 # API pour mobile
│   │   ├── BadgeController.php
│   │   ├── LineController.php
│   │   ├── ProfileController.php
│   │   ├── RegistrationController.php
│   │   └── SecurityController.php
│   ├── DataFixtures/            # Fixtures
│   │   ├── BadgeFixtures.php
│   │   ├── LineFixtures.php
│   │   └── StationFixtures.php
│   ├── Entity/                  # Entités Doctrine
│   │   ├── Badge.php
│   │   ├── Line.php
│   │   ├── Station.php
│   │   ├── User.php
│   │   └── UserStation.php
│   ├── Form/                    # Formulaires
│   │   ├── LoginFormType.php
│   │   ├── ProfileEditFormType.php
│   │   └── RegistrationFormType.php
│   ├── Repository/              # Repositories Doctrine
│   │   ├── BadgeRepository.php
│   │   ├── LineRepository.php
│   │   ├── StationRepository.php
│   │   ├── UserRepository.php
│   │   └── UserStationRepository.php
│   ├── Service/                 # Services métier
│   │   └── BadgeService.php
│   └── Kernel.php
├── templates/                   # Templates Twig
│   ├── base.html.twig
│   ├── home/
│   ├── line/
│   ├── profile/
│   ├── registration/
│   └── security/
├── translations/                # Traductions
│   └── security.fr.yaml
├── docker-compose.yml
└── README.md
```

### Modèle de données

```
User
├── id
├── email (unique)
├── password (hashed)
├── username
├── avatar
├── roles (JSON)
├── createdAt
├── displayedBadges (JSON, max 3)
├── favoriteLine → Line
├── userStations → [UserStation]
└── badges → [Badge]

Line
├── id
├── number
├── name
├── color (hex)
├── textColor (hex)
└── stations → [Station]

Station
├── id
├── name
├── position
├── branch (nullable)
├── line → Line
└── userStations → [UserStation]

UserStation
├── id
├── passed (boolean)
├── stopped (boolean)
├── firstPassedAt (datetime)
├── firstStoppedAt (datetime)
├── updatedAt (datetime)
├── user → User
└── station → Station

Badge
├── id
├── name
├── description
├── icon (emoji)
├── type
├── criteria (JSON)
└── users → [User]
```

---

## 🤝 Contribution

Les contributions sont les bienvenues ! 

### Comment contribuer

1. **Fork** le projet
2. **Créez** votre branche (`git checkout -b feature/AmazingFeature`)
3. **Committez** vos changements (`git commit -m 'Add some AmazingFeature'`)
4. **Push** vers la branche (`git push origin feature/AmazingFeature`)
5. **Ouvrez** une Pull Request

### Guidelines

- Suivre les conventions de code PSR-12
- Ajouter des tests pour les nouvelles fonctionnalités
- Mettre à jour la documentation si nécessaire
- Utiliser des commits clairs et descriptifs

### Rapporter un bug

Ouvrez une issue avec :
- Description claire du bug
- Steps to reproduce
- Comportement attendu vs actuel
- Screenshots si applicable
- Version de l'application

---

## 📄 License

Ce projet est sous licence MIT. Voir le fichier [LICENSE](LICENSE) pour plus de détails.

```
MIT License

Copyright (c) 2025 À la bonne station

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.
```

---

<div align="center">

[![GitHub Stars](https://img.shields.io/github/stars/votre-username/a-la-bonne-station?style=social)](https://github.com/votre-username/a-la-bonne-station)
[![GitHub Forks](https://img.shields.io/github/forks/votre-username/a-la-bonne-station?style=social)](https://github.com/votre-username/a-la-bonne-station)

</div>