/* ============================================
   LOST & FOUND CAMPUS — APP.JS (localStorage)
   ============================================ */

// ─── STORAGE KEYS ───────────────────────────
const KEY_USERS = 'laf_users';
const KEY_ITEMS = 'laf_items';
const KEY_SESSION = 'laf_session';
const KEY_COUNTER = 'laf_counter';
const KEY_MESSAGES = 'laf_messages';
const KEY_MSG_COUNTER = 'laf_msg_counter';

// ─── SEED DEFAULT DATA ───────────────────────
(function seedData() {
  if (localStorage.getItem(KEY_USERS)) return; // already seeded

  const users = [
    { id: 1, full_name: 'Admin User', email: 'admin@campus.edu', password: 'admin123', role: 'admin', created_at: new Date().toISOString() },
    { id: 2, full_name: 'John Doe', email: 'john@campus.edu', password: 'student123', role: 'student', created_at: new Date().toISOString() },
    { id: 3, full_name: 'Jane Smith', email: 'jane@campus.edu', password: 'student123', role: 'student', created_at: new Date().toISOString() },
  ];

  const now = new Date();
  const d = (minus) => new Date(now - minus * 3600000).toISOString();

  const items = [
    { id: 1, user_id: 2, poster_name: 'John Doe', type: 'lost', title: 'Blue Hydro Flask', description: 'A blue water bottle with sticker decorations. Very sentimental to me. Please contact!', category: 'Personal Items', location: 'Library – 2nd Floor', date_reported: '2026-02-20', contact_info: 'john@campus.edu', image_url: null, status: 'approved', created_at: d(48) },
    { id: 2, user_id: 3, poster_name: 'Jane Smith', type: 'found', title: 'Black Leather Wallet', description: 'Found a black wallet near the cafeteria entrance. Has student ID and some cash inside.', category: 'Wallets', location: 'Cafeteria Entrance', date_reported: '2026-02-21', contact_info: 'jane@campus.edu', image_url: null, status: 'approved', created_at: d(36) },
    { id: 3, user_id: 2, poster_name: 'John Doe', type: 'lost', title: 'Dell Laptop Charger', description: 'Lost a Dell 65W USB-C laptop charger. Black brick-style. Was in Science Block.', category: 'Electronics', location: 'Science Block – Room 201', date_reported: '2026-02-22', contact_info: 'john@campus.edu', image_url: null, status: 'approved', created_at: d(24) },
    { id: 4, user_id: 3, poster_name: 'Jane Smith', type: 'found', title: 'Set of 3 Keys', description: 'Found a keychain with 3 keys and a small red rubber keyring, near the main parking area.', category: 'Keys', location: 'Main Parking Lot', date_reported: '2026-02-22', contact_info: 'jane@campus.edu', image_url: null, status: 'approved', created_at: d(20) },
    { id: 5, user_id: 2, poster_name: 'John Doe', type: 'lost', title: 'Calculus Textbook', description: 'James Stewart Calculus 8th Edition. My name is written on the inner cover page.', category: 'Books', location: 'Engineering Block – 305', date_reported: '2026-02-21', contact_info: 'john@campus.edu', image_url: null, status: 'approved', created_at: d(16) },
    { id: 6, user_id: 3, poster_name: 'Jane Smith', type: 'found', title: 'AirPods Pro (White)', description: 'Found white AirPods Pro in charging case near the sports ground benches.', category: 'Electronics', location: 'Sports Ground', date_reported: '2026-02-23', contact_info: 'jane@campus.edu', image_url: null, status: 'approved', created_at: d(2) },
    { id: 7, user_id: 2, poster_name: 'John Doe', type: 'lost', title: 'Student ID Card', description: 'Lost my university ID card. Name: John Doe, Roll No: CS2021045.', category: 'Personal Items', location: 'Main Canteen', date_reported: '2026-02-23', contact_info: 'john@campus.edu', image_url: null, status: 'pending', created_at: d(1) },
    { id: 8, user_id: 3, poster_name: 'Jane Smith', type: 'found', title: 'Spectacles (Black Frame)', description: 'Found a pair of glasses with black rectangular frames in the computer lab.', category: 'Personal Items', location: 'Computer Lab – Block B', date_reported: '2026-02-23', contact_info: 'jane@campus.edu', image_url: null, status: 'pending', created_at: d(0.5) },
  ];

  const messages = [
    { id: 1, item_id: 1, sender_id: 3, receiver_id: 2, text: "Hi, is this hydro flask still available?", created_at: d(40) },
    { id: 2, item_id: 1, sender_id: 2, receiver_id: 3, text: "Yes, I lost it near the library. Do you have it?", created_at: d(39) }
  ];

  localStorage.setItem(KEY_USERS, JSON.stringify(users));
  localStorage.setItem(KEY_ITEMS, JSON.stringify(items));
  localStorage.setItem(KEY_MESSAGES, JSON.stringify(messages));
  localStorage.setItem(KEY_COUNTER, '8');
  localStorage.setItem(KEY_MSG_COUNTER, '2');
})();

