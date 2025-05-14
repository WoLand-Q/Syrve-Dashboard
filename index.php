<?php
// index.php
$loginsFile = __DIR__ . '/logins.json';
$logins = @json_decode(file_get_contents($loginsFile), true) ?: [];
?><!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Syrve Validator Web</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-4">
<div class="container">
    <h1 class="mb-4">Syrve Validator Web</h1>

    <!-- Управление логинами -->
    <section>
        <h2>API Логины</h2>
        <ul class="list-group mb-3" id="login-list">
            <?php foreach($logins as $i => $item): ?>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <div>
                        <strong><?= htmlspecialchars($item['name']) ?></strong><br>
                        <small class="text-muted"><?= htmlspecialchars($item['login']) ?></small>
                    </div>
                    <button class="btn btn-sm btn-outline-danger" onclick="removeLogin(<?= $i ?>)">×</button>
                </li>
            <?php endforeach ?>
        </ul>
        <div class="input-group mb-4">
            <input type="text" id="new-name" class="form-control" placeholder="Название заведения">
            <input type="text" id="new-login" class="form-control" placeholder="Новый API login">
            <button class="btn btn-primary" onclick="addLogin()">Добавить</button>
        </div>
    </section>

    <!-- Запрос доставок -->
    <section>
        <h2>Запрос доставок</h2>
        <div class="mb-3">
            <label class="form-label">Выберите логин</label>
            <select id="select-login" class="form-select">
                <?php foreach($logins as $item): ?>
                    <option value="<?= htmlspecialchars($item['login']) ?>">
                        <?= htmlspecialchars($item['name']) ?> — <?= htmlspecialchars($item['login']) ?>
                    </option>
                <?php endforeach ?>
            </select>
        </div>
        <div class="row g-2 mb-3">
            <div class="col">
                <label class="form-label">С</label>
                <input type="datetime-local" id="from" class="form-control">
            </div>
            <div class="col">
                <label class="form-label">По</label>
                <input type="datetime-local" id="to" class="form-control">
            </div>
        </div>
        <button class="btn btn-success mb-3" onclick="fetchDeliveries()">Получить отчёт</button>
        <pre id="report" class="border p-3" style="height:400px;overflow:auto;"></pre>
    </section>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- чуть ниже — ваш главный скрипт -->
<script src="assets/js/app.js"></script>
</body>
</html>
