<?php
/**
 * LOPANGO — Couche de données
 * JSON (développement) ou MySQL (production)
 * Basculement automatique selon USE_JSON défini dans config.php
 */

// ══════════════════════════════════════════════════════════════ PDO

function db_pdo(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }
    return $pdo;
}

// ══════════════════════════════════════════════════════════════ JSON helpers

function json_read(string $file): array {
    $path = DATA_PATH . '/' . $file . '.json';
    if (!file_exists($path)) return [];
    return json_decode(file_get_contents($path), true) ?? [];
}

function json_write(string $file, array $data): bool {
    $path = DATA_PATH . '/' . $file . '.json';
    return file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX) !== false;
}

// ══════════════════════════════════════════════════════════════ COMMUNES

function db_get_communes(): array {
    if (USE_JSON) return json_read('communes');
    return db_pdo()->query('SELECT * FROM communes ORDER BY nom')->fetchAll();
}

function db_get_commune(string $code): ?array {
    if (USE_JSON) {
        foreach (json_read('communes') as $c) if ($c['code'] === $code) return $c;
        return null;
    }
    $stmt = db_pdo()->prepare('SELECT * FROM communes WHERE code = ?');
    $stmt->execute([$code]);
    return $stmt->fetch() ?: null;
}

function db_update_commune(string $code, array $data): bool {
    if (USE_JSON) {
        $communes = json_read('communes');
        foreach ($communes as &$c) { if ($c['code'] === $code) { $c = array_merge($c, $data); return json_write('communes', $communes); } }
        return false;
    }
    $sets = implode(',', array_map(fn($k) => "`$k`=?", array_keys($data)));
    $stmt = db_pdo()->prepare("UPDATE communes SET $sets WHERE code=?");
    return $stmt->execute([...array_values($data), $code]);
}

// ══════════════════════════════════════════════════════════════ BIENS

function db_get_biens(?string $commune = null, ?string $statut = null): array {
    if (USE_JSON) {
        $biens = json_read('biens');
        if ($commune) $biens = array_values(array_filter($biens, fn($b) => $b['commune'] === $commune));
        if ($statut)  $biens = array_values(array_filter($biens, fn($b) => $b['statut']  === $statut));
        return $biens;
    }
    $sql = 'SELECT * FROM biens WHERE 1=1';
    $params = [];
    if ($commune) { $sql .= ' AND commune_code=?'; $params[] = $commune; }
    if ($statut)  { $sql .= ' AND statut=?';       $params[] = $statut; }
    $stmt = db_pdo()->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();
    // Normaliser commune_code → commune pour compatibilité
    return array_map(function($b) {
        $b['commune'] = $b['commune_code'] ?? $b['commune'] ?? '';
        $b['loyer']   = $b['loyer_usd']   ?? $b['loyer']   ?? 0;
        return $b;
    }, $rows);
}

function db_get_bien(string $id): ?array {
    if (USE_JSON) {
        foreach (json_read('biens') as $b) if ($b['id'] === $id) return $b;
        return null;
    }
    $stmt = db_pdo()->prepare('SELECT * FROM biens WHERE id=?');
    $stmt->execute([$id]);
    $b = $stmt->fetch();
    if ($b) {
        $b['commune'] = $b['commune_code'] ?? '';
        $b['loyer']   = $b['loyer_usd']   ?? 0; // normalisation
    }
    return $b ?: null;
}

