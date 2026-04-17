USE developmentdb;

CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(255) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('user', 'admin') NOT NULL DEFAULT 'user',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS events (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(255) NOT NULL,
  description TEXT,
  venue VARCHAR(255) NOT NULL,
  start_at DATETIME NOT NULL,
  end_at DATETIME NOT NULL,
  tickets_total INT NOT NULL,
  tickets_available INT NOT NULL,
  price_cents INT NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS bookings (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  event_id INT NOT NULL,
  quantity INT NOT NULL,
  status ENUM('pending', 'paid', 'cancelled') NOT NULL DEFAULT 'pending',
  external_payment_ref VARCHAR(255) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_bookings_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_bookings_event FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE
);

INSERT INTO users (email, password_hash, role) VALUES
  ('admin@eventhub.local', '$2y$12$Ae8Xr2f2xtcN893uPdRwv.QArdsqnroSYOv4soBIF.IXkPnLY8XYG', 'admin'),
  ('user@eventhub.local', '$2y$12$yhIWLBZLnmIOvddJm/lyPuFHotjTzPLF0gTyVghJnd4hFgt9ygm0y', 'user')
ON DUPLICATE KEY UPDATE email = email;

INSERT INTO events (title, description, venue, start_at, end_at, tickets_total, tickets_available, price_cents) VALUES
  ('Vue.js Workshop', 'Hands-on SPA patterns and Pinia state management.', 'Amsterdam Campus Hall A', DATE_ADD(NOW(), INTERVAL 14 DAY), DATE_ADD(NOW(), INTERVAL 14 DAY) + INTERVAL 3 HOUR, 80, 80, 1500),
  ('PHP REST & JWT', 'Secure APIs with PHP, middleware, and tokens.', 'Rotterdam Lab 2', DATE_ADD(NOW(), INTERVAL 21 DAY), DATE_ADD(NOW(), INTERVAL 21 DAY) + INTERVAL 4 HOUR, 50, 45, 0),
  ('Design Systems Meetup', 'UI consistency with component libraries.', 'The Hague Hub', DATE_ADD(NOW(), INTERVAL 30 DAY), DATE_ADD(NOW(), INTERVAL 30 DAY) + INTERVAL 2 HOUR, 120, 120, 500);
