const messagesContainer = document.getElementById('chatMessages');
const form = document.getElementById('chatForm');
const messageInput = document.getElementById('messageInput');
const conversationItems = document.getElementById('conversationItems');
const activeBotName = document.getElementById('activeBotName');

let currentUserName = localStorage.getItem('chatty_name') || 'Anonymous';
let activeConversationId = 0;

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

function renderConversations(conversations) {
    if (!conversationItems) return;
    conversationItems.innerHTML = '';

    conversations.forEach((c) => {
        const item = document.createElement('div');
        item.className = 'conv-item' + (c.id === activeConversationId ? ' active' : '');
        item.dataset.conversationId = String(c.id);

        const img = document.createElement('img');
        img.className = 'conv-avatar';
        img.src = '../../assets/avatar.png';
        img.alt = '';
        img.width = 42;
        img.height = 42;

        const content = document.createElement('div');
        content.className = 'conv-content';

        const name = document.createElement('div');
        name.className = 'conv-name';
        name.textContent = c.bot?.name || c.title || 'Conversation';

        const preview = document.createElement('div');
        preview.className = 'conv-preview';
        preview.textContent = c.last_message
            ? (c.last_message.length > 40 ? c.last_message.slice(0, 37) + '...' : c.last_message)
            : 'No messages yet';
        preview.dataset.previewFor = String(c.id);

        content.appendChild(name);
        content.appendChild(preview);

        const time = document.createElement('div');
        time.className = 'conv-time';
        time.textContent = c.last_message_at ? formatTime(c.last_message_at) : '-';
        time.dataset.timeFor = String(c.id);

        item.appendChild(img);
        item.appendChild(content);
        item.appendChild(time);

        item.addEventListener('click', async () => {
            activeConversationId = c.id;
            if (activeBotName) activeBotName.textContent = c.bot?.name || 'Chat';
            renderConversations(conversations);
            await loadMessages(activeConversationId);
        });

        conversationItems.appendChild(item);
    });
}

async function loadConversations() {
    const res = await fetch('../../back-end/apis/api_conversations.php');
    const data = await res.json();
    if (!res.ok) {
        if (res.status === 401) window.location.href = '../html/login.html';
        return [];
    }
    const list = Array.isArray(data) ? data : [];
    if (list.length > 0 && activeConversationId === 0) {
        activeConversationId = list[0].id;
        if (activeBotName) activeBotName.textContent = list[0]?.bot?.name || 'Chat';
    }
    renderConversations(list);
    return list;
}

async function loadMessages(conversationId = activeConversationId) {
    try {
        const url = new URL('../../back-end/apis/api_messages.php', window.location.href);
        if (conversationId) url.searchParams.set('conversation_id', String(conversationId));

        const res = await fetch(url.toString());
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

        if (lastMsg && conversationId) {
            const previewEl = document.querySelector(`[data-preview-for="${conversationId}"]`);
            const timeEl = document.querySelector(`[data-time-for="${conversationId}"]`);
            if (previewEl) previewEl.textContent = lastMsg.message.length > 40 ? lastMsg.message.slice(0, 37) + '...' : lastMsg.message;
            if (timeEl) timeEl.textContent = formatTime(lastMsg.created_at);
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
    if (activeConversationId) formData.append('conversation_id', String(activeConversationId));

    const sendRes = await fetch('../../back-end/apis/api_send.php', { method: 'POST', body: formData });
    if (sendRes.status === 401) {
        window.location.href = '../html/login.html';
        return;
    }

    messageInput.value = '';
    messageInput.focus();
    await loadMessages(activeConversationId);
});

(async function init() {
    await initUser();
    await loadConversations();
    await loadMessages(activeConversationId);
    setInterval(() => loadMessages(activeConversationId), 5000);
})();

