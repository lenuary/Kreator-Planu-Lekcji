<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kreator Planu Lekcji (MySQL)</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        /* (Twoje style CSS - bez zmian, skopiowane dla kompletności) */
        :root { --primary-color: #2c3e50; --secondary-color: #34495e; --accent-color: #3498db; --bg-light: #f8f9fa; }
        body { background-color: var(--bg-light); font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; overflow-x: hidden; }
        #sidebar-wrapper { min-height: 100vh; margin-left: -15rem; transition: margin .25s ease-out; background-color: var(--primary-color); color: white; position: fixed; z-index: 1000; width: 15rem; }
        #sidebar-wrapper .sidebar-heading { padding: 1.5rem; font-size: 1.2rem; font-weight: bold; background-color: var(--secondary-color); text-align: center; border-bottom: 1px solid rgba(255,255,255,0.1); }
        #sidebar-wrapper .list-group-item { background-color: transparent; color: #ecf0f1; border: none; padding: 1rem 1.5rem; cursor: pointer; width: 100%; text-align: left; }
        #sidebar-wrapper .list-group-item:hover { background-color: rgba(255,255,255,0.1); padding-left: 2rem; }
        #sidebar-wrapper .list-group-item.active { background-color: var(--accent-color); border-left: 4px solid white; }
        #wrapper.toggled #sidebar-wrapper { margin-left: 0; }
        #page-content-wrapper { width: 100%; padding-left: 15rem; transition: padding .25s ease-out; position: relative; z-index: 1; }
        @media (max-width: 768px) { #sidebar-wrapper { margin-left: -15rem; } #page-content-wrapper { padding-left: 0; } #wrapper.toggled #sidebar-wrapper { margin-left: 0; } }
        .stat-card { border: none; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); transition: transform 0.2s; }
        .stat-card:hover { transform: translateY(-5px); }
        .stat-icon { font-size: 2.5rem; opacity: 0.3; }
        .schedule-table th { background-color: var(--primary-color); color: white; text-align: center; vertical-align: middle; font-weight: 500; }
        .col-hour { width: 10%; } .col-day { width: 18%; }
        .schedule-table td { height: 140px; vertical-align: top; padding: 0; background-color: white; border: 1px solid #dee2e6; position: relative; }
        .lesson-card { background-color: #e3f2fd; border-left: 4px solid var(--accent-color); padding: 8px; margin: 4px; border-radius: 4px; font-size: 0.8rem; box-shadow: 0 2px 4px rgba(0,0,0,0.05); cursor: pointer; transition: all 0.2s; position: relative; height: calc(100% - 8px); overflow: hidden; display: flex; flex-direction: column; justify-content: space-between; z-index: 5; }
        .lesson-card:hover { background-color: #bbdefb; transform: scale(1.02); z-index: 10; }
        .lesson-time { font-size: 0.75rem; font-weight: bold; color: var(--accent-color); margin-bottom: 2px; }
        .lesson-subject { font-weight: 700; font-size: 0.95rem; color: #2c3e50; margin-bottom: 2px; line-height: 1.2; }
        .lesson-teacher { font-size: 0.8rem; color: #34495e; margin-bottom: 4px; }
        .lesson-meta { font-size: 0.75rem; color: #7f8c8d; border-top: 1px solid rgba(0,0,0,0.1); padding-top: 4px; margin-top: auto; display: flex; justify-content: space-between; background-color: rgba(255,255,255,0.3); border-radius: 2px; padding: 2px 4px; }
        .lesson-action-btn { position: absolute; top: 2px; right: 5px; color: #e74c3c; opacity: 0.6; cursor: pointer; z-index: 20; padding: 4px; transition: opacity 0.2s; background: rgba(255,255,255,0.7); border-radius: 50%; }
        .lesson-card:hover .lesson-action-btn { opacity: 1; }
        .empty-slot { width: 100%; height: 100%; display: flex; justify-content: center; align-items: center; color: #e0e0e0; font-size: 2rem; cursor: pointer; transition: all 0.2s; background-color: transparent; position: relative; z-index: 1; }
        .empty-slot:hover { background-color: #f1f8ff; color: var(--accent-color); }
    </style>
</head>
<body>

<div class="d-flex toggled" id="wrapper">
    <!-- Sidebar -->
    <div class="bg-dark border-right" id="sidebar-wrapper">
        <div class="sidebar-heading"><i class="fas fa-graduation-cap me-2"></i>Szkolny Planer</div>
        <div class="list-group list-group-flush">
            <button class="list-group-item list-group-item-action bg-dark text-light active" onclick="showSection('dashboard')"><i class="fas fa-tachometer-alt me-2"></i> Pulpit</button>
            <button class="list-group-item list-group-item-action bg-dark text-light" onclick="showSection('schedule')"><i class="fas fa-calendar-alt me-2"></i> Plan Lekcji</button>
            <button class="list-group-item list-group-item-action bg-dark text-light" onclick="showSection('data-mgmt')"><i class="fas fa-database me-2"></i> Dane</button>
        </div>
    </div>

    <!-- Page Content -->
    <div id="page-content-wrapper">
        <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom shadow-sm">
            <div class="container-fluid">
                <button class="btn btn-primary btn-sm" id="menu-toggle"><i class="fas fa-bars"></i> Menu</button>
                <span class="navbar-brand ms-3 fs-6 text-muted">System PHP/MySQL v1.0</span>
            </div>
        </nav>

        <div class="container-fluid p-4">
            <!-- DASHBOARD -->
            <div id="section-dashboard" class="content-section">
                <h2 class="mb-4">Przegląd systemu</h2>
                <div class="row">
                    <div class="col-md-3 mb-4"><div class="card stat-card bg-primary text-white h-100"><div class="card-body d-flex justify-content-between align-items-center"><div><h5 class="card-title">Klasy</h5><h2 class="display-4" id="count-classes">0</h2></div><i class="fas fa-users stat-icon"></i></div></div></div>
                    <div class="col-md-3 mb-4"><div class="card stat-card bg-success text-white h-100"><div class="card-body d-flex justify-content-between align-items-center"><div><h5 class="card-title">Nauczyciele</h5><h2 class="display-4" id="count-teachers">0</h2></div><i class="fas fa-chalkboard-teacher stat-icon"></i></div></div></div>
                    <div class="col-md-3 mb-4"><div class="card stat-card bg-warning text-dark h-100"><div class="card-body d-flex justify-content-between align-items-center"><div><h5 class="card-title">Przedmioty</h5><h2 class="display-4" id="count-subjects">0</h2></div><i class="fas fa-book stat-icon"></i></div></div></div>
                    <div class="col-md-3 mb-4"><div class="card stat-card bg-info text-white h-100"><div class="card-body d-flex justify-content-between align-items-center"><div><h5 class="card-title">Lekcje</h5><h2 class="display-4" id="count-entries">0</h2></div><i class="fas fa-calendar-check stat-icon"></i></div></div></div>
                </div>
                <div class="alert alert-success shadow-sm">
                    <h4><i class="fas fa-database"></i> Połączono z bazą danych</h4>
                    <p class="mb-0">Aplikacja działa w trybie online. Wszystkie zmiany są zapisywane w bazie MySQL.</p>
                    <hr>
                    <button class="btn btn-outline-danger btn-sm" onclick="askClearData()">Wyczyść całą bazę danych</button>
                </div>
            </div>

            <!-- DATA MANAGEMENT -->
            <div id="section-data-mgmt" class="content-section d-none">
                <h2 class="mb-4">Zarządzanie danymi</h2>
                <ul class="nav nav-tabs mb-3" id="dataTab" role="tablist">
                    <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#classes-content">Klasy</button></li>
                    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#teachers-content">Nauczyciele</button></li>
                    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#subjects-content">Przedmioty</button></li>
                    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#rooms-content">Sale</button></li>
                </ul>
                <div class="tab-content">
                    <!-- Templates for Data Tabs -->
                    <div class="tab-pane fade show active" id="classes-content">
                        <div class="row">
                            <div class="col-md-4"><div class="card shadow-sm"><div class="card-header bg-light">Dodaj Klasę</div><div class="card-body"><form onsubmit="addItem(event, 'classes', 'class-name')"><div class="mb-3"><label class="form-label">Nazwa</label><input type="text" class="form-control" id="class-name" required></div><button type="submit" class="btn btn-success w-100">Zapisz</button></form></div></div></div>
                            <div class="col-md-8"><div class="card shadow-sm"><div class="card-header bg-light">Lista Klas</div><ul class="list-group list-group-flush" id="classes-list"></ul></div></div>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="teachers-content">
                        <div class="row">
                            <div class="col-md-4"><div class="card shadow-sm"><div class="card-header bg-light">Dodaj Nauczyciela</div><div class="card-body"><form onsubmit="addItem(event, 'teachers', 'teacher-name')"><div class="mb-3"><label class="form-label">Nazwisko</label><input type="text" class="form-control" id="teacher-name" required></div><button type="submit" class="btn btn-success w-100">Zapisz</button></form></div></div></div>
                            <div class="col-md-8"><ul class="list-group list-group-flush" id="teachers-list"></ul></div>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="subjects-content">
                        <div class="row">
                            <div class="col-md-4"><div class="card shadow-sm"><div class="card-header bg-light">Dodaj Przedmiot</div><div class="card-body"><form onsubmit="addItem(event, 'subjects', 'subject-name')"><div class="mb-3"><label class="form-label">Nazwa</label><input type="text" class="form-control" id="subject-name" required></div><button type="submit" class="btn btn-success w-100">Zapisz</button></form></div></div></div>
                            <div class="col-md-8"><ul class="list-group list-group-flush" id="subjects-list"></ul></div>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="rooms-content">
                        <div class="row">
                            <div class="col-md-4"><div class="card shadow-sm"><div class="card-header bg-light">Dodaj Salę</div><div class="card-body"><form onsubmit="addItem(event, 'rooms', 'room-name')"><div class="mb-3"><label class="form-label">Numer</label><input type="text" class="form-control" id="room-name" required></div><button type="submit" class="btn btn-success w-100">Zapisz</button></form></div></div></div>
                            <div class="col-md-8"><ul class="list-group list-group-flush" id="rooms-list"></ul></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SCHEDULE -->
            <div id="section-schedule" class="content-section d-none">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>Plan Lekcji</h2>
                    <div class="d-flex gap-2">
                        <select class="form-select" id="schedule-class-filter" onchange="renderSchedule()">
                            <option value="">-- Wybierz klasę --</option>
                        </select>
                        <button class="btn btn-primary" onclick="openAddLessonModal()"><i class="fas fa-plus"></i> Dodaj lekcję</button>
                    </div>
                </div>
                <div class="card shadow-sm">
                    <div class="card-body p-0 table-responsive">
                        <table class="table table-bordered schedule-table mb-0">
                            <thead><tr><th class="col-hour">Godzina</th><th class="col-day">Poniedziałek</th><th class="col-day">Wtorek</th><th class="col-day">Środa</th><th class="col-day">Czwartek</th><th class="col-day">Piątek</th></tr></thead>
                            <tbody id="schedule-body"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Add/Edit Lesson -->
<div class="modal fade" id="addLessonModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title" id="modalTitle">Dodaj lekcję</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <form id="addLessonForm">
                    <input type="hidden" id="lesson-id">
                    <div class="mb-3"><label class="form-label">Klasa</label><select class="form-select" id="lesson-class" required></select></div>
                    <div class="row">
                        <div class="col-6 mb-3"><label class="form-label">Dzień</label><select class="form-select" id="lesson-day" required><option value="1">Poniedziałek</option><option value="2">Wtorek</option><option value="3">Środa</option><option value="4">Czwartek</option><option value="5">Piątek</option></select></div>
                        <div class="col-6 mb-3"><label class="form-label">Godzina</label><select class="form-select" id="lesson-hour" required><option value="1">1. 08:00-08:45</option><option value="2">2. 08:55-09:40</option><option value="3">3. 09:50-10:35</option><option value="4">4. 10:50-11:35</option><option value="5">5. 11:45-12:30</option><option value="6">6. 12:40-13:25</option><option value="7">7. 13:35-14:20</option><option value="8">8. 14:25-15:10</option></select></div>
                    </div>
                    <div class="mb-3"><label class="form-label">Przedmiot</label><select class="form-select" id="lesson-subject" required></select></div>
                    <div class="mb-3"><label class="form-label">Nauczyciel</label><select class="form-select" id="lesson-teacher" required></select></div>
                    <div class="row">
                        <div class="col-6 mb-3"><label class="form-label">Typ</label><select class="form-select" id="lesson-type"><option value="Wykład">Wykład</option><option value="Laboratorium">Laboratorium</option><option value="Projekt">Projekt</option><option value="Ćwiczenia">Ćwiczenia</option></select></div>
                        <div class="col-6 mb-3"><label class="form-label">Godziny</label><input type="text" class="form-control" id="lesson-hours" placeholder="np. 30h"></div>
                    </div>
                    <div class="mb-3"><label class="form-label">Sala</label><select class="form-select" id="lesson-room" required></select></div>
                </form>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anuluj</button><button type="button" class="btn btn-primary" onclick="saveLesson()">Zapisz</button></div>
        </div>
    </div>
</div>

<!-- Simple Msg Modal -->
<div class="modal fade" id="msgModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content"><div class="modal-body" id="msgBody"></div><div class="modal-footer"><button type="button" class="btn btn-primary" data-bs-dismiss="modal">OK</button></div></div></div></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // --- GLOBAL DATA STATE ---
    let data = { classes: [], teachers: [], subjects: [], rooms: [], schedule: [] };
    const HOURS = [
        {id: 1, label: "08:00 - 08:45"}, {id: 2, label: "08:55 - 09:40"}, {id: 3, label: "09:50 - 10:35"},
        {id: 4, label: "10:50 - 11:35"}, {id: 5, label: "11:45 - 12:30"}, {id: 6, label: "12:40 - 13:25"},
        {id: 7, label: "13:35 - 14:20"}, {id: 8, label: "14:25 - 15:10"}
    ];

    // --- API COMMUNICATION ---
    async function loadData() {
        try {
            const response = await fetch('api.php?action=get_all');
            const result = await response.json();
            if(result.error) throw new Error(result.error);
            
            data = result;
            updateDashboard();
            renderLists();
            
            // If we are on schedule view, re-render it
            if(!document.getElementById('section-schedule').classList.contains('d-none')) {
                updateSelectOptions('schedule-class-filter', data.classes);
                renderSchedule();
            }
        } catch (e) {
            console.error(e);
            alert("Błąd ładowania danych z bazy: " + e.message);
        }
    }

    async function addItem(e, table, inputId) {
        e.preventDefault();
        const nameVal = document.getElementById(inputId).value;
        
        try {
            const res = await fetch('api.php?action=add_item', {
                method: 'POST',
                body: JSON.stringify({ table: table, name: nameVal })
            });
            const json = await res.json();
            if(json.success) {
                document.getElementById(inputId).value = '';
                loadData(); // Reload all data to refresh IDs
            }
        } catch(e) { console.error(e); }
    }

    async function deleteItem(table, id) {
        if(!confirm("Czy na pewno chcesz usunąć?")) return;
        try {
            await fetch('api.php?action=delete_item', {
                method: 'POST',
                body: JSON.stringify({ table: table, id: id })
            });
            loadData();
        } catch(e) { console.error(e); }
    }
    
    async function askClearData() {
        if(!confirm("UWAGA: To usunie WSZYSTKIE dane z bazy. Kontynuować?")) return;
        await fetch('api.php?action=clear_data');
        loadData();
    }

    // --- UI RENDERERS ---
    function showSection(sectionId) {
        document.querySelectorAll('.content-section').forEach(el => el.classList.add('d-none'));
        document.getElementById('section-' + sectionId).classList.remove('d-none');
        document.querySelectorAll('.list-group-item').forEach(el => el.classList.remove('active'));
        event.currentTarget.classList.add('active');
        if(sectionId === 'schedule') {
            updateSelectOptions('schedule-class-filter', data.classes);
            renderSchedule();
        }
    }

    function updateDashboard() {
        document.getElementById('count-classes').innerText = data.classes.length;
        document.getElementById('count-teachers').innerText = data.teachers.length;
        document.getElementById('count-subjects').innerText = data.subjects.length;
        document.getElementById('count-entries').innerText = data.schedule.length;
    }

    function renderLists() {
        const render = (items, listId, table) => {
            const list = document.getElementById(listId);
            list.innerHTML = "";
            items.forEach(item => {
                list.innerHTML += `
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        ${item.name}
                        <button class="btn btn-sm btn-outline-danger" onclick="deleteItem('${table}', ${item.id})"><i class="fas fa-trash"></i></button>
                    </li>`;
            });
        };
        render(data.classes, 'classes-list', 'classes');
        render(data.teachers, 'teachers-list', 'teachers');
        render(data.subjects, 'subjects-list', 'subjects');
        render(data.rooms, 'rooms-list', 'rooms');
    }

    function updateSelectOptions(elementId, items) {
        const select = document.getElementById(elementId);
        if(!select) return;
        const currentVal = select.value;
        let placeholder = select.options.length > 0 ? select.options[0].outerHTML : '<option value="">-- Wybierz --</option>';
        select.innerHTML = placeholder + items.map(i => `<option value="${i.id}">${i.name}</option>`).join('');
        select.value = currentVal;
    }

    // --- SCHEDULE LOGIC ---
    function renderSchedule() {
        const filterClassId = document.getElementById('schedule-class-filter').value;
        const tbody = document.getElementById('schedule-body');
        tbody.innerHTML = "";

        if (!filterClassId) {
            tbody.innerHTML = `<tr><td colspan="6" class="text-center py-5 text-muted">Wybierz klasę z listy.</td></tr>`;
            return;
        }

        HOURS.forEach(hour => {
            let rowHtml = `<tr><td class="align-middle text-center bg-light"><strong>${hour.id}</strong><br><small>${hour.label}</small></td>`;
            for(let day=1; day<=5; day++) {
                const lessons = data.schedule.filter(s => s.classId == filterClassId && s.day == day && s.hour == hour.id);
                rowHtml += `<td>`;
                if(lessons.length > 0) {
                    lessons.forEach(l => {
                        const sub = data.subjects.find(x => x.id == l.subjectId)?.name || '?';
                        const teach = data.teachers.find(x => x.id == l.teacherId)?.name || '?';
                        const room = data.rooms.find(x => x.id == l.roomId)?.name || '?';
                        rowHtml += `
                            <div class="lesson-card" onclick="editLesson(${l.id})">
                                <div>
                                    <span class="lesson-time">${hour.label}</span>
                                    <span class="lesson-subject">${sub}</span>
                                    <div class="lesson-teacher"><i class="fas fa-user-tie"></i> ${teach}</div>
                                </div>
                                <div class="lesson-meta">
                                    <span>${l.type || '-'} (${l.hoursTotal || '-'})</span>
                                    <span><i class="fas fa-door-open"></i> ${room}</span>
                                </div>
                                <i class="fas fa-times lesson-action-btn" onclick="event.stopPropagation(); deleteItem('schedule', ${l.id})"></i>
                            </div>`;
                    });
                } else {
                    rowHtml += `<div class="empty-slot" onclick="quickAdd(${day}, ${hour.id})"><i class="fas fa-plus"></i></div>`;
                }
                rowHtml += `</td>`;
            }
            tbody.innerHTML += rowHtml + `</tr>`;
        });
    }

    // --- LESSON CRUD ---
    function openAddLessonModal() {
        document.getElementById('modalTitle').innerText = "Dodaj lekcję";
        document.getElementById('lesson-id').value = "";
        document.getElementById('addLessonForm').reset();
        
        updateSelectOptions('lesson-class', data.classes);
        updateSelectOptions('lesson-subject', data.subjects);
        updateSelectOptions('lesson-teacher', data.teachers);
        updateSelectOptions('lesson-room', data.rooms);
        
        const filter = document.getElementById('schedule-class-filter').value;
        if(filter) document.getElementById('lesson-class').value = filter;
        
        const m = bootstrap.Modal.getOrCreateInstance(document.getElementById('addLessonModal'));
        m.show();
    }

    function quickAdd(day, hour) {
        openAddLessonModal();
        document.getElementById('lesson-day').value = day;
        document.getElementById('lesson-hour').value = hour;
    }

    function editLesson(id) {
        const lesson = data.schedule.find(s => s.id == id);
        if(!lesson) return;
        
        openAddLessonModal();
        document.getElementById('modalTitle').innerText = "Edytuj lekcję";
        document.getElementById('lesson-id').value = lesson.id;
        document.getElementById('lesson-class').value = lesson.classId;
        document.getElementById('lesson-day').value = lesson.day;
        document.getElementById('lesson-hour').value = lesson.hour;
        document.getElementById('lesson-subject').value = lesson.subjectId;
        document.getElementById('lesson-teacher').value = lesson.teacherId;
        document.getElementById('lesson-room').value = lesson.roomId;
        document.getElementById('lesson-type').value = lesson.type;
        document.getElementById('lesson-hours').value = lesson.hoursTotal;
    }

    async function saveLesson() {
        const formData = {
            id: document.getElementById('lesson-id').value,
            classId: document.getElementById('lesson-class').value,
            day: document.getElementById('lesson-day').value,
            hour: document.getElementById('lesson-hour').value,
            subjectId: document.getElementById('lesson-subject').value,
            teacherId: document.getElementById('lesson-teacher').value,
            roomId: document.getElementById('lesson-room').value,
            type: document.getElementById('lesson-type').value,
            hoursTotal: document.getElementById('lesson-hours').value
        };

        if(!formData.classId || !formData.subjectId) { alert("Wypełnij wymagane pola!"); return; }

        // Conflict Checks
        const teacherBusy = data.schedule.find(s => s.teacherId == formData.teacherId && s.day == formData.day && s.hour == formData.hour && s.id != formData.id);
        const roomBusy = data.schedule.find(s => s.roomId == formData.roomId && s.day == formData.day && s.hour == formData.hour && s.id != formData.id);
        
        let warnings = [];
        if(teacherBusy) warnings.push("Nauczyciel jest zajęty.");
        if(roomBusy) warnings.push("Sala jest zajęta.");
        
        if(warnings.length > 0 && !confirm(warnings.join("\n") + "\nZapisać mimo to?")) return;

        try {
            const res = await fetch('api.php?action=save_lesson', {
                method: 'POST',
                body: JSON.stringify(formData)
            });
            await loadData();
            bootstrap.Modal.getInstance(document.getElementById('addLessonModal')).hide();
            
            // Auto switch view to the edited class
            document.getElementById('schedule-class-filter').value = formData.classId;
            renderSchedule();
            
        } catch(e) { console.error(e); }
    }

    document.getElementById("menu-toggle").onclick = (e) => { e.preventDefault(); document.getElementById("wrapper").classList.toggle("toggled"); };
    window.onload = loadData;

</script>
</body>
</html>