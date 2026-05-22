let isLogin = true;

function toggleAuth() {
    isLogin = !isLogin;
    document.getElementById('auth-title').innerText = isLogin ? "Login" : "Register";
    document.getElementById('auth-toggle').innerText = isLogin ? "Need an account? Register" : "Have an account? Login";
}

function handleAuth() {
    // Logic for connecting to backend would go here
    document.getElementById('auth-container').style.display = 'none';
    document.getElementById('main-content').style.display = 'block';
    
    // Simulate Admin login for demo
    const email = document.getElementById('email').value;
    if(email.includes('admin')) {
        document.getElementById('adminBtn').style.display = 'inline-block';
    }
    loadMockItems();
}

function showPage(pageId) {
    document.querySelectorAll('.page').forEach(p => p.style.display = 'none');
    document.getElementById(pageId + '-page').style.display = 'block';
}

function loadMockItems() {
    const grid = document.getElementById('items-grid');
    const items = [
        { title: 'Airpods Case', status: 'lost', loc: 'Library' },
        { title: 'Hydroflask', status: 'found', loc: 'Gym' }
    ];
    
    grid.innerHTML = items.map(item => `
        <div class="card ${item.status}">
            <h3>${item.title}</h3>
            <p>Status: <strong>${item.status.toUpperCase()}</strong></p>
            <p>Location: ${item.loc}</p>
            <button class="main-btn" style="width:auto">Claim/Contact</button>
        </div>
    `).join('');
}

function logout() { location.reload(); }