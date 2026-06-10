-- Importar diretamente no phpMyAdmin
SET autocommit=0;

-- Sensei: Dione Monteblaco
INSERT INTO users (name, email, password, role) VALUES ('Dione Monteblaco', 'dmonteblanco@gmail.com', '$2y$10$2nq3MAsWZfG.83h0mMzuM.nXsOqPCgmKnhGzBadlrzQRMsOCYtdWG', 'sensei');
SET @sensei_id_0 = LAST_INSERT_ID();
INSERT INTO dojos (name, city, state, sensei_id) VALUES ('Dojo Dione Monteblaco', 'Santa Rosa', 'RS', @sensei_id_0);
SET @dojo_id_0 = LAST_INSERT_ID();
UPDATE users SET dojo_id = @dojo_id_0 WHERE id = @sensei_id_0;

-- Sensei: Luis Aresso
INSERT INTO users (name, email, password, role) VALUES ('Luis Aresso', 'lc.aresso@outlook.com', '$2y$10$BpETiMvglmLFEUJTq6McQevPEGhAJxiyKCDtklSkEBC9GTNb7Z8gS', 'sensei');
SET @sensei_id_1 = LAST_INSERT_ID();
INSERT INTO dojos (name, city, state, sensei_id) VALUES ('Dojo Luis Aresso', 'Santa Rosa', 'RS', @sensei_id_1);
SET @dojo_id_1 = LAST_INSERT_ID();
UPDATE users SET dojo_id = @dojo_id_1 WHERE id = @sensei_id_1;

-- Sensei: Rogério Chagas
INSERT INTO users (name, email, password, role) VALUES ('Rogério Chagas', 'rogeliochagasrodriguez@gmail.com', '$2y$10$xEB4nl2ej4zfKS2Q7a39quk7X0uoraj7Ye4LMWdy4LNftPCSmQvc.', 'sensei');
SET @sensei_id_2 = LAST_INSERT_ID();
INSERT INTO dojos (name, city, state, sensei_id) VALUES ('Dojo Rogério Chagas', 'Santa Rosa', 'RS', @sensei_id_2);
SET @dojo_id_2 = LAST_INSERT_ID();
UPDATE users SET dojo_id = @dojo_id_2 WHERE id = @sensei_id_2;

-- Sensei: Rosângela Quatrin Ponciano
INSERT INTO users (name, email, password, role) VALUES ('Rosângela Quatrin Ponciano', 'ro_quatrin.ponciano@hotmail.com', '$2y$10$8P89zo.hPuLokI8M23iYCuI.DTB/bMf/brGzH5aOkg45mq3kAqMcS', 'sensei');
SET @sensei_id_3 = LAST_INSERT_ID();
INSERT INTO dojos (name, city, state, sensei_id) VALUES ('Dojo Rosângela Quatrin Ponciano', 'Santa Rosa', 'RS', @sensei_id_3);
SET @dojo_id_3 = LAST_INSERT_ID();
UPDATE users SET dojo_id = @dojo_id_3 WHERE id = @sensei_id_3;

-- Sensei: Daniel Soares Guimarães (já cadastrado — atualiza nome e dojo)
UPDATE users SET name = 'Daniel Soares Guimarães' WHERE email = 'profdaniboy@gmail.com';
UPDATE dojos SET name = 'Dojo Daniel Soares Guimarães' WHERE sensei_id = (SELECT id FROM users WHERE email = 'profdaniboy@gmail.com');

COMMIT;
