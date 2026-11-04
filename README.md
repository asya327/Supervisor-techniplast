# 🏭 Supervisor Techniplast

> Système de supervision industrielle multi-services (PHP / SQLite / Python / MQTT / Docker) conçu pour le suivi en temps réel d'un parc de presses industrielles.

---

## 🚀 À propos du projet

**Supervisor Techniplast** est une solution complète de supervision et d'alerte pour environnements industriels. Le système combine une interface web sécurisée pour les opérateurs et un moteur intelligent en arrière-plan pour surveiller l'état des machines, consigner les arrêts et déclencher des notifications automatisées.

### 🌟 Fonctionnalités clés
* **Interface Web Sécurisée (PHP 8.2) :** Tableau de bord de suivi en temps réel, gestion des sessions et protection renforcée contre les attaques par force brute (verrouillage temporaire par adresse IP après plusieurs échecs).
* **Moteur de Supervision (Python 3) :** Script autonome simulant/surveillant les états des presses (`run`, `stop`, `off`, `maintenance`).
* **Communication IoT (MQTT) :** Publication en temps réel des statuts des machines via un broker MQTT local.
* **Alertes Intelligentes par E-mail :** Envoi de notifications SMTP (via SSL) en cas d'arrêt prolongé (au-delà d'un seuil configurable), avec vérification des plages horaires d'activité de l'usine.
* **Stockage Léger et Robuste :** Base de données relationnelle **SQLite** intégrée avec mode WAL (Write-Ahead Logging) activé pour de meilleures performances concurrentes.
* **100% Conteneurisé (Docker) :** Déploiement universel et portable (Windows / Linux / macOS) grâce à Docker et Docker Compose.

---

## 🛠️ Architecture Technique

* **Front-End / Back-End Web :** PHP 8.2 (Apache), HTML5, CSS3, JavaScript.
* **Base de données :** SQLite3.
* **Moteur d'arrière-plan :** Python 3 (`paho-mqtt`, `sqlite3`, `smtplib`).
* **Conteneurisation :** Docker & Docker Compose.

---

## 📂 Structure du Projet

```text
.
├── css/                  # Feuilles de style (login, dashboard, etc.)
├── images/               # Ressources graphiques et logos
├── .dockerignore         # Fichiers exclus des images Docker
├── .htaccess             # Configuration Apache (Redirection vers login.php)
├── Dockerfile            # Recette de construction de l'image Docker (PHP + Python)
├── docker-compose.yml    # Orchestration des services
├── config.php            # Configuration globale PHP
├── login.php             # Page de connexion sécurisée
├── main.php              # Tableau de bord principal de supervision
├── simulateurPresse.py   # Moteur Python (MQTT, SQLite, Alertes E-mail)
└── README.md             # Documentation du projet
