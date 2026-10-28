-- Divisões de faixa em 4 grupos: Branca e Amarela (1-2), Vermelha e Laranja (3-4), Verde e Roxa (5-6),
-- Marrom e Preta (7-20). Só troca os regulamentos que ainda têm a divisão antiga (COL/PRE).
-- Eventos já criados não mudam: as categorias deles são cópia (event_categories).

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
