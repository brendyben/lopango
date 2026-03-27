-- ════════════════════════════════════════════════════════════
-- LOPANGO — Script de Migration SQL (MySQL 8+)
-- À exécuter via phpMyAdmin ou ligne de commande
-- mysql -u root -p lopango < migration.sql
-- ════════════════════════════════════════════════════════════

CREATE DATABASE IF NOT EXISTS `lopango`
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE `lopango`;

-- ── TABLE COMMUNES ────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `communes` (
    `code`      VARCHAR(10)    NOT NULL COMMENT 'Code unique (ex: GOM)',
    `nom`       VARCHAR(100)   NOT NULL COMMENT 'Nom de la commune',
    `biens`     INT UNSIGNED   NOT NULL DEFAULT 0,
    `occupes`   INT UNSIGNED   NOT NULL DEFAULT 0,
    `libres`    INT UNSIGNED   NOT NULL DEFAULT 0,
    `litiges`   INT UNSIGNED   NOT NULL DEFAULT 0,
    `travaux`   INT UNSIGNED   NOT NULL DEFAULT 0,
    `collecte`  BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'IRL collecté (FC)',
    `attendu`   BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'IRL attendu (FC)',
    `agents`    TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='21 communes de la Ville de Kinshasa';

-- ── TABLE UTILISATEURS ────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `utilisateurs` (
    `id`            VARCHAR(20)   NOT NULL COMMENT 'ex: USR-001',
    `code`          VARCHAR(20)   NOT NULL UNIQUE COMMENT 'ex: AGT-001, HAB-GOM',
    `nom`           VARCHAR(100)  NOT NULL,
    `role`          ENUM('agent','habitat','hvk') NOT NULL,
    `commune_code`  VARCHAR(10)   NULL REFERENCES communes(code),
    `email`         VARCHAR(150)  NULL,
    `password_hash` VARCHAR(255)  NOT NULL,
    `actif`         TINYINT(1)    NOT NULL DEFAULT 1,
    `score`         TINYINT UNSIGNED NULL COMMENT 'Score 0-100 (agents uniquement)',
    `quittances`    INT UNSIGNED  NOT NULL DEFAULT 0,
    `montant_total` BIGINT UNSIGNED NOT NULL DEFAULT 0,
    `last_login`    TIMESTAMP     NULL,
    `created_at`    TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_code` (`code`),
    INDEX `idx_role` (`role`),
    INDEX `idx_commune` (`commune_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Utilisateurs du système Lopango';

-- ── TABLE BIENS ───────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `biens` (
    `id`                VARCHAR(50)   NOT NULL COMMENT 'Code Lopango: KIN-GOM-TLCM-070C-U01',
    `adresse`           VARCHAR(255)  NOT NULL,
    `commune_code`      VARCHAR(10)   NOT NULL REFERENCES communes(code),
    `quartier`          VARCHAR(100)  NULL,
    `avenue`            VARCHAR(10)   NOT NULL COMMENT 'Code 4 lettres (ex: TLCM)',
    `parcelle`          VARCHAR(10)   NOT NULL,
    `unite`             VARCHAR(5)    NOT NULL COMMENT 'ex: U01',
    `type`              ENUM('Habitation','Commerce','Bureau','Entrepôt') NOT NULL DEFAULT 'Habitation',
    `proprio`           VARCHAR(150)  NOT NULL COMMENT 'Nom du propriétaire',
    `proprio_tel`       VARCHAR(25)   NULL,
    `loyer_usd`         DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Loyer en USD',
    `statut`            ENUM('occupé','libre','litige','travaux') NOT NULL DEFAULT 'libre',
    `locataire`         VARCHAR(150)  NULL,
    `locataire_tel`     VARCHAR(25)   NULL,
    `observations`      TEXT          NULL,
    `score_conformite`  TINYINT UNSIGNED NOT NULL DEFAULT 50 COMMENT 'Score 0-100',
    `irl_dernier`       BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Dernier IRL perçu (FC)',
    `periode_irl`       VARCHAR(7)    NULL COMMENT 'Dernière période ex: 2025-03',
    `agent_recenseur`   VARCHAR(20)   NULL REFERENCES utilisateurs(code),
    `date_creation`     DATE          NOT NULL,
    `created_at`        TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`        TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_commune` (`commune_code`),
    INDEX `idx_statut` (`statut`),
    INDEX `idx_proprio` (`proprio`),
    INDEX `idx_type` (`type`),
    INDEX `idx_agent` (`agent_recenseur`),
    FULLTEXT INDEX `ft_search` (`adresse`, `proprio`, `locataire`, `quartier`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Registre des biens locatifs — identifiant unique Lopango';

-- ── TABLE PAIEMENTS IRL ───────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `paiements` (
    `id`              VARCHAR(20)   NOT NULL COMMENT 'ex: PAY-0001',
    `num_quittance`   VARCHAR(30)   NOT NULL UNIQUE COMMENT 'ex: 20250326-GOM-001',
    `bien_id`         VARCHAR(50)   NOT NULL REFERENCES biens(id),
    `agent_code`      VARCHAR(20)   NOT NULL REFERENCES utilisateurs(code),
    `commune_code`    VARCHAR(10)   NOT NULL REFERENCES communes(code),
    `montant`         BIGINT UNSIGNED NOT NULL COMMENT 'Montant IRL en Francs Congolais',
    `periode`         VARCHAR(7)    NOT NULL COMMENT 'Format YYYY-MM ex: 2025-03',
    `mode_paiement`   ENUM('Espèces','Mobile Money (M-Pesa)','Airtel Money','Orange Money','Virement') NOT NULL DEFAULT 'Espèces',
    `reference`       VARCHAR(100)  NULL COMMENT 'Référence transaction mobile/virement',
    `statut`          ENUM('pending','synced','error') NOT NULL DEFAULT 'pending',
    `date`            DATE          NOT NULL,
    `heure`           TIME          NOT NULL,
    `synced_at`       TIMESTAMP     NULL COMMENT 'Date/heure de synchronisation cloud',
    `created_at`      TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_quittance` (`num_quittance`),
    INDEX `idx_bien` (`bien_id`),
    INDEX `idx_agent` (`agent_code`),
    INDEX `idx_commune` (`commune_code`),
    INDEX `idx_periode` (`periode`),
    INDEX `idx_statut` (`statut`),
    INDEX `idx_date` (`date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Paiements IRL — quittances générées par les agents';

-- ── TABLE ALERTES (log) ───────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `alertes` (
    `id`          INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    `type`        ENUM('fraude','impayé','sync','litige','doublon') NOT NULL,
    `niveau`      ENUM('danger','warn','ok') NOT NULL DEFAULT 'warn',
    `titre`       VARCHAR(200)  NOT NULL,
    `message`     TEXT          NOT NULL,
    `commune_code`VARCHAR(10)   NULL REFERENCES communes(code),
    `bien_id`     VARCHAR(50)   NULL REFERENCES biens(id),
    `traite`      TINYINT(1)    NOT NULL DEFAULT 0,
    `traite_par`  VARCHAR(20)   NULL REFERENCES utilisateurs(code),
    `traite_at`   TIMESTAMP     NULL,
    `created_at`  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_type` (`type`),
    INDEX `idx_niveau` (`niveau`),
    INDEX `idx_traite` (`traite`),
    INDEX `idx_commune` (`commune_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Journal des alertes système Lopango';

-- ── TABLE SESSIONS (alternative native PHP) ───────────────────────────────
CREATE TABLE IF NOT EXISTS `sessions` (
    `id`          VARCHAR(64)   NOT NULL,
    `user_code`   VARCHAR(20)   NOT NULL REFERENCES utilisateurs(code),
    `ip_address`  VARCHAR(45)   NULL,
    `user_agent`  VARCHAR(500)  NULL,
    `payload`     LONGBLOB      NULL,
    `last_active` TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `created_at`  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_user` (`user_code`),
    INDEX `idx_active` (`last_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Sessions utilisateurs Lopango';

-- ── TABLE COMPTEURS ───────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `compteurs` (
    `cle`     VARCHAR(50) NOT NULL COMMENT 'ex: quittances_GOM',
    `valeur`  INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (`cle`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
  COMMENT='Séquences de numérotation automatique';

-- ── VUES UTILES ───────────────────────────────────────────────────────────

-- Vue : biens avec IRL théorique et score
CREATE OR REPLACE VIEW `v_biens_complet` AS
SELECT
    b.*,
    c.nom AS commune_nom,
    ROUND(b.loyer_usd * 2750 * 0.15) AS irl_theorique,
    (SELECT COUNT(*) FROM paiements p WHERE p.bien_id = b.id) AS nb_paiements,
    (SELECT COALESCE(SUM(montant),0) FROM paiements p WHERE p.bien_id = b.id) AS total_irl_percu
FROM biens b
LEFT JOIN communes c ON b.commune_code = c.code;

-- Vue : statistiques par commune
CREATE OR REPLACE VIEW `v_stats_commune` AS
SELECT
    c.*,
    ROUND((c.collecte / NULLIF(c.attendu,0)) * 100) AS taux_recouvrement,
    (SELECT COUNT(*) FROM biens b WHERE b.commune_code = c.code) AS nb_biens_db,
    (SELECT COUNT(*) FROM utilisateurs u WHERE u.commune_code = c.code AND u.role = 'agent') AS nb_agents_db
FROM communes c;

-- Vue : paiements avec détails
CREATE OR REPLACE VIEW `v_paiements_complet` AS
SELECT
    p.*,
    b.adresse,
    b.proprio,
    b.type AS bien_type,
    u.nom AS agent_nom,
    c.nom AS commune_nom
FROM paiements p
LEFT JOIN biens b ON p.bien_id = b.id
LEFT JOIN utilisateurs u ON p.agent_code = u.code
LEFT JOIN communes c ON p.commune_code = c.code;

-- ── DONNÉES INITIALES ─────────────────────────────────────────────────────

INSERT IGNORE INTO `communes` (`code`,`nom`,`biens`,`occupes`,`libres`,`litiges`,`travaux`,`collecte`,`attendu`,`agents`) VALUES
('GOM','Gombe',1847,1234,312,187,114,9235000,12000000,8),
('LIM','Limete',2341,1580,421,198,142,7820000,10500000,12),
('NGA','Ngaliema',3102,2100,520,284,198,11450000,14000000,15),
('KIN','Kinshasa',1523,980,320,143,80,5630000,8000000,7),
('BAN','Bandalungwa',987,650,180,98,59,3240000,4500000,5),
('KAL','Kalamu',2156,1420,380,220,136,6780000,9000000,10),
('MAK','Makala',1234,820,240,120,54,4120000,6000000,6),
('MAT','Matete',1876,1240,340,196,100,5940000,8500000,9),
('MAS','Masina',2890,1900,480,290,220,8340000,11000000,13),
('NSE',"N'Sele",743,490,140,73,40,2100000,3200000,4),
('NKO','Nkole',1120,740,210,110,60,3450000,5000000,6),
('KIB','Kisenso',1680,1100,300,168,112,4980000,7200000,8),
('LEM','Lemba',2020,1340,360,196,124,6120000,8800000,10),
('MOK','Mokali',860,560,164,84,52,2640000,4000000,5),
('NJI',"N'Djili",2340,1560,420,228,132,7020000,10000000,12),
('KIN2','Kintambo',980,640,180,96,64,2940000,4400000,5),
('BAR','Barumbu',760,500,140,74,46,2280000,3400000,4),
('KIN3','Kinseso',640,420,120,62,38,1920000,2800000,3),
('NUM','Ndjoku-Mbaya',580,380,110,56,34,1740000,2500000,3),
('MNG','Mont-Ngafula',1380,900,260,136,84,4140000,6100000,7),
('MAL','Maluku',420,270,90,40,20,1260000,1900000,3);

INSERT IGNORE INTO `utilisateurs` (`id`,`code`,`nom`,`role`,`commune_code`,`email`,`password_hash`,`actif`,`score`,`quittances`,`montant_total`) VALUES
('USR-001','AGT-001','KABILA Emile','agent','GOM','kabila.emile@lopango.cd', '$2y$10$hashedpassword1', 1, 94, 47, 4820000),
('USR-002','AGT-002','MBEKI Sandra','agent','GOM','mbeki.sandra@lopango.cd', '$2y$10$hashedpassword2', 1, 88, 38, 3920000),
('USR-003','AGT-003','TSHISEKEDI Paul','agent','GOM','tshisekedi.paul@lopango.cd','$2y$10$hashedpassword3',0,71,21,2140000),
('USR-004','AGT-004','LUKUSA Bernadette','agent','GOM','lukusa.bernadette@lopango.cd','$2y$10$hashedpassword4',1,85,32,3280000),
('USR-010','HAB-GOM','Service Habitat Gombe','habitat','GOM','habitat.gombe@lopango.cd','$2y$10$hashedpassword5',1,NULL,0,0),
('USR-020','HVK-IRL-001','Dir. Impôts Locatifs','hvk',NULL,'irl@hvk.kinshasa.cd','$2y$10$hashedpassword6',1,NULL,0,0);

INSERT IGNORE INTO `compteurs` (`cle`,`valeur`) VALUES
('quittances_GOM',47),('quittances_LIM',38),('quittances_NGA',52),
('biens',10),('paiements',6),('utilisateurs',6);

-- ════════════════════════════════════════════════════════════
-- INSTRUCTIONS DE MIGRATION DEPUIS JSON
-- 1. Créer la base : CREATE DATABASE lopango;
-- 2. Exécuter ce script
-- 3. Dans config/config.php : passer USE_JSON à false
-- 4. Dans includes/db.php : décommenter les fonctions PDO
-- 5. Importer les données JSON via un script PHP de migration
-- ════════════════════════════════════════════════════════════
