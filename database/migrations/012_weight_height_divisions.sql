-- Divisões de peso e altura (regulamento do Presidente):
--   Menores de 15 (Mirim, Infantil, Infanto Juvenil): por ALTURA, até 1,55 m | acima de 1,55 m.
--   Juvenil 15-17:  masc. até 50 | 50-65 | acima de 65 kg   —  fem. até 50 | 50-55 | acima de 55 kg.
--   Adulto e Master: masc. até 50 | 50-65 | 65-75 | acima de 75 kg — fem. até 50 | 50-55 | 55-65 | acima de 65 kg.
-- Limite inferior exclusivo e superior inclusivo. Em altura os limites ficam em centímetros (155).
-- Só o regulamento marcado como padrão. Eventos já criados não mudam (as categorias deles são cópia).

ALTER TABLE weight_classes
  ADD COLUMN IF NOT EXISTS measure ENUM('weight','height') NOT NULL DEFAULT 'weight' AFTER weight_max;

ALTER TABLE event_categories
  ADD COLUMN IF NOT EXISTS measure ENUM('weight','height') NOT NULL DEFAULT 'weight' AFTER weight_max;

ALTER TABLE championship_registrations
  ADD COLUMN IF NOT EXISTS declared_height SMALLINT UNSIGNED NULL AFTER declared_weight;

DELETE w FROM weight_classes w
JOIN age_divisions a ON a.id = w.age_division_id
JOIN rulesets r ON r.id = a.ruleset_id
WHERE r.is_default = 1 AND a.code IN ('MIR', 'INF', 'IJU', 'JUV', 'ADU', 'MAS');

INSERT INTO weight_classes (ruleset_id, age_division_id, gender, name, weight_min, weight_max, sort_order, measure)
SELECT a.ruleset_id, a.id, s.gender, s.name, s.wmin, s.wmax, s.sort_order,
       CASE WHEN a.code IN ('MIR', 'INF', 'IJU') THEN 'height' ELSE 'weight' END
FROM age_divisions a
JOIN rulesets r ON r.id = a.ruleset_id AND r.is_default = 1
JOIN (
  SELECT 'MIR' AS age_code, 'M' AS gender, 'até 1,55m' AS name, NULL AS wmin, 155 AS wmax, 1 AS sort_order
  UNION ALL SELECT 'MIR', 'M', 'acima de 1,55m', 155, NULL, 2
  UNION ALL SELECT 'MIR', 'F', 'até 1,55m', NULL, 155, 1
  UNION ALL SELECT 'MIR', 'F', 'acima de 1,55m', 155, NULL, 2
  UNION ALL SELECT 'INF', 'M', 'até 1,55m', NULL, 155, 1
  UNION ALL SELECT 'INF', 'M', 'acima de 1,55m', 155, NULL, 2
  UNION ALL SELECT 'INF', 'F', 'até 1,55m', NULL, 155, 1
  UNION ALL SELECT 'INF', 'F', 'acima de 1,55m', 155, NULL, 2
  UNION ALL SELECT 'IJU', 'M', 'até 1,55m', NULL, 155, 1
  UNION ALL SELECT 'IJU', 'M', 'acima de 1,55m', 155, NULL, 2
  UNION ALL SELECT 'IJU', 'F', 'até 1,55m', NULL, 155, 1
  UNION ALL SELECT 'IJU', 'F', 'acima de 1,55m', 155, NULL, 2
  UNION ALL SELECT 'JUV', 'M', 'até 50kg', NULL, 50, 1
  UNION ALL SELECT 'JUV', 'M', 'acima de 50 até 65kg', 50, 65, 2
  UNION ALL SELECT 'JUV', 'M', 'acima de 65kg', 65, NULL, 3
  UNION ALL SELECT 'JUV', 'F', 'até 50kg', NULL, 50, 1
  UNION ALL SELECT 'JUV', 'F', 'acima de 50 até 55kg', 50, 55, 2
  UNION ALL SELECT 'JUV', 'F', 'acima de 55kg', 55, NULL, 3
  UNION ALL SELECT 'ADU', 'M', 'até 50kg', NULL, 50, 1
  UNION ALL SELECT 'ADU', 'M', 'acima de 50 até 65kg', 50, 65, 2
  UNION ALL SELECT 'ADU', 'M', 'acima de 65 até 75kg', 65, 75, 3
  UNION ALL SELECT 'ADU', 'M', 'acima de 75kg', 75, NULL, 4
  UNION ALL SELECT 'ADU', 'F', 'até 50kg', NULL, 50, 1
  UNION ALL SELECT 'ADU', 'F', 'acima de 50 até 55kg', 50, 55, 2
  UNION ALL SELECT 'ADU', 'F', 'acima de 55 até 65kg', 55, 65, 3
  UNION ALL SELECT 'ADU', 'F', 'acima de 65kg', 65, NULL, 4
  UNION ALL SELECT 'MAS', 'M', 'até 50kg', NULL, 50, 1
  UNION ALL SELECT 'MAS', 'M', 'acima de 50 até 65kg', 50, 65, 2
  UNION ALL SELECT 'MAS', 'M', 'acima de 65 até 75kg', 65, 75, 3
  UNION ALL SELECT 'MAS', 'M', 'acima de 75kg', 75, NULL, 4
  UNION ALL SELECT 'MAS', 'F', 'até 50kg', NULL, 50, 1
  UNION ALL SELECT 'MAS', 'F', 'acima de 50 até 55kg', 50, 55, 2
  UNION ALL SELECT 'MAS', 'F', 'acima de 55 até 65kg', 55, 65, 3
  UNION ALL SELECT 'MAS', 'F', 'acima de 65kg', 65, NULL, 4
) s ON s.age_code = a.code;
