-- Aposenta o modelo antigo de eventos: apaga os eventos 'legacy' (e os árbitros deles), remove as tabelas
-- do modelo antigo e a coluna registration_model. As inscrições novas (championship_registrations,
-- event_*) e os árbitros dos eventos novos não são tocados.

DELETE r FROM championship_referees r
JOIN championships c ON c.id = r.championship_id
WHERE c.registration_model = 'legacy';

DROP TABLE IF EXISTS championship_team_members;
DROP TABLE IF EXISTS championship_teams;
DROP TABLE IF EXISTS championship_entries;
DROP TABLE IF EXISTS championship_athletes;
DROP TABLE IF EXISTS championship_categories;
DROP TABLE IF EXISTS competition_categories;

DELETE FROM championships WHERE registration_model = 'legacy';

ALTER TABLE championships DROP COLUMN registration_model;
