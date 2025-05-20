<?php

// Função para lidar com erros e exceções
function handleError($errno, $errstr, $errfile, $errline) {
    // Registrar erro no log
    error_log("Erro [$errno]: $errstr em $errfile:$errline");
    
    // Para erros críticos, redirecionar para uma página de erro
    if ($errno == E_ERROR || $errno == E_CORE_ERROR || $errno == E_COMPILE_ERROR || $errno == E_USER_ERROR) {
        // Salvar mensagem de erro na sessão
        $_SESSION['system_error'] = "Ocorreu um erro no sistema. Por favor, tente novamente mais tarde.";
        
        // Redirecionar para página de erro ou homepage
        if (!headers_sent()) {
            header('Location: index.php');
            exit;
        }
    }
    
    // Retornar false para permitir que o PHP continue com seu próprio manipulador de erros
    return false;
}

// Registrar manipulador de erros
set_error_handler('handleError');
