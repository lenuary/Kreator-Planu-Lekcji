-- Utworzenie tabel
CREATE TABLE IF NOT EXISTS classes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL
);

CREATE TABLE IF NOT EXISTS teachers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL
);

CREATE TABLE IF NOT EXISTS subjects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL
);

CREATE TABLE IF NOT EXISTS rooms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL
);

CREATE TABLE IF NOT EXISTS schedule (
    id INT AUTO_INCREMENT PRIMARY KEY,
    class_id INT,
    teacher_id INT,
    subject_id INT,
    room_id INT,
    day INT,
    hour INT,
    type VARCHAR(50),
    hours_total VARCHAR(20),
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
    FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE
);

-- Przykładowe dane (opcjonalne)
INSERT INTO classes (name) VALUES ('1A'), ('2B');
INSERT INTO teachers (name) VALUES ('Jan Kowalski'), ('Anna Nowak');
INSERT INTO subjects (name) VALUES ('Matematyka'), ('Polski');
INSERT INTO rooms (name) VALUES ('101'), ('202');