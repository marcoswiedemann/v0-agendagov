-- Atualizar a tabela de tentativas de login para usar nome de usuário em vez de email
ALTER TABLE tentativas_login CHANGE email usuario VARCHAR(255) NOT NULL;

-- Se a tabela ainda não existir, este comando a criará
CREATE TABLE IF NOT EXISTS tentativas_login (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario VARCHAR(255) NOT NULL,
    ip VARCHAR(45) NOT NULL,
    success TINYINT(1) NOT NULL DEFAULT 0,
    time TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Criar tabela de log de atividades se ainda não existir
CREATE TABLE IF NOT EXISTS log_atividades (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    acao VARCHAR(50) NOT NULL,
    descricao TEXT NULL,
    ip VARCHAR(45) NOT NULL,
    user_agent VARCHAR(255) NOT NULL,
    data_hora DATETIME NOT NULL
);
