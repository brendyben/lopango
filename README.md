# LOPANGO — Système de Gouvernance Locative
**Ville de Kinshasa · République Démocratique du Congo**  
Version 1.0 · AmeriKin LLC

---

## 📋 Description

Lopango est un système numérique de gestion et de suivi des biens locatifs et de l'Impôt sur les Revenus Locatifs (IRL) pour la Ville de Kinshasa. Il permet de recenser tous les biens immobiliers mis en location, d'identifier chaque unité via un code unique, de suivre les paiements IRL et de fournir aux autorités des tableaux de bord de pilotage en temps réel.

---

## 🏗️ Architecture

```
/lopango
├── config/
│   └── config.php          ← Configuration centrale (DB, constantes, chemins)
│
├── data/                   ← Données JSON (remplacé par MySQL en production)
│   ├── communes.json       ← 21 communes de Kinshasa
│   ├── biens.json          ← Registre des biens locatifs
│   ├── paiements.json      ← Quittances IRL
│   ├── utilisateurs.json   ← Comptes utilisateurs
│   └── compteurs.json      ← Séquences de numérotation
│
├── includes/
│   ├── db.php              ← Couche de données (JSON + stubs MySQL)
│   ├── auth.php            ← Authentification & gestion des rôles
│   └── functions.php       ← Fonctions métier (formatage, calculs, sécurité)
│
├── views/
│   ├── layout/
│   │   ├── header.php      ← En-tête HTML + topbar
│   │   ├── sidebar.php     ← Navigation latérale
│   │   └── footer.php      ← Pied de page + scripts JS
│   └── pages/
│       ├── login.php               ← Connexion (3 rôles)
│       ├── recensement.php         ← Agent : recenser un bien
│       ├── collecte.php            ← Agent : collecte IRL + quittance
│       ├── buffer.php              ← Agent : synchronisation offline
│       ├── mes_biens.php           ← Agent : historique collectes
│       ├── dashboard_habitat.php   ← Habitat : tableau de bord commune
│       ├── dashboard_hvk.php       ← HVK : vue globale ville
│       ├── biens.php               ← Liste biens (filtres, export)
│       ├── bien_detail.php         ← Fiche détaillée d'un bien
│       ├── agents.php              ← Gestion et performance agents
│       ├── validation.php          ← Habitat : validation dossiers
│       ├── rapports.php            ← Habitat : exports CSV
│       ├── communes.php            ← HVK : tableau toutes communes
│       ├── projections.php         ← HVK : projections budgétaires
│       ├── alertes.php             ← HVK : alertes fraude/impayés
│       └── rapport_hvk.php         ← HVK : rapport mensuel complet
│
├── api/
│   ├── biens.php           ← REST API biens (CRUD)
│   ├── paiements.php       ← REST API paiements
│   ├── communes.php        ← REST API communes
│   └── auth.php            ← REST API authentification
│
├── public/
│   ├── index.php           ← Router principal
│   ├── login.php           ← Point d'entrée login
│   ├── logout.php          ← Déconnexion
│   ├── .htaccess           ← Config Apache (sécurité, réécriture)
│   └── assets/
│       ├── css/
│       │   └── lopango.css ← Design system complet
│       └── js/
│           ├── app.js      ← Application JS (toasts, API, splash)
│           └── qr.js       ← Générateur QR code canvas
│
└── migration.sql           ← Script MySQL pour migration depuis JSON
```

---

## 🚀 Installation (XAMPP)

### Prérequis
- XAMPP avec PHP 8.1+ et Apache
- Navigateur moderne (Chrome, Firefox, Edge)
- Connexion internet (pour Google Fonts et Chart.js CDN)

### Étapes

**1. Copier le projet**
```bash
# Copier le dossier lopango dans le répertoire XAMPP :
C:\xampp\htdocs\lopango\
# ou sur Linux/Mac :
/opt/lampp/htdocs/lopango/
```

**2. Vérifier les permissions**
```bash
# Le dossier data/ doit être accessible en écriture :
chmod 755 /opt/lampp/htdocs/lopango/data/
chmod 644 /opt/lampp/htdocs/lopango/data/*.json
```

**3. Configurer l'URL de base**

Ouvrir `config/config.php` et adapter :
```php
define('BASE_URL', 'http://localhost/lopango/public');
```

**4. Démarrer XAMPP**
- Lancer Apache depuis le panneau de contrôle XAMPP
- Ouvrir : `http://localhost/lopango/public/login.php`

---

## 🔑 Comptes de démonstration

| Rôle | Code | Mot de passe |
|------|------|-------------|
| Agent de Terrain | `AGT-001` | `agent001` |
| Agent de Terrain | `AGT-002` | `agent002` |
| Service Habitat | `HAB-GOM` | `habitat2025` |
| Hôtel de Ville | `HVK-IRL-001` | `hvk2025` |

---

## 🧭 Fonctionnalités par Rôle

### 👮 Agent de Terrain
- **Recensement** : formulaire complet, génération code `KIN-[COM]-[AVN]-[PAR]-[UNT]`, QR code en temps réel
- **Collecte IRL** : lookup bien par ID ou QR, calcul IRL automatique, génération quittance imprimable
- **Synchronisation** : buffer offline, synchronisation manuelle vers cloud (Google Sheets)
- **Mes Collectes** : historique personnel, export CSV

