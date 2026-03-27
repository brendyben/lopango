<?php
/**
 * LOPANGO — Déconnexion
 * public/logout.php
 */
require_once dirname(__DIR__) . '/config/config.php';
auth_logout(); // Redirige automatiquement vers login.php
