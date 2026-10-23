<?php
header('Content-Type: text/html; charset=utf-8');

$host = getenv('DB_HOST') ?: 'db';
$db   = getenv('DB_NAME') ?: 'podcast_db';
$user = getenv('DB_USER') ?: 'podcast_user';
$pass = getenv('DB_PASS') ?: 'User_Strong_Password_123!';

$message = '';
$message_type = '';
$categories = [];
$podcasts = [];

try {
    // Kết nối CSDL chuẩn UTF-8
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
    ]);

    // 1. Thêm danh mục mới
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_category') {
        $cat_name = trim($_POST['category_name'] ?? '');
        if (!empty($cat_name)) {
            $stmt = $pdo->prepare("INSERT INTO categories (name) VALUES (?)");
            $stmt->execute([$cat_name]);
            $message = "Thêm danh mục thành công!";
            $message_type = "success";
        }
    }

    // 2. Thêm tập podcast mới
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_podcast') {
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $audio_url = trim($_POST['audio_url'] ?? '');
        $category_id = intval($_POST['category_id'] ?? 0);

        if (!empty($title) && !empty($audio_url) && $category_id > 0) {
            $stmt = $pdo->prepare("INSERT INTO podcasts (title, description, audio_url, category_id) VALUES (?, ?, ?, ?)");
            $stmt->execute([$title, $description, $audio_url, $category_id]);
            $message = "Đăng tập podcast thành công!";
            $message_type = "success";
        } else {
            $message = "Vui lòng nhập đầy đủ thông tin bắt buộc!";
            $message_type = "error";
        }
    }

    // 3. Xóa tập podcast
    if (isset($_GET['delete_podcast'])) {
        $id = intval($_GET['delete_podcast']);
        $stmt = $pdo->prepare("DELETE FROM podcasts WHERE id = ?");
        $stmt->execute([$id]);
        header("Location: index.php");
        exit;
    }

    // Lấy danh sách danh mục
    $categories = $pdo->query("SELECT * FROM categories ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);

    // Lấy danh sách podcast
    $filter_category = intval($_GET['cat'] ?? 0);
    if ($filter_category > 0) {
        $stmt = $pdo->prepare("SELECT p.*, c.name as category_name FROM podcasts p LEFT JOIN categories c ON p.category_id = c.id WHERE p.category_id = ? ORDER BY p.id DESC");
        $stmt->execute([$filter_category]);
    } else {
        $stmt = $pdo->query("SELECT p.*, c.name as category_name FROM podcasts p LEFT JOIN categories c ON p.category_id = c.id ORDER BY p.id DESC");
    }
    $podcasts = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $db_error = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Website Podcast & Media Library</title>
    <!-- Google Fonts & Font Awesome Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --bg-body: #f4f6f9;
            --bg-card: #ffffff;
            --bg-header: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            --text-primary: #1e293b;
            --text-secondary: #64748b;
            --border-color: #e2e8f0;
            --accent-color: #2563eb;
            --accent-hover: #1d4ed8;
            --player-bg: #1e293b;
            --player-text: #ffffff;
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --radius: 12px;
        }

        [data-theme="dark"] {
            --bg-body: #0f172a;
            --bg-card: #1e293b;
            --bg-header: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            --text-primary: #f8fafc;
            --text-secondary: #94a3b8;
            --border-color: #334155;
            --accent-color: #3b82f6;
            --accent-hover: #60a5fa;
            --player-bg: #020617;
            --player-text: #f8fafc;
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.3);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Inter', sans-serif;
            transition: background-color 0.3s, color 0.3s, border-color 0.3s;
        }

        body {
            background-color: var(--bg-body);
            color: var(--text-primary);
            padding-bottom: 110px;
            min-height: 100vh;
        }

        /* Header Header */
        header {
            background: var(--bg-header);
            color: white;
            padding: 2rem 1.5rem;
            position: relative;
            box-shadow: var(--shadow);
        }

        .header-content {
            max-width: 1280px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header-title h1 {
            font-size: 1.8rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .header-title p {
            font-size: 0.9rem;
            opacity: 0.85;
            margin-top: 4px;
        }

        .theme-toggle {
            background: rgba(255, 255, 255, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.25);
            color: white;
            width: 42px;
            height: 42px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
        }

        /* Layout Grid */
        .container {
            max-width: 1280px;
            margin: 2rem auto;
            padding: 0 1.5rem;
            display: grid;
            grid-template-columns: 340px 1fr;
            gap: 2rem;
        }

        @media (max-width: 992px) {
            .container {
                grid-template-columns: 1fr;
            }
        }

        /* Cards Style */
        .card {
            background: var(--bg-card);
            border-radius: var(--radius);
            padding: 1.5rem;
            box-shadow: var(--shadow);
            border: 1px solid var(--border-color);
            margin-bottom: 1.5rem;
        }

        .card-title {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 1.2rem;
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--accent-color);
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 0.8rem;
        }

        /* Forms */
        .form-group {
            margin-bottom: 1rem;
        }

        .form-group label {
            display: block;
            font-size: 0.85rem;
            font-weight: 500;
            margin-bottom: 0.4rem;
            color: var(--text-secondary);
        }

        .form-control {
            width: 100%;
            padding: 0.75rem;
            border-radius: 8px;
            border: 1px solid var(--border-color);
            background: var(--bg-body);
            color: var(--text-primary);
            font-size: 0.9rem;
            outline: none;
        }

        .form-control:focus {
            border-color: var(--accent-color);
        }

        textarea.form-control {
            resize: vertical;
            min-height: 80px;
        }

        .btn {
            width: 100%;
            padding: 0.75rem;
            border: none;
            border-radius: 8px;
            background: var(--accent-color);
            color: white;
            font-weight: 600;
            font-size: 0.9rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn:hover {
            background: var(--accent-hover);
        }

        /* Filter Controls */
        .controls-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .filter-pills {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        .pill {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            color: var(--text-secondary);
            font-size: 0.85rem;
            font-weight: 500;
            cursor: pointer;
        }

        .pill.active {
            background: var(--accent-color);
            color: white;
            border-color: var(--accent-color);
        }

        .search-box {
            position: relative;
            min-width: 240px;
        }

        .search-box input {
            padding-left: 2.2rem;
        }

        .search-box i {
            position: absolute;
            left: 0.8rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-secondary);
            font-size: 0.85rem;
        }

        /* View Toggle Switch */
        .view-options {
            display: flex;
            gap: 4px;
            background: var(--bg-body);
            padding: 3px;
            border-radius: 8px;
            border: 1px solid var(--border-color);
        }

        .view-btn {
            background: transparent;
            border: none;
            color: var(--text-secondary);
            padding: 6px 10px;
            border-radius: 6px;
            cursor: pointer;
        }

        .view-btn.active {
            background: var(--bg-card);
            color: var(--accent-color);
            box-shadow: var(--shadow);
        }

        /* Podcast Display List */
        .podcast-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1.2rem;
        }

        .podcast-grid.list-view {
            grid-template-columns: 1fr;
        }

        .podcast-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: var(--radius);
            padding: 1.2rem;
            box-shadow: var(--shadow);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
        }

        .podcast-card:hover {
            border-color: var(--accent-color);
        }

        .badge {
            display: inline-block;
            padding: 0.25rem 0.6rem;
            border-radius: 6px;
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            background: rgba(37, 99, 235, 0.1);
            color: var(--accent-color);
            margin-bottom: 0.8rem;
            align-self: flex-start;
        }

        .podcast-card-title {
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            line-height: 1.4;
        }

        .podcast-card-desc {
            font-size: 0.85rem;
            color: var(--text-secondary);
            margin-bottom: 1rem;
            line-height: 1.5;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .podcast-card-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-top: 1px solid var(--border-color);
            padding-top: 0.8rem;
            margin-top: auto;
        }

        .play-btn-card {
            background: var(--accent-color);
            color: white;
            border: none;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }

        .delete-btn-card {
            background: transparent;
            border: none;
            color: #ef4444;
            cursor: pointer;
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        /* Sticky Bottom Audio Player */
        .bottom-player {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: var(--player-bg);
            color: var(--player-text);
            padding: 0.8rem 1.5rem;
            box-shadow: 0 -4px 15px rgba(0, 0, 0, 0.2);
            z-index: 100;
        }

        .player-container {
            max-width: 1280px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 280px 1fr 180px;
            align-items: center;
            gap: 1.5rem;
        }

        @media (max-width: 768px) {
            .player-container {
                grid-template-columns: 1fr;
                gap: 0.5rem;
            }
            .player-volume { display: none; }
        }

        .player-info {
            display: flex;
            align-items: center;
            gap: 12px;
            overflow: hidden;
        }

        .player-icon {
            width: 44px;
            height: 44px;
            background: var(--accent-color);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            flex-shrink: 0;
        }

        .player-text {
            overflow: hidden;
        }

        .player-now-playing {
            font-size: 0.9rem;
            font-weight: 600;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .player-category {
            font-size: 0.75rem;
            opacity: 0.7;
        }

        .player-controls {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 6px;
        }

        .player-buttons {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .btn-player-icon {
            background: transparent;
            border: none;
            color: white;
            font-size: 1.1rem;
            cursor: pointer;
            opacity: 0.8;
        }

        .btn-player-icon:hover { opacity: 1; }

        .btn-play-main {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: white;
            color: black;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 1rem;
        }

        .progress-bar-container {
            width: 100%;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.75rem;
            opacity: 0.8;
        }

        .progress-bar {
            flex: 1;
            height: 4px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 2px;
            position: relative;
            cursor: pointer;
        }

        .progress-fill {
            height: 100%;
            background: var(--accent-color);
            border-radius: 2px;
            width: 0%;
        }

        .player-volume {
            display: flex;
            align-items: center;
            gap: 8px;
            justify-content: flex-end;
        }

        .volume-slider {
            width: 80px;
            accent-color: var(--accent-color);
        }
    </style>
</head>
<body>

    <!-- Header Header -->
    <header>
        <div class="header-content">
            <div class="header-title">
                <h1><i class="fa-solid => fa-podcast"></i> Website Podcast & Media Library</h1>
                <p>Khám phá, quản lý và lắng nghe kiến thức mọi lúc mọi nơi</p>
            </div>
            <button class="theme-toggle" id="themeToggle" title="Chuyển chế độ Sáng/Tối">
                <i class="fa-solid fa-moon"></i>
            </button>
        </div>
    </header>

    <div class="container">
        <!-- Sidebar trái: Form Quản lý -->
        <aside class="sidebar">
            <!-- Quản lý danh mục -->
            <div class="card">
                <div class="card-title">
                    <i class="fa-solid fa-folder-plus"></i> Quản Lý Danh Mục
                </div>
                <div class="form-group">
                    <label for="catName">Tên danh mục mới</label>
                    <input type="text" id="catName" class="form-control" placeholder="VD: Khoa Học, Tâm Lý...">
                </div>
                <button class="btn" id="addCatBtn">
                    <i class="fa-solid fa-plus"></i> Thêm Danh Mục
                </button>
            </div>

            <!-- Thêm tập podcast mới -->
            <div class="card">
                <div class="card-title">
                    <i class="fa-solid fa-square-plus"></i> Thêm Tập Podcast Mới
                </div>
                <div class="form-group">
                    <label for="podTitle">Tiêu đề tập Podcast</label>
                    <input type="text" id="podTitle" class="form-control" placeholder="VD: Tập 1: Học Docker cơ bản">
                </div>
                <div class="form-group">
                    <label for="podCategory">Danh mục</label>
                    <select id="podCategory" class="form-control">
                        <!-- Sẽ tự động load qua JavaScript -->
                    </select>
                </div>
                <div class="form-group">
                    <label for="podUrl">Đường dẫn file Audio (.mp3)</label>
                    <input type="text" id="podUrl" class="form-control" placeholder="https://domain.com/audio.mp3">
                </div>
                <div class="form-group">
                    <label for="podDesc">Mô tả chi tiết</label>
                    <textarea id="podDesc" class="form-control" placeholder="Nhập tóm tắt nội dung podcast..."></textarea>
                </div>
                <button class="btn" id="addPodBtn">
                    <i class="fa-solid fa-cloud-arrow-up"></i> Đăng Tập Podcast
                </button>
            </div>
        </aside>

        <!-- Main Content phải: Danh sách Thư viện -->
        <main class="main-content">
            <div class="controls-bar">
                <div class="filter-pills" id="categoryFilter">
                    <!-- Dynamic Pills -->
                </div>

                <div style="display: flex; gap: 10px; align-items: center;">
                    <div class="search-box">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <input type="text" id="searchInput" class="form-control" placeholder="Tìm kiếm podcast...">
                    </div>
                    <div class="view-options">
                        <button class="view-btn active" id="gridViewBtn" title="Xem dạng Lưới"><i class="fa-solid fa-border-all"></i></button>
                        <button class="view-btn" id="listViewBtn" title="Xem dạng Danh sách"><i class="fa-solid fa-list"></i></button>
                    </div>
                </div>
            </div>

            <div class="podcast-grid" id="podcastContainer">
                <!-- PodCast items sẽ hiển thị bằng JS -->
            </div>
        </main>
    </div>

    <!-- Floating Audio Player -->
    <div class="bottom-player">
        <div class="player-container">
            <div class="player-info">
                <div class="player-icon">
                    <i class="fa-solid fa-music"></i>
                </div>
                <div class="player-text">
                    <div class="player-now-playing" id="nowPlayingTitle">Chọn bài để phát</div>
                    <div class="player-category" id="nowPlayingCat">Sẵn sàng</div>
                </div>
            </div>

            <div class="player-controls">
                <div class="player-buttons">
                    <button class="btn-player-icon" id="seekPrev" title="Tua lùi 10s"><i class="fa-solid fa-rotate-left"></i></button>
                    <button class="btn-play-main" id="mainPlayBtn"><i class="fa-solid fa-play"></i></button>
                    <button class="btn-player-icon" id="seekNext" title="Tua tới 10s"><i class="fa-solid fa-rotate-right"></i></button>
                </div>
                <div class="progress-bar-container">
                    <span id="currentTime">0:00</span>
                    <div class="progress-bar" id="progressBar">
                        <div class="progress-fill" id="progressFill"></div>
                    </div>
                    <span id="durationTime">0:00</span>
                </div>
            </div>

            <div class="player-volume">
                <i class="fa-solid fa-volume-high" style="font-size: 0.85rem;"></i>
                <input type="range" class="volume-slider" id="volumeSlider" min="0" max="1" step="0.1" value="0.8">
            </div>
        </div>
    </div>

    <!-- Hidden Native Audio Element -->
    <audio id="globalAudio"></audio>

    <script>
        // Dữ liệu mẫu ban đầu
        const initialCategories = ["Kỹ Năng Sống & Phát Triển", "Âm Nhạc & Thư Giãn", "Công Nghệ & DevOps"];
        
        const initialPodcasts = [
            {
                id: 1,
                title: "Tập 3: Kỹ Năng Quản Lý Thời Gian",
                category: "Kỹ Năng Sống & Phát Triển",
                url: "https://www.soundhelix.com/examples/mp3/SoundHelix-Song-1.mp3",
                desc: "Phương pháp sắp xếp thời gian hiệu quả và tối ưu hóa năng suất làm việc hằng ngày."
            },
            {
                id: 2,
                title: "Tập 2: Nhạc Piano Không Lời Trung Lẫm",
                category: "Âm Nhạc & Thư Giãn",
                url: "https://www.soundhelix.com/examples/mp3/SoundHelix-Song-2.mp3",
                desc: "Giai điệu piano nhẹ nhàng giúp nâng cao khả năng tập trung sâu và giải tỏa căng thẳng."
            },
            {
                id: 3,
                title: "Tập 1: Giá Trị Thực Sự Docker Và Container",
                category: "Công Nghệ & DevOps",
                url: "https://www.soundhelix.com/examples/mp3/SoundHelix-Song-3.mp3",
                desc: "Hướng dẫn căn bản về Docker, Nginx, MySQL phục vụ cho việc đóng gói ứng dụng."
            }
        ];

        // Khởi tạo State từ LocalStorage
        let categories = JSON.parse(localStorage.getItem('podcast_categories')) || initialCategories;
        let podcasts = JSON.parse(localStorage.getItem('podcast_items')) || initialPodcasts;
        let selectedCategory = "Tất cả";
        let currentPlayingId = null;

        // Elements
        const audio = document.getElementById('globalAudio');
        const themeToggle = document.getElementById('themeToggle');
        const podCategorySelect = document.getElementById('podCategory');
        const categoryFilter = document.getElementById('categoryFilter');
        const podcastContainer = document.getElementById('podcastContainer');
        const searchInput = document.getElementById('searchInput');

        // Dark/Light Theme Switch
        themeToggle.addEventListener('click', () => {
            const isDark = document.body.getAttribute('data-theme') === 'dark';
            if(isDark) {
                document.body.removeAttribute('data-theme');
                themeToggle.innerHTML = '<i class="fa-solid fa-moon"></i>';
            } else {
                document.body.setAttribute('data-theme', 'dark');
                themeToggle.innerHTML = '<i class="fa-solid fa-sun"></i>';
            }
        });

        // Render Danh mục vào Dropdown và Filter
        function renderCategories() {
            podCategorySelect.innerHTML = categories.map(cat => `<option value="${cat}">${cat}</option>`).join('');
            
            const filterHTML = [`<button class="pill ${selectedCategory === 'Tất cả' ? 'active' : ''}" onclick="filterByCat('Tất cả')">Tất cả</button>`];
            categories.forEach(cat => {
                filterHTML.push(`<button class="pill ${selectedCategory === cat ? 'active' : ''}" onclick="filterByCat('${cat}')">${cat}</button>`);
            });
            categoryFilter.innerHTML = filterHTML.join('');
            
            localStorage.setItem('podcast_categories', JSON.stringify(categories));
        }

        function filterByCat(cat) {
            selectedCategory = cat;
            renderCategories();
            renderPodcasts();
        }

        // Render Danh sách Podcast
        function renderPodcasts() {
            const searchTerm = searchInput.value.toLowerCase();
            const filtered = podcasts.filter(p => {
                const matchCat = (selectedCategory === "Tất cả" || p.category === selectedCategory);
                const matchSearch = p.title.toLowerCase().includes(searchTerm) || p.desc.toLowerCase().includes(searchTerm);
                return matchCat && matchSearch;
            });

            if(filtered.length === 0) {
                podcastContainer.innerHTML = `<div style="grid-column: 1/-1; text-align: center; padding: 3rem; color: var(--text-secondary);">Không tìm thấy tập podcast nào.</div>`;
                return;
            }

            podcastContainer.innerHTML = filtered.map(p => `
                <div class="podcast-card">
                    <div>
                        <span class="badge">${p.category}</span>
                        <h3 class="podcast-card-title">${p.title}</h3>
                        <p class="podcast-card-desc">${p.desc}</p>
                    </div>
                    <div class="podcast-card-actions">
                        <button class="play-btn-card" onclick="playPodcast(${p.id})">
                            <i class="fa-solid ${currentPlayingId === p.id && !audio.paused ? 'fa-pause' : 'fa-play'}"></i>
                        </button>
                        <button class="delete-btn-card" onclick="deletePodcast(${p.id})">
                            <i class="fa-solid fa-trash"></i> Xóa
                        </button>
                    </div>
                </div>
            `).join('');

            localStorage.setItem('podcast_items', JSON.stringify(podcasts));
        }

        // Thêm Danh Mục Mới
        document.getElementById('addCatBtn').addEventListener('click', () => {
            const catNameInput = document.getElementById('catName');
            const val = catNameInput.value.trim();
            if(val && !categories.includes(val)) {
                categories.push(val);
                catNameInput.value = '';
                renderCategories();
            }
        });

        // Thêm Podcast Mới
        document.getElementById('addPodBtn').addEventListener('click', () => {
            const title = document.getElementById('podTitle').value.trim();
            const category = document.getElementById('podCategory').value;
            const url = document.getElementById('podUrl').value.trim();
            const desc = document.getElementById('podDesc').value.trim();

            if(!title || !url) {
                alert('Vui lòng điền tiêu đề và đường dẫn file audio!');
                return;
            }

            const newPod = {
                id: Date.now(),
                title,
                category,
                url,
                desc: desc || "Không có mô tả."
            };

            podcasts.unshift(newPod);
            document.getElementById('podTitle').value = '';
            document.getElementById('podUrl').value = '';
            document.getElementById('podDesc').value = '';
            
            renderPodcasts();
        });

        // Xóa Podcast
        function deletePodcast(id) {
            if(confirm('Bạn có chắc chắn muốn xóa tập podcast này?')) {
                podcasts = podcasts.filter(p => p.id !== id);
                if(currentPlayingId === id) {
                    audio.pause();
                    currentPlayingId = null;
                    updatePlayerInfo(null);
                }
                renderPodcasts();
            }
        }

        // Trình phát nhạc Audio Player
        const mainPlayBtn = document.getElementById('mainPlayBtn');
        const progressBar = document.getElementById('progressBar');
        const progressFill = document.getElementById('progressFill');
        const currentTimeEl = document.getElementById('currentTime');
        const durationTimeEl = document.getElementById('durationTime');

        function playPodcast(id) {
            const pod = podcasts.find(p => p.id === id);
            if(!pod) return;

            if(currentPlayingId === id) {
                if(audio.paused) {
                    audio.play();
                } else {
                    audio.pause();
                }
            } else {
                currentPlayingId = id;
                audio.src = pod.url;
                audio.play();
                updatePlayerInfo(pod);
            }
            renderPodcasts();
        }

        function updatePlayerInfo(pod) {
            if(pod) {
                document.getElementById('nowPlayingTitle').innerText = pod.title;
                document.getElementById('nowPlayingCat').innerText = pod.category;
            } else {
                document.getElementById('nowPlayingTitle').innerText = "Chọn bài để phát";
                document.getElementById('nowPlayingCat').innerText = "Sẵn sàng";
                progressFill.style.width = '0%';
                currentTimeEl.innerText = "0:00";
                durationTimeEl.innerText = "0:00";
            }
        }

        // Sync Audio Events
        audio.addEventListener('play', () => {
            mainPlayBtn.innerHTML = '<i class="fa-solid fa-pause"></i>';
            renderPodcasts();
        });

        audio.addEventListener('pause', () => {
            mainPlayBtn.innerHTML = '<i class="fa-solid fa-play"></i>';
            renderPodcasts();
        });

        audio.addEventListener('timeupdate', () => {
            if(audio.duration) {
                const pct = (audio.currentTime / audio.duration) * 100;
                progressFill.style.width = `${pct}%`;
                currentTimeEl.innerText = formatTime(audio.currentTime);
                durationTimeEl.innerText = formatTime(audio.duration);
            }
        });

        mainPlayBtn.addEventListener('click', () => {
            if(currentPlayingId) {
                if(audio.paused) audio.play();
                else audio.pause();
            }
        });

        document.getElementById('seekPrev').addEventListener('click', () => audio.currentTime = Math.max(0, audio.currentTime - 10));
        document.getElementById('seekNext').addEventListener('click', () => audio.currentTime = Math.min(audio.duration, audio.currentTime + 10));

        progressBar.addEventListener('click', (e) => {
            const rect = progressBar.getBoundingClientRect();
            const pct = (e.clientX - rect.left) / rect.width;
            audio.currentTime = pct * audio.duration;
        });

        document.getElementById('volumeSlider').addEventListener('input', (e) => {
            audio.volume = e.target.value;
        });

        function formatTime(sec) {
            const m = Math.floor(sec / 60);
            const s = Math.floor(sec % 60);
            return `${m}:${s < 10 ? '0' : ''}${s}`;
        }

        // Chuyển chế độ Lưới / Danh sách
        document.getElementById('gridViewBtn').addEventListener('click', function() {
            podcastContainer.classList.remove('list-view');
            this.classList.add('active');
            document.getElementById('listViewBtn').classList.remove('active');
        });

        document.getElementById('listViewBtn').addEventListener('click', function() {
            podcastContainer.classList.add('list-view');
            this.classList.add('active');
            document.getElementById('gridViewBtn').classList.remove('active');
        });

        searchInput.addEventListener('input', renderPodcasts);

        // Run On Load
        renderCategories();
        renderPodcasts();
    </script>
</body>
</html>