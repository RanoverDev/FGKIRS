-- =====================================================================================
-- FGKIRS — aposenta o modelo antigo de eventos. ATENÇÃO: APAGA OS EVENTOS ANTIGOS.
--
-- Apaga os eventos 'legacy' (e as inscrições e árbitros deles), remove as tabelas do modelo antigo
-- (championship_athletes, championship_entries, championship_teams, championship_team_members,
-- championship_categories e competition_categories) e a coluna championships.registration_model.
-- Os eventos novos, as inscrições novas e os árbitros dos eventos novos NÃO são tocados.
--
-- ORDEM: 1) faça o backup; 2) suba o código novo (que já não usa nada disso); 3) rode este arquivo.
-- Rode UMA vez, na aba SQL do phpMyAdmin (cole o texto inteiro). Se repetir e aparecer
-- "Unknown column registration_model", é porque já foi aplicado.
-- =====================================================================================

SET NAMES utf8mb4;

DELETE r FROM championship_referees r
JOIN championships c ON c.id = r.championship_id
WHERE c.registration_model = 'legacy';

DROP TABLE IF EXISTS `championship_team_members`;
DROP TABLE IF EXISTS `championship_teams`;
DROP TABLE IF EXISTS `championship_entries`;
DROP TABLE IF EXISTS `championship_athletes`;
DROP TABLE IF EXISTS `championship_categories`;
DROP TABLE IF EXISTS `competition_categories`;

DELETE FROM `championships` WHERE `registration_model` = 'legacy';

ALTER TABLE `championships` DROP COLUMN IF EXISTS `registration_model`;

INSERT IGNORE INTO schema_migrations (version) VALUES ('006_retire_legacy_events');

-- ---------------------------------------------------------------- conferência
SELECT COUNT(*) AS eventos_restantes FROM `championships`;
SHOW TABLES LIKE 'championship%';
-- Esperado: só championships, championship_referees e championship_registrations