// ─── DATA HELPERS ────────────────────────────
function getUsers() { return JSON.parse(localStorage.getItem(KEY_USERS) || '[]'); }
function getItems() { return JSON.parse(localStorage.getItem(KEY_ITEMS) || '[]'); }
function saveUsers(u) { localStorage.setItem(KEY_USERS, JSON.stringify(u)); }
function saveItems(i) { localStorage.setItem(KEY_ITEMS, JSON.stringify(i)); }
function nextId() { const n = parseInt(localStorage.getItem(KEY_COUNTER) || '0') + 1; localStorage.setItem(KEY_COUNTER, n); return n; }

function getMessages() { return JSON.parse(localStorage.getItem(KEY_MESSAGES) || '[]'); }
function saveMessages(m) { localStorage.setItem(KEY_MESSAGES, JSON.stringify(m)); }
function nextMsgId() { const n = parseInt(localStorage.getItem(KEY_MSG_COUNTER) || '0') + 1; localStorage.setItem(KEY_MSG_COUNTER, n); return n; }

// ─── CHAT HELPERS ────────────────────────────
function sendMessage(itemId, senderId, receiverId, text) {
  const msgs = getMessages();
  const newMsg = {
    id: nextMsgId(),
    item_id: itemId,
    sender_id: senderId,
    receiver_id: receiverId,
    text: text,
    created_at: new Date().toISOString()
  };
  msgs.push(newMsg);
  saveMessages(msgs);
  return newMsg;
}

function getChatHistory(itemId, userA, userB) {
  return getMessages().filter(m => 
    m.item_id === itemId && 
    ((m.sender_id === userA && m.receiver_id === userB) || 
     (m.sender_id === userB && m.receiver_id === userA))
  ).sort((a,b) => new Date(a.created_at) - new Date(b.created_at));
}

function getMyChatThreads(myUserId) {
  const msgs = getMessages().filter(m => m.sender_id === myUserId || m.receiver_id === myUserId);
  const threads = {};
  
  // Group by (item_id + other_user_id)
  msgs.forEach(m => {
    const otherUserId = m.sender_id === myUserId ? m.receiver_id : m.sender_id;
    const threadKey = `${m.item_id}_${otherUserId}`;
    if (!threads[threadKey]) {
      threads[threadKey] = { item_id: m.item_id, other_user_id: otherUserId, messages: [] };
    }
    threads[threadKey].messages.push(m);
  });
  
  // Convert to array and sort by latest message
  return Object.values(threads).map(t => {
    t.messages.sort((a,b) => new Date(a.created_at) - new Date(b.created_at));
    t.latest_message = t.messages[t.messages.length - 1];
    return t;
  }).sort((a,b) => new Date(b.latest_message.created_at) - new Date(a.latest_message.created_at));
}

