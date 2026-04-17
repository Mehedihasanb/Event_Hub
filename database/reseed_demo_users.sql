-- Run if demo logins fail (e.g. users were never seeded).
-- From project root, with Docker running:
--   Get-Content database\reseed_demo_users.sql | docker compose exec -T mysql mariadb -u developer -psecret123 developmentdb

USE developmentdb;

INSERT INTO users (email, password_hash, role) VALUES
  ('admin@eventhub.local', '$2y$12$Ae8Xr2f2xtcN893uPdRwv.QArdsqnroSYOv4soBIF.IXkPnLY8XYG', 'admin'),
  ('user@eventhub.local', '$2y$12$yhIWLBZLnmIOvddJm/lyPuFHotjTzPLF0gTyVghJnd4hFgt9ygm0y', 'user')
ON DUPLICATE KEY UPDATE
  password_hash = VALUES(password_hash),
  role = VALUES(role);
