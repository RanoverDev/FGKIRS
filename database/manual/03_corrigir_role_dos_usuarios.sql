-- =====================================================================================
-- FGKIRS — corrige users.role para os valores que o sistema usa.
--
-- Em produção o enum é ('admin','sensei','collaborator','student'), mas o código grava 'aluno' e
-- 'aluno-colaborador'. Como esses valores não existiam, os alunos ficaram com role vazia (''): o dashboard
-- do sensei conta 0 alunos e o filtro por papel na lista de usuários não os encontra.
--
-- O que faz: aceita os valores novos, converte os usuários com role vazia (e os poucos 'student'/'collaborator',
-- se existirem) para 'aluno'/'aluno-colaborador' e fecha o enum nos quatro valores do sistema.
-- Admins e senseis não são tocados. Idempotente. Faça o backup antes (se já fez o da migração e nada mudou
-- em usuários desde então, vale o mesmo).
-- Rode na aba SQL do phpMyAdmin (cole o texto inteiro).
-- =====================================================================================

SET NAMES utf8mb4;

-- 1) aceita os valores novos mantendo os antigos (não perde nenhuma linha)
ALTER TABLE `users`
  MODIFY `role` ENUM('admin','sensei','collaborator','student','aluno-colaborador','aluno') NOT NULL DEFAULT 'aluno';

-- 2) converte quem está com role vazia ou nos nomes antigos em inglês
UPDATE `users` SET `role` = 'aluno'             WHERE `role` = '' OR `role` = 'student';
UPDATE `users` SET `role` = 'aluno-colaborador' WHERE `role` = 'collaborator';

-- 3) fecha o enum nos quatro valores do sistema (só funciona se nenhuma linha ficou de fora)
ALTER TABLE `users`
  MODIFY `role` ENUM('admin','sensei','aluno-colaborador','aluno') NOT NULL DEFAULT 'aluno';

-- ---------------------------------------------------------------- conferência
SELECT `role`, COUNT(*) AS total FROM `users` GROUP BY `role`;
-- Esperado: admin 3, sensei 38, aluno 145 (e nenhuma linha com role vazia)
