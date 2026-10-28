-- =====================================================================================
-- FGKIRS — divisões de faixa em 4 grupos (migration 011_belt_divisions_split), para o phpMyAdmin.
-- Branca e Amarela | Vermelha e Laranja | Verde e Roxa | Marrom e Preta
-- Pode rodar de novo sem erro. Eventos já criados não mudam; vale para os próximos eventos.
-- =====================================================================================

SET NAMES utf8mb4;

INSERT INTO belt_divisions (ruleset_id, code, name, level_min, level_max, sort_order)
SELECT r.id, 'BAM', 'Branca e Amarela', 1, 2, 1
FROM rulesets r
WHERE EXISTS (SELECT 1 FROM belt_divisions b WHERE b.ruleset_id = r.id AND b.code = 'COL')
  AND NOT EXISTS (SELECT 1 FROM belt_divisions b WHERE b.ruleset_id = r.id AND b.code = 'BAM');

INSERT INTO belt_divisions (ruleset_id, code, name, level_min, level_max, sort_order)
SELECT r.id, 'VLA', 'Vermelha e Laranja', 3, 4, 2
FROM rulesets r
WHERE EXISTS (SELECT 1 FROM belt_divisions b WHERE b.ruleset_id = r.id AND b.code = 'COL')
  AND NOT EXISTS (SELECT 1 FROM belt_divisions b WHERE b.ruleset_id = r.id AND b.code = 'VLA');

INSERT INTO belt_divisions (ruleset_id, code, name, level_min, level_max, sort_order)
SELECT r.id, 'VRO', 'Verde e Roxa', 5, 6, 3
FROM rulesets r
WHERE EXISTS (SELECT 1 FROM belt_divisions b WHERE b.ruleset_id = r.id AND b.code = 'COL')
  AND NOT EXISTS (SELECT 1 FROM belt_divisions b WHERE b.ruleset_id = r.id AND b.code = 'VRO');

INSERT INTO belt_divisions (ruleset_id, code, name, level_min, level_max, sort_order)
SELECT r.id, 'MPR', 'Marrom e Preta', 7, 20, 4
FROM rulesets r
WHERE EXISTS (SELECT 1 FROM belt_divisions b WHERE b.ruleset_id = r.id AND b.code = 'COL')
  AND NOT EXISTS (SELECT 1 FROM belt_divisions b WHERE b.ruleset_id = r.id AND b.code = 'MPR');

DELETE FROM belt_divisions WHERE code IN ('COL', 'PRE');

INSERT IGNORE INTO schema_migrations (version) VALUES ('011_belt_divisions_split');

-- ---------------------------------------------------------------- conferência
SELECT ruleset_id, code, name, level_min, level_max FROM belt_divisions ORDER BY ruleset_id, sort_order;
-- Esperado: 4 linhas por regulamento (BAM 1-2, VLA 3-4, VRO 5-6, MPR 7-20)
