<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Verificar se o usuário está logado e é administrador
if (!isLoggedIn() || getCurrentUser()['papel'] !== 'admin') {
    header('Location: index.php');
    exit;
}

// Obter configurações do sistema
$config = getConfiguracoes();

// Obter IDs do prefeito e vice-prefeito
$stmt_prefeito = $pdo->prepare("SELECT id FROM usuarios WHERE papel = 'prefeito' LIMIT 1");
$stmt_prefeito->execute();
$id_prefeito = $stmt_prefeito->fetchColumn() ?: 1;

$stmt_vice = $pdo->prepare("SELECT id FROM usuarios WHERE papel = 'vice' LIMIT 1");
$stmt_vice->execute();
$id_vice = $stmt_vice->fetchColumn() ?: 2;

$mensagens = [];
$erros = [];
$registros_importados = 0;

// Função para mapear o status
function mapearStatus($status) {
    switch ($status) {
        case 'Pendente': return 'pendente';
        case 'Realizado': return 'realizado';
        case 'Não Realizado': return 'cancelado';
        default: return 'pendente';
    }
}

// Verificar se o formulário foi enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Iniciar transação
        $pdo->beginTransaction();
        
        // Dados dos compromissos do arquivo SQL
        $compromissos = [
            // ID 2
            ['id' => 2, 'data' => '2025-04-30', 'hora' => '08:00:00', 'titulo' => 'REUNIÃO DE ALINHAMENTO COM TODOS OS SECRETÁRIOS', 'tipo' => 'Reunião', 'prioridade' => 'Alta', 'sigiloso' => 'Sim', 'pessoas' => 'Todos Scretários', 'contato' => '', 'local' => 'Gabinete', 'status' => 'Realizado', 'criado_por' => $id_prefeito],
            
            // ID 4
            ['id' => 4, 'data' => '2025-04-30', 'hora' => '14:00:00', 'titulo' => 'POSSE DO NÚCLEO DO BAIRRO SÃO JOÃO', 'tipo' => 'Evento', 'prioridade' => 'Normal', 'sigiloso' => 'Não', 'pessoas' => '', 'contato' => '', 'local' => 'Bairro São João', 'status' => 'Realizado', 'criado_por' => $id_vice],
            
            // ID 5
            ['id' => 5, 'data' => '2025-04-30', 'hora' => '15:00:00', 'titulo' => 'REUNIÃO COM O SECRETÁRIO CLEBERSON', 'tipo' => 'Reunião', 'prioridade' => 'Média', 'sigiloso' => 'Não', 'pessoas' => 'Cleberson Taborda', 'contato' => '', 'local' => 'Gabinete', 'status' => 'Realizado', 'criado_por' => $id_prefeito],
            
            // ID 6
            ['id' => 6, 'data' => '2025-04-30', 'hora' => '16:00:00', 'titulo' => 'REUNIÃO COM RODRIGO FLORES E ANDRÉ RUSCHEL', 'tipo' => 'Reunião', 'prioridade' => 'Alta', 'sigiloso' => 'Sim', 'pessoas' => 'Rodrigo Flores, Carlos Gonçalves, Rolando Burgel, André Ruschel', 'contato' => '55997000995', 'local' => 'Gabinete do Vice-Prefeito', 'status' => 'Pendente', 'criado_por' => $id_vice],
            
            // ID 7
            ['id' => 7, 'data' => '2025-04-30', 'hora' => '19:00:00', 'titulo' => 'Live com o secretário Cleberson', 'tipo' => 'Evento', 'prioridade' => 'Alta', 'sigiloso' => 'Não', 'pessoas' => 'Cleberson', 'contato' => '', 'local' => 'Gabinete', 'status' => 'Pendente', 'criado_por' => $id_prefeito],
            
            // ID 8
            ['id' => 8, 'data' => '2025-05-02', 'hora' => '09:00:00', 'titulo' => 'CÂMARA DE VEREADORES SOBRE OS 400 ANOS DAS MISSÕES', 'tipo' => 'Reunião', 'prioridade' => 'Média', 'sigiloso' => 'Não', 'pessoas' => 'Vereadores, Sec.Turismo/Vinicius Makvitiz. Sr. Carlos Gonçalves participou.', 'contato' => '', 'local' => 'R. Antunes Ribas, 1111 - Centro, Santo Ângelo - RS, 98801-630', 'status' => 'Realizado', 'criado_por' => $id_prefeito],
            
            // ID 9
            ['id' => 9, 'data' => '2025-05-02', 'hora' => '16:00:00', 'titulo' => 'Visita na Unimed logo após janta com Dr Madureira e Tiago da UPA', 'tipo' => 'Evento', 'prioridade' => 'Normal', 'sigiloso' => 'Não', 'pessoas' => 'Dr. Madureira e Tiago UPA', 'contato' => '', 'local' => 'Av. Getúlio Vargas, 1079 - Missões, Santo Ângelo - RS, 98801-703', 'status' => 'Pendente', 'criado_por' => $id_vice],
            
            // ID 10
            ['id' => 10, 'data' => '2025-05-02', 'hora' => '19:00:00', 'titulo' => 'LIVE CANCELADA', 'tipo' => 'Evento', 'prioridade' => 'Normal', 'sigiloso' => 'Não', 'pessoas' => '', 'contato' => '', 'local' => 'Gabinete', 'status' => 'Não Realizado', 'criado_por' => $id_prefeito],
            
            // ID 11
            ['id' => 11, 'data' => '2025-05-03', 'hora' => '15:30:00', 'titulo' => 'MISSA NA CATEDRAL', 'tipo' => 'Evento', 'prioridade' => 'Média', 'sigiloso' => 'Não', 'pessoas' => 'Vinícius Makivitz', 'contato' => '', 'local' => 'Praça Pinheiro Machado', 'status' => 'Pendente', 'criado_por' => $id_prefeito],
            
            // ID 12
            ['id' => 12, 'data' => '2025-05-04', 'hora' => '12:00:00', 'titulo' => 'ALMOÇO NO LAGEADO MICUIM', 'tipo' => 'Evento', 'prioridade' => 'Normal', 'sigiloso' => 'Não', 'pessoas' => '', 'contato' => '', 'local' => 'Lajeado Micuim', 'status' => 'Pendente', 'criado_por' => $id_vice],
            
            // ID 15
            ['id' => 15, 'data' => '2025-05-05', 'hora' => '08:00:00', 'titulo' => 'REUNIÃO C/LEANDRA-RH E MARISA/SINDICATO FUNCIONÁRIOS MUNICIPAIS, REF. IPE', 'tipo' => 'Reunião', 'prioridade' => 'Normal', 'sigiloso' => 'Não', 'pessoas' => 'Srª. Leandra/RH; Srª. Marisa/Sindicato; Sr. Nívio/Prefeito', 'contato' => '', 'local' => 'no GAB', 'status' => 'Realizado', 'criado_por' => $id_prefeito],
            
            // ID 16
            ['id' => 16, 'data' => '2025-05-06', 'hora' => '08:30:00', 'titulo' => 'REUNIÃO C/PRODUTOR RURAL, FRANCISCO E CULTURA.', 'tipo' => 'Reunião', 'prioridade' => 'Normal', 'sigiloso' => 'Não', 'pessoas' => 'Sr. Nívio; Sr. Carlos Gonçalves; Cleberson; Edimilson e Assessor; Vinicius e Cris.', 'contato' => '', 'local' => 'no GAB', 'status' => 'Realizado', 'criado_por' => $id_vice],
            
            // ID 17
            ['id' => 17, 'data' => '2025-05-05', 'hora' => '10:00:00', 'titulo' => 'REUNIÃO DE ALINHAMENTO C/VEREADORES', 'tipo' => 'Reunião', 'prioridade' => 'Normal', 'sigiloso' => 'Não', 'pessoas' => 'Prefeito; Vice-Prefeito; Chefe de Gabinete e Vereadores', 'contato' => '', 'local' => 'no GAB', 'status' => 'Realizado', 'criado_por' => $id_prefeito],
            
            // ID 18
            ['id' => 18, 'data' => '2025-05-05', 'hora' => '14:00:00', 'titulo' => 'REUNIAO AMM', 'tipo' => 'Reunião', 'prioridade' => 'Alta', 'sigiloso' => 'Sim', 'pessoas' => '', 'contato' => '', 'local' => 'Em Cerro Largo', 'status' => 'Não Realizado', 'criado_por' => $id_vice],
            
            // ID 19
            ['id' => 19, 'data' => '2025-05-06', 'hora' => '14:00:00', 'titulo' => 'REUNIÃO C/ADV. NICO MARCHIONATTI', 'tipo' => 'Reunião', 'prioridade' => 'Alta', 'sigiloso' => 'Sim', 'pessoas' => 'Sr. Nívio; Sr. Nico Marchionatti; Srª Rosimere/Sec. Assistência Social', 'contato' => '', 'local' => 'no GAB', 'status' => 'Realizado', 'criado_por' => $id_prefeito],
            
            // ID 20
            ['id' => 20, 'data' => '2025-05-06', 'hora' => '15:00:00', 'titulo' => 'REUNIÃO C/VINICIUS MAKVITIZ E PESSOAL SISTEMA DE VIGILÂNCIA SANITÁRIA E DEPTO DE TRÂNSITO', 'tipo' => 'Reunião', 'prioridade' => 'Normal', 'sigiloso' => 'Não', 'pessoas' => 'Sec.Turismo/Sr. Vinicius Makvitz; Coord. Trânsito/Sr. Nelson Koch;', 'contato' => '', 'local' => 'no GAB', 'status' => 'Realizado', 'criado_por' => $id_vice],
            
            // ID 21
            ['id' => 21, 'data' => '2025-05-04', 'hora' => '13:30:00', 'titulo' => 'SOLENIDADE DE ABERTURA DO CAMPEONATO MUNICIPAL DE FUTEBOL AMADOR/2025-TROFÉU JARDEL AUGUSTO OBERMEIER', 'tipo' => 'Evento', 'prioridade' => 'Normal', 'sigiloso' => 'Não', 'pessoas' => 'Sr. Nívio; Edimilson.', 'contato' => '', 'local' => 'no Campo Municipal de Eventos Hélio Costa de Oliveira (antigo SESI).', 'status' => 'Pendente', 'criado_por' => $id_prefeito],
            
            // ID 22
            ['id' => 22, 'data' => '2025-05-07', 'hora' => '10:00:00', 'titulo' => 'VISITA DO COMANDANTE DOS BOMBEIROS/DEFESA CIVIL DO RS.', 'tipo' => 'Visita', 'prioridade' => 'Média', 'sigiloso' => 'Não', 'pessoas' => 'Sr. Nívio; Sr. Castanho; Sr. Carlos Kelm deverá providenciar um mimo p/entrega ao convidado.', 'contato' => '', 'local' => 'no GAB', 'status' => 'Realizado', 'criado_por' => $id_vice],
            
            // ID 23
            ['id' => 23, 'data' => '2025-05-05', 'hora' => '08:00:00', 'titulo' => 'REUNIÃO C/SRª LEANDRA/RH E  A SRª MARISA/SINDICATO DOS FUNCIONÁRIOS MUNICIPAIS, P/TRATAR SOBRE IPE E OUTROS ASSUNTOS DE INTERESSE DA CLASSE', 'tipo' => 'Evento', 'prioridade' => 'Normal', 'sigiloso' => 'Não', 'pessoas' => 'Leandra/RH; Marisa/Sindicato Func. Municipais; Sr. Nívio', 'contato' => '', 'local' => 'no GAB', 'status' => 'Realizado', 'criado_por' => $id_prefeito],
            
            // ID 24
            ['id' => 24, 'data' => '2025-05-06', 'hora' => '11:00:00', 'titulo' => 'REUNIÃO COM CARLÃO/ZAGUEIRO.', 'tipo' => 'Reunião', 'prioridade' => 'Normal', 'sigiloso' => 'Não', 'pessoas' => '', 'contato' => '', 'local' => 'no GAB', 'status' => 'Realizado', 'criado_por' => $id_vice],
            
            // ID 25
            ['id' => 25, 'data' => '2025-05-06', 'hora' => '16:00:00', 'titulo' => 'REUNIÃO COM O RURAL, SOBRE PAA', 'tipo' => 'Reunião', 'prioridade' => 'Média', 'sigiloso' => 'Não', 'pessoas' => '', 'contato' => '', 'local' => 'no GAB', 'status' => 'Realizado', 'criado_por' => $id_prefeito],
            
            // ID 26
            ['id' => 26, 'data' => '2025-05-08', 'hora' => '08:00:00', 'titulo' => 'REUNIÃO C/SEC.EDUCAÇÃO-ANE E ADV.LUIS ANTONIO (PROFISSIONAL DE APOIO A ESTAGIÁRIO)', 'tipo' => 'Reunião', 'prioridade' => 'Média', 'sigiloso' => 'Não', 'pessoas' => 'Sr. Nívio; Sec. Educação/Ane e Adv. Luiz Antônio', 'contato' => '', 'local' => 'no GAB', 'status' => 'Realizado', 'criado_por' => $id_vice],
            
            // ID 27
            ['id' => 27, 'data' => '2025-05-10', 'hora' => '09:00:00', 'titulo' => 'INAUGURAÇÃO DA FARMÁCIA SÃO JOÃO', 'tipo' => 'Evento', 'prioridade' => 'Normal', 'sigiloso' => 'Não', 'pessoas' => '', 'contato' => '', 'local' => 'Antigo ARENA', 'status' => 'Realizado', 'criado_por' => $id_prefeito],
            
            // ID 28
            ['id' => 28, 'data' => '2025-05-12', 'hora' => '14:00:00', 'titulo' => 'AUDIÊNCIA REF. INQUÉRITO POLICIAL/ZONA ELEITORAL', 'tipo' => 'Evento', 'prioridade' => 'Alta', 'sigiloso' => 'Sim', 'pessoas' => 'Sr. Nívio; Márcio Zimpel; Rubia Ignes Rubi Schneider e Rolando Eduardo Esswein Burgel.', 'contato' => '', 'local' => '2ª V.CRIMINAL/FORUM (SL. 306-3º ANDAR)', 'status' => 'Realizado', 'criado_por' => $id_vice],
            
            // ID 29
            ['id' => 29, 'data' => '2025-05-13', 'hora' => '09:00:00', 'titulo' => 'REUNIÃO NO DETUR', 'tipo' => 'Evento', 'prioridade' => 'Alta', 'sigiloso' => 'Sim', 'pessoas' => '', 'contato' => '', 'local' => 'Cerro Largo (na Sede da Entidade)', 'status' => 'Realizado', 'criado_por' => $id_prefeito],
            
            // ID 30
            ['id' => 30, 'data' => '2025-05-10', 'hora' => '11:00:00', 'titulo' => 'SOLENIDADE DE INAUGURAÇÃO DA PISTA DE JULGAMENTO DE CAVALOS CRIOULOS', 'tipo' => 'Evento', 'prioridade' => 'Normal', 'sigiloso' => 'Não', 'pessoas' => 'vice Carlos Gonçalves', 'contato' => '', 'local' => 'nucleo missioneiro', 'status' => 'Realizado', 'criado_por' => $id_vice],
            
            // ID 31
            ['id' => 31, 'data' => '2025-05-13', 'hora' => '15:00:00', 'titulo' => 'REUNIÃO COM O PESSOAL DA CAIXA ECONOMICA FEDERAL DE PASSO FUNDO', 'tipo' => 'Reunião', 'prioridade' => 'Média', 'sigiloso' => 'Sim', 'pessoas' => 'CARLOS GONÇALVES E CARLOS KELM', 'contato' => '5421047981', 'local' => 'GABINETE', 'status' => 'Realizado', 'criado_por' => $id_prefeito],
            
            // ID 32
            ['id' => 32, 'data' => '2025-05-12', 'hora' => '10:00:00', 'titulo' => 'REUNIÃO SEMANAL COM OS VEREADORES DA BASE', 'tipo' => 'Reunião', 'prioridade' => 'Alta', 'sigiloso' => 'Sim', 'pessoas' => 'Vereadores: Belmiro, Buiu; Toledo; Bispo', 'contato' => '', 'local' => 'GABINETE', 'status' => 'Realizado', 'criado_por' => $id_vice],
            
            // ID 33
            ['id' => 33, 'data' => '2025-05-09', 'hora' => '18:30:00', 'titulo' => 'LIVE SOBRE A SAÚDE', 'tipo' => 'Reunião', 'prioridade' => 'Alta', 'sigiloso' => 'Não', 'pessoas' => 'secretário da saúde FLAVIO', 'contato' => '', 'local' => 'GABINETE', 'status' => 'Realizado', 'criado_por' => $id_prefeito],
            
            // ID 34
            ['id' => 34, 'data' => '2025-06-07', 'hora' => '18:30:00', 'titulo' => 'LIVE SOCIAL', 'tipo' => 'Evento', 'prioridade' => 'Alta', 'sigiloso' => 'Não', 'pessoas' => 'SECRETÁRIA ROSE', 'contato' => '', 'local' => 'GABINETE', 'status' => 'Pendente', 'criado_por' => $id_vice],
            
            // ID 35
            ['id' => 35, 'data' => '2025-05-07', 'hora' => '18:30:00', 'titulo' => 'LIVE SOCIAL', 'tipo' => 'Reunião', 'prioridade' => 'Alta', 'sigiloso' => 'Não', 'pessoas' => 'SECRETÁRIA ROSE', 'contato' => '', 'local' => 'GABINETE', 'status' => 'Realizado', 'criado_por' => $id_prefeito],
            
            // ID 36
            ['id' => 36, 'data' => '2025-05-13', 'hora' => '16:00:00', 'titulo' => 'REUNIÃO COM JOÃO CARLOS GROS DE ALMEIDA SOBRE VALORES DE FUNDOS MUNICIPAIS', 'tipo' => 'Reunião', 'prioridade' => 'Alta', 'sigiloso' => 'Não', 'pessoas' => 'CARLOS GONÇALVES', 'contato' => '', 'local' => 'GABINETE', 'status' => 'Realizado', 'criado_por' => $id_vice],
            
            // ID 37
            ['id' => 37, 'data' => '2025-05-13', 'hora' => '17:00:00', 'titulo' => 'REUNIÃO  COM ALCIDES SCHIRMER SOBRE TERRENO NA MARECHAL FLORIANO QUE DA ACESSO AO FLORIPA', 'tipo' => 'Reunião', 'prioridade' => 'Média', 'sigiloso' => 'Sim', 'pessoas' => 'CARLOS GONÇALVES', 'contato' => '', 'local' => 'NO GABINETE', 'status' => 'Realizado', 'criado_por' => $id_prefeito],
            
            // ID 38
            ['id' => 38, 'data' => '2025-05-09', 'hora' => '08:00:00', 'titulo' => 'REUNIÃO COM O SECRETARIO DA SAÚDE FLAVIO', 'tipo' => 'Reunião', 'prioridade' => 'Média', 'sigiloso' => 'Não', 'pessoas' => '', 'contato' => '', 'local' => 'NO GABINETE', 'status' => 'Realizado', 'criado_por' => $id_vice],
            
            // ID 39
            ['id' => 39, 'data' => '2025-05-12', 'hora' => '08:00:00', 'titulo' => 'REUNIÃO COM O COMANDANTE DA BRIGADA MILITAR SOBRE O MONITORAMENTO', 'tipo' => 'Reunião', 'prioridade' => 'Média', 'sigiloso' => 'Não', 'pessoas' => 'RODRIGO FLORES, ANDRÉ PEDROSO E CLEBER', 'contato' => '', 'local' => 'GABINETE', 'status' => 'Realizado', 'criado_por' => $id_prefeito],
            
            // ID 40
            ['id' => 40, 'data' => '2025-05-14', 'hora' => '08:00:00', 'titulo' => 'REUNIÃO COM SECRETÁRIO ANDRÉ PEDROSO', 'tipo' => 'Reunião', 'prioridade' => 'Normal', 'sigiloso' => 'Não', 'pessoas' => 'ANDRE PEDROSO', 'contato' => '', 'local' => 'gabinete', 'status' => 'Realizado', 'criado_por' => $id_vice],
            
            // ID 41
            ['id' => 41, 'data' => '2025-05-14', 'hora' => '15:00:00', 'titulo' => 'VISITA NA ESCOLA EURICO DE MORAES', 'tipo' => 'Visita', 'prioridade' => 'Normal', 'sigiloso' => 'Não', 'pessoas' => 'SECRETÁRIO CLEBERSON E SECRETÁRIA ANNE', 'contato' => '', 'local' => 'ESCOLA NO BAIRRO HALLER', 'status' => 'Realizado', 'criado_por' => $id_prefeito],
            
            // ID 42
            ['id' => 42, 'data' => '2025-05-15', 'hora' => '14:00:00', 'titulo' => 'REUNIÃO COM O CONSELHO DE PROTEÇÃO DOS ANIMAIS', 'tipo' => 'Reunião', 'prioridade' => 'Média', 'sigiloso' => 'Não', 'pessoas' => '', 'contato' => '', 'local' => 'NO GABINETE', 'status' => 'Realizado', 'criado_por' => $id_vice],
            
            // ID 43
            ['id' => 43, 'data' => '2025-05-16', 'hora' => '18:00:00', 'titulo' => 'ABERTURA DA SEMANA DO COMERCIO', 'tipo' => 'Evento', 'prioridade' => 'Alta', 'sigiloso' => 'Não', 'pessoas' => '', 'contato' => '', 'local' => 'PRAÇA PINHEIRO MACHADO', 'status' => 'Pendente', 'criado_por' => $id_prefeito],
            
            // ID 45
            ['id' => 45, 'data' => '2025-05-12', 'hora' => '18:30:00', 'titulo' => 'LIVE SEC. DA FAZENDA C/SR. BRUNO HESSE', 'tipo' => 'Evento', 'prioridade' => 'Alta', 'sigiloso' => 'Não', 'pessoas' => 'Sr. Nívio; Sr. Bruno', 'contato' => '', 'local' => 'no GAB', 'status' => 'Realizado', 'criado_por' => $id_vice],
            
            // ID 46
            ['id' => 46, 'data' => '2025-05-18', 'hora' => '12:00:00', 'titulo' => 'FESTA PADROEIRA NO INTERIOR OLHOS D"ÁGUA', 'tipo' => 'Visita', 'prioridade' => 'Média', 'sigiloso' => 'Não', 'pessoas' => '', 'contato' => '', 'local' => 'olhos d\'água', 'status' => 'Pendente', 'criado_por' => $id_prefeito],
            
            // ID 47
            ['id' => 47, 'data' => '2025-05-14', 'hora' => '09:00:00', 'titulo' => 'REUNIÃO COM SECRETÁRIO FLÁVIO', 'tipo' => 'Reunião', 'prioridade' => 'Média', 'sigiloso' => 'Não', 'pessoas' => '', 'contato' => '', 'local' => 'NO GABINETE', 'status' => 'Realizado', 'criado_por' => $id_vice],
            
            // ID 48
            ['id' => 48, 'data' => '2025-05-15', 'hora' => '17:00:00', 'titulo' => 'REUNIÃO COM BELTRAME E ROGER SOBRE EMENDAS PARLAMENTARES', 'tipo' => 'Reunião', 'prioridade' => 'Alta', 'sigiloso' => 'Sim', 'pessoas' => '', 'contato' => '', 'local' => 'NO GABINETE', 'status' => 'Realizado', 'criado_por' => $id_prefeito],
            
            // ID 49
            ['id' => 49, 'data' => '2025-05-21', 'hora' => '09:00:00', 'titulo' => 'REUNIÃO COM CARLOS GROSS', 'tipo' => 'Reunião', 'prioridade' => 'Média', 'sigiloso' => 'Não', 'pessoas' => 'procurador Leandro e Bruno Hesse', 'contato' => '', 'local' => 'NO GABINETE', 'status' => 'Pendente', 'criado_por' => $id_vice],
            
            // ID 50
            ['id' => 50, 'data' => '2025-05-14', 'hora' => '10:00:00', 'titulo' => 'REUNIÃO SOBRE O CADIN', 'tipo' => 'Reunião', 'prioridade' => 'Média', 'sigiloso' => 'Não', 'pessoas' => 'secretário Cleber , Iloide e Cleber da cultura', 'contato' => '', 'local' => 'NO GABINETE', 'status' => 'Realizado', 'criado_por' => $id_prefeito],
            
            // ID 51
            ['id' => 51, 'data' => '2025-05-24', 'hora' => '19:00:00', 'titulo' => 'JANTAR OFICIAL DA ABERTURA DE COMEMORAÇÃO DOS 150 ANOS DA IMIGRAÇÃO POLONESA NO RS', 'tipo' => 'Evento', 'prioridade' => 'Média', 'sigiloso' => 'Sim', 'pessoas' => '', 'contato' => '', 'local' => 'salão paroquial de Guarani da Missões', 'status' => 'Pendente', 'criado_por' => $id_vice],
            
            // ID 52
            ['id' => 52, 'data' => '2025-05-23', 'hora' => '09:00:00', 'titulo' => 'REUNIÃO DA AMM EM CERRO LARGO', 'tipo' => 'Evento', 'prioridade' => 'Alta', 'sigiloso' => 'Não', 'pessoas' => '', 'contato' => '', 'local' => 'CERRO LARGO', 'status' => 'Pendente', 'criado_por' => $id_prefeito],
            
            // ID 53
            ['id' => 53, 'data' => '2025-05-20', 'hora' => '09:00:00', 'titulo' => 'REUNIÃO COM JOEL AUTUÁRIO DE POA', 'tipo' => 'Reunião', 'prioridade' => 'Alta', 'sigiloso' => 'Sim', 'pessoas' => 'BRUNO HESSE', 'contato' => '', 'local' => 'NO GABINETE', 'status' => 'Pendente', 'criado_por' => $id_vice],
            
            // ID 54
            ['id' => 54, 'data' => '2025-05-16', 'hora' => '14:00:00', 'titulo' => 'ENCONTRO DOS PREFEITO', 'tipo' => 'Evento', 'prioridade' => 'Alta', 'sigiloso' => 'Sim', 'pessoas' => '', 'contato' => '', 'local' => 'Em CAIBATÉ', 'status' => 'Realizado', 'criado_por' => $id_prefeito],
            
            // ID 55
            ['id' => 55, 'data' => '2025-05-21', 'hora' => '14:00:00', 'titulo' => 'REUNIÃO COM MAJOR ALDO DA BRIGADA MILITAR SOBRE OS CABEAMENTOS E CÂMERAS', 'tipo' => 'Reunião', 'prioridade' => 'Normal', 'sigiloso' => 'Sim', 'pessoas' => 'Rodrigo Flores', 'contato' => 'carlos Kelm', 'local' => 'NO GABINETE', 'status' => 'Pendente', 'criado_por' => $id_vice],
            
            // ID 56
            ['id' => 56, 'data' => '2025-05-20', 'hora' => '14:00:00', 'titulo' => 'REUNIÃO COM VEREADOR LORENZO TONETTO', 'tipo' => 'Reunião', 'prioridade' => 'Normal', 'sigiloso' => 'Não', 'pessoas' => '', 'contato' => '', 'local' => 'NO GABINETE', 'status' => 'Pendente', 'criado_por' => $id_prefeito],
            
            // ID 57
            ['id' => 57, 'data' => '2025-05-21', 'hora' => '15:00:00', 'titulo' => 'REUNINÃO COM A DIRETORA DO PRESIDIO REGIONAL DE SANTO ÂNGELO', 'tipo' => 'Reunião', 'prioridade' => 'Alta', 'sigiloso' => 'Não', 'pessoas' => '', 'contato' => '', 'local' => 'GABINETE', 'status' => 'Pendente', 'criado_por' => $id_vice],
            
            // ID 58
            ['id' => 58, 'data' => '2025-05-16', 'hora' => '08:30:00', 'titulo' => 'ENTREGA DE MÁQUINAS NA SECRETARIA RURAL', 'tipo' => 'Visita', 'prioridade' => 'Alta', 'sigiloso' => 'Não', 'pessoas' => 'secretário juca', 'contato' => '', 'local' => 'secretaria', 'status' => 'Realizado', 'criado_por' => $id_prefeito],
            
            // ID 59
            ['id' => 59, 'data' => '2025-05-10', 'hora' => '08:00:00', 'titulo' => 'REUNIÃO COM A LEANDRA', 'tipo' => 'Reunião', 'prioridade' => 'Alta', 'sigiloso' => 'Sim', 'pessoas' => '', 'contato' => '', 'local' => 'NO GABINETE', 'status' => 'Pendente', 'criado_por' => $id_vice],
            
            // ID 60
            ['id' => 60, 'data' => '2025-05-16', 'hora' => '10:00:00', 'titulo' => 'REUNIÃO COM CLEBERSON DO PLANEJAMENTO E REURB', 'tipo' => 'Reunião', 'prioridade' => 'Alta', 'sigiloso' => 'Sim', 'pessoas' => '', 'contato' => '', 'local' => 'NO GABINETE', 'status' => 'Realizado', 'criado_por' => $id_prefeito],
            
            // ID 61
            ['id' => 61, 'data' => '2025-05-21', 'hora' => '16:00:00', 'titulo' => 'VISITA NO CENTRO DE ESPORTES NA FRENTE DA PROSSEGUR', 'tipo' => 'Visita', 'prioridade' => 'Normal', 'sigiloso' => 'Não', 'pessoas' => 'secretário Edimilson', 'contato' => '', 'local' => 'centro de esportes', 'status' => 'Pendente', 'criado_por' => $id_vice],
            
            // ID 62
            ['id' => 62, 'data' => '2025-05-19', 'hora' => '14:00:00', 'titulo' => 'VISITA NA DRACO', 'tipo' => 'Visita', 'prioridade' => 'Alta', 'sigiloso' => 'Não', 'pessoas' => 'secretária Leandra', 'contato' => '', 'local' => 'DRACO', 'status' => 'Realizado', 'criado_por' => $id_prefeito],
            
            // ID 63
            ['id' => 63, 'data' => '2025-05-19', 'hora' => '08:00:00', 'titulo' => 'RENIÃO COM ANDRE PEDROSO E BRUNO HESSE', 'tipo' => 'Reunião', 'prioridade' => 'Média', 'sigiloso' => 'Sim', 'pessoas' => '', 'contato' => '', 'local' => 'GABINETE', 'status' => 'Realizado', 'criado_por' => $id_vice],
            
            // ID 65
            ['id' => 65, 'data' => '2025-05-24', 'hora' => '12:00:00', 'titulo' => 'ALMOÇO NA CONSTRUTORA STILLER', 'tipo' => 'Evento', 'prioridade' => 'Normal', 'sigiloso' => 'Não', 'pessoas' => '', 'contato' => '', 'local' => 'construtora stiller', 'status' => 'Pendente', 'criado_por' => $id_prefeito],
            
            // ID 66
            ['id' => 66, 'data' => '2025-05-22', 'hora' => '19:00:00', 'titulo' => 'COQUETEL INAUGURAÇÃO', 'tipo' => 'Evento', 'prioridade' => 'Normal', 'sigiloso' => 'Não', 'pessoas' => '', 'contato' => '', 'local' => 'FELICE/SANTA ROSA (Av. Expedicionário Weber,2099)', 'status' => 'Pendente', 'criado_por' => $id_vice],
            
            // ID 67
            ['id' => 67, 'data' => '2025-05-20', 'hora' => '08:00:00', 'titulo' => 'REUNIÃO C/ROSEMERI (SEC. DESENVOLV.SOCIAL E CIDADANIA)', 'tipo' => 'Evento', 'prioridade' => 'Média', 'sigiloso' => 'Sim', 'pessoas' => '', 'contato' => '', 'local' => 'no GAB', 'status' => 'Pendente', 'criado_por' => $id_prefeito],
            
            // ID 68
            ['id' => 68, 'data' => '2025-05-19', 'hora' => '10:00:00', 'titulo' => 'REUNIÃO DE ALINHAMENTO C/VEREADORES', 'tipo' => 'Evento', 'prioridade' => 'Normal', 'sigiloso' => 'Sim', 'pessoas' => '', 'contato' => '', 'local' => 'no GAB', 'status' => 'Realizado', 'criado_por' => $id_vice],
            
            // ID 69
            ['id' => 69, 'data' => '2025-06-17', 'hora' => '13:30:00', 'titulo' => 'REUNIÃO DO PSA: PGTO POR SERVIÇOS AMBIENTAIS', 'tipo' => 'Evento', 'prioridade' => 'Alta', 'sigiloso' => 'Sim', 'pessoas' => '', 'contato' => '', 'local' => 'Auditório da SEMMA', 'status' => 'Pendente', 'criado_por' => $id_prefeito],
            
            // ID 70
            ['id' => 70, 'data' => '2025-05-22', 'hora' => '14:00:00', 'titulo' => 'REUNIÃO COM SR. TOSCANI E ODILA P/APRESENTAR PROJETOS', 'tipo' => 'Evento', 'prioridade' => 'Alta', 'sigiloso' => 'Sim', 'pessoas' => '', 'contato' => '', 'local' => 'no GAB', 'status' => 'Pendente', 'criado_por' => $id_vice]
        ];
        
        // Inserir os dados
        $stmt = $pdo->prepare("
            INSERT INTO compromissos 
            (titulo, data, hora, local, responsavel, observacoes, status, publico, criado_por) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        foreach ($compromissos as $c) {
            // Pular registros com data inválida
            if ($c['data'] == '0000-00-00') {
                continue;
            }
            
            // Preparar observações
            $obs = '';
            if (!empty($c['pessoas'])) {
                $obs .= "Participantes: " . $c['pessoas'] . "\n";
            }
            if (!empty($c['contato'])) {
                $obs .= "Contato: " . $c['contato'] . "\n";
            }
            $obs .= "Tipo: " . $c['tipo'] . "\n";
            $obs .= "Prioridade: " . $c['prioridade'];
            
            // Mapear status
            $status = mapearStatus($c['status']);
            
            // Determinar se é público
            $publico = ($c['sigiloso'] == 'Sim') ? 0 : 1;
            
            $stmt->execute([
                $c['titulo'],
                $c['data'],
                $c['hora'],
                $c['local'],
                $c['contato'],
                $obs,
                $status,
                $publico,
                $c['criado_por']
            ]);
            
            $registros_importados++;
        }
        
        // Commit da transação
        $pdo->commit();
        
        $mensagens[] = "Importação concluída com sucesso! $registros_importados registros importados.";
    } catch (Exception $e) {
        // Rollback em caso de erro
        $pdo->rollBack();
        $erros[] = "Erro na importação: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($config['nome_aplicacao']); ?> - Importação de Compromissos</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="assets/css/custom.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container mt-4">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Importação de Todos os Compromissos</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($mensagens)): ?>
                            <div class="alert alert-success">
                                <i class="bi bi-check-circle-fill me-2"></i>
                                <?php echo implode('<br>', $mensagens); ?>
                            </div>
                            
                            <div class="mt-4">
                                <a href="agenda_publica.php" class="btn btn-primary">
                                    <i class="bi bi-calendar-check me-2"></i>
                                    Ver Agenda Pública
                                </a>
                                <a href="dashboard.php" class="btn btn-secondary ms-2">
                                    <i class="bi bi-house me-2"></i>
                                    Voltar ao Dashboard
                                </a>
                            </div>
                        <?php elseif (!empty($erros)): ?>
                            <div class="alert alert-danger">
                                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                <?php echo implode('<br>', $erros); ?>
                            </div>
                            
                            <div class="mt-4">
                                <a href="dashboard.php" class="btn btn-secondary">
                                    <i class="bi bi-house me-2"></i>
                                    Voltar ao Dashboard
                                </a>
                            </div>
                        <?php else: ?>
                            <p>Este script importará todos os compromissos do arquivo SQL para o sistema.</p>
                            
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle-fill me-2"></i>
                                <strong>Importante:</strong> Este processo adicionará aproximadamente 70 novos compromissos ao sistema. Certifique-se de que deseja prosseguir.
                            </div>
                            
                            <form method="post" action="">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-upload me-2"></i>
                                    Importar Todos os Compromissos
                                </button>
                                <a href="dashboard.php" class="btn btn-secondary ms-2">
                                    <i class="bi bi-x-circle me-2"></i>
                                    Cancelar
                                </a>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
