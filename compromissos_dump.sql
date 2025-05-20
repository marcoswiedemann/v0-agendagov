-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Tempo de geração: 19/05/2025 às 23:41
-- Versão do servidor: 10.11.10-MariaDB
-- Versão do PHP: 7.2.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `u414602466_prefeitoagenda`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `compromissos`
--

CREATE TABLE `compromissos` (
  `id` int(11) NOT NULL,
  `data` date NOT NULL,
  `hora` time NOT NULL,
  `titulo` varchar(255) NOT NULL,
  `tipo` enum('Evento','Reunião','Visita') NOT NULL,
  `prioridade` enum('Alta','Média','Normal') NOT NULL,
  `sigiloso` enum('Sim','Não') NOT NULL DEFAULT 'Não',
  `pessoas` text DEFAULT NULL,
  `contato_responsavel` varchar(255) DEFAULT NULL,
  `localizacao` varchar(255) NOT NULL,
  `status` enum('Pendente','Realizado','Não Realizado') NOT NULL DEFAULT 'Pendente',
  `criado_em` datetime DEFAULT current_timestamp(),
  `criado_por_id` int(11) DEFAULT NULL,
  `atualizado_em` datetime DEFAULT NULL,
  `atualizado_por_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `compromissos`
--

INSERT INTO `compromissos` (`id`, `data`, `hora`, `titulo`, `tipo`, `prioridade`, `sigiloso`, `pessoas`, `contato_responsavel`, `localizacao`, `status`, `criado_em`, `criado_por_id`, `atualizado_em`, `atualizado_por_id`) VALUES
(2, '2025-04-30', '08:00:00', 'REUNIãO DE ALINHAMENTO COM TODOS OS SECRETáRIOS', 'Reunião', 'Alta', 'Sim', 'Todos Scretários ', '', 'Gabinete', 'Realizado', '2025-04-30 13:24:02', NULL, '2025-04-30 20:42:55', 1),
(4, '2025-04-30', '14:00:00', 'POSSE DO NúCLEO DO BAIRRO SãO JOãO ', 'Evento', 'Normal', 'Não', '', '', 'Bairro São João', 'Realizado', '2025-04-30 13:25:49', NULL, '2025-04-30 20:48:33', 2),
(5, '2025-04-30', '15:00:00', 'REUNIãO COM O SECRETáRIO CLEBERSON', 'Reunião', 'Média', 'Não', 'Cleberson Taborda', '', 'Gabinete', 'Realizado', '2025-04-30 13:26:31', NULL, NULL, NULL),
(6, '2025-04-30', '16:00:00', 'REUNIÃO COM RODRIGO FLORES E ANDRÉ RUSCHEL', 'Reunião', 'Alta', 'Sim', 'Rodrigo Flores, Carlos Gonçalves, Rolando Burgel, André Ruschel', '55997000995', 'Gabinete do Vice-Prefeito', 'Pendente', '2025-04-30 13:37:27', NULL, NULL, NULL),
(7, '2025-04-30', '19:00:00', 'Live com o secretário Cleberson', 'Evento', 'Alta', 'Não', 'Cleberson', '', 'Gabinete', 'Pendente', '2025-04-30 14:22:23', NULL, NULL, NULL),
(8, '2025-05-02', '09:00:00', 'CâMARA DE VEREADORES SOBRE OS 400 ANOS DAS MISSõES ', 'Reunião', 'Média', 'Não', 'Vereadores, Sec.Turismo/Vinicius Makvitiz. Sr. Carlos Gonçalves participou. ', '', 'R. Antunes Ribas, 1111 - Centro, Santo Ângelo - RS, 98801-630', 'Realizado', '2025-04-30 14:23:13', NULL, '2025-05-02 12:04:12', 1),
(9, '2025-05-02', '16:00:00', 'Visita na Unimed logo após janta com Dr Madureira e Tiago da UPA', 'Evento', 'Normal', 'Não', 'Dr. Madureira e Tiago UPA', '', 'Av. Getúlio Vargas, 1079 - Missões, Santo Ângelo - RS, 98801-703', 'Pendente', '2025-04-30 14:26:17', NULL, NULL, NULL),
(10, '2025-05-02', '19:00:00', 'LIVE CANCELADA', 'Evento', 'Normal', 'Não', '', '', 'Gabinete', 'Não Realizado', '2025-04-30 14:27:04', NULL, NULL, NULL),
(11, '2025-05-03', '15:30:00', 'MISSA NA CATEDRAL', 'Evento', 'Média', 'Não', 'Vinícius Makivitz', '', 'Praça Pinheiro Machado', 'Pendente', '2025-04-30 14:29:39', NULL, NULL, NULL),
(12, '2025-05-04', '12:00:00', 'ALMOÇO NO LAGEADO MICUIM', 'Evento', 'Normal', 'Não', '', '', 'Lajeado Micuim', 'Pendente', '2025-04-30 14:31:19', NULL, '2025-05-02 14:20:21', 1),
(15, '2025-05-05', '08:00:00', 'REUNIÃO C/LEANDRA-RH E MARISA/SINDICATO FUNCIONÁRIOS MUNICIPAIS, REF. IPE', 'Reunião', 'Normal', 'Não', 'Srª. Leandra/RH; \r\nSrª. Marisa/Sindicato;\r\nSr. Nívio/Prefeito', '', 'no GAB', 'Realizado', '2025-04-30 19:42:17', NULL, '2025-05-05 13:36:46', 1),
(16, '2025-05-06', '08:30:00', 'REUNIÃO C/PRODUTOR RURAL, FRANCISCO E CULTURA.', 'Reunião', 'Normal', 'Não', 'Sr. Nívio; Sr. Carlos Gonçalves; Cleberson; Edimilson e Assessor; Vinicius e Cris.', '', 'no GAB', 'Realizado', '2025-04-30 19:44:38', NULL, '2025-05-06 09:28:35', 1),
(17, '2025-05-05', '10:00:00', 'REUNIÃO DE ALINHAMENTO C/VEREADORES ', 'Reunião', 'Normal', 'Não', 'Prefeito; Vice-Prefeito; Chefe de Gabinete e Vereadores ', '', 'no GAB', 'Realizado', '2025-04-30 19:48:21', NULL, '2025-05-05 13:37:00', 1),
(18, '2025-05-05', '14:00:00', 'REUNIAO AMM', 'Reunião', 'Alta', 'Sim', '', '', 'Em Cerro Largo', 'Não Realizado', '2025-04-30 19:49:12', NULL, '2025-05-06 15:38:06', 1),
(19, '2025-05-06', '14:00:00', 'REUNIÃO C/ADV. NICO MARCHIONATTI', 'Reunião', 'Alta', 'Sim', 'Sr. Nívio; Sr. Nico Marchionatti; Srª Rosimere/Sec. Assistência Social', '', 'no GAB', 'Realizado', '2025-04-30 19:51:52', NULL, '2025-05-06 15:07:53', 2),
(20, '2025-05-06', '15:00:00', 'REUNIÃO C/VINICIUS MAKVITIZ E PESSOAL SISTEMA DE VIGILÂNCIA SANITÁRIA E DEPTO DE TRÂNSITO', 'Reunião', 'Normal', 'Não', 'Sec.Turismo/Sr. Vinicius Makvitz; Coord. Trânsito/Sr. Nelson Koch;', '', 'no GAB', 'Realizado', '2025-04-30 19:54:37', NULL, '2025-05-06 15:18:58', 1),
(21, '2025-05-04', '13:30:00', 'SOLENIDADE DE ABERTURA DO CAMPEONATO MUNICIPAL DE FUTEBOL AMADOR/2025-TROFÉU JARDEL AUGUSTO OBERMEIER', 'Evento', 'Normal', 'Não', 'Sr. Nívio; Edimilson. ', '', 'no Campo Municipal de Eventos Hélio Costa de Oliveira (antigo SESI).', 'Pendente', '2025-05-02 14:19:28', 1, '2025-05-02 13:46:40', 1),
(22, '2025-05-07', '10:00:00', 'VISITA DO COMANDANTE DOS BOMBEIROS/DEFESA CIVIL DO RS.', 'Visita', 'Média', 'Não', 'Sr. Nívio; Sr. Castanho; Sr. Carlos Kelm deverá providenciar um mimo p/entrega ao convidado.', '', 'no GAB', 'Realizado', '2025-05-02 14:56:03', 1, '2025-05-07 16:21:51', 1),
(23, '2025-05-05', '08:00:00', 'REUNIÃO C/SRª LEANDRA/RH E  A SRª MARISA/SINDICATO DOS FUNCIONÁRIOS MUNICIPAIS, P/TRATAR SOBRE IPE E OUTROS ASSUNTOS DE INTERESSE DA CLASSE', 'Evento', 'Normal', 'Não', 'Leandra/RH; Marisa/Sindicato Func. Municipais; Sr. Nívio', '', 'no GAB', 'Realizado', '2025-05-02 14:49:20', 1, '2025-05-05 13:55:08', 2),
(24, '2025-05-06', '11:00:00', 'REUNIÃO COM CARLÃO/ZAGUEIRO.', 'Reunião', 'Normal', 'Não', '', '', 'no GAB', 'Realizado', '2025-05-06 07:35:41', 1, '2025-05-06 15:20:05', 1),
(25, '2025-05-06', '16:00:00', 'REUNIÃO COM O RURAL, SOBRE PAA', 'Reunião', 'Média', 'Não', '', '', 'no GAB', 'Realizado', '2025-05-06 08:54:10', 1, '2025-05-06 17:04:15', 1),
(26, '2025-05-08', '08:00:00', 'REUNIÃO C/SEC.EDUCAÇÃO-ANE E ADV.LUIS ANTONIO (PROFISSIONAL DE APOIO A ESTAGIÁRIO)', 'Reunião', 'Média', 'Não', 'Sr. Nívio; Sec. Educação/Ane e Adv. Luiz Antônio', '', 'no GAB', 'Realizado', '2025-05-06 08:56:22', 1, '2025-05-08 08:44:11', 1),
(27, '2025-05-10', '09:00:00', 'INAUGURAÇÃO DA FARMÁCIA SÃO JOÃO', 'Evento', 'Normal', 'Não', '', '', 'Antigo ARENA', 'Realizado', '2025-05-06 08:57:14', 1, '2025-05-13 16:44:14', 1),
(28, '2025-05-12', '14:00:00', 'AUDIÊNCIA REF. INQUÉRITO POLICIAL/ZONA ELEITORAL', 'Evento', 'Alta', 'Sim', 'Sr. Nívio; Márcio Zimpel; Rubia Ignes Rubi Schneider e Rolando Eduardo Esswein Burgel.', '', '2ª V.CRIMINAL/FORUM (SL. 306-3º ANDAR), ', 'Realizado', '2025-05-06 09:02:55', 1, '2025-05-12 15:10:46', 1),
(29, '2025-05-13', '09:00:00', 'REUNIÃO NO DETUR', 'Evento', 'Alta', 'Sim', '', '', ' Cerro Largo (na Sede da Entidade)', 'Realizado', '2025-05-06 09:16:02', 1, '2025-05-13 16:42:55', 1),
(30, '2025-05-10', '11:00:00', 'SOLENIDADE DE INAUGURAÇÃO DA PISTA DE JULGAMENTO DE CAVALOS CRIOULOS', 'Evento', 'Normal', 'Não', 'vice Carlos Gonçalves', '', 'nucleo missioneiro', 'Realizado', '2025-05-06 14:05:43', 2, '2025-05-13 16:44:38', 1),
(31, '2025-05-13', '15:00:00', 'REUNIÃO COM O PESSOAL DA CAIXA ECONOMICA FEDERAL DE PASSO FUNDO', 'Reunião', 'Média', 'Sim', 'CARLOS GONÇALVES E CARLOS KELM', '5421047981', 'GABINETE', 'Realizado', '2025-05-06 14:09:26', 2, '2025-05-13 16:42:16', 1),
(32, '2025-05-12', '10:00:00', 'REUNIÃO SEMANAL COM OS VEREADORES DA BASE', 'Reunião', 'Alta', 'Sim', 'Vereadores: Belmiro, Buiu; Toledo; Bispo', '', 'GABINETE', 'Realizado', '2025-05-06 14:11:14', 2, '2025-05-12 15:10:24', 1),
(33, '2025-05-09', '18:30:00', 'LIVE SOBRE A SAÚDE', 'Reunião', 'Alta', 'Não', 'secretário da saúde FLAVIO', '', 'GABINETE', 'Realizado', '2025-05-06 15:12:26', 2, '2025-05-13 16:43:48', 1),
(34, '2025-06-07', '18:30:00', 'LIVE SOCIAL', 'Evento', 'Alta', 'Não', 'SECRETÁRIA ROSE', '', 'GABINETE', 'Pendente', '2025-05-06 15:14:15', 2, '2025-05-06 15:14:15', 2),
(35, '2025-05-07', '18:30:00', 'LIVE SOCIAL', 'Reunião', 'Alta', 'Não', 'SECRETÁRIA ROSE', '', 'GABINETE', 'Realizado', '2025-05-06 15:17:19', 2, '2025-05-08 08:32:21', 1),
(36, '2025-05-13', '16:00:00', 'REUNIÃO COM JOÃO CARLOS GROS DE ALMEIDA SOBRE VALORES DE FUNDOS MUNICIPAIS', 'Reunião', 'Alta', 'Não', 'CARLOS GONÇALVES', '', 'GABINETE', 'Realizado', '2025-05-07 09:55:21', 2, '2025-05-13 16:43:10', 1),
(37, '2025-05-13', '17:00:00', 'REUNIÃO  COM ALCIDES SCHIRMER SOBRE TERRENO NA MARECHAL FLORIANO QUE DA ACESSO AO FLORIPA', 'Reunião', 'Média', 'Sim', 'CARLOS GONÇALVES', '', 'NO GABINETE', 'Realizado', '2025-05-07 09:56:47', 2, '2025-05-14 07:46:21', 1),
(38, '2025-05-09', '08:00:00', 'REUNIÃO COM O SECRETARIO DA SAÚDE FLAVIO', 'Reunião', 'Média', 'Não', '', '', 'NO GABINETE', 'Realizado', '2025-05-07 09:58:01', 2, '2025-05-13 16:41:53', 1),
(39, '2025-05-12', '08:00:00', 'REUNIÃO COM O COMANDANTE DA BRIGADA MILITAR SOBRE O MONITORAMENTO', 'Reunião', 'Média', 'Não', 'RODRIGO FLORES, ANDRÉ PEDROSO E CLEBER', '', 'GABINETE', 'Realizado', '2025-05-07 17:25:44', 2, '2025-05-12 15:09:39', 1),
(40, '2025-05-14', '08:00:00', 'REUNIÃO COM SECRETÁRIO ANDRÉ PEDROSO', 'Reunião', 'Normal', 'Não', 'ANDRE PEDROSO', '', 'gabinete', 'Realizado', '2025-05-08 08:23:25', 2, '2025-05-14 10:06:23', 1),
(41, '2025-05-14', '15:00:00', 'VISITA NA ESCOLA EURICO DE MORAES', 'Visita', 'Normal', 'Não', 'SECRETÁRIO CLEBERSON E SECRETÁRIA ANNE', '', 'ESCOLA NO BAIRRO HALLER', 'Realizado', '2025-05-08 14:41:44', 2, '2025-05-15 13:44:58', 1),
(42, '2025-05-15', '14:00:00', 'REUNIÃO COM O CONSELHO DE PROTEÇÃO DOS ANIMAIS', 'Reunião', 'Média', 'Não', '', '', 'NO GABINETE', 'Realizado', '2025-05-08 16:02:51', 2, '2025-05-15 14:12:31', 1),
(43, '2025-05-16', '18:00:00', 'ABERTURA DA SEMANA DO COMERCIO ', 'Evento', 'Alta', 'Não', '', '', 'PRAÇA PINHEIRO MACHADO', 'Pendente', '2025-05-08 17:00:27', 2, '2025-05-08 17:00:27', 2),
(45, '2025-05-12', '18:30:00', 'LIVE SEC. DA FAZENDA C/SR. BRUNO HESSE', 'Evento', 'Alta', 'Não', 'Sr. Nívio; Sr. Bruno', '', 'no GAB', 'Realizado', '2025-05-12 15:09:24', 1, '2025-05-13 16:42:41', 1),
(46, '2025-05-18', '12:00:00', 'FESTA PADROEIRA NO INTERIOR OLHOS D\"ÁGUA', 'Visita', 'Média', 'Não', '', '', 'olhos d\'água', 'Pendente', '2025-05-12 17:13:42', 2, '2025-05-12 17:13:42', 2),
(47, '2025-05-14', '09:00:00', 'REUNIÃO COM SECRETÁRIO FLÁVIO', 'Reunião', 'Média', 'Não', '', '', 'NO GABINETE', 'Realizado', '2025-05-13 14:58:19', 2, '2025-05-14 10:06:37', 1),
(48, '2025-05-15', '17:00:00', 'REUNIÃO COM BELTRAME E ROGER SOBRE EMENDAS PARLAMENTARES', 'Reunião', 'Alta', 'Sim', '', '', 'NO GABINETE', 'Realizado', '2025-05-13 14:59:29', 2, '2025-05-15 17:56:44', 1),
(49, '2025-05-21', '09:00:00', 'REUNIÃO COM CARLOS GROSS', 'Reunião', 'Média', 'Não', 'procurador Leandro e Bruno Hesse', '', 'NO GABINETE', 'Pendente', '2025-05-13 16:43:57', 2, '2025-05-14 09:19:07', 2),
(50, '2025-05-14', '10:00:00', 'REUNIÃO SOBRE O CADIN', 'Reunião', 'Média', 'Não', 'secretário Cleber , Iloide e Cleber da cultura', '', 'NO GABINETE', 'Realizado', '2025-05-13 16:50:16', 2, '2025-05-14 10:06:58', 1),
(51, '2025-05-24', '19:00:00', 'JANTAR OFICIAL DA ABERTURA DE COMEMORAÇÃO DOS 150 ANOS DA IMIGRAÇÃO POLONESA NO RS', 'Evento', 'Média', 'Sim', '', '', 'salão paroquial de Guarani da Missões', 'Pendente', '2025-05-14 08:18:21', 2, '2025-05-14 08:18:21', 2),
(52, '2025-05-23', '09:00:00', 'REUNIÃO DA AMM EM CERRO LARGO', 'Evento', 'Alta', 'Não', '', '', 'CERRO LARGO', 'Pendente', '2025-05-14 08:23:08', 2, '2025-05-14 08:23:08', 2),
(53, '2025-05-20', '09:00:00', 'REUNIÃO COM JOEL AUTUÁRIO DE POA', 'Reunião', 'Alta', 'Sim', 'BRUNO HESSE', '', 'NO GABINETE', 'Pendente', '2025-05-14 09:20:41', 2, '2025-05-14 09:20:41', 2),
(54, '2025-05-16', '14:00:00', 'ENCONTRO DOS PREFEITO', 'Evento', 'Alta', 'Sim', '', '', 'Em CAIBATÉ', 'Realizado', '2025-05-14 10:05:59', 1, '2025-05-16 16:30:06', 1),
(55, '2025-05-21', '14:00:00', 'REUNIÃO COM MAJOR ALDO DA BRIGADA MILITAR SOBRE OS CABEAMENTOS E CÂMERAS', 'Reunião', 'Normal', 'Sim', 'Rodrigo Flores', 'carlos Kelm', 'NO GABINETE', 'Pendente', '2025-05-14 10:08:54', 1, '2025-05-19 09:39:29', 2),
(56, '2025-05-20', '14:00:00', 'REUNIÃO COM VEREADOR LORENZO TONETTO', 'Reunião', 'Normal', 'Não', '', '', 'NO GABINETE', 'Pendente', '2025-05-14 10:16:15', 2, '2025-05-14 10:16:15', 2),
(57, '2025-05-21', '15:00:00', 'REUNINÃO COM A DIRETORA DO PRESIDIO REGIONAL DE SANTO ÂNGELO', 'Reunião', 'Alta', 'Não', '', '', 'GABINETE', 'Pendente', '2025-05-14 11:25:53', 2, '2025-05-15 17:42:18', 2),
(58, '2025-05-16', '08:30:00', 'ENTREGA DE MÁQUINAS NA SECRETARIA RURAL', 'Visita', 'Alta', 'Não', 'secretário juca', '', 'secretaria', 'Realizado', '2025-05-15 14:01:37', 2, '2025-05-16 09:12:43', 1),
(59, '2025-05-10', '08:00:00', 'REUNIÃO COM A LEANDRA', 'Reunião', 'Alta', 'Sim', '', '', 'NO GABINETE', 'Pendente', '2025-05-15 17:43:11', 2, '2025-05-15 17:43:11', 2),
(60, '2025-05-16', '10:00:00', 'REUNIÃO COM CLEBERSON DO PLANEJAMENTO E REURB', 'Reunião', 'Alta', 'Sim', '', '', 'NO GABINETE', 'Realizado', '2025-05-15 17:43:55', 2, '2025-05-16 11:45:20', 2),
(61, '2025-05-21', '16:00:00', 'VISITA NO CENTRO DE ESPORTES NA FRENTE DA PROSSEGUR', 'Visita', 'Normal', 'Não', 'secretário Edimilson', '', 'centro de esportes', 'Pendente', '2025-05-16 11:42:42', 2, '2025-05-16 11:54:57', 2),
(62, '2025-05-19', '14:00:00', 'VISITA NA DRACO', 'Visita', 'Alta', 'Não', 'secretária Leandra', '', 'DRACO', 'Realizado', '2025-05-16 11:44:01', 2, '2025-05-19 16:28:51', 1),
(63, '2025-05-19', '08:00:00', 'RENIÃO COM ANDRE PEDROSO E BRUNO HESSE', 'Reunião', 'Média', 'Sim', '', '', 'GABINETE', 'Realizado', '2025-05-19 08:15:30', 2, '2025-05-19 10:30:14', 1),
(64, '0000-00-00', '10:00:00', 'REUNIÃO DOS VEREADORES', 'Reunião', 'Alta', 'Não', 'vereadores da base', '', 'NO GABINETE', 'Pendente', '2025-05-19 08:16:11', 2, '2025-05-19 08:16:11', 2),
(65, '2025-05-24', '12:00:00', 'ALMOÇO NA CONSTRUTORA STILLER', 'Evento', 'Normal', 'Não', '', '', 'construtora stiller', 'Pendente', '2025-05-19 08:17:53', 2, '2025-05-19 08:17:53', 2),
(66, '2025-05-22', '19:00:00', 'COQUETEL INAUGURAÇÃO ', 'Evento', 'Normal', 'Não', '', '', 'FELICE/SANTA ROSA (Av. Expedicionário Weber,2099)', 'Pendente', '2025-05-19 10:40:09', 1, '2025-05-19 10:40:09', 1),
(67, '2025-05-20', '08:00:00', 'REUNIÃO C/ROSEMERI (SEC. DESENVOLV.SOCIAL E CIDADANIA)', 'Evento', 'Média', 'Sim', '', '', 'no GAB', 'Pendente', '2025-05-19 11:05:05', 1, '2025-05-19 11:05:05', 1),
(68, '2025-05-19', '10:00:00', 'REUNIÃO DE ALINHAMENTO C/VEREADORES ', 'Evento', 'Normal', 'Sim', '', '', 'no GAB', 'Realizado', '2025-05-19 11:05:50', 1, '2025-05-19 11:05:50', 1),
(69, '2025-06-17', '13:30:00', 'REUNIÃO DO PSA: PGTO POR SERVIÇOS AMBIENTAIS', 'Evento', 'Alta', 'Sim', '', '', 'Auditório da SEMMA', 'Pendente', '2025-05-19 16:19:23', 1, '2025-05-19 16:19:23', 1),
(70, '2025-05-22', '14:00:00', 'REUNIÃO COM SR. TOSCANI E ODILA P/APRESENTAR PROJETOS ', 'Evento', 'Alta', 'Sim', '', '', 'no GAB', 'Pendente', '2025-05-19 16:26:36', 1, '2025-05-19 16:26:36', 1);

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `compromissos`
--
ALTER TABLE `compromissos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_data` (`data`),
  ADD KEY `fk_compromisso_criado_por` (`criado_por_id`),
  ADD KEY `fk_compromisso_atualizado_por` (`atualizado_por_id`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `compromissos`
--
ALTER TABLE `compromissos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=71;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `compromissos`
--
ALTER TABLE `compromissos`
  ADD CONSTRAINT `fk_compromisso_atualizado_por` FOREIGN KEY (`atualizado_por_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_compromisso_criado_por` FOREIGN KEY (`criado_por_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
