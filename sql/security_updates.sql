-- Criar tabela para registrar tentativas de login
CREATE TABLE IF NOT EXISTS tentativas_login (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL,
    ip VARCHAR(45) NOT NULL,
    success TINYINT(1) NOT NULL DEFAULT 0,
    time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX (email),
    INDEX (ip),
    INDEX (time)
);

-- Criar tabela para log de atividades
CREATE TABLE IF NOT EXISTS log_atividades (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    acao VARCHAR(50) NOT NULL,
    descricao TEXT NULL,
    ip VARCHAR(45) NOT NULL,
    user_agent TEXT NULL,
    data_hora TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX (user_id),
    INDEX (acao),
    INDEX (data_hora)
);

-- Adicionar índice para melhorar performance de consultas de login
ALTER TABLE usuarios ADD INDEX (email);
