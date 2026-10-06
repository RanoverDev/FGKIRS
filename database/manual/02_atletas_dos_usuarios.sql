-- =====================================================================================
-- FGKIRS — cadastra em `athletes` os alunos que já têm perfil de atleta (users + athlete_profiles).
--
-- Equivale ao passo 1 de scripts/migrate_athletes.php. Só entra quem tem dojo, data de nascimento e
-- sexo M/F no perfil (campos obrigatórios em athletes). É idempotente: quem já está em athletes
-- (por user_id) não é duplicado, então pode ser importado de novo.
--
-- Pré-requisito: 01_aplicar_migrations_001_a_005.sql já importado.
-- COMO: phpMyAdmin > selecione o banco > Importar > escolha este arquivo > Executar.
--
-- Fica de fora (e é opcional): os atletas AVULSOS de eventos antigos. Eles só importam para migrar os
-- eventos antigos ao modelo novo (fase 5) e rodam depois, com `php scripts/migrate_athletes.php`.
-- =====================================================================================

SET NAMES utf8mb4;

INSERT INTO athletes
    (dojo_id, user_id, full_name, birth_date, gender, style_id, graduation_id, weight_kg, height_cm,
     is_para_karate, fgkirs_registration, cbki_registration, status, created_by)
SELECT u.dojo_id, u.id, u.name, ap.birth_date, ap.gender, ap.style_id, ap.graduation_id, ap.weight,
       IF(ap.height > 0, ap.height, NULL), ap.is_para_karate,
       CAST(ap.fgkirs_registration AS CHAR), ap.cbki_registration, 'active', u.id
FROM users u
JOIN athlete_profiles ap ON ap.user_id = u.id
WHERE u.dojo_id IS NOT NULL
  AND ap.birth_date IS NOT NULL
  AND ap.gender IN ('M', 'F')
  AND NOT EXISTS (SELECT 1 FROM athletes a WHERE a.user_id = u.id);

-- Liga as inscrições antigas dos alunos ao cadastro novo (as de atletas avulsos ficam como estão)
UPDATE championship_athletes ca
JOIN athletes a ON a.user_id = ca.user_id
SET ca.athlete_id = a.id
WHERE ca.athlete_id IS NULL AND ca.user_id IS NOT NULL;

-- ---------------------------------------------------------------- conferência
SELECT 'atletas cadastrados' AS item, COUNT(*) AS total FROM athletes
UNION ALL SELECT 'alunos com perfil que ficaram de fora (sem dojo, nascimento ou sexo M/F)',
    COUNT(*) FROM users u JOIN athlete_profiles ap ON ap.user_id = u.id
    WHERE NOT EXISTS (SELECT 1 FROM athletes a WHERE a.user_id = u.id)
UNION ALL SELECT 'inscrições antigas ainda sem athlete_id (avulsos, ou alunos de fora)',
    COUNT(*) FROM championship_athletes WHERE athlete_id IS NULL;
