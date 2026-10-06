-- =====================================================================================
-- FGKIRS — remove 4 tabelas que sobraram de uma versão antiga do sistema.
--
-- `news`, `categories`, `schedule` e `styles` não são lidas nem gravadas por nenhuma linha do código
-- (as notícias e a agenda usam a tabela `posts`; os estilos usam `martial_arts_styles`) e não são criadas
-- pelo Database.php. Pelo export de produção as quatro estão vazias.
--
-- PASSO 1 (rode só este SELECT e confira): todas as contagens devem ser 0.
-- PASSO 2: só se for tudo 0, rode os DROP. A ordem importa: `news` tem FK para `categories`.
-- Faça o backup antes. Rode na aba SQL do phpMyAdmin.
-- =====================================================================================

-- PASSO 1
SELECT 'news' AS tabela, COUNT(*) AS linhas FROM news
UNION ALL SELECT 'categories', COUNT(*) FROM categories
UNION ALL SELECT 'schedule', COUNT(*) FROM schedule
UNION ALL SELECT 'styles', COUNT(*) FROM styles;

-- PASSO 2 (só depois de conferir o passo 1)
DROP TABLE IF EXISTS `news`;
DROP TABLE IF EXISTS `categories`;
DROP TABLE IF EXISTS `schedule`;
DROP TABLE IF EXISTS `styles`;
