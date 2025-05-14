// assets/js/app.js

// ===========================================
// 1) CRUD для логинов
// ===========================================
async function getLogins() {
    const res = await fetch('fetch_logins.php');
    return await res.json();
}

async function saveLogins(arr) {
    await fetch('fetch_logins.php', {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(arr, null, 2)
    });
}

async function addLogin() {
    const name  = document.getElementById('new-name').value.trim();
    const login = document.getElementById('new-login').value.trim();
    if (!name || !login) return alert('Заполните оба поля');

    const arr = await getLogins();
    arr.push({ name, login });
    await saveLogins(arr);
    location.reload();
}

async function removeLogin(idx) {
    const arr = await getLogins();
    arr.splice(idx, 1);
    await saveLogins(arr);
    location.reload();
}

// Экспортим на window, чтобы inline-onclick в index.php их видел
window.addLogin    = addLogin;
window.removeLogin = removeLogin;


// ===========================================
// 2) Запрос доставок
// ===========================================
async function fetchDeliveries() {
    const login = document.getElementById('select-login').value;
    let from = document.getElementById('from').value;
    let to   = document.getElementById('to').value;

    if (!login || !from || !to) {
        return alert('Выберите логин и оба периода');
    }

    // приводим к формату "YYYY-MM-DD HH:MM:SS.vvv"
    from = from.replace('T',' ') + '.000';
    to   = to.replace('T',' ') + '.999';

    const res = await fetch('fetch_deliveries.php', {
        method:'POST',
        headers:{ 'Content-Type':'application/json' },
        body: JSON.stringify({ login, from, to })
    });

    const json = await res.json();
    document.getElementById('report').textContent = json.report || json.error;
}
window.fetchDeliveries = fetchDeliveries;
