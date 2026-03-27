# LOPANGO — Guide de Déploiement Coolify
**lopango.bakapdatalabs.com**

---

## ÉTAPE 1 — Sous-domaine DNS

Dans le panneau DNS de votre registrar (ou Cloudflare si utilisé) :

```
Type : A
Nom  : lopango
Valeur : [IP du serveur bakapdatalabs.com]
TTL  : Auto (ou 300)
```

→ Résultat : `lopango.bakapdatalabs.com` pointe vers votre serveur.

**Vérifier après quelques minutes :**
```bash
ping lopango.bakapdatalabs.com
# doit retourner l'IP de votre serveur
```

---

## ÉTAPE 2 — Dépôt Git

Lopango doit être dans un dépôt Git accessible par Coolify.

**Option A — GitHub (recommandé)**
```bash
# Sur votre PC, dans le dossier lopango/
git init
git add .
git commit -m "Initial deploy Lopango v1"
git remote add origin https://github.com/VOTRE_COMPTE/lopango.git
git push -u origin main
```

**Option B — Gitea/GitLab auto-hébergé sur bakapdatalabs.com**
Si vous avez Gitea sur Coolify, créez un dépôt privé et poussez dedans.

---

## ÉTAPE 3 — Créer le projet dans Coolify

1. Ouvrir Coolify → **Projects** → **New Project**
2. Nom : `Lopango`
3. Environment : `Production`

---

## ÉTAPE 4 — Ajouter le service MySQL

*Avant l'app, pour avoir les credentials disponibles.*

1. Dans le projet → **+ New Resource** → **Database** → **MySQL**
2. Version : `8.0`
3. Coolify génère automatiquement :
   - `MYSQL_DATABASE` → copier la valeur
   - `MYSQL_USER` → copier
   - `MYSQL_PASSWORD` → copier
   - `MYSQL_HOST` → c'est le nom interne du container

---

## ÉTAPE 5 — Déployer l'application

1. **+ New Resource** → **Application** → **From Git Repository**
2. Choisir votre dépôt `lopango`
3. Branch : `main`
4. Build Pack : `Dockerfile` ← **important, pas Nixpacks**
5. **Domain** : `https://lopango.bakapdatalabs.com`
6. **Port** : `80`

### Variables d'environnement à configurer :

| Variable | Valeur |
|----------|--------|
| `APP_ENV` | `production` |
| `APP_URL` | `https://lopango.bakapdatalabs.com` |
| `DB_HOST` | *(nom du container MySQL généré par Coolify)* |
| `DB_NAME` | *(valeur générée par Coolify)* |
| `DB_USER` | *(valeur générée par Coolify)* |
| `DB_PASS` | *(valeur générée par Coolify)* |

### Volume persistant (données JSON) :

Ajouter un volume dans Coolify → **Storages** :
```
Source (host) : lopango_data
Destination (container) : /var/www/html/data
```

Cela garantit que les fichiers JSON ne sont pas effacés à chaque redéploiement.

---

## ÉTAPE 6 — Déployer

Cliquer **Deploy**. Coolify va :
1. Cloner le dépôt Git
2. Construire l'image Docker avec le `Dockerfile`
3. Démarrer le container Apache + PHP
4. Configurer automatiquement le SSL Let's Encrypt pour `lopango.bakapdatalabs.com`
5. Proxyfier via Traefik

Durée : 2–5 minutes.

---

## ÉTAPE 7 — Vérifier le déploiement

Ouvrir : `https://lopango.bakapdatalabs.com/login.php`

Se connecter avec :
- `HVK-IRL-001` / `hvk2025`
- `HAB-GOM` / `habitat2025`
- `AGT-001` / `agent001`

---

## ÉTAPE 8 — Migration des données XAMPP → Production

Si vous avez des données sur XAMPP qu'on veut migrer :

1. Sur XAMPP, compresser le dossier `data/` en zip
2. Dans Coolify → terminal du container → uploader et décompresser dans `/var/www/html/data/`

Ou plus simple :
```bash
# Sur votre PC
scp -r C:/xampp/htdocs/lopango/data/ user@bakapdatalabs.com:/var/lib/docker/volumes/lopango_data/_data/
```

---

## Redéployer après une mise à jour

```bash
# Sur votre PC, dans le dossier lopango/
git add .
git commit -m "Fix: description du changement"
git push

# Dans Coolify : cliquer Deploy (ou activer le webhook GitHub pour auto-deploy)
```

---

## XAMPP continue de fonctionner

XAMPP reste opérationnel pour le développement.
La variable `APP_URL` différencie les deux environnements :
- XAMPP : `http://localhost/lopango/public` (pas de variable → fallback)
- Production : `https://lopango.bakapdatalabs.com` (variable Coolify)