function db_create_bien(array $bien): bool {
    if (USE_JSON) {
        $biens = json_read('biens');
        foreach ($biens as $b) if ($b['id'] === $bien['id']) return false;
        $biens[] = $bien;
        $compteurs = json_read('compteurs');
        $compteurs['biens'] = ($compteurs['biens'] ?? 0) + 1;
        json_write('compteurs', $compteurs);
        return json_write('biens', $biens);
    }
    try {
        $stmt = db_pdo()->prepare('INSERT INTO biens (id,adresse,commune_code,quartier,avenue,parcelle,unite,type,proprio,proprio_tel,loyer_usd,statut,locataire,locataire_tel,observations,score_conformite,agent_recenseur,date_creation) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
        return $stmt->execute([
            $bien['id'], $bien['adresse'], $bien['commune'], $bien['quartier'] ?? '',
            $bien['avenue'], $bien['parcelle'], $bien['unite'], $bien['type'],
            $bien['proprio'], $bien['proprio_tel'] ?? '', $bien['loyer'] ?? 0,
            $bien['statut'], $bien['locataire'] ?? '', $bien['locataire_tel'] ?? '',
            $bien['observations'] ?? '', $bien['score_conformite'] ?? 50,
            $bien['agent_recenseur'] ?? '', $bien['date_creation'] ?? date('Y-m-d'),
        ]);
    } catch (Exception $e) {
        error_log('db_create_bien error: ' . $e->getMessage());
        return false;
    }
}

function db_update_bien(string $id, array $data): bool {
    if (USE_JSON) {
        $biens = json_read('biens');
        foreach ($biens as &$b) { if ($b['id'] === $id) { $b = array_merge($b, $data); return json_write('biens', $biens); } }
        return false;
    }
    // Mapper commune → commune_code
    if (isset($data['commune'])) { $data['commune_code'] = $data['commune']; unset($data['commune']); }
    if (isset($data['loyer']))   { $data['loyer_usd']    = $data['loyer'];   unset($data['loyer']); }
    $allowed = ['statut','locataire','locataire_tel','proprio','proprio_tel','loyer_usd','observations','adresse','quartier','commune_code','score_conformite','irl_dernier','periode_irl'];
    $data = array_intersect_key($data, array_flip($allowed));
    if (empty($data)) return true;
    $sets = implode(',', array_map(fn($k) => "`$k`=?", array_keys($data)));
    $stmt = db_pdo()->prepare("UPDATE biens SET $sets WHERE id=?");
    return $stmt->execute([...array_values($data), $id]);
}

function db_delete_bien(string $id): bool {
    if (USE_JSON) {
        $biens = array_values(array_filter(json_read('biens'), fn($b) => $b['id'] !== $id));
        return json_write('biens', $biens);
    }
    return db_pdo()->prepare('DELETE FROM biens WHERE id=?')->execute([$id]);
}

function db_search_biens(string $query): array {
    if (USE_JSON) {
        $q = mb_strtolower(trim($query));
        return array_values(array_filter(json_read('biens'), fn($b) =>
            str_contains(mb_strtolower($b['id']), $q) ||
            str_contains(mb_strtolower($b['adresse']), $q) ||
            str_contains(mb_strtolower($b['proprio']), $q) ||
            str_contains(mb_strtolower($b['locataire'] ?? ''), $q)
        ));
    }
    $stmt = db_pdo()->prepare('SELECT * FROM biens WHERE id LIKE ? OR adresse LIKE ? OR proprio LIKE ? OR locataire LIKE ?');
    $q = '%' . $query . '%';
    $stmt->execute([$q, $q, $q, $q]);
    return array_map(function($b) { $b['commune'] = $b['commune_code'] ?? ''; return $b; }, $stmt->fetchAll());
}

// ══════════════════════════════════════════════════════════════ PAIEMENTS

function db_get_paiements(?string $commune = null, ?string $periode = null): array {
    if (USE_JSON) {
        $p = json_read('paiements');
        if ($commune) $p = array_values(array_filter($p, fn($x) => $x['commune'] === $commune));
        if ($periode) $p = array_values(array_filter($p, fn($x) => $x['periode'] === $periode));
        return $p;
    }
    $sql = 'SELECT * FROM paiements WHERE 1=1';
    $params = [];
    if ($commune) { $sql .= ' AND commune_code=?'; $params[] = $commune; }
    if ($periode) { $sql .= ' AND periode=?';      $params[] = $periode; }
    $stmt = db_pdo()->prepare($sql . ' ORDER BY date DESC, heure DESC');
    $stmt->execute($params);
    return array_map(function($p) {
        $p['commune'] = $p['commune_code'] ?? '';
        $p['heure']   = is_string($p['heure']) ? substr($p['heure'], 0, 5) : $p['heure'];
        return $p;
    }, $stmt->fetchAll());
}

function db_get_paiements_bien(string $bien_id): array {
    if (USE_JSON) return array_values(array_filter(json_read('paiements'), fn($p) => $p['bien_id'] === $bien_id));
    $stmt = db_pdo()->prepare('SELECT * FROM paiements WHERE bien_id=? ORDER BY date DESC');
    $stmt->execute([$bien_id]);
    return $stmt->fetchAll();
}

function db_create_paiement(array $p): bool {
    if (USE_JSON) {
        $paiements = json_read('paiements');
        $paiements[] = $p;
        $compteurs = json_read('compteurs');
        $compteurs['quittances'][$p['commune']] = ($compteurs['quittances'][$p['commune']] ?? 0) + 1;
        $compteurs['paiements'] = ($compteurs['paiements'] ?? 0) + 1;
        json_write('compteurs', $compteurs);
        db_update_bien($p['bien_id'], ['irl_dernier' => $p['montant'], 'periode_irl' => $p['periode']]);
        return json_write('paiements', $paiements);
    }
    $stmt = db_pdo()->prepare('INSERT INTO paiements (id,num_quittance,bien_id,agent_code,commune_code,montant,periode,mode_paiement,reference,statut,date,heure) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)');
    $ok = $stmt->execute([
        $p['id'], $p['num_quittance'], $p['bien_id'], $p['agent_code'],
        $p['commune'], $p['montant'], $p['periode'],
        $p['mode_paiement'], $p['reference'] ?? '', $p['statut'],
        $p['date'], $p['heure'],
    ]);
    if ($ok) db_update_bien($p['bien_id'], ['irl_dernier' => $p['montant'], 'periode_irl' => $p['periode']]);
    // Incrémenter compteur
    $n = db_pdo()->query("SELECT valeur FROM compteurs WHERE cle='quittances_" . $p['commune'] . "'")->fetchColumn();
    if ($n !== false) db_pdo()->prepare("UPDATE compteurs SET valeur=? WHERE cle=?")->execute([$n+1, 'quittances_' . $p['commune']]);
    else db_pdo()->prepare("INSERT INTO compteurs (cle,valeur) VALUES (?,1)")->execute(['quittances_' . $p['commune']]);
    return $ok;
}

function db_sync_paiements(string $commune): int {
    if (USE_JSON) {
        $paiements = json_read('paiements'); $count = 0;
        foreach ($paiements as &$p) { if ($p['commune'] === $commune && $p['statut'] === 'pending') { $p['statut'] = 'synced'; $p['synced_at'] = date('Y-m-d H:i:s'); $count++; } }
        json_write('paiements', $paiements); return $count;
    }
    $stmt = db_pdo()->prepare("UPDATE paiements SET statut='synced', synced_at=NOW() WHERE commune_code=? AND statut='pending'");
    $stmt->execute([$commune]);
    return $stmt->rowCount();
}

// ══════════════════════════════════════════════════════════════ UTILISATEURS

function db_get_utilisateurs(?string $role = null): array {
    if (USE_JSON) {
        $u = json_read('utilisateurs');
        return $role ? array_values(array_filter($u, fn($x) => $x['role'] === $role)) : array_values($u);
    }
    $sql = 'SELECT * FROM utilisateurs';
    $params = [];
    if ($role) { $sql .= ' WHERE role=?'; $params[] = $role; }
    $stmt = db_pdo()->prepare($sql);
    $stmt->execute($params);
    return array_map(fn($u) => array_merge($u, ['commune' => $u['commune_code'] ?? null]), $stmt->fetchAll());
}

function db_get_utilisateur_by_code(string $code): ?array {
    if (USE_JSON) {
        foreach (json_read('utilisateurs') as $u) if ($u['code'] === $code) return $u;
        return null;
    }
    $stmt = db_pdo()->prepare('SELECT * FROM utilisateurs WHERE code=?');
    $stmt->execute([$code]);
    $u = $stmt->fetch();
    if ($u) $u['commune'] = $u['commune_code'] ?? null;
    return $u ?: null;
}

function db_auth_utilisateur(string $code, string $password): ?array {
    if (USE_JSON) {
        foreach (json_read('utilisateurs') as $u) {
            if ($u['code'] !== $code || !$u['actif']) continue;
            if (isset($u['password_plain']) && $u['password_plain'] === $password) return $u;
            $hash = $u['password_hash'] ?? '';
            if (strlen($hash) >= 60 && str_starts_with($hash, '$2') && password_verify($password, $hash)) return $u;
        }
        return null;
    }
    $stmt = db_pdo()->prepare('SELECT * FROM utilisateurs WHERE code=? AND actif=1');
    $stmt->execute([$code]);
    $u = $stmt->fetch();
    if (!$u) return null;
    $u['commune'] = $u['commune_code'] ?? null;
    // Vérifier password_plain d'abord, puis hash bcrypt
    if (isset($u['password_plain']) && $u['password_plain'] === $password) return $u;
    $hash = $u['password_hash'] ?? '';
    if (strlen($hash) >= 60 && str_starts_with($hash, '$2') && password_verify($password, $hash)) return $u;
    return null;
}

function db_create_utilisateur(array $user): bool {
    if (USE_JSON) {
        $users = json_read('utilisateurs');
        foreach ($users as $u) if ($u['code'] === $user['code']) return false;
        $user['password_hash'] = password_hash($user['password_plain'] ?? '', PASSWORD_BCRYPT);
        $users[] = $user;
        return json_write('utilisateurs', $users);
    }
    $stmt = db_pdo()->prepare('SELECT COUNT(*) FROM utilisateurs WHERE code=?');
    $stmt->execute([$user['code']]);
    if ($stmt->fetchColumn() > 0) return false;
    $hash = password_hash($user['password_plain'] ?? '', PASSWORD_BCRYPT);
    $stmt = db_pdo()->prepare('INSERT INTO utilisateurs (id,code,nom,role,commune_code,email,password_hash,password_plain,actif,score,quittances,montant_total,date_creation) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)');
    return $stmt->execute([
        $user['id'], $user['code'], $user['nom'], $user['role'],
        $user['commune'] ?? null, $user['email'] ?? '',
        $hash, $user['password_plain'] ?? '',
        $user['actif'] ? 1 : 0, $user['score'] ?? 50,
        $user['quittances'] ?? 0, $user['montant'] ?? 0,
        $user['date_creation'] ?? date('Y-m-d'),
    ]);
}

// ══════════════════════════════════════════════════════════════ COMPTEURS

function db_next_quittance_num(string $commune): string {
    if (USE_JSON) {
        $compteurs = json_read('compteurs');
        $n = ($compteurs['quittances'][$commune] ?? 0) + 1;
        return date('Ymd') . '-' . $commune . '-' . str_pad($n, 3, '0', STR_PAD_LEFT);
    }
    $key = 'quittances_' . $commune;
    $pdo = db_pdo();
    $n = $pdo->query("SELECT valeur FROM compteurs WHERE cle='$key'")->fetchColumn();
    $n = $n ? (int)$n + 1 : 1;
    return date('Ymd') . '-' . $commune . '-' . str_pad($n, 3, '0', STR_PAD_LEFT);
}

function db_next_id(string $type): int {
    if (USE_JSON) {
        $compteurs = json_read('compteurs');
        $next = ($compteurs[$type] ?? 0) + 1;
        $compteurs[$type] = $next;
        json_write('compteurs', $compteurs);
        return $next;
    }
    $pdo = db_pdo();
    $n = $pdo->query("SELECT valeur FROM compteurs WHERE cle='$type'")->fetchColumn();
    $next = $n ? (int)$n + 1 : 1;
    if ($n !== false) $pdo->prepare("UPDATE compteurs SET valeur=? WHERE cle=?")->execute([$next, $type]);
    else              $pdo->prepare("INSERT INTO compteurs (cle,valeur) VALUES (?,?)")->execute([$type, $next]);
    return $next;
}

// ══════════════════════════════════════════════════════════════ STATISTIQUES

function db_stats_ville(): array {
    $communes  = db_get_communes();
    $biens     = USE_JSON ? json_read('biens') : db_pdo()->query('SELECT id FROM biens')->fetchAll();
    $paiements = USE_JSON ? json_read('paiements') : db_pdo()->query('SELECT id FROM paiements')->fetchAll();
    $agents    = db_get_utilisateurs(ROLE_AGENT);
    return [
        'total_biens'     => count($biens),
        'total_communes'  => count($communes),
        'total_agents'    => count($agents),
        'total_collecte'  => array_sum(array_column($communes, 'collecte')),
        'total_attendu'   => array_sum(array_column($communes, 'attendu')),
        'total_biens_all' => array_sum(array_column($communes, 'biens')),
        'total_litiges'   => array_sum(array_column($communes, 'litiges')),
        'total_paiements' => count($paiements),
    ];
}

function db_stats_commune(string $code): array {
    $commune   = db_get_commune($code);
    $biens     = db_get_biens($code);
    $paiements = db_get_paiements($code);
    $agents    = array_values(array_filter(db_get_utilisateurs(ROLE_AGENT), fn($u) => ($u['commune'] ?? $u['commune_code'] ?? '') === $code));
    return [
        'commune'      => $commune,
        'biens'        => $biens,
        'nb_biens'     => count($biens),
        'nb_occupes'   => count(array_filter($biens, fn($b) => $b['statut'] === 'occupé')),
        'nb_libres'    => count(array_filter($biens, fn($b) => $b['statut'] === 'libre')),
        'nb_litiges'   => count(array_filter($biens, fn($b) => $b['statut'] === 'litige')),
        'nb_travaux'   => count(array_filter($biens, fn($b) => $b['statut'] === 'travaux')),
        'paiements'    => $paiements,
        'nb_paiements' => count($paiements),
        'agents'       => $agents,
        'collecte'     => $commune['collecte'] ?? 0,
        'attendu'      => $commune['attendu']  ?? 0,
    ];
}

function db_get_alertes(?string $commune = null): array {
    $alertes = [];
    $biens = db_get_biens($commune);
    $paiements = db_get_paiements($commune);
    foreach ($biens as $b) {
        if ($b['statut'] === 'occupé') {
            $pays = array_filter($paiements, fn($p) => $p['bien_id'] === $b['id']);
            if (empty($pays)) $alertes[] = ['type'=>'impayé','niveau'=>'warn','titre'=>'IRL non reçu','msg'=>"Bien {$b['id']} occupé — aucun paiement IRL enregistré.",'commune'=>$b['commune'],'bien_id'=>$b['id'],'date'=>date('d/m/Y')];
        }
        if ($b['statut'] === 'litige') $alertes[] = ['type'=>'litige','niveau'=>'danger','titre'=>'Bien en litige','msg'=>"Bien {$b['id']} — " . ($b['observations'] ?? ''),'commune'=>$b['commune'],'bien_id'=>$b['id'],'date'=>date('d/m/Y')];
    }
    return $alertes;
}
