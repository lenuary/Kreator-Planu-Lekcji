<?php
header('Content-Type: application/json');
require_once 'db.php';

// Odczytanie danych wejściowych JSON (dla POST)
$input = json_decode(file_get_contents('php://input'), true);
$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        // --- POBIERANIE WSZYSTKICH DANYCH ---
        case 'get_all':
            $data = [
                'classes' => $pdo->query("SELECT * FROM classes ORDER BY name")->fetchAll(),
                'teachers' => $pdo->query("SELECT * FROM teachers ORDER BY name")->fetchAll(),
                'subjects' => $pdo->query("SELECT * FROM subjects ORDER BY name")->fetchAll(),
                'rooms' => $pdo->query("SELECT * FROM rooms ORDER BY name")->fetchAll(),
                // Mapowanie nazw kolumn z bazy na nazwy używane w JS (camelCase)
                'schedule' => $pdo->query("
                    SELECT id, class_id as classId, teacher_id as teacherId, 
                           subject_id as subjectId, room_id as roomId, 
                           day, hour, type, hours_total as hoursTotal 
                    FROM schedule
                ")->fetchAll()
            ];
            echo json_encode($data);
            break;

        // --- DODAWANIE / EDYCJA ---
        case 'add_item':
            // type: 'classes', 'teachers', 'subjects', 'rooms'
            $table = $input['table'];
            $name = $input['name'];
            
            // Prosta walidacja nazwy tabeli dla bezpieczeństwa
            if (!in_array($table, ['classes', 'teachers', 'subjects', 'rooms'])) throw new Exception("Nieprawidłowa tabela");

            $stmt = $pdo->prepare("INSERT INTO $table (name) VALUES (?)");
            $stmt->execute([$name]);
            echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
            break;

        case 'save_lesson':
            $id = $input['id'] ?? null;
            $fields = [
                $input['classId'], $input['teacherId'], $input['subjectId'], 
                $input['roomId'], $input['day'], $input['hour'], 
                $input['type'], $input['hoursTotal']
            ];

            if ($id) {
                // UPDATE
                $sql = "UPDATE schedule SET class_id=?, teacher_id=?, subject_id=?, room_id=?, day=?, hour=?, type=?, hours_total=? WHERE id=?";
                $fields[] = $id; // dodajemy ID na koniec parametrów
                $stmt = $pdo->prepare($sql);
                $stmt->execute($fields);
            } else {
                // INSERT
                $sql = "INSERT INTO schedule (class_id, teacher_id, subject_id, room_id, day, hour, type, hours_total) VALUES (?,?,?,?,?,?,?,?)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute($fields);
                $id = $pdo->lastInsertId();
            }
            echo json_encode(['success' => true, 'id' => $id]);
            break;

        // --- USUWANIE ---
        case 'delete_item':
            $table = $input['table'];
            $id = $input['id'];

            if (!in_array($table, ['classes', 'teachers', 'subjects', 'rooms', 'schedule'])) throw new Exception("Nieprawidłowa tabela");

            $stmt = $pdo->prepare("DELETE FROM $table WHERE id = ?");
            $stmt->execute([$id]);
            echo json_encode(['success' => true]);
            break;

        case 'clear_data':
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
            $pdo->exec("TRUNCATE TABLE schedule");
            $pdo->exec("TRUNCATE TABLE classes");
            $pdo->exec("TRUNCATE TABLE teachers");
            $pdo->exec("TRUNCATE TABLE subjects");
            $pdo->exec("TRUNCATE TABLE rooms");
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
            echo json_encode(['success' => true]);
            break;
            
        default:
            echo json_encode(['error' => 'Nieznana akcja']);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>