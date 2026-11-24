CREATE DATABASE IF NOT EXISTS valaichchenai_radius25
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE valaichchenai_radius25;

DROP TABLE IF EXISTS itineraries;
DROP TABLE IF EXISTS places;
DROP TABLE IF EXISTS users;

CREATE TABLE users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('visitor', 'admin') NOT NULL DEFAULT 'visitor',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE places (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(180) NOT NULL,
  description TEXT NOT NULL,
  category VARCHAR(40) NOT NULL,
  address VARCHAR(255) NOT NULL DEFAULT 'Valaichchenai, Sri Lanka',
  latitude DECIMAL(10,7) NOT NULL,
  longitude DECIMAL(10,7) NOT NULL,
  opening_time TIME NULL,
  closing_time TIME NULL,
  visit_duration INT UNSIGNED NOT NULL DEFAULT 60,
  entry_fee DECIMAL(10,2) NULL,
  image_url VARCHAR(1000) NULL,
  travel_tips TEXT NULL,
  dress_code TEXT NULL,
  facilities TEXT NULL,
  best_visit_time VARCHAR(120) NULL,
  distance_from_home DECIMAL(6,2) NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_places_active_category (is_active, category),
  INDEX idx_places_name (name)
) ENGINE=InnoDB;

CREATE TABLE itineraries (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  title VARCHAR(160) NOT NULL,
  visit_date DATE NOT NULL,
  start_time TIME NOT NULL,
  pace ENUM('relaxed', 'balanced', 'quick') NOT NULL DEFAULT 'balanced',
  route_order JSON NOT NULL,
  total_distance DECIMAL(7,2) NOT NULL DEFAULT 0,
  total_travel_time INT UNSIGNED NOT NULL DEFAULT 0,
  expected_end_time TIME NOT NULL,
  warnings JSON NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_itineraries_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

INSERT INTO places
(name, description, category, address, latitude, longitude, opening_time, closing_time, visit_duration, entry_fee, image_url, travel_tips, dress_code, facilities, best_visit_time, distance_from_home, is_active)
VALUES
('Nasiyanthivu Lagoon Ride','A scenic lagoon area suitable for experiencing the natural environment, lagoon views, and recreational activities such as lagoon rides.','Recreation','Nasiyanthivu, Valaichchenai',7.9412,81.5447,'06:00','18:30',90,NULL,NULL,'Early morning and late afternoon are calmest for boat rides. Bring sun protection and drinking water.','Comfortable, modest clothing; a hat and sunscreen are recommended on the water.','Boat access points and shaded rest spots vary by operator.','Early morning or late afternoon',5.4,1),
('Nasavanthiv Moya','A local natural attraction offering visitors an opportunity to experience the surrounding landscape and peaceful environment.','Nature','Nasavanthiv, Valaichchenai',7.9521,81.5196,NULL,NULL,45,NULL,NULL,'Paths may be uneven; wear comfortable footwear and carry water.','Comfortable outdoor clothing.','Limited facilities; check locally before setting out.','Morning or late afternoon',4.8,1),
('Elephant Rock Beach','A coastal attraction known for its scenic surroundings and beach environment, suitable for sightseeing, photography, and relaxation.','Beach','Elephant Rock, Valaichchenai coast',7.8824,81.5522,'06:00','18:00',60,NULL,NULL,'Sea conditions can change quickly; observe safety flags and avoid swimming in rough surf.','Light beach clothing; carry sun protection.','Beach access and nearby small shops vary.','Late afternoon',4.9,1),
('Pasikuda Beach','A popular east-coast beach known for its attractive shoreline and calm coastal environment, suitable for relaxation and sightseeing.','Beach','Pasikuda, Batticaloa District',7.9238,81.5647,'06:00','19:00',90,NULL,NULL,'The bay is calm most of the year, but respect local signage and swim within marked areas.','Light beach clothing; modest swimwear.','Beachfront shops and restaurants nearby.','Early morning or late afternoon',5.9,1),
('Pasikuda View Point','A scenic viewpoint providing visitors with an opportunity to enjoy coastal scenery and capture photographs of the surrounding area.','Viewpoint','Pasikuda, Batticaloa District',7.9301,81.5718,'06:00','18:30',30,NULL,NULL,'Best light is at sunrise and just before sunset; watch your footing near edges.','Comfortable clothing; wear sturdy shoes.','Limited facilities; bring water.','Sunrise or sunset',7.2,1),
('Sunrise View Point – Flaises Ross Fauve','A scenic location suitable for experiencing sunrise views, enjoying the coastal landscape, and photography.','Viewpoint','Near Valaichchenai, Eastern Province',7.8996,81.5777,'05:30','08:00',45,NULL,NULL,'Arrive before sunrise to secure a viewing spot; carry a torch for pre-dawn paths.','Comfortable clothing; a light layer before sunrise.','Open-air location; facilities limited.','Sunrise',6.0,1),
('Kalkudah Beach','A scenic coastal destination offering a quieter beach environment suitable for relaxation, sightseeing, and photography.','Beach','Kalkudah, Batticaloa District',7.9239,81.5153,'06:00','18:30',60,NULL,NULL,'Quiet stretches of beach; take care near rocks and follow local advice for swimming.','Light beach clothing; carry sun protection.','Limited shade; bring water and snacks.','Early morning or late afternoon',4.7,1),
('Puliyadithurai','A local attraction within the Valaichchenai area where visitors can experience the surrounding natural environment and local landscape.','Local Attraction','Puliyadithurai, Valaichchenai',7.9425,81.5074,NULL,NULL,40,NULL,NULL,'A quiet local area; ask locally for the best viewpoints and respect residents’ privacy.','Modest, comfortable clothing.','Limited facilities.','Morning or late afternoon',3.8,1),
('Pandarakattu Anicut','An irrigation-related landmark surrounded by a natural environment, providing an opportunity to observe local water-management infrastructure and scenery.','Heritage','Pandarakattu, Batticaloa District',7.9867,81.4204,NULL,NULL,45,NULL,NULL,'A working irrigation area — stay on accessible paths and be mindful of water flow.','Modest, comfortable clothing.','Basic access; bring water.','Morning',17.0,1),
('Pandarakattu Bridge','A local landmark offering views of the surrounding landscape and an opportunity to experience the rural environment of the area.','Landmark','Pandarakattu, Batticaloa District',7.9901,81.4122,NULL,NULL,25,NULL,NULL,'A short photography stop; take care with traffic on and near the bridge.','Comfortable clothing; wear shoes suitable for walking.','Roadside access only; no dedicated facilities.','Morning or late afternoon',18.0,1);