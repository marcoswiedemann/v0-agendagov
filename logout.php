<?php
require_once 'includes/auth.php';

// Usar a função de logout aprimorada
logout();

// Redirecionar para a página de login
header('Location: index.php');
exit;
?>
