-- Adicionar campos para o sistema de compartilhamento
ALTER TABLE compromissos 
ADD COLUMN compartilhado_com INT NULL AFTER compartilhado,
ADD COLUMN status_compartilhamento ENUM('pendente', 'aceito', 'recusado') DEFAULT 'pendente' AFTER compartilhado_com,
ADD COLUMN data_compartilhamento DATETIME NULL AFTER status_compartilhamento;

-- Adicionar índices para melhorar performance
ALTER TABLE compromissos
ADD INDEX idx_criado_por (criado_por),
ADD INDEX idx_compartilhado_com (compartilhado_com),
ADD INDEX idx_data (data);

-- Adicionar chave estrangeira para garantir integridade referencial
ALTER TABLE compromissos
ADD CONSTRAINT fk_compartilhado_com FOREIGN KEY (compartilhado_com) REFERENCES usuarios(id) ON DELETE SET NULL;
