<?php
/**
 * LOPANGO — Couche de données
 * Actuellement : JSON files
 * Migration SQL : remplacer les fonctions json_* par des requêtes PDO
 */

// ── LECTURE JSON ───────────────────────────────────────────────────────────
function json_read(string $file): array {
    $path = DATA_PATH . '/' . $file . '.json';
    if (!file_exists($path)) return [];
    $content = file_get_contents($path);
    return json_decode($content, true) ?? [];
}

// ── ÉCRITURE JSON ──────────────────────────────────────────────────────────
function json_write(string $file, array $data): bool {
    $path = DATA_PATH . '/' . $file . '.json';
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    return file_put_contents($path, $json, LOCK_EX) !== false;
}

// ══════════════════════════════════════════════════════════════ COMMUNES

function db_get_communes(): array {
    return json_read('communes');
}

function db_get_commune(string $code): ?array {
    $communes = db_get_communes();
    foreach ($communes as $c) {
        if ($c['code'] === $code) return $c;
    }
    return null;
}

function db_update_commune(string $code, array $data): bool {
    $communes = db_get_communes();
    foreach ($communes as &$c) {
        if ($c['code'] === $code) {
            $c = array_merge($c, $data);
            return json_write('communes', $communes);
        }
    }
    return false;
}

// ══════════════════════════════════════════════════════════════ BIENS

function db_get_biens(?string $commune = null, ?string $statut = null): array {
    $biens = json_read('biens');
    if ($commune) {
        $biens = array_filter($biens, fn($b) => $b['commune'] === $commune);
    }
    if ($statut) {
        $biens = array_filter($biens, fn($b) => $b['statut'] === $statut);
    }
    return array_values($biens);
}

function db_get_bien(string $id): ?array {
    $biens = json_read('biens');
    foreach ($biens as $b) {
        if ($b['id'] === $id) return $b;
    }
    return null;
}

function db_create_bien(array $bien): bool {
    $biens = json_read('biens');
    // Vérifier doublon
    foreach ($biens as $b) {
        if ($b['id'] === $bien['id']) return false;
    }
    $biens[] = $bien;
    // Incrémenter compteur
    $compteurs = json_read('compteurs');
    $compteurs['biens'] = ($compteurs['biens'] ?? 0) + 1;
    json_write('compteurs', $compteurs);
    return json_write('biens', $biens);
}

function db_update_bien(string $id, array $data): bool {
    $biens = json_read('biens');
    foreach ($biens as &$b) {
        if ($b['id'] === $id) {
            $b = array_merge($b, $data);
            return json_write('biens', $biens);
        }
    }
    return false;
}

function db_delete_bien(string $id): bool {
    $biens = json_read('biens');
    $biens = array_filter($biens, fn($b) => $b['id'] !== $id);
    return json_write('biens', array_values($biens));
}

function db_search_biens(string $query): array {
    $biens = json_read('biens');
    $q = mb_strtolower(trim($query));
    return array_values(array_filter($biens, function($b) use ($q) {
        return str_contains(mb_strtolower($b['id']), $q)
            || str_contains(mb_strtolower($b['adresse']), $q)
            || str_contains(mb_strtolower($b['proprio']), $q)
            || str_contains(mb_strtolower($b['locataire'] ?? ''), $q);
    }));
}

// ══════════════════════════════════════════════════════════════ PAIEMENTS

function db_get_paiements(?string $commune = null, ?string $periode = null): array {
    $paiements = json_read('paiements');
    if ($commune) {
        $paiements = array_filter($paiements, fn($p) => $p['commune'] === $commune);
    }
    if ($periode) {
        $paiements = array_filter($paiements, fn($p) => $p['periode'] === $periode);
    }
    return array_values($paiements);
}

function db_get_paiements_bien(string $bien_id): array {
    $paiements = json_read('paiements');
    return array_values(array_filter($paiements, fn($p) => $p['bien_id'] === $bien_id));
}

function db_create_paiement(array $paiement): bool {
    $paiements = json_read('paiements');
    $paiements[] = $paiement;
    // Incrémenter compteur quittances par commune
    $compteurs = json_read('compteurs');
    $commune = $paiement['commune'];
    $compteurs['quittances'][$commune] = ($compteurs['quittances'][$commune] ?? 0) + 1;
    $compteurs['paiements'] = ($compteurs['paiements'] ?? 0) + 1;
    json_write('compteurs', $compteurs);
    // Mettre à jour le statut IRL du bien
    db_update_bien($paiement['bien_id'], [
        'irl_dernier'  => $paiement['montant'],
        'periode_irl'  => $paiement['periode'],
    ]);
    return json_write('paiements', $paiements);
}

function db_sync_paiements(string $commune): int {
    $paiements = json_read('paiements');
    $count = 0;
    foreach ($paiements as &$p) {
        if ($p['commune'] === $commune && $p['statut'] === 'pending') {
            $p['statut']    = 'synced';
            $p['synced_at'] = date('Y-m-d H:i:s');
            $count++;
        }
    }
    json_write('paiements', $paiements);
    return $count;
}

// ══════════════════════════════════════════════════════════════ UTILISATEURS

function db_get_utilisateurs(?string $role = null): array {
    $users = json_read('utilisateurs');
    if ($role) {
        $users = array_filter($users, fn($u) => $u['role'] === $role);
    }
    return array_values($users);
}

function db_get_utilisateur_by_code(string $code): ?array {
    $users = json_read('utilisateurs');
    foreach ($users as $u) {
        if ($u['code'] === $code) return $u;
    }
    return null;
}

