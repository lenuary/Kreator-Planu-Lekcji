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
        :root { 
            --primary-color: #2c3e50; 
            --secondary-color: #34495e; 
            --accent-color: #3498db; 
            --bg-light: #f4f6f9; /* Trochę ciemniejszy dla kontrastu */
        }
        body { background-color: var(--bg-light); font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; overflow-x: hidden; }
        
        /* --- LAYOUT --- */
        #wrapper { display: flex; width: 100%; overflow-x: hidden; }
        #sidebar-wrapper { min-height: 100vh; margin-left: -16rem; transition: margin .25s ease-out; background-color: var(--primary-color); color: white; width: 16rem; position: fixed; z-index: 1000; height: 100%; overflow-y: auto;}
        #sidebar-wrapper .sidebar-heading { padding: 1.2rem; font-size: 1.1rem; font-weight: bold; background-color: rgba(0,0,0,0.2); text-align: center; border-bottom: 1px solid rgba(255,255,255,0.1); }
        #sidebar-wrapper .list-group-item { background-color: transparent; color: #ecf0f1; border: none; padding: 1rem 1.5rem; cursor: pointer; }
        #sidebar-wrapper .list-group-item:hover { background-color: rgba(255,255,255,0.1); padding-left: 2rem; transition: 0.2s; }
        #sidebar-wrapper .list-group-item.active { background-color: var(--accent-color); border-left: 5px solid white; font-weight: 600; }
        
        #page-content-wrapper { width: 100%; margin-left: 0; transition: margin .25s ease-out; }
        
        #wrapper.toggled #sidebar-wrapper { margin-left: 0; }
        #wrapper.toggled #page-content-wrapper { margin-left: 16rem; } /* Pycha treść */

        /* Responsive - na małych ekranach sidebar chowany */
        @media (max-width: 768px) {
            #sidebar-wrapper { margin-left: -16rem; }
            #page-content-wrapper { margin-left: 0; }
            #wrapper.toggled #sidebar-wrapper { margin-left: 0; }
            #wrapper.toggled #page-content-wrapper { margin-left: 0; position: relative; } /* Overlay styled instead if needed */
        }

        /* --- STAT CARDS --- */
        .stat-card { border: none; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); transition: transform 0.2s; overflow: hidden; }
        .stat-card:hover { transform: translateY(-3px); }
        .stat-icon { font-size: 3rem; opacity: 0.2; position: absolute; right: 15px; bottom: 10px; }
        .card-body { position: relative; z-index: 2; }

        /* --- TABLE FIXES (CRITICAL) --- */
        .schedule-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            overflow: hidden; /* prevents spillover */
        }

        .schedule-table {
            table-layout: fixed; /* To naprawia szerokości! */
            width: 100%;
            border-collapse: collapse;
        }

        .schedule-table th {
            background-color: var(--primary-color);
            color: white;
            text-align: center;
            vertical-align: middle;
            font-weight: 600;
            padding: 12px 5px;
            border: 1px solid #34495e;
        }

        /* Ustawienie szerokości kolumn */
        .col-hour { width: 90px; } /* Stała szerokość godziny */
        /* Pozostałe 5 kolumn (dni) podzieli się resztą miejsca po równo */

        .schedule-table td {
            height: 130px; /* Minimalna wysokość komórki */
            vertical-align: top;
            padding: 0; /* Padding jest wewnątrz cell-wrap */
            border: 1px solid #dee2e6;
            background-color: #fff;
        }

        /* Wewnętrzny kontener komórki - to on obsługuje Flexbox */
        .cell-wrap {
            width: 100%;
            height: 100%;
            min-height: 130px;
            padding: 4px;
            display: flex;
            flex-wrap: wrap; /* Pozwala kafelkom spadać niżej */
            align-content: flex-start; /* Kafelki zaczynają od góry */
            gap: 4px;
            overflow-y: auto; /* Scrollbar, jeśli lekcji jest za dużo */
        }

        /* --- LESSON CARDS --- */
        .lesson-card {
            background-color: #f0f7ff;
            border-left: 4px solid var(--accent-color);
            border-radius: 4px;
            padding: 6px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            cursor: pointer;
            transition: all 0.15s ease;
            position: relative;
            width: 100%; /* Domyślnie zajmuje całą szerokość */
            min-height: 65px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .lesson-card:hover {
            background-color: #e3efff;
            transform: scale(1.02);
            z-index: 5;
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }

        /* Styl dla widoku "Wszystkie klasy" */
        .lesson-card.compact {
            /* Na dużych ekranach 2 obok siebie, na mniejszych 1 */
            width: 100%; 
            font-size: 0.85em; /* Nieco mniejsza czcionka */
            border-left-width: 3px;
        }
        
        /* Responsywność kafelków wewnątrz komórki */
        @media (min-width: 1400px) {
            /* Na bardzo szerokich ekranach, w widoku "Wszystkie", kafelki 2 w rzędzie */
            .lesson-card.compact {
                width: calc(50% - 2px); 
            }
        }

        .lesson-subject {
            font-weight: 700;
            color: #2c3e50;
            display: block;
            line-height: 1.2;
            margin-bottom: 2px;
            font-size: 1.05em;
        }
        
        .lesson-teacher {
            color: #555;
            font-size: 0.9em;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .lesson-badge {
            position: absolute;
            top: 4px;
            right: 4px;
            background-color: var(--primary-color);
            color: white;
            font-size: 0.7em;
            padding: 1px 5px;
            border-radius: 3px;
            font-weight: bold;
        }

        .lesson-room {
            font-size: 0.8em;
            color: #888;
            margin-top: auto;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .lesson-delete {
            position: absolute;
            bottom: 4px;
            right: 4px;
            color: #e74c3c;
            opacity: 0;
            transition: 0.2s;
            padding: 2px;
        }
        .lesson-card:hover .lesson-delete { opacity: 1; }

        /* Empty Slot */
        .empty-slot {
            flex-grow: 1;
            width: 100%;
            display: flex;
            justify-content: center;
            align-items: center;
            color: #e0e0e0;
            font-size: 1.8rem;
            cursor: pointer;
            transition: 0.2s;
            min-height: 100%;
        }
        .empty-slot:hover {
            background-color: rgba(52, 152, 219, 0.05);
            color: var(--accent-color);
        }

        /* Godziny */
        .hour-cell {
            background-color: #f8f9fa;
            text-align: center;
            vertical-align: middle;
            font-weight: bold;
            color: #555;
            border-bottom: 1px solid #dee2e6;
        }
        .hour-num { font-size: 1.2em; display: block; margin-bottom: 4px; color: var(--primary-color); }
        .hour-range { font-size: 0.75em; color: #777; display: block; }

    </style>
</head>
<body>

<div class="d-flex toggled" id="wrapper">
    <!-- Sidebar -->
    <div id="sidebar-wrapper">
        <div class="sidebar-heading"><i class="fas fa-graduation-cap me-2"></i>Szkolny Planer</div>
        <div class="list-group list-group-flush">
            <a class="list-group-item list-group-item-action active" onclick="showSection('dashboard')"><i class="fas fa-tachometer-alt me-3"></i>Pulpit</a>
            <a class="list-group-item list-group-item-action" onclick="showSection('schedule')"><i class="fas fa-calendar-alt me-3"></i>Plan Lekcji</a>
            <a class="list-group-item list-group-item-action" onclick="showSection('data-mgmt')"><i class="fas fa-database me-3"></i>Dane</a>
        </div>
    </div>

    <!-- Page Content -->
    <div id="page-content-wrapper">
        <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom shadow-sm px-4">
            <button class="btn btn-outline-primary btn-sm me-3" id="menu-toggle"><i class="fas fa-bars"></i></button>
            <span class="navbar-brand fs-6 fw-bold text-secondary">Panel Administratora v2.1 (Fix Layout)</span>
        </nav>

        <div class="container-fluid p-4">
            
            <!-- DASHBOARD -->
            <div id="section-dashboard" class="content-section">
                <h3 class="mb-4 text-gray-800">Pulpit</h3>
                <div class="row g-4 mb-4">
                    <div class="col-md-3">
                        <div class="card stat-card bg-primary text-white h-100">
                            <div class="card-body">
                                <h6 class="card-title text-white-50">Klasy</h6>
                                <h2 class="display-5 fw-bold mb-0" id="count-classes">0</h2>
                                <i class="fas fa-users stat-icon"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stat-card bg-success text-white h-100">
                            <div class="card-body">
                                <h6 class="card-title text-white-50">Nauczyciele</h6>
                                <h2 class="display-5 fw-bold mb-0" id="count-teachers">0</h2>
                                <i class="fas fa-chalkboard-teacher stat-icon"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stat-card bg-warning text-dark h-100">
                            <div class="card-body">
                                <h6 class="card-title text-black-50">Przedmioty</h6>
                                <h2 class="display-5 fw-bold mb-0" id="count-subjects">0</h2>
                                <i class="fas fa-book stat-icon"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stat-card bg-info text-white h-100">
                            <div class="card-body">
                                <h6 class="card-title text-white-50">Lekcje</h6>
                                <h2 class="display-5 fw-bold mb-0" id="count-entries">0</h2>
                                <i class="fas fa-calendar-check stat-icon"></i>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="alert alert-light border shadow-sm">
                    <h5><i class="fas fa-server text-success"></i> Status Połączenia</h5>
                    <p class="text-muted">Połączono z bazą danych MySQL. Wszystkie zmiany są zapisywane automatycznie.</p>
                    <button class="btn btn-outline-danger btn-sm" onclick="askClearData()">Resetuj bazę danych</button>
                </div>
            </div>

            <!-- DATA MGMT -->
            <div id="section-data-mgmt" class="content-section d-none">
                <h3 class="mb-4">Edycja Danych</h3>
                <div class="card shadow-sm">
                    <div class="card-header">
                        <ul class="nav nav-tabs card-header-tabs" id="dataTab">
                            <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#classes-tab">Klasy</a></li>
                            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#teachers-tab">Nauczyciele</a></li>
                            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#subjects-tab">Przedmioty</a></li>
                            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#rooms-tab">Sale</a></li>
                        </ul>
                    </div>
                    <div class="card-body">
                        <div class="tab-content">
                            <!-- Helper function fills this, structure is same for all -->
                            <div class="tab-pane active" id="classes-tab">
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <form onsubmit="addItem(event, 'classes', 'class-name')" class="p-3 bg-light rounded border">
                                            <label class="form-label fw-bold">Nowa Klasa</label>
                                            <div class="input-group mb-2">
                                                <input type="text" class="form-control" id="class-name" placeholder="np. 1A" required>
                                                <button class="btn btn-success" type="submit"><i class="fas fa-plus"></i></button>
                                            </div>
                                        </form>
                                    </div>
                                    <div class="col-md-8"><ul class="list-group" id="classes-list"></ul></div>
                                </div>
                            </div>
                            <div class="tab-pane" id="teachers-tab">
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <form onsubmit="addItem(event, 'teachers', 'teacher-name')" class="p-3 bg-light rounded border">
                                            <label class="form-label fw-bold">Nowy Nauczyciel</label>
                                            <div class="input-group mb-2">
                                                <input type="text" class="form-control" id="teacher-name" placeholder="Imię Nazwisko" required>
                                                <button class="btn btn-success" type="submit"><i class="fas fa-plus"></i></button>
                                            </div>
                                        </form>
                                    </div>
                                    <div class="col-md-8"><ul class="list-group" id="teachers-list"></ul></div>
                                </div>
                            </div>
                            <div class="tab-pane" id="subjects-tab">
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <form onsubmit="addItem(event, 'subjects', 'subject-name')" class="p-3 bg-light rounded border">
                                            <label class="form-label fw-bold">Nowy Przedmiot</label>
                                            <div class="input-group mb-2">
                                                <input type="text" class="form-control" id="subject-name" placeholder="Nazwa" required>
                                                <button class="btn btn-success" type="submit"><i class="fas fa-plus"></i></button>
                                            </div>
                                        </form>
                                    </div>
                                    <div class="col-md-8"><ul class="list-group" id="subjects-list"></ul></div>
                                </div>
                            </div>
                            <div class="tab-pane" id="rooms-tab">
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <form onsubmit="addItem(event, 'rooms', 'room-name')" class="p-3 bg-light rounded border">
                                            <label class="form-label fw-bold">Nowa Sala</label>
                                            <div class="input-group mb-2">
                                                <input type="text" class="form-control" id="room-name" placeholder="Numer" required>
                                                <button class="btn btn-success" type="submit"><i class="fas fa-plus"></i></button>
                                            </div>
                                        </form>
                                    </div>
                                    <div class="col-md-8"><ul class="list-group" id="rooms-list"></ul></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SCHEDULE -->
            <div id="section-schedule" class="content-section d-none">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3 class="mb-0">Plan Lekcji</h3>
                    <div class="d-flex gap-2">
                        <select class="form-select border-primary fw-bold" id="schedule-class-filter" style="min-width: 250px;" onchange="renderSchedule()">
                            <!-- JS fills this -->
                        </select>
                        <button class="btn btn-primary" onclick="openAddLessonModal()"><i class="fas fa-plus me-2"></i>Dodaj Lekcję</button>
                    </div>
                </div>

                <div class="schedule-card">
                    <div class="table-responsive">
                        <table class="table schedule-table mb-0">
                            <thead>
                                <tr>
                                    <th class="col-hour">Godz.</th>
                                    <th>Poniedziałek</th>
                                    <th>Wtorek</th>
                                    <th>Środa</th>
                                    <th>Czwartek</th>
                                    <th>Piątek</th>
                                </tr>
                            </thead>
                            <tbody id="schedule-body">
                                <!-- JS fills rows -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- Modal: Add Lesson -->
<div class="modal fade" id="addLessonModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold" id="modalTitle">Edycja Lekcji</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="addLessonForm">
                    <input type="hidden" id="lesson-id">
                    
                    <div class="mb-3">
                        <label class="form-label text-muted small fw-bold">KLASA</label>
                        <select class="form-select fw-bold" id="lesson-class" required></select>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label text-muted small fw-bold">DZIEŃ</label>
                            <select class="form-select" id="lesson-day" required>
                                <option value="1">Poniedziałek</option>
                                <option value="2">Wtorek</option>
                                <option value="3">Środa</option>
                                <option value="4">Czwartek</option>
                                <option value="5">Piątek</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label text-muted small fw-bold">GODZINA</label>
                            <select class="form-select" id="lesson-hour" required>
                                <!-- JS fills hours -->
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-muted small fw-bold">PRZEDMIOT</label>
                        <select class="form-select" id="lesson-subject" required></select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-muted small fw-bold">NAUCZYCIEL</label>
                        <select class="form-select" id="lesson-teacher" required></select>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label text-muted small fw-bold">TYP ZAJĘĆ</label>
                            <select class="form-select" id="lesson-type">
                                <option value="Wykład">Wykład</option>
                                <option value="Ćwiczenia">Ćwiczenia</option>
                                <option value="Lab">Lab</option>
                                <option value="Projekt">Projekt</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label text-muted small fw-bold">GODZINY</label>
                            <input type="text" class="form-control" id="lesson-hours" placeholder="np. 30h">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-muted small fw-bold">SALA</label>
                        <select class="form-select" id="lesson-room" required></select>
                    </div>
                </form>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-link text-muted text-decoration-none" data-bs-dismiss="modal">Anuluj</button>
                <button type="button" class="btn btn-primary px-4" onclick="saveLesson()">Zapisz</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // --- CONFIG ---
    const HOURS = [
        {id: 1, label: "08:00 - 08:45"}, {id: 2, label: "08:55 - 09:40"}, {id: 3, label: "09:50 - 10:35"},
        {id: 4, label: "10:50 - 11:35"}, {id: 5, label: "11:45 - 12:30"}, {id: 6, label: "12:40 - 13:25"},
        {id: 7, label: "13:35 - 14:20"}, {id: 8, label: "14:25 - 15:10"}
    ];
    let data = { classes: [], teachers: [], subjects: [], rooms: [], schedule: [] };

    // --- INIT ---
    window.onload = async () => {
        // Fill hours select in modal
        const hSelect = document.getElementById('lesson-hour');
        hSelect.innerHTML = HOURS.map(h => `<option value="${h.id}">${h.id}. ${h.label}</option>`).join('');
        
        await loadData();
    };

    // --- API ---
    async function loadData() {
        try {
            const res = await fetch('api.php?action=get_all');
            const json = await res.json();
            if(json.error) throw new Error(json.error);
            data = json;
            
            updateDashboard();
            renderLists();
            updateFilterDropdown();
            
            // If schedule visible, re-render
            if(!document.getElementById('section-schedule').classList.contains('d-none')) {
                renderSchedule();
            }
        } catch(e) {
            console.error(e);
            alert("Błąd: " + e.message);
        }
    }

    function updateFilterDropdown() {
        const sel = document.getElementById('schedule-class-filter');
        const curr = sel.value;
        
        let html = `<option value="" disabled ${!curr ? 'selected' : ''}>-- Wybierz widok --</option>` +
                   `<option value="all" ${curr === 'all' ? 'selected' : ''}>📅 Pokaż WSZYSTKIE klasy</option>` +
                   `<option disabled>──────────</option>`;
                   
        html += data.classes.map(c => `<option value="${c.id}" ${curr == c.id ? 'selected' : ''}>Klasa ${c.name}</option>`).join('');
        sel.innerHTML = html;
    }

    async function addItem(e, table, inputId) {
        e.preventDefault();
        const val = document.getElementById(inputId).value;
        await fetch('api.php?action=add_item', { method: 'POST', body: JSON.stringify({table, name: val}) });
        document.getElementById(inputId).value = '';
        loadData();
    }

    async function deleteItem(table, id) {
        if(!confirm("Usunąć?")) return;
        await fetch('api.php?action=delete_item', { method: 'POST', body: JSON.stringify({table, id}) });
        loadData();
    }

    async function askClearData() {
        if(confirm("To usunie WSZYSTKIE dane! Kontynuować?")) {
            await fetch('api.php?action=clear_data');
            loadData();
        }
    }

    // --- RENDERERS ---
    function showSection(id) {
        document.querySelectorAll('.content-section').forEach(e => e.classList.add('d-none'));
        document.getElementById('section-'+id).classList.remove('d-none');
        
        // Sidebar active state
        document.querySelectorAll('.list-group-item').forEach(e => e.classList.remove('active'));
        // (Optional: highlight clicked link)
        
        if(id === 'schedule') renderSchedule();
    }

    function updateDashboard() {
        document.getElementById('count-classes').innerText = data.classes.length;
        document.getElementById('count-teachers').innerText = data.teachers.length;
        document.getElementById('count-subjects').innerText = data.subjects.length;
        document.getElementById('count-entries').innerText = data.schedule.length;
    }

    function renderLists() {
        const makeList = (items, listId, table) => {
            document.getElementById(listId).innerHTML = items.map(i => `
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    ${i.name}
                    <button class="btn btn-sm btn-outline-danger" onclick="deleteItem('${table}', ${i.id})"><i class="fas fa-trash"></i></button>
                </li>`).join('');
        };
        makeList(data.classes, 'classes-list', 'classes');
        makeList(data.teachers, 'teachers-list', 'teachers');
        makeList(data.subjects, 'subjects-list', 'subjects');
        makeList(data.rooms, 'rooms-list', 'rooms');
    }

    // --- CORE SCHEDULE LOGIC ---
    function renderSchedule() {
        const filter = document.getElementById('schedule-class-filter').value;
        const tbody = document.getElementById('schedule-body');
        tbody.innerHTML = "";

        if(!filter) {
            tbody.innerHTML = `<tr><td colspan="6" class="text-center p-5 text-muted bg-light"><h5>Wybierz klasę z listy powyżej ☝️</h5></td></tr>`;
            return;
        }

        const isAll = (filter === 'all');

        HOURS.forEach(h => {
            let row = `<tr>`;
            // Hour Cell
            row += `<td class="hour-cell"><span class="hour-num">${h.id}</span><span class="hour-range">${h.label}</span></td>`;
            
            for(let d=1; d<=5; d++) {
                // Filter lessons
                let lessons = data.schedule.filter(s => s.day == d && s.hour == h.id);
                if(!isAll) lessons = lessons.filter(s => s.classId == filter);

                row += `<td><div class="cell-wrap">`;
                
                if(lessons.length > 0) {
                    lessons.forEach(l => {
                        const sub = data.subjects.find(x=>x.id==l.subjectId)?.name || '?';
                        const tea = data.teachers.find(x=>x.id==l.teacherId)?.name || '?';
                        const roo = data.rooms.find(x=>x.id==l.roomId)?.name || '?';
                        const cls = data.classes.find(x=>x.id==l.classId)?.name || '?';
                        
                        const compactClass = isAll ? 'compact' : '';
                        const badge = isAll ? `<span class="lesson-badge">${cls}</span>` : '';

                        row += `
                        <div class="lesson-card ${compactClass}" onclick="editLesson(${l.id})">
                            ${badge}
                            <div>
                                <span class="lesson-subject">${sub}</span>
                                <span class="lesson-teacher"><i class="fas fa-user-tie me-1"></i>${tea}</span>
                            </div>
                            <div class="lesson-room"><i class="fas fa-door-open"></i> ${roo}</div>
                            <i class="fas fa-times lesson-delete" onclick="event.stopPropagation(); deleteItem('schedule', ${l.id})"></i>
                        </div>`;
                    });
                }
                
                // Add button logic
                // If specific class view: always show add button if empty (or small button if not empty?)
                // If ALL view: show add button only if empty slot (to keep clean) or always small
                if(!isAll && lessons.length === 0) {
                    row += `<div class="empty-slot" onclick="quickAdd(${d}, ${h.id})"><i class="fas fa-plus"></i></div>`;
                } else if (!isAll) {
                    // row += `<div class="empty-slot small..." ...></div>` // optional: allow adding 2nd lesson
                } else if (isAll) {
                    // In ALL view, we allow adding via empty slot space
                    row += `<div class="empty-slot" style="min-height:30px; font-size:1rem; opacity:0.1;" onclick="quickAdd(${d}, ${h.id})"><i class="fas fa-plus"></i></div>`;
                }

                row += `</div></td>`;
            }
            row += `</tr>`;
            tbody.innerHTML += row;
        });
    }

    // --- MODAL LOGIC ---
    function fillSelects() {
        const fill = (id, arr) => {
            const el = document.getElementById(id);
            el.innerHTML = `<option value="" disabled selected>-- Wybierz --</option>` + 
                           arr.map(x => `<option value="${x.id}">${x.name}</option>`).join('');
        };
        fill('lesson-class', data.classes);
        fill('lesson-subject', data.subjects);
        fill('lesson-teacher', data.teachers);
        fill('lesson-room', data.rooms);
    }

    function openAddLessonModal() {
        document.getElementById('lesson-id').value = "";
        document.getElementById('addLessonForm').reset();
        fillSelects();
        
        // Auto-select class if filter is active
        const filter = document.getElementById('schedule-class-filter').value;
        if(filter && filter !== 'all') document.getElementById('lesson-class').value = filter;

        new bootstrap.Modal(document.getElementById('addLessonModal')).show();
    }

    function quickAdd(day, hour) {
        openAddLessonModal();
        document.getElementById('lesson-day').value = day;
        document.getElementById('lesson-hour').value = hour;
    }

    function editLesson(id) {
        const l = data.schedule.find(s => s.id == id);
        if(!l) return;
        openAddLessonModal();
        fillSelects(); // ensure selects are populated
        
        document.getElementById('modalTitle').innerText = "Edycja Lekcji";
        document.getElementById('lesson-id').value = l.id;
        document.getElementById('lesson-class').value = l.classId;
        document.getElementById('lesson-day').value = l.day;
        document.getElementById('lesson-hour').value = l.hour;
        document.getElementById('lesson-subject').value = l.subjectId;
        document.getElementById('lesson-teacher').value = l.teacherId;
        document.getElementById('lesson-room').value = l.roomId;
        document.getElementById('lesson-type').value = l.type || 'Wykład';
        document.getElementById('lesson-hours').value = l.hoursTotal || '';
    }

    async function saveLesson() {
        const fd = {
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

        if(!fd.classId || !fd.subjectId) return alert("Wybierz Klasę i Przedmiot!");

        // Simple conflict check
        const tBusy = data.schedule.find(s => s.teacherId == fd.teacherId && s.day == fd.day && s.hour == fd.hour && s.id != fd.id);
        const rBusy = data.schedule.find(s => s.roomId == fd.roomId && s.day == fd.day && s.hour == fd.hour && s.id != fd.id);
        
        let warn = [];
        if(tBusy) warn.push("⚠️ Ten nauczyciel ma już lekcję w tym czasie!");
        if(rBusy) warn.push("⚠️ Ta sala jest zajęta w tym czasie!");

        if(warn.length > 0) {
            if(!confirm(warn.join("\n") + "\n\nCzy na pewno zapisać?")) return;
        }

        await fetch('api.php?action=save_lesson', { method: 'POST', body: JSON.stringify(fd) });
        bootstrap.Modal.getInstance(document.getElementById('addLessonModal')).hide();
        loadData();
    }

    // Toggle Sidebar
    document.getElementById("menu-toggle").onclick = (e) => {
        e.preventDefault();
        document.getElementById("wrapper").classList.toggle("toggled");
    };
</script>
</body>
</html>
