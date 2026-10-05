-- Fase 3: regulamento atual da FGKIRS, equivalente a CompetitionCategory::defaultCatalog() (88 categorias).
-- Pesos provisórios: as divisões oficiais de peso do kumite ainda estão em aberto.
-- Faixa preta = nível 11 em diante (graduations.level, definido na fase 2).

INSERT INTO rulesets (name, age_policy, is_default, is_active)
VALUES ('Regulamento FGKIRS (atual)', 'event_date', 1, 1);

SET @rs = LAST_INSERT_ID();

INSERT INTO age_divisions (ruleset_id, code, name, age_min, age_max, sort_order) VALUES
  (@rs, 'MIR',   'Mirim',           7,    10,   1),
  (@rs, 'INF',   'Infantil',        11,   12,   2),
  (@rs, 'IJU',   'Infanto Juvenil', 13,   14,   3),
  (@rs, 'JUV',   'Juvenil',         15,   17,   4),
  (@rs, 'ADU',   'Adulto',          18,   35,   5),
  (@rs, 'MAS',   'Master',          36,   NULL, 6),
  (@rs, 'EQ-14', 'Até 14 anos',     NULL, 14,   7),
  (@rs, 'EQ-15', '15 anos acima',   15,   NULL, 8);

INSERT INTO belt_divisions (ruleset_id, code, name, level_min, level_max, sort_order) VALUES
  (@rs, 'COL', 'Faixa Colorida', 1,  10, 1),
  (@rs, 'PRE', 'Faixa Preta',    11, 20, 2);

INSERT INTO weight_classes (ruleset_id, age_division_id, gender, name, weight_min, weight_max, sort_order)
SELECT @rs, a.id, g.gender, w.name, w.wmin, w.wmax, w.sort_order
FROM age_divisions a
CROSS JOIN (SELECT 'M' AS gender UNION ALL SELECT 'F') g
CROSS JOIN (
  SELECT 'até 50kg (provisório)' AS name, NULL AS wmin, 50.00 AS wmax, 1 AS sort_order
  UNION ALL
  SELECT 'acima de 50kg (provisório)', 50.00, NULL, 2
) w
WHERE a.ruleset_id = @rs AND a.code NOT LIKE 'EQ-%';

INSERT INTO ruleset_disciplines
  (ruleset_id, code, name, modality, format, split_gender, split_belt, split_weight, team_size, sort_order) VALUES
  (@rs, 'KTI', 'Kata Individual',              'kata',   'individual', 1, 1, 0, NULL, 1),
  (@rs, 'KMI', 'Kumite Individual',            'kumite', 'individual', 1, 1, 1, NULL, 2),
  (@rs, 'KTE', 'Kata Equipe',                  'kata',   'team',       1, 0, 0, 3,    3),
  (@rs, 'KME', 'Kumite Equipe (Revezamento)',  'kumite', 'team',       1, 0, 0, 3,    4);

INSERT INTO ruleset_discipline_ages (discipline_id, age_division_id)
SELECT d.id, a.id
FROM ruleset_disciplines d
JOIN age_divisions a ON a.ruleset_id = d.ruleset_id
WHERE d.ruleset_id = @rs
  AND ((d.code = 'KTE' AND a.code LIKE 'EQ-%') OR (d.code <> 'KTE' AND a.code NOT LIKE 'EQ-%'));
