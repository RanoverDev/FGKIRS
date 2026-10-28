-- =====================================================================================
-- FGKIRS — zera TODOS os eventos de disputa (dados de teste) e tudo que depende deles.
-- DESTRUTIVO e sem volta: faça o backup (Exportar) das tabelas abaixo antes de rodar.
--
-- Apaga:   resultados, chaves, lutas, log de inscrição, equipes, inscrições, categorias,
--          árbitros e os próprios eventos (championships).
-- Mantém:  atletas, dojos, usuários, regulamentos/divisões, estilos/graduações,
--          certificados, posts (Agenda de Eventos) e schema_migrations.
-- =====================================================================================

SET NAMES utf8mb4;

-- ---------------------------------------------------------------- antes
SELECT 'championships' AS tabela, COUNT(*) AS linhas FROM championships
UNION ALL SELECT 'championship_registrations', COUNT(*) FROM championship_registrations
UNION ALL SELECT 'registration_entries',        COUNT(*) FROM registration_entries
UNION ALL SELECT 'event_categories',            COUNT(*) FROM event_categories
UNION ALL SELECT 'event_teams',                 COUNT(*) FROM event_teams
UNION ALL SELECT 'brackets',                    COUNT(*) FROM brackets
UNION ALL SELECT 'event_results',               COUNT(*) FROM event_results;

-- ---------------------------------------------------------------- limpeza (filhos → pais)
START TRANSACTION;

DELETE FROM event_results;
DELETE FROM bracket_matches;
DELETE FROM brackets;
DELETE FROM registration_log;
DELETE FROM event_team_members;
DELETE FROM registration_entries;
DELETE FROM event_teams;
DELETE FROM championship_registrations;
DELETE FROM event_categories;
DELETE FROM championship_referees;
DELETE FROM championships;

COMMIT;

-- ---------------------------------------------------------------- recomeça os IDs em 1
ALTER TABLE event_results             AUTO_INCREMENT = 1;
ALTER TABLE bracket_matches           AUTO_INCREMENT = 1;
ALTER TABLE brackets                  AUTO_INCREMENT = 1;
ALTER TABLE registration_log          AUTO_INCREMENT = 1;
ALTER TABLE event_team_members        AUTO_INCREMENT = 1;
ALTER TABLE registration_entries      AUTO_INCREMENT = 1;
ALTER TABLE event_teams               AUTO_INCREMENT = 1;
ALTER TABLE championship_registrations AUTO_INCREMENT = 1;
ALTER TABLE event_categories          AUTO_INCREMENT = 1;
ALTER TABLE championship_referees     AUTO_INCREMENT = 1;
ALTER TABLE championships             AUTO_INCREMENT = 1;

-- ---------------------------------------------------------------- opcional: convidados criados nos testes
-- Só rode se NENHUM convidado real precisa ser mantido (os atletas dos dojos não são tocados).
-- DELETE FROM athletes WHERE is_guest = 1
--   AND NOT EXISTS (SELECT 1 FROM athlete_certificates c WHERE c.athlete_id = athletes.id);

-- ---------------------------------------------------------------- depois
SELECT 'championships' AS tabela, COUNT(*) AS linhas FROM championships
UNION ALL SELECT 'championship_registrations', COUNT(*) FROM championship_registrations
UNION ALL SELECT 'event_categories',            COUNT(*) FROM event_categories
UNION ALL SELECT 'brackets',                    COUNT(*) FROM brackets
UNION ALL SELECT 'event_results',               COUNT(*) FROM event_results;
-- Esperado: tudo 0.
