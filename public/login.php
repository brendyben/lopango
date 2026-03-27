<?php
/**
 * LOPANGO — Page de Connexion
 * public/login.php
 */
require_once dirname(__DIR__) . '/config/config.php';

// Si déjà connecté → rediriger
if (auth_check()) {
    redirect(BASE_URL . '/index.php?page=' . auth_default_page());
}

// Inclure la vue login
include VIEWS_PATH . '/pages/login.php';
