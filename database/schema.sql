-- Fest Event Organizer -- schema + seed data
-- Import via: mysql -u root < schema.sql  (or phpMyAdmin import; XAMPP's MySQL uses the default port 3306)

DROP DATABASE IF EXISTS fest_event_organizer;
CREATE DATABASE fest_event_organizer CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE fest_event_organizer;

-- ---------------------------------------------------------------------
-- Core tables
-- ---------------------------------------------------------------------

CREATE TABLE users (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    name          VARCHAR(100) NOT NULL,
    email         VARCHAR(150) NOT NULL UNIQUE,
    password      VARCHAR(255) NOT NULL,
    role          ENUM('super_admin', 'organizer', 'participant') NOT NULL,
    status        ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    college       VARCHAR(150) NULL,
    phone         VARCHAR(20) NULL,
    created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE clubs (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    name          VARCHAR(120) NOT NULL,
    slug          VARCHAR(120) NOT NULL UNIQUE,
    description   VARCHAR(500) NOT NULL,
    organizer_id  INT NOT NULL UNIQUE,
    status        ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (organizer_id) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE fests (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    name          VARCHAR(150) NOT NULL,
    slug          VARCHAR(150) NOT NULL UNIQUE,
    description   TEXT NOT NULL,
    theme         VARCHAR(100) NOT NULL,
    start_date    DATE NOT NULL,
    end_date      DATE NOT NULL,
    venue         VARCHAR(150) NOT NULL,
    status        ENUM('draft', 'published', 'completed') NOT NULL DEFAULT 'draft',
    created_by    INT NOT NULL,
    created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE events (
    id                INT AUTO_INCREMENT PRIMARY KEY,
    fest_id           INT NOT NULL,
    club_id           INT NOT NULL,
    title             VARCHAR(150) NOT NULL,
    slug              VARCHAR(160) NOT NULL,
    description       TEXT NOT NULL,
    category          ENUM('competition', 'workshop', 'show', 'seminar') NOT NULL,
    venue             VARCHAR(150) NOT NULL,
    start_time        DATETIME NOT NULL,
    end_time          DATETIME NOT NULL,
    capacity          INT NOT NULL,
    team_size_min     INT NOT NULL DEFAULT 1,
    team_size_max     INT NOT NULL DEFAULT 1,
    entry_fee         DECIMAL(8,2) NOT NULL DEFAULT 0.00,
    status            ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    rejection_reason  VARCHAR(255) NULL,
    created_by        INT NOT NULL,
    created_at        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_fest_slug (fest_id, slug),
    FOREIGN KEY (fest_id) REFERENCES fests(id) ON DELETE CASCADE,
    FOREIGN KEY (club_id) REFERENCES clubs(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE registrations (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    event_id       INT NOT NULL,
    leader_id      INT NOT NULL,
    team_name      VARCHAR(120) NULL,
    team_size      INT NOT NULL,
    epass_code     VARCHAR(20) NOT NULL UNIQUE,
    status         ENUM('confirmed', 'cancelled') NOT NULL DEFAULT 'confirmed',
    registered_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_leader_event (leader_id, event_id),
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    FOREIGN KEY (leader_id) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE registration_members (
    id                INT AUTO_INCREMENT PRIMARY KEY,
    registration_id   INT NOT NULL,
    member_name       VARCHAR(120) NOT NULL,
    member_email      VARCHAR(150) NULL,
    FOREIGN KEY (registration_id) REFERENCES registrations(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE results (
    id                INT AUTO_INCREMENT PRIMARY KEY,
    event_id          INT NOT NULL,
    registration_id   INT NOT NULL,
    position          ENUM('winner', 'runner_up', 'second_runner_up', 'participant') NOT NULL,
    remarks           VARCHAR(255) NULL,
    recorded_by       INT NOT NULL,
    recorded_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_event_registration (event_id, registration_id),
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    FOREIGN KEY (registration_id) REFERENCES registrations(id) ON DELETE CASCADE,
    FOREIGN KEY (recorded_by) REFERENCES users(id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Seed data
-- ---------------------------------------------------------------------

-- Demo passwords (bcrypt hash of "Password123!" for every account below):
-- $2y$10$39eZxWrsWRhqz9RV3O1nk.3aXReu6vFvmk/t2TG1fe9U87PR8V/KC
INSERT INTO users (name, email, password, role, status, college, phone) VALUES
('Ishaan Kapoor (Fest Committee)', 'committee@fest.test', '$2y$10$39eZxWrsWRhqz9RV3O1nk.3aXReu6vFvmk/t2TG1fe9U87PR8V/KC', 'super_admin', 'active', 'Central Institute of Technology', '9800000001'),
('Rhea Verma (Music Club)', 'organizer.music@fest.test', '$2y$10$39eZxWrsWRhqz9RV3O1nk.3aXReu6vFvmk/t2TG1fe9U87PR8V/KC', 'organizer', 'active', 'Central Institute of Technology', '9800000002'),
('Kabir Anand (Coding Club)', 'organizer.coding@fest.test', '$2y$10$39eZxWrsWRhqz9RV3O1nk.3aXReu6vFvmk/t2TG1fe9U87PR8V/KC', 'organizer', 'active', 'Central Institute of Technology', '9800000003'),
('Naina Chopra (Dance Club)', 'organizer.dance@fest.test', '$2y$10$39eZxWrsWRhqz9RV3O1nk.3aXReu6vFvmk/t2TG1fe9U87PR8V/KC', 'organizer', 'active', 'Central Institute of Technology', '9800000004'),
('Yusuf Sheikh (Robotics Club)', 'organizer.robotics@fest.test', '$2y$10$39eZxWrsWRhqz9RV3O1nk.3aXReu6vFvmk/t2TG1fe9U87PR8V/KC', 'organizer', 'active', 'Central Institute of Technology', '9800000005'),
('Tara Iyer (unassigned organizer)', 'organizer.unassigned@fest.test', '$2y$10$39eZxWrsWRhqz9RV3O1nk.3aXReu6vFvmk/t2TG1fe9U87PR8V/KC', 'organizer', 'active', 'Central Institute of Technology', '9800000006'),
('Aarav Mehta', 'aarav@student.test', '$2y$10$39eZxWrsWRhqz9RV3O1nk.3aXReu6vFvmk/t2TG1fe9U87PR8V/KC', 'participant', 'active', 'Central Institute of Technology', '9811111101'),
('Diya Patel', 'diya@student.test', '$2y$10$39eZxWrsWRhqz9RV3O1nk.3aXReu6vFvmk/t2TG1fe9U87PR8V/KC', 'participant', 'active', 'Central Institute of Technology', '9811111102'),
('Reyansh Gupta', 'reyansh@student.test', '$2y$10$39eZxWrsWRhqz9RV3O1nk.3aXReu6vFvmk/t2TG1fe9U87PR8V/KC', 'participant', 'active', 'City College of Engineering', '9811111103'),
('Ananya Das', 'ananya@student.test', '$2y$10$39eZxWrsWRhqz9RV3O1nk.3aXReu6vFvmk/t2TG1fe9U87PR8V/KC', 'participant', 'active', 'Central Institute of Technology', '9811111104'),
('Vivaan Joshi', 'vivaan@student.test', '$2y$10$39eZxWrsWRhqz9RV3O1nk.3aXReu6vFvmk/t2TG1fe9U87PR8V/KC', 'participant', 'active', 'City College of Engineering', '9811111105'),
('Myra Khanna', 'myra@student.test', '$2y$10$39eZxWrsWRhqz9RV3O1nk.3aXReu6vFvmk/t2TG1fe9U87PR8V/KC', 'participant', 'active', 'Central Institute of Technology', '9811111106'),
('Arnav Bose', 'arnav@student.test', '$2y$10$39eZxWrsWRhqz9RV3O1nk.3aXReu6vFvmk/t2TG1fe9U87PR8V/KC', 'participant', 'active', 'Central Institute of Technology', '9811111107'),
('Ira Malhotra', 'ira@student.test', '$2y$10$39eZxWrsWRhqz9RV3O1nk.3aXReu6vFvmk/t2TG1fe9U87PR8V/KC', 'participant', 'active', 'City College of Engineering', '9811111108'),
('Sai Reddy', 'sai@student.test', '$2y$10$39eZxWrsWRhqz9RV3O1nk.3aXReu6vFvmk/t2TG1fe9U87PR8V/KC', 'participant', 'active', 'Central Institute of Technology', '9811111109'),
('Kiara Sen', 'kiara@student.test', '$2y$10$39eZxWrsWRhqz9RV3O1nk.3aXReu6vFvmk/t2TG1fe9U87PR8V/KC', 'participant', 'active', 'Central Institute of Technology', '9811111110'),
('Dev Kulkarni (deactivated)', 'dev@student.test', '$2y$10$39eZxWrsWRhqz9RV3O1nk.3aXReu6vFvmk/t2TG1fe9U87PR8V/KC', 'participant', 'inactive', 'Central Institute of Technology', '9811111111');

INSERT INTO clubs (name, slug, description, organizer_id, status) VALUES
('Euphony Music Club', 'euphony-music-club', 'The campus music society running gigs, open mics and the fest concert lineup.', 2, 'active'),
('ByteForge Coding Club', 'byteforge-coding-club', 'Competitive programming and hackathon crew behind the fest''s coding events.', 3, 'active'),
('Rhythm Dance Crew', 'rhythm-dance-crew', 'Choreography and dance-battle collective.', 4, 'active'),
('Vertex Robotics Club', 'vertex-robotics-club', 'Builds and races bots, runs robotics workshops and combat events.', 5, 'active');

INSERT INTO fests (name, slug, description, theme, start_date, end_date, venue, status, created_by) VALUES
('Zenith 2026', 'zenith-2026', 'The flagship annual techno-cultural fest of Central Institute of Technology -- three days of competitions, workshops and live shows across every club on campus.', 'Neon Odyssey', '2026-09-18', '2026-09-20', 'Main Campus Grounds', 'published', 1),
('Ignite Tech Meet 2026', 'ignite-tech-meet-2026', 'A focused one-day technical symposium ahead of the main fest -- coding, robotics and workshop-only.', 'Circuit Reboot', '2026-08-30', '2026-08-30', 'CS Block Auditorium', 'published', 1),
('Zenith 2025 (Archive)', 'zenith-2025', 'Last year''s fest, kept for reference and results history.', 'Retro Wave', '2025-09-19', '2025-09-21', 'Main Campus Grounds', 'completed', 1);

-- Events for Zenith 2026 (fest_id 1) -- a mix of approved / pending / rejected across clubs
INSERT INTO events (fest_id, club_id, title, slug, description, category, venue, start_time, end_time, capacity, team_size_min, team_size_max, entry_fee, status, rejection_reason, created_by) VALUES
(1, 1, 'Battle of Bands', 'battle-of-bands', 'Live band showdown -- each band gets 20 minutes on the main stage, judged on performance and originality.', 'competition', 'Main Amphitheatre', '2026-09-18 18:00:00', '2026-09-18 21:00:00', 60, 3, 6, 150.00, 'approved', NULL, 2),
(1, 1, 'Open Mic Night', 'open-mic-night', 'Unplugged solo/duet slots -- sing, rap, spoken word, whatever you''ve got.', 'show', 'Amphitheatre Lawn', '2026-09-19 19:00:00', '2026-09-19 21:00:00', 40, 1, 2, 0.00, 'approved', NULL, 2),
(1, 2, 'CodeStorm Hackathon', 'codestorm-hackathon', '12-hour overnight build sprint -- ship a working prototype on the given theme.', 'competition', 'CS Block Labs 1-3', '2026-09-18 20:00:00', '2026-09-19 08:00:00', 80, 2, 4, 200.00, 'approved', NULL, 3),
(1, 2, 'Competitive Programming Sprint', 'competitive-programming-sprint', 'Solo 3-hour rated contest, ICPC-style problem set.', 'competition', 'CS Block Auditorium', '2026-09-19 10:00:00', '2026-09-19 13:00:00', 50, 1, 1, 50.00, 'approved', NULL, 3),
(1, 2, 'Intro to Web3 Workshop', 'intro-to-web3-workshop', 'Hands-on workshop covering wallets, smart contracts and a starter dApp.', 'workshop', 'CS Block Seminar Hall', '2026-09-19 10:30:00', '2026-09-19 13:00:00', 45, 1, 1, 0.00, 'pending', NULL, 3),
(1, 3, 'Street Dance Battle', 'street-dance-battle', 'Crew vs crew knockout battle, freestyle rounds judged live.', 'competition', 'Open Air Theatre', '2026-09-19 16:00:00', '2026-09-19 19:00:00', 100, 4, 10, 100.00, 'approved', NULL, 4),
(1, 3, 'Bollywood Fusion Showcase', 'bollywood-fusion-showcase', 'Choreographed group performance showcase, non-competitive.', 'show', 'Main Amphitheatre', '2026-09-20 17:00:00', '2026-09-20 19:00:00', 50, 5, 12, 0.00, 'approved', NULL, 4),
(1, 4, 'Robo Race', 'robo-race', 'Line-following bot race on an obstacle track.', 'competition', 'Engineering Block Ground Floor', '2026-09-19 10:00:00', '2026-09-19 14:00:00', 36, 2, 3, 250.00, 'approved', NULL, 5),
(1, 4, 'Bot Combat Arena', 'bot-combat-arena', '1kg combat-bot elimination bracket.', 'competition', 'Engineering Block Arena', '2026-09-20 11:00:00', '2026-09-20 15:00:00', 24, 2, 4, 300.00, 'approved', NULL, 5),
(1, 4, 'Drone Building 101', 'drone-building-101', 'Full-day workshop assembling and flying a basic quadcopter.', 'workshop', 'Engineering Block Lab 4', '2026-09-18 09:00:00', '2026-09-18 17:00:00', 30, 1, 1, 500.00, 'rejected', 'Clashes with facility maintenance window; resubmit with an afternoon-only slot.', 5),
(1, 2, 'AI Pitch Slam', 'ai-pitch-slam', 'Pitch an AI product idea to a panel of judges in 5 minutes flat.', 'competition', 'CS Block Auditorium', '2026-09-20 10:00:00', '2026-09-20 12:30:00', 30, 1, 3, 0.00, 'pending', NULL, 3);

-- Events for Ignite Tech Meet 2026 (fest_id 2)
INSERT INTO events (fest_id, club_id, title, slug, description, category, venue, start_time, end_time, capacity, team_size_min, team_size_max, entry_fee, status, rejection_reason, created_by) VALUES
(2, 2, 'Quick DSA Quiz', 'quick-dsa-quiz', 'Rapid-fire data structures & algorithms quiz, solo entry.', 'competition', 'CS Block Auditorium', '2026-08-30 10:00:00', '2026-08-30 11:00:00', 40, 1, 1, 0.00, 'approved', NULL, 3),
(2, 4, 'Sensors & IoT Workshop', 'sensors-iot-workshop', 'Build a small sensor-driven IoT demo end to end.', 'workshop', 'Engineering Block Lab 2', '2026-08-30 11:30:00', '2026-08-30 15:00:00', 25, 1, 1, 100.00, 'approved', NULL, 5);

-- Events for Zenith 2025 archive (fest_id 3) -- approved & already "finished" so results exist
INSERT INTO events (fest_id, club_id, title, slug, description, category, venue, start_time, end_time, capacity, team_size_min, team_size_max, entry_fee, status, rejection_reason, created_by) VALUES
(3, 2, 'CodeStorm Hackathon 2025', 'codestorm-hackathon-2025', 'Last year''s overnight hackathon.', 'competition', 'CS Block Labs', '2025-09-19 20:00:00', '2025-09-20 08:00:00', 60, 2, 4, 150.00, 'approved', NULL, 3);

-- Registrations -- a handful of solo + team registrations across approved Zenith 2026 events
-- (event ids follow insertion order: 1 Battle of Bands, 2 Open Mic, 3 CodeStorm, 4 CP Sprint,
--  6 Street Dance Battle, 7 Bollywood Fusion, 8 Robo Race, 9 Bot Combat, 12 Quick DSA Quiz (fest 2 evt),
--  13 Sensors & IoT (fest 2), 14 CodeStorm 2025 (fest 3))
INSERT INTO registrations (event_id, leader_id, team_name, team_size, epass_code, status) VALUES
(3, 7, 'Null Pointers', 3, 'FEST-A1B2-C3D4', 'confirmed'),   -- Aarav leads a CodeStorm team
(4, 7, NULL, 1, 'FEST-A1B3-C3D5', 'confirmed'),               -- Aarav also solo in CP Sprint (no time clash)
(3, 8, 'Stack Overflow', 4, 'FEST-B2C3-D4E5', 'confirmed'),   -- Diya leads another CodeStorm team
(9, 9, 'Iron Wolves', 2, 'FEST-C3D4-E5F6', 'confirmed'),      -- Reyansh in Robo Race
(6, 10, 'Beat Breakers', 6, 'FEST-D4E5-F6G7', 'confirmed'),    -- Ananya in Street Dance Battle
(1, 11, 'The Amplifiers', 4, 'FEST-E5F6-G7H8', 'confirmed'),  -- Vivaan in Battle of Bands
(2, 12, NULL, 1, 'FEST-F6G7-H8I9', 'confirmed'),              -- Myra solo Open Mic
(4, 13, NULL, 1, 'FEST-G7H8-I9J0', 'confirmed'),              -- Arnav solo CP Sprint
(7, 14, 'Fusion Squad', 6, 'FEST-H8I9-J0K1', 'confirmed'),    -- Ira in Bollywood Fusion
(14, 7, 'Legacy Hackers', 3, 'FEST-I9J0-K1L2', 'confirmed');  -- Aarav's 2025 archive registration

INSERT INTO registration_members (registration_id, member_name, member_email) VALUES
(1, 'Diya Patel', 'diya@student.test'),
(1, 'Sai Reddy', 'sai@student.test'),
(3, 'Reyansh Gupta', 'reyansh@student.test'),
(3, 'Myra Khanna', 'myra@student.test'),
(3, 'Kiara Sen', 'kiara@student.test'),
(4, 'Ananya Das', 'ananya@student.test'),
(5, 'Arnav Bose', 'arnav@student.test'),
(5, 'Ira Malhotra', 'ira@student.test'),
(5, 'Sai Reddy', 'sai@student.test'),
(5, 'Kiara Sen', 'kiara@student.test'),
(5, 'Dev Kulkarni', NULL),
(6, 'Myra Khanna', 'myra@student.test'),
(6, 'Reyansh Gupta', 'reyansh@student.test'),
(6, 'Kiara Sen', 'kiara@student.test'),
(9, 'Aarav Mehta', 'aarav@student.test'),
(9, 'Diya Patel', 'diya@student.test'),
(9, 'Reyansh Gupta', 'reyansh@student.test'),
(9, 'Sai Reddy', 'sai@student.test'),
(9, 'Kiara Sen', 'kiara@student.test'),
(10, 'Diya Patel', 'diya@student.test'),
(10, 'Reyansh Gupta', 'reyansh@student.test');

-- Results for the completed 2025 archive hackathon
INSERT INTO results (event_id, registration_id, position, remarks, recorded_by) VALUES
(14, 10, 'winner', 'Best overall build quality and demo -- unanimous judge decision.', 3);

-- ---------------------------------------------------------------------
-- An always-upcoming fest, dated relative to CURDATE() rather than a fixed
-- calendar date. Zenith 2026 above is deliberately already "finished" (its
-- literal dates fall in the past soon enough), so a demo can show organizers
-- declaring results and participants holding e-passes for events that
-- already ran. But that leaves nothing open for registration -- the actual
-- register -> e-pass -> cancel flow needs an event whose start_time is
-- still in the future *whenever this file happens to be imported*, so it
-- can't be a literal date either. This fest is that one: no registrations
-- seeded against it, so it's free for a live demo of the full flow.
-- ---------------------------------------------------------------------
INSERT INTO fests (name, slug, description, theme, start_date, end_date, venue, status, created_by) VALUES
('Winter Carnival 2026', 'winter-carnival-2026', 'The season''s second campus fest -- a smaller, single-weekend follow-up to Zenith with fresh registrations still open.', 'Frostbyte', DATE_ADD(CURDATE(), INTERVAL 14 DAY), DATE_ADD(CURDATE(), INTERVAL 16 DAY), 'Main Campus Grounds', 'published', 1);

INSERT INTO events (fest_id, club_id, title, slug, description, category, venue, start_time, end_time, capacity, team_size_min, team_size_max, entry_fee, status, rejection_reason, created_by) VALUES
(4, 1, 'Acoustic Night', 'acoustic-night', 'Unplugged solo/duet sets around a bonfire stage -- sign up and play two songs.', 'show', 'Amphitheatre Lawn', DATE_ADD(CURDATE(), INTERVAL 14 DAY) + INTERVAL 18 HOUR, DATE_ADD(CURDATE(), INTERVAL 14 DAY) + INTERVAL 21 HOUR, 40, 1, 2, 0.00, 'approved', NULL, 2),
(4, 2, 'Rapid Code Duel', 'rapid-code-duel', 'Head-to-head 1-on-1 speed coding knockout bracket.', 'competition', 'CS Block Auditorium', DATE_ADD(CURDATE(), INTERVAL 15 DAY) + INTERVAL 10 HOUR, DATE_ADD(CURDATE(), INTERVAL 15 DAY) + INTERVAL 13 HOUR, 50, 1, 1, 50.00, 'approved', NULL, 3),
(4, 4, 'Robo Sprint Trials', 'robo-sprint-trials', 'Timed obstacle-course qualifier ahead of the main Robo Race.', 'competition', 'Engineering Block Ground Floor', DATE_ADD(CURDATE(), INTERVAL 15 DAY) + INTERVAL 14 HOUR, DATE_ADD(CURDATE(), INTERVAL 15 DAY) + INTERVAL 17 HOUR, 30, 2, 3, 150.00, 'approved', NULL, 5),
(4, 3, 'Dance Fusion Workshop', 'dance-fusion-workshop', 'Learn a short contemporary-fusion routine, no prior experience needed.', 'workshop', 'Open Air Theatre', DATE_ADD(CURDATE(), INTERVAL 16 DAY) + INTERVAL 10 HOUR, DATE_ADD(CURDATE(), INTERVAL 16 DAY) + INTERVAL 13 HOUR, 30, 1, 1, 0.00, 'pending', NULL, 4);
