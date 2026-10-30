-- =====================================================================================
-- FGKIRS — duas chaves que faltavam, ambas em Kata Individual e Kumite Individual:
--   "Até 6 anos": sexo misto e todas as faixas juntas (divisão etária U6).
--   "Para-karatê": todas as idades, faixas e sexos; só atleta marcado como para-karatê entra.
-- Vale para o regulamento padrão. Depois de rodar, em cada evento já criado use "Gerar categorias":
-- só as categorias que faltam são criadas, as existentes e suas inscrições não mudam.
-- Pode rodar de novo sem erro. RODE ANTES de subir o código novo.
-- =====================================================================================

SET NAMES utf8mb4;

ALTER TABLE age_divisions
  ADD COLUMN IF NOT EXISTS mixed_gender TINYINT(1) NOT NULL DEFAULT 0 AFTER sort_order,
  ADD COLUMN IF NOT EXISTS all_belts TINYINT(1) NOT NULL DEFAULT 0 AFTER mixed_gender;

ALTER TABLE ruleset_disciplines
  ADD COLUMN IF NOT EXISTS para_category TINYINT(1) NOT NULL DEFAULT 0 AFTER split_weight;

INSERT INTO age_divisions (ruleset_id, code, name, age_min, age_max, sort_order, mixed_gender, all_belts)
SELECT r.id, 'U6', 'Até 6 anos', NULL, 6, 0, 1, 1
FROM rulesets r
WHERE r.is_default = 1
  AND NOT EXISTS (SELECT 1 FROM age_divisions a WHERE a.ruleset_id = r.id AND a.code = 'U6');

INSERT IGNORE INTO ruleset_discipline_ages (discipline_id, age_division_id)
SELECT d.id, a.id
FROM ruleset_disciplines d
JOIN rulesets r ON r.id = d.ruleset_id AND r.is_default = 1
JOIN age_divisions a ON a.ruleset_id = d.ruleset_id AND a.code = 'U6'
WHERE d.code IN ('KTI', 'KMI');

UPDATE ruleset_disciplines d
JOIN rulesets r ON r.id = d.ruleset_id AND r.is_default = 1
SET d.para_category = 1
WHERE d.code IN ('KTI', 'KMI');

INSERT IGNORE INTO schema_migrations (version) VALUES ('013_para_and_under6');

-- ---------------------------------------------------------------- conferência
SELECT a.code, a.name, a.age_min, a.age_max, a.mixed_gender, a.all_belts FROM age_divisions a
JOIN rulesets r ON r.id = a.ruleset_id AND r.is_default = 1 WHERE a.code = 'U6';
SELECT d.code, d.para_category, (SELECT COUNT(*) FROM ruleset_discipline_ages l JOIN age_divisions x ON x.id = l.age_division_id
  WHERE l.discipline_id = d.id AND x.code = 'U6') AS usa_ate_6
FROM ruleset_disciplines d JOIN rulesets r ON r.id = d.ruleset_id AND r.is_default = 1 ORDER BY d.sort_order;
-- Esperado: 1 linha U6 (misto=1, faixas=1); KTI e KMI com para_category=1 e usa_ate_6=1; KTE e KME com 0 e 0.
