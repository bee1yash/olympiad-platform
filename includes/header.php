<?php

$path_prefix = '';
if (strpos($_SERVER['PHP_SELF'], '/admin/') !== false || 
    strpos($_SERVER['PHP_SELF'], '/student/') !== false || 
    strpos($_SERVER['PHP_SELF'], '/specialist/') !== false) {
    $path_prefix = '../';
}

$role_label = 'Гість';
if (isset($_SESSION['role'])) {
    if ($_SESSION['role'] === 'admin') {
        $role_label = 'Адміністратор';
    } elseif ($_SESSION['role'] === 'specialist') {
        $role_label = 'Фахівець';
    } else {
        $role_label = 'Студент';
    }
}
?>
<!DOCTYPE html>
<html lang="uk" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Olympiad Platform</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    
    <link rel="stylesheet" href="<?= $path_prefix ?>assets/css/style.css">

    <script>
        const storedTheme = localStorage.getItem('theme');
        if (storedTheme) {
            document.documentElement.setAttribute('data-bs-theme', storedTheme);
        } else {
            const systemTheme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
            document.documentElement.setAttribute('data-bs-theme', systemTheme);
        }
    </script>
</head>
<body>
    <nav class="navbar navbar-expand-lg border-bottom mb-4 d-print-none" style="background-color: var(--bs-body-bg);">
        <div class="container">
            <a class="navbar-brand fw-bold text-primary" href="<?= $path_prefix ?>index.php">
                <i class="bi bi-mortarboard-fill"></i> Olympiad Platform
            </a>

            <div class="d-flex align-items-center gap-3">
                
                <button class="btn btn-outline-secondary btn-sm" id="theme-toggle" title="Змінити тему">
                    <i class="bi bi-sun-fill" id="theme-icon"></i>
                </button>

                <?php if(isset($_SESSION['user_id'])): ?>
                    <div class="vr"></div> 
                    
                    <div class="d-flex align-items-center">
                        <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle me-2">
                            <?= $role_label ?>
                        </span>
                        <span class="fw-bold text-primary me-3 d-none d-sm-inline">
                            <i class="bi bi-person-circle fs-5 align-middle me-1"></i> <?= htmlspecialchars($_SESSION['full_name']) ?>
                        </span>
                        
                        <a href="<?= $path_prefix ?>logout.php" class="btn btn-outline-danger btn-sm fw-bold">
                            <i class="bi bi-box-arrow-right"></i> Вийти
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </nav>
    <div class="container">

<script>
    const toggleButton = document.getElementById('theme-toggle');
    const themeIcon = document.getElementById('theme-icon');
    const htmlElement = document.documentElement;

    function updateIcon() {
        const currentTheme = htmlElement.getAttribute('data-bs-theme');
        if (currentTheme === 'dark') {
            themeIcon.classList.remove('bi-sun-fill');
            themeIcon.classList.add('bi-moon-stars-fill');
            toggleButton.classList.replace('btn-outline-secondary', 'btn-outline-light');
        } else {
            themeIcon.classList.remove('bi-moon-stars-fill');
            themeIcon.classList.add('bi-sun-fill');
            toggleButton.classList.replace('btn-outline-light', 'btn-outline-secondary');
        }
    }
    updateIcon();

    toggleButton.addEventListener('click', () => {
        const currentTheme = htmlElement.getAttribute('data-bs-theme');
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
        htmlElement.setAttribute('data-bs-theme', newTheme);
        localStorage.setItem('theme', newTheme);
        updateIcon();
    });
</script>