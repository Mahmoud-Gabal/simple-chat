const messagesContainer = document.getElementById('chatMessages');
const form = document.getElementById('chatForm');
const messageInput = document.getElementById('messageInput');
const convPreview = document.getElementById('convPreview');
const convTime = document.getElementById('convTime');

let currentUserName = localStorage.getItem('chatty_name') || 'Anonymous';

async function initUser() {
    try {
        const res = await fetch('../../back-end/apis/api_me.php');
        const data = await res.json();
        if (data.loggedIn && data.name) {
            currentUserName = data.name;
            const nameEl = document.getElementById('sidebarUserName');
            const roleEl = document.getElementById('sidebarUserRole');
            if (nameEl) nameEl.textContent = data.name;
            if (roleEl) roleEl.textContent = data.email || 'Logged in';
        }
    } catch (e) {
        console.error('Could not load user', e);
    }
}

function escapeHtml(str) {
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;');
}

function formatTime(dateStr) {
    const d = new Date(dateStr);
    const h = d.getHours();
    const m = d.getMinutes();
    const ampm = h >= 12 ? 'pm' : 'am';
    return (h % 12 || 12) + ':' + String(m).padStart(2, '0') + ampm;
}

async function loadMessages() {
    try {
        const res = await fetch('../../back-end/apis/api_messages.php');
        const data = await res.json();

        if (!res.ok) {
            if (res.status === 401) window.location.href = '../html/login.html';
            return;
        }
        const list = Array.isArray(data) ? data : [];

        messagesContainer.innerHTML = '';

        let lastMsg = null;
        list.forEach(msg => {
            lastMsg = msg;
            const isSelf = msg.name === currentUserName;
            const row = document.createElement('div');
            row.className = 'message ' + (isSelf ? 'outgoing' : 'incoming');

            if (!isSelf) {
                const wrap = document.createElement('div');
                wrap.className = 'message-avatar-wrap avatar-36';
                const img = document.createElement('img');
                img.className = 'message-avatar';
                img.src = '../../assets/avatar.png';
                img.alt = '';
                img.width = 36;
                img.height = 36;
                const dot = document.createElement('span');
                dot.className = 'avatar-online';
                wrap.appendChild(img);
                wrap.appendChild(dot);
                row.appendChild(wrap);
            }

            const content = document.createElement('div');
            content.className = 'message-content';

            const meta = document.createElement('div');
            meta.className = 'message-name';
            meta.textContent = msg.name;
            content.appendChild(meta);

            const bubble = document.createElement('div');
            bubble.className = 'message-bubble';
            bubble.textContent = msg.message;
            content.appendChild(bubble);

            const time = document.createElement('div');
            time.className = 'message-time';
            time.textContent = formatTime(msg.created_at);
            content.appendChild(time);

            row.appendChild(content);
            messagesContainer.appendChild(row);
        });

        if (lastMsg) {
            convPreview.textContent = lastMsg.message.length > 40 ? lastMsg.message.slice(0, 37) + '...' : lastMsg.message;
            convTime.textContent = formatTime(lastMsg.created_at);
        }

        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    } catch (e) {
        console.error('Failed to load messages:', e);
    }
}

form.addEventListener('submit', async (e) => {
    e.preventDefault();
    const message = messageInput.value.trim();
    if (!message) return;

    const formData = new FormData();
    formData.append('name', currentUserName);
    formData.append('message', message);

    const sendRes = await fetch('../../back-end/apis/api_send.php', { method: 'POST', body: formData });
    if (sendRes.status === 401) {
        window.location.href = '../html/login.html';
        return;
    }

    messageInput.value = '';
    messageInput.focus();
    await loadMessages();
});

(async function init() {
    await initUser();
    await loadMessages();
    setInterval(loadMessages, 5000);
})();