function db_auth_utilisateur(string $code, string $password): ?array {
    $users = json_read('utilisateurs');
    foreach ($users as $u) {
        if ($u['code'] !== $code) continue;
        if (!$u['actif'])        continue;

        // 1. Comparaison en clair (développement / JSON)
        if (isset($u['password_plain']) && $u['password_plain'] === $password) {
            return $u;
        }

        // 2. Vérification bcrypt (seulement si le hash est valide)
        $hash = $u['password_hash'] ?? '';
        if (strlen($hash) >= 60 && str_starts_with($hash, '$2')) {
            if (password_verify($password, $hash)) {
                return $u;
            }
        }
    }
    return null;
}

function db_create_utilisateur(array $user): bool {
    $users = json_read('utilisateurs');
    // Vérifier code unique
    foreach ($users as $u) {
        if ($u['code'] === $user['code']) return false; // Code déjà pris
    }
    $user['password_hash'] = password_hash($user['password_plain'] ?? '', PASSWORD_BCRYPT);
    $users[] = $user;
    return json_write('utilisateurs', $users);
}

// ══════════════════════════════════════════════════════════════ COMPTEURS

function db_next_quittance_num(string $commune): string {
    $compteurs = json_read('compteurs');
    $n = ($compteurs['quittances'][$commune] ?? 0) + 1;
    // Ne pas écrire ici — sera fait dans db_create_paiement
    return date('Ymd') . '-' . $commune . '-' . str_pad($n, 3, '0', STR_PAD_LEFT);
}

function db_next_id(string $type): int {
    $compteurs = json_read('compteurs');
    $next = ($compteurs[$type] ?? 0) + 1;
    $compteurs[$type] = $next;
    json_write('compteurs', $compteurs);
    return $next;
}

// ══════════════════════════════════════════════════════════════ STATISTIQUES

function db_stats_ville(): array {
    $communes = db_get_communes();
    $biens    = db_get_biens();
    $paiements = db_get_paiements();
    $users    = db_get_utilisateurs();
    return [
        'total_biens'     => count($biens),
        'total_communes'  => count($communes),
        'total_agents'    => count(array_filter($users, fn($u) => $u['role'] === ROLE_AGENT)),
        'total_collecte'  => array_sum(array_column($communes, 'collecte')),
        'total_attendu'   => array_sum(array_column($communes, 'attendu')),
        'total_biens_all' => array_sum(array_column($communes, 'biens')),
        'total_litiges'   => array_sum(array_column($communes, 'litiges')),
        'total_paiements' => count($paiements),
    ];
}

function db_stats_commune(string $code): array {
    $commune  = db_get_commune($code);
    $biens    = db_get_biens($code);
    $paiements = db_get_paiements($code);
    $agents   = db_get_utilisateurs(ROLE_AGENT);
    $agents   = array_filter($agents, fn($u) => $u['commune'] === $code);
    return [
        'commune'          => $commune,
        'biens'            => $biens,
        'nb_biens'         => count($biens),
        'nb_occupes'       => count(array_filter($biens, fn($b) => $b['statut'] === 'occupé')),
        'nb_libres'        => count(array_filter($biens, fn($b) => $b['statut'] === 'libre')),
        'nb_litiges'       => count(array_filter($biens, fn($b) => $b['statut'] === 'litige')),
        'nb_travaux'       => count(array_filter($biens, fn($b) => $b['statut'] === 'travaux')),
        'paiements'        => $paiements,
        'nb_paiements'     => count($paiements),
        'agents'           => array_values($agents),
        'collecte'         => $commune['collecte'] ?? 0,
        'attendu'          => $commune['attendu']  ?? 0,
    ];
}

// ══════════════════════════════════════════════════════════════ ALERTES
// (Calculées dynamiquement — pas de fichier statique)

function db_get_alertes(?string $commune = null): array {
    $alertes = [];
    $biens = db_get_biens($commune);
    $paiements = db_get_paiements($commune);

    // Biens occupés sans paiement récent (simulation)
    foreach ($biens as $b) {
        if ($b['statut'] === 'occupé') {
            $pays_bien = array_filter($paiements, fn($p) => $p['bien_id'] === $b['id']);
            if (empty($pays_bien)) {
                $alertes[] = [
                    'type'    => 'impayé',
                    'niveau'  => 'warn',
                    'titre'   => 'IRL non reçu',
                    'msg'     => "Bien {$b['id']} occupé — aucun paiement IRL enregistré.",
                    'commune' => $b['commune'],
                    'bien_id' => $b['id'],
                    'date'    => date('d/m/Y'),
                ];
            }
        }
        if ($b['statut'] === 'litige') {
            $alertes[] = [
                'type'    => 'litige',
                'niveau'  => 'danger',
                'titre'   => 'Bien en litige',
                'msg'     => "Bien {$b['id']} — {$b['observations']}",
                'commune' => $b['commune'],
                'bien_id' => $b['id'],
                'date'    => date('d/m/Y'),
            ];
        }
    }
    return $alertes;
}

// ══════════════════════════════════════════════════════════════
// PRÉPARER MIGRATION MYSQL
// Remplacer chaque fonction json_* par l'équivalent PDO :
//
// function db_get_pdo(): PDO {
//     static $pdo = null;
//     if (!$pdo) {
//         $dsn = "mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=".DB_CHARSET;
//         $pdo = new PDO($dsn, DB_USER, DB_PASS, [
//             PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
//             PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
//         ]);
//     }
//     return $pdo;
// }
//
// function db_get_biens(?string $commune=null): array {
//     $pdo = db_get_pdo();
//     $sql = "SELECT * FROM biens";
//     $params = [];
//     if ($commune) { $sql .= " WHERE commune_code = ?"; $params[] = $commune; }
//     $stmt = $pdo->prepare($sql);
//     $stmt->execute($params);
//     return $stmt->fetchAll();
// }