// ─── AUTH ─────────────────────────────────────
function getSession() { return JSON.parse(sessionStorage.getItem(KEY_SESSION) || 'null'); }
function setSession(user) { sessionStorage.setItem(KEY_SESSION, JSON.stringify(user)); }
function clearSession() { sessionStorage.removeItem(KEY_SESSION); }
function requireLogin(role) {
  const s = getSession();
  if (!s) { window.location.href = 'login.html'; return null; }
  if (role && s.role !== role) { window.location.href = s.role === 'admin' ? 'home-admin.html' : 'home-student.html'; return null; }
  return s;
}

function authLogin(email, password) {
  const users = getUsers();
  const user = users.find(u => u.email === email && u.password === password);
  if (!user) return { success: false, message: 'Invalid email or password' };
  const session = { id: user.id, full_name: user.full_name, email: user.email, role: user.role };
  setSession(session);
  return { success: true, role: user.role, name: user.full_name };
}

function authRegister(full_name, email, password) {
  const users = getUsers();
  if (users.find(u => u.email === email)) return { success: false, message: 'Email already registered' };
  const newUser = { id: nextId(), full_name, email, password, role: 'student', created_at: new Date().toISOString() };
  users.push(newUser);
  saveUsers(users);
  const session = { id: newUser.id, full_name, email, role: 'student' };
  setSession(session);
  return { success: true, role: 'student', name: full_name };
}

// ─── ITEM CRUD ────────────────────────────────
function getApprovedItems(type, category) {
  let items = getItems().filter(i => i.status === 'approved');
  if (type) items = items.filter(i => i.type === type);
  if (category) items = items.filter(i => i.category === category);
  return items.sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
}

function getPendingItems() {
  return getItems().filter(i => i.status === 'pending').sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
}

function getMyItems(userId) {
  return getItems().filter(i => i.user_id === userId).sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
}

function searchItems(q) {
  const qL = q.toLowerCase();
  return getItems().filter(i => i.status === 'approved' && (
    i.title.toLowerCase().includes(qL) ||
    i.description.toLowerCase().includes(qL) ||
    i.location.toLowerCase().includes(qL) ||
    (i.category || '').toLowerCase().includes(qL)
  )).sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
}

function postItem(userId, posterName, data) {
  const items = getItems();
  const item = {
    id: nextId(),
    user_id: userId,
    poster_name: posterName,
    type: data.type,
    title: data.title,
    description: data.description,
    category: data.category || 'Other',
    location: data.location,
    date_reported: data.date_reported || new Date().toISOString().split('T')[0],

    image_url: data.image_url || null,
    status: 'pending',
    created_at: new Date().toISOString()
  };
  items.push(item);
  saveItems(items);
  return item;
}

function updateItemStatus(itemId, status) {
  const items = getItems();
  const idx = items.findIndex(i => i.id === itemId);
  if (idx === -1) return false;
  items[idx].status = status;
  saveItems(items);
  return true;
}

function deleteItemById(itemId, userId, role) {
  let items = getItems();
  if (role === 'admin') items = items.filter(i => i.id !== itemId);
  else items = items.filter(i => !(i.id === itemId && i.user_id === userId));
  saveItems(items);
}

// ─── UI HELPERS ──────────────────────────────
function showToast(msg, type = 'info') {
  let c = document.getElementById('toast-container');
  if (!c) { c = document.createElement('div'); c.id = 'toast-container'; document.body.appendChild(c); }
  const t = document.createElement('div');
  t.className = `toast ${type}`;
  const icons = { success: '✅', error: '❌', info: 'ℹ️', warning: '⚠️' };
  t.innerHTML = `${icons[type] || ''} ${msg}`;
  c.appendChild(t);
  setTimeout(() => { t.style.opacity = '0'; t.style.transform = 'translateX(40px)'; setTimeout(() => t.remove(), 300); }, 3000);
}

function showAlert(id, msg, type = 'info') {
  const el = document.getElementById(id);
  if (!el) return;
  el.innerHTML = `<div class="alert alert-${type}" style="margin-bottom:14px;">${msg}</div>`;
  setTimeout(() => { if (el) el.innerHTML = ''; }, 5000);
}

