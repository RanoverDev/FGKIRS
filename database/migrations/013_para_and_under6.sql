-- Duas chaves que faltavam, ambas em Kata Individual e Kumite Individual:
--   "Até 6 anos": sexo misto e todas as faixas juntas (divisão etária U6).
--   "Para-karatê": todas as idades, faixas e sexos; só atleta marcado como para-karatê entra.
-- Vale para o regulamento padrão. Depois de rodar, em cada evento já criado use "Gerar categorias":
-- só as categorias que faltam são criadas, as existentes e suas inscrições não mudam.

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