### 🏢 Service Habitat (Commune)
- **Dashboard** : KPIs du jour, graphiques, performance agents
- **Biens Locatifs** : liste complète avec filtres/recherche, export CSV
- **Fiche Bien** : détail complet, historique paiements, mise à jour statut
- **Validation** : approbation/rejet des recensements agents
- **Agents** : performance comparée, ajout d'agents
- **Rapports** : exports ciblés (biens, paiements, litiges)

### 🏛️ Hôtel de Ville (HVK)
- **Vue Globale** : dashboard ville, KPIs, graphiques toutes communes
- **Communes** : tableau détaillé 21 communes, filtrage, export
- **Projections** : simulation paramétrique avec slider de croissance
- **Alertes** : fraudes, impayés, syncs en retard
- **Rapport Mensuel** : rapport institutionnel complet, imprimable

---

## 🔌 API REST

Base URL : `http://localhost/lopango/api/`

### Authentification
Toutes les requêtes nécessitent soit :
- Une session PHP active (navigation interne)
- Le header `X-API-Key: LOPANGO_DEV_KEY_2025` (développement uniquement)

### Endpoints

```
# Communes
GET  /api/communes.php              → Liste toutes les communes
GET  /api/communes.php?code=GOM     → Détail commune
GET  /api/communes.php?stats=1      → Stats globales ville

# Biens
GET  /api/biens.php                 → Liste (filtres: commune, statut, q, page)
GET  /api/biens.php?id=KIN-GOM-...  → Détail bien + paiements
POST /api/biens.php                 → Créer un bien (JSON body)
PUT  /api/biens.php?id=KIN-GOM-...  → Mettre à jour
DEL  /api/biens.php?id=KIN-GOM-...  → Supprimer (HVK only)

# Paiements
GET  /api/paiements.php             → Liste (filtres: commune, periode, statut)
GET  /api/paiements.php?id=PAY-001  → Détail paiement
POST /api/paiements.php             → Créer paiement/quittance
POST /api/paiements.php?action=sync → Synchroniser buffer commune

# Auth
GET  /api/auth.php?me=1             → Utilisateur courant
POST /api/auth.php                  → Login
DEL  /api/auth.php                  → Logout
```

### Exemple : créer un bien via API
```bash
curl -X POST http://localhost/lopango/api/biens.php \
  -H "Content-Type: application/json" \
  -H "X-API-Key: LOPANGO_DEV_KEY_2025" \
  -d '{
    "commune": "GOM",
    "avenue": "Télécom",
    "parcelle": "070C",
    "unite": "U03",
    "type": "Habitation",
    "proprio": "KABILA Jean",
    "loyer": 400,
    "statut": "occupé",
    "locataire": "MBEKI Sandra"
  }'
```

---

## 🗃️ Migration vers MySQL

**1. Créer la base de données**
```bash
mysql -u root -p < migration.sql
```

**2. Importer les données JSON** (script à créer)
```php
// Lire chaque fichier JSON et insérer en base
// Les fonctions db_* dans includes/db.php sont prêtes
```

**3. Basculer la configuration**

Dans `config/config.php` :
```php
define('USE_JSON', false); // ← Changer ici
```

Dans `includes/db.php` : décommenter les fonctions PDO et les fonctions `db_get_pdo()`.

---

## 🎨 Personnalisation Design

Le design system est dans `public/assets/css/lopango.css`.

Variables CSS principales :
```css
:root {
  --green:   #0f4c35;   /* Vert institutionnel */
  --gold-l:  #c9a227;   /* Or accent */
  --serif:   'Cormorant Garamond';
  --sans:    'DM Sans';
  --mono:    'JetBrains Mono';
}
```

---

## 🔒 Sécurité

- **CSRF** : token sur tous les formulaires POST
- **Session** : régénération ID à chaque login, expiration par inactivité
- **Mots de passe** : hashés avec `password_hash()` (bcrypt)
- **XSS** : échappement systématique via `lp_h()`
- **Path Traversal** : chemins construits via constantes PHP uniquement
- **.htaccess** : interdiction d'accès direct aux fichiers JSON et sensibles

---

## 🔜 Évolutions Prévues

- [ ] Migration MySQL complète
- [ ] Authentification renforcée agents (QR code matricule)
- [ ] Notifications SMS bailleurs (Africa's Talking)
- [ ] Carte thermique Kinshasa (Leaflet.js + GeoJSON communes)
- [ ] Gamification agents (badges, classement mensuel)
- [ ] Synchronisation Google Sheets API v4
- [ ] Module contrat numérique (PDF via TCPDF/DomPDF)
- [ ] Application mobile PWA (offline-first)
- [ ] Détection fraude automatisée (règles métier)
- [ ] Tableau de bord multi-périodes avec comparaisons

---

## 📄 Licence

Propriétaire — AmeriKin LLC · Kinshasa, DRC · © 2025  
Tous droits réservés. Usage exclusif Ville de Kinshasa.