function setLoading(btn, on) {
  if (!btn) return;
  if (on) { btn._html = btn.innerHTML; btn.disabled = true; btn.innerHTML = '<span class="loader" style="width:18px;height:18px;border-width:2px;margin:0;display:inline-block;"></span>'; }
  else { btn.disabled = false; if (btn._html) btn.innerHTML = btn._html; }
}

function renderBadge(type) {
  const map = {
    lost: '<span class="badge badge-lost">🔴 Lost</span>',
    found: '<span class="badge badge-found">🟢 Found</span>',
    pending: '<span class="badge badge-pending">🟡 Pending</span>',
    approved: '<span class="badge badge-approved">✅ Approved</span>',
    rejected: '<span class="badge badge-rejected">❌ Rejected</span>',
    resolved: '<span class="badge badge-resolved">💜 Resolved</span>',
  };
  return map[type] || `<span class="badge">${type}</span>`;
}

function timeAgo(iso) {
  const d = new Date(iso), diff = Math.floor((Date.now() - d) / 1000);
  if (diff < 60) return 'Just now';
  if (diff < 3600) return `${Math.floor(diff / 60)}m ago`;
  if (diff < 86400) return `${Math.floor(diff / 3600)}h ago`;
  if (diff < 604800) return `${Math.floor(diff / 86400)}d ago`;
  return d.toLocaleDateString('en-IN', { day: 'numeric', month: 'short' });
}

function formatDate(s) {
  if (!s) return '';
  return new Date(s).toLocaleDateString('en-IN', { day: 'numeric', month: 'short', year: 'numeric' });
}

function escHtml(s) {
  if (!s) return '';
  return s.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

function buildItemCard(item, actions = []) {
  const emoji = item.type === 'lost' ? '🔍' : '📦';
  const imgHtml = item.image_url
    ? `<img src="${item.image_url}" alt="${escHtml(item.title)}" class="item-card-img"/>`
    : `<div class="item-card-img" style="background:linear-gradient(135deg,rgba(108,92,231,0.18),rgba(0,206,201,0.1));display:flex;align-items:center;justify-content:center;font-size:3rem;">${emoji}</div>`;
  const actHtml = actions.map(a => `<button class="btn btn-sm ${a.cls}" onclick="${a.fn}">${a.label}</button>`).join('');
  return `
  <div class="item-card ${item.type}-card" id="card-${item.id}">
    ${imgHtml}
    <div class="item-card-header">
      <div>
        <div class="item-card-title">${escHtml(item.title)}</div>
        <div style="margin-top:5px;">${renderBadge(item.type)} ${renderBadge(item.status)}</div>
      </div>
    </div>
    <p class="item-card-desc">${escHtml(item.description)}</p>
    <div class="item-card-meta">
      <span>📍 ${escHtml(item.location)}</span>
      <span>🗂 ${escHtml(item.category || 'General')}</span>
      <span>🕐 ${timeAgo(item.created_at)}</span>
      ${item.poster_name ? `<span>👤 ${escHtml(item.poster_name)}</span>` : ''}
    </div>
    ${actHtml ? `<div class="item-card-actions">${actHtml}</div>` : ''}
  </div>`;
}

function openModal(id) { const m = document.getElementById(id); if (m) { m.style.display = 'flex'; document.body.style.overflow = 'hidden'; } }
function closeModal(id) { const m = document.getElementById(id); if (m) { m.style.display = 'none'; document.body.style.overflow = ''; } }

function debounce(fn, ms) { let t; return (...a) => { clearTimeout(t); t = setTimeout(() => fn(...a), ms); }; }

function initHamburger() {
  const btn = document.getElementById('hamburger'), nav = document.getElementById('navLinks');
  if (btn && nav) btn.addEventListener('click', () => nav.classList.toggle('open'));
}

function logout() { clearSession(); window.location.href = 'login.html'; }
