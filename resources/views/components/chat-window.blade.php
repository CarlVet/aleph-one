<!-- Chat Windows Container -->
<div
    id="chat-windows-container"
    class="fixed flex flex-col items-end space-y-2"
    style="z-index: 1200; right: 1rem; bottom: 4.75rem;"
>
    <!-- Chat windows will be dynamically added here -->
</div>

<!-- Active chat shortcuts (avatars) -->
<div
    id="chat-shortcuts"
    class="fixed flex flex-row-reverse flex-wrap items-center justify-end gap-2 overflow-visible"
    style="z-index: 1110; right: 4.25rem; bottom: 1rem; max-width: calc(100vw - 6rem);"
></div>

<!-- Chat Toggle Button -->
<button id="chat-toggle"
    class="fixed bottom-4 right-4 w-10 h-10 bg-black text-white rounded-full shadow-lg flex items-center justify-center hover:bg-blue-600 focus:outline-none cursor-move"
    style="z-index: 1120;">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
    </svg>
    <span id="chat-badge"
        class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center hidden">0</span>
</button>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const chatWindowsContainer = document.getElementById('chat-windows-container');
            const chatToggle = document.getElementById('chat-toggle');
            const chatBadge = document.getElementById('chat-badge');
            const chatShortcuts = document.getElementById('chat-shortcuts');
            let activeChats = new Map(); // (at most one) active chat window
            let usersListWindow = null;
            let zIndexCounter = 1001;
            const currentProjectId = @json(session('selected_project_id'));
            const pinnedStorageKey = `chat_pins:${String({{ (int) Auth::id() }})}:${String(currentProjectId ?? 'none')}`;
            let isDragging = false;
            let currentX;
            let currentY;
            let initialX;
            let initialY;
            let xOffset = 0;
            let yOffset = 0;
            const typingHeartbeatIntervals = new Map();

            // Make element draggable
            function dragElement(element) {
                if (!element) return;

                element.addEventListener('mousedown', dragMouseDown);

                function dragMouseDown(e) {
                    e.preventDefault();
                    initialX = e.clientX - xOffset;
                    initialY = e.clientY - yOffset;

                    if (e.target === element || e.target.parentElement === element) {
                        isDragging = true;
                    }

                    document.addEventListener('mousemove', elementDrag);
                    document.addEventListener('mouseup', closeDragElement);
                }

                function elementDrag(e) {
                    if (isDragging) {
                        e.preventDefault();
                        currentX = e.clientX - initialX;
                        currentY = e.clientY - initialY;

                        xOffset = currentX;
                        yOffset = currentY;

                        setTranslate(currentX, currentY, element);
                    }
                }

                function closeDragElement() {
                    initialX = currentX;
                    initialY = currentY;
                    isDragging = false;

                    document.removeEventListener('mousemove', elementDrag);
                    document.removeEventListener('mouseup', closeDragElement);
                }

                function setTranslate(xPos, yPos, el) {
                    el.style.transform = `translate3d(${xPos}px, ${yPos}px, 0)`;
                }
            }

            // Make chat toggle button draggable
            if (chatToggle) dragElement(chatToggle);

            function escapeHtml(unsafe) {
                return String(unsafe)
                    .replaceAll('&', '&amp;')
                    .replaceAll('<', '&lt;')
                    .replaceAll('>', '&gt;')
                    .replaceAll('"', '&quot;')
                    .replaceAll("'", '&#039;');
            }

            function urlForEntityCode(code) {
                const parts = String(code || '').split('-');
                if (parts.length < 3) return null;

                const type = parts[1];
                const last = parts[parts.length - 1];
                const isTubeCode = parts.length >= 4 && /^[0-9]+$/.test(last);

                if (isTubeCode) {
                    return `/bank/tubes/${code}`;
                }

                switch (type) {
                    case 'AS':
                        return `/samples/animals/${code}`;
                    case 'HS':
                        return `/samples/humans/${code}`;
                    case 'ES':
                        return `/samples/environment/${code}`;
                    case 'PS':
                        return `/samples/parasites/${code}`;
                    case 'NA':
                        return `/samples/nucleic/${code}`;
                    case 'CU':
                        return `/samples/cultures/${code}`;
                    case 'PO':
                        return `/samples/pools/${code}`;
                    case 'BO':
                        return `/bank/boxes/${code}/contents`;
                    case 'HU':
                        return `/humans/${code}`;
                    case 'AN':
                        return `/animals/${code}`;
                    case 'EX':
                        return `/experiments/${code}`;
                    case 'PR':
                        return `/protocols/${code}`;
                    default:
                        return null;
                }
            }

            // Convert @entity tags into clickable profile links.
            function processMessageContent(content) {
                const safe = escapeHtml(content);
                const mentionRegex = /@([A-Z0-9]+-[A-Z]{2,3}-[0-9]+(?:-[0-9]+)*)/g;

                return safe.replace(mentionRegex, (match, code) => {
                    const url = urlForEntityCode(code);
                    if (!url) return match;

                    return `<a href="${url}" class="underline decoration-dotted underline-offset-2 hover:decoration-solid"
                              onclick="event.preventDefault(); window.open('${url}', '_blank');">${match}</a>`;
                });
            }

            function dateLabel(date) {
                const d = new Date(date);
                const today = new Date();
                const startOfToday = new Date(today.getFullYear(), today.getMonth(), today.getDate());
                const startOfThatDay = new Date(d.getFullYear(), d.getMonth(), d.getDate());
                const diffDays = Math.round((startOfToday - startOfThatDay) / (1000 * 60 * 60 * 24));

                if (diffDays === 0) return 'Today';
                if (diffDays === 1) return 'Yesterday';
                return d.toLocaleDateString([], { year: 'numeric', month: 'short', day: 'numeric' });
            }

            function timeLabel(date) {
                return new Date(date).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
            }

            function createDateSeparator(date) {
                const div = document.createElement('div');
                div.className = 'flex justify-center my-3';
                div.innerHTML = `<span class="px-3 py-1 rounded-full bg-gray-200/70 text-[11px] text-gray-700 shadow-sm">${dateLabel(date)}</span>`;
                return div;
            }

            function bringToFront(el) {
                if (!el) return;
                zIndexCounter += 1;
                el.style.zIndex = String(zIndexCounter);
            }

            function closeChatWindowByUserId(userId, { removeShortcut = false } = {}) {
                const win = activeChats.get(String(userId));
                if (!win) return;

                const intervalId = parseInt(win.dataset.pollingInterval || '0', 10);
                if (intervalId) clearInterval(intervalId);
                const typingIntervalId = parseInt(win.dataset.typingPollingInterval || '0', 10);
                if (typingIntervalId) clearInterval(typingIntervalId);
                const inputTypingHeartbeat = typingHeartbeatIntervals.get(String(userId));
                if (inputTypingHeartbeat) {
                    clearInterval(inputTypingHeartbeat);
                    typingHeartbeatIntervals.delete(String(userId));
                }
                stopTyping(String(userId));
                win.remove();
                activeChats.delete(String(userId));

                if (removeShortcut) {
                    removeChatShortcut(String(userId));
                }
            }

            function closeAllChatWindows({ removeShortcuts = false } = {}) {
                Array.from(activeChats.keys()).forEach((id) => closeChatWindowByUserId(id, { removeShortcut: removeShortcuts }));
            }

            function getPinnedChats() {
                try {
                    const raw = localStorage.getItem(pinnedStorageKey);
                    if (!raw) return [];
                    const parsed = JSON.parse(raw);
                    return Array.isArray(parsed) ? parsed : [];
                } catch {
                    return [];
                }
            }

            function setPinnedChats(chats) {
                try {
                    localStorage.setItem(pinnedStorageKey, JSON.stringify(chats));
                } catch {
                    // ignore
                }
            }

            function pinChat(userId, userName, photoPath) {
                const id = String(userId);
                const existing = getPinnedChats();
                if (existing.some((c) => String(c.userId) === id)) return;

                existing.push({
                    userId: id,
                    userName: String(userName || 'Chat'),
                    photoPath: String(photoPath || ''),
                });
                setPinnedChats(existing);
            }

            function unpinChat(userId) {
                const id = String(userId);
                const next = getPinnedChats().filter((c) => String(c.userId) !== id);
                setPinnedChats(next);
            }

            let hydratedUserMap = null;
            let hydrateUserMapPromise = null;
            let hydratedUserMapAt = 0;

            function hydrateUserMapIfNeeded({ force = false } = {}) {
                const isFresh = hydratedUserMap && (Date.now() - hydratedUserMapAt) < 60_000;
                if (!force && isFresh) return Promise.resolve(hydratedUserMap);
                if (!force && hydrateUserMapPromise) return hydrateUserMapPromise;

                hydrateUserMapPromise = fetch('/chat')
                    .then(response => response.text())
                    .then(html => {
                        const parser = new DOMParser();
                        const doc = parser.parseFromString(html, 'text/html');
                        const users = doc.querySelectorAll('.user-chat');
                        const map = new Map();
                        users.forEach(user => {
                            const userId = String(user.dataset.userId || '').trim();
                            if (!userId) return;
                            map.set(userId, {
                                userName: String(user.dataset.userName || user.querySelector('p')?.textContent || 'Chat'),
                                photoPath: String(user.dataset.photoPath || ''),
                            });
                        });
                        hydratedUserMap = map;
                        hydratedUserMapAt = Date.now();
                        return hydratedUserMap;
                    })
                    .catch(() => {
                        hydratedUserMap = hydratedUserMap || new Map();
                        hydratedUserMapAt = Date.now();
                        return hydratedUserMap;
                    })
                    .finally(() => {
                        hydrateUserMapPromise = null;
                    });

                return hydrateUserMapPromise;
            }

            function ensureChatShortcut(userId, userName, photoPath) {
                if (!chatShortcuts) return;

                const existing = chatShortcuts.querySelector(`[data-shortcut-user-id="${userId}"]`);
                if (existing) return;

                const avatarUrl = resolvePhotoUrl(photoPath);
                const initials = String(userName || 'U')
                    .trim()
                    .split(/\s+/)
                    .slice(0, 2)
                    .map(p => p.charAt(0).toUpperCase())
                    .join('') || 'U';

                const button = document.createElement('button');
                button.type = 'button';
                button.dataset.shortcutUserId = String(userId);
                button.dataset.shortcutUserName = String(userName || 'Chat');
                button.dataset.shortcutPhotoPath = String(photoPath || '');
                button.className =
                    'relative h-10 w-10 rounded-full shadow-lg ring-1 ring-black/10 bg-white hover:scale-105 transition-transform overflow-visible';
                button.title = userName || 'Chat';

                button.innerHTML = `
                    <span class="absolute top-0 left-0 -translate-x-1/2 -translate-y-1/2 h-2.5 w-2.5 rounded-full bg-emerald-500 ring-2 ring-white z-30 hidden pointer-events-none" data-shortcut-online="1"></span>
                    <span class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center hidden" data-shortcut-unread="1">0</span>
                    <span class="block h-full w-full rounded-full overflow-hidden">
                        ${
                            avatarUrl
                                ? `<img src="${avatarUrl}" alt="${escapeHtml(userName)}" class="h-full w-full object-cover" />`
                                : `<span class="h-full w-full flex items-center justify-center bg-slate-900 text-white text-xs font-bold">${initials}</span>`
                        }
                    </span>
                `;

                const unread = button.querySelector('[data-shortcut-unread="1"]');

                button.addEventListener('click', function() {
                    // Optimistically hide badge; backend will be marked read on message fetch.
                    unread?.classList.add('hidden');
                    if (usersListWindow && document.body.contains(usersListWindow)) {
                        usersListWindow.remove();
                        usersListWindow = null;
                    }
                    createChatWindow(
                        String(userId),
                        String(userName || 'Chat'),
                        String(photoPath || '')
                    );
                });

                chatShortcuts.appendChild(button);
                pinChat(userId, userName, photoPath);
                loadUserUnreadCount(String(userId));
            }

            function setOnlineState(userId, isOnline) {
                const id = String(userId);
                const shortcut = chatShortcuts?.querySelector(`[data-shortcut-user-id="${id}"]`);
                const shortcutDot = shortcut?.querySelector('[data-shortcut-online="1"]');
                if (shortcutDot) {
                    shortcutDot.classList.toggle('hidden', !isOnline);
                }

                const chatDot = document.getElementById(`chat-online-dot-${id}`);
                if (chatDot) {
                    chatDot.classList.toggle('hidden', !isOnline);
                }

                document.querySelectorAll(`.user-chat[data-user-id="${id}"] [data-user-online-dot="1"]`).forEach((dot) => {
                    dot.classList.toggle('hidden', !isOnline);
                });

                const statusEl = document.getElementById(`chat-status-${id}`);
                if (statusEl) {
                    statusEl.textContent = isOnline ? 'Online' : 'Offline';
                    statusEl.classList.toggle('text-emerald-200', isOnline);
                    statusEl.classList.toggle('text-white/70', !isOnline);
                }
            }

            function isPeerOnline(userId) {
                const id = String(userId);
                const shortcutDot = chatShortcuts?.querySelector(`[data-shortcut-user-id="${id}"] [data-shortcut-online="1"]`);
                if (shortcutDot) {
                    return !shortcutDot.classList.contains('hidden');
                }
                const chatDot = document.getElementById(`chat-online-dot-${id}`);
                if (chatDot) {
                    return !chatDot.classList.contains('hidden');
                }
                return false;
            }

            function collectKnownUserIds() {
                const ids = new Set();
                document.querySelectorAll('.user-chat[data-user-id]').forEach((el) => ids.add(String(el.dataset.userId)));
                if (chatShortcuts) {
                    chatShortcuts.querySelectorAll('[data-shortcut-user-id]').forEach((el) => ids.add(String(el.dataset.shortcutUserId)));
                }
                activeChats.forEach((_, key) => ids.add(String(key)));
                return Array.from(ids).filter(Boolean);
            }

            function refreshOnlineStatuses() {
                const ids = collectKnownUserIds();
                if (!ids.length) return;
                fetch(`/messages/online-statuses?user_ids=${encodeURIComponent(ids.join(','))}`)
                    .then((r) => r.json())
                    .then((data) => {
                        const statuses = data.statuses || {};
                        Object.entries(statuses).forEach(([id, isOnline]) => setOnlineState(String(id), !!isOnline));
                    })
                    .catch(() => {});
            }

            function sendHeartbeat() {
                fetch('/messages/heartbeat', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: '{}'
                }).catch(() => {});
            }

            function startTyping(userId) {
                fetch('/messages/typing/start', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ receiver_id: Number(userId) })
                }).catch(() => {});
            }

            function stopTyping(userId) {
                fetch('/messages/typing/stop', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ receiver_id: Number(userId) })
                }).catch(() => {});
            }

            function pollTypingStatus(userId) {
                fetch(`/messages/${userId}/typing/status`)
                    .then((r) => r.json())
                    .then((data) => {
                        const typing = !!(data && data.typing);
                        setTypingBubble(userId, typing);
                    })
                    .catch(() => {});
            }

            function setTypingBubble(userId, isTyping) {
                const container = document.getElementById(`messages-container-${userId}`);
                if (!container) return;

                const selector = `[data-typing-indicator-for="${userId}"]`;
                let indicator = container.querySelector(selector);

                if (isTyping) {
                    if (!indicator) {
                        indicator = document.createElement('div');
                        indicator.setAttribute('data-typing-indicator-for', String(userId));
                        indicator.className = 'flex justify-start mb-2';
                        indicator.innerHTML = `
                            <div class="bg-white text-gray-700 border border-gray-200 rounded-2xl px-3 py-2 max-w-[80%] shadow-sm">
                                <span class="inline-flex items-center text-sm leading-none animate-pulse">...</span>
                            </div>
                        `;
                        container.appendChild(indicator);
                    }
                    container.scrollTop = container.scrollHeight;
                } else if (indicator) {
                    indicator.remove();
                }
            }

            function removeChatShortcut(userId) {
                if (!chatShortcuts) return;
                const el = chatShortcuts.querySelector(`[data-shortcut-user-id="${userId}"]`);
                if (el) el.remove();
                unpinChat(userId);
            }

            function resolvePhotoUrl(photoPath) {
                const path = String(photoPath || '').trim();
                if (!path) return null;

                if (path.startsWith('http://') || path.startsWith('https://')) return path;
                if (path.startsWith('/')) return path;
                if (path.startsWith('images/')) return '/' + path;
                if (path.startsWith('storage/')) return '/' + path;

                // Assume public disk path (e.g. "profile-photos/...")
                return '/storage/' + path.replace(/^\/+/, '');
            }

            // Create a new chat window
            function createChatWindow(userId, userName, photoPath = '') {
                // If the user picker is open, close it so we never stack "windows".
                if (usersListWindow && document.body.contains(usersListWindow)) {
                    usersListWindow.remove();
                    usersListWindow = null;
                }

                // If it already exists, just focus it (no duplicates).
                if (activeChats.has(userId)) {
                    const existingWindow = activeChats.get(userId);
                    bringToFront(existingWindow);
                    const input = existingWindow.querySelector('input');
                    if (input) input.focus();
                    return;
                }

                // Only one chat window at a time: close any other open chat windows (keep shortcuts).
                closeAllChatWindows({ removeShortcuts: false });

                const chatWindow = document.createElement('div');
                chatWindow.className = 'w-80 overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-black/5 transition-all duration-300 cursor-move';
                chatWindow.dataset.userId = userId;
                chatWindow.style.transform = 'translate3d(0, 0, 0)';
                bringToFront(chatWindow);

                const initials = String(userName || 'U')
                    .trim()
                    .split(/\s+/)
                    .slice(0, 2)
                    .map(p => p.charAt(0).toUpperCase())
                    .join('') || 'U';

                const avatarUrl = resolvePhotoUrl(photoPath);
                const avatarInner = avatarUrl
                    ? `<img src="${avatarUrl}" alt="${escapeHtml(userName)}" class="h-9 w-9 rounded-full object-cover border border-white/20 shadow-sm" />`
                    : `<div class="h-9 w-9 rounded-full bg-white/15 flex items-center justify-center text-sm font-bold">${initials}</div>`;
                const avatarHtml = `
                    <div class="relative overflow-visible">
                        <span id="chat-online-dot-${userId}" class="absolute top-0 left-0 -translate-x-1/2 -translate-y-1/2 h-2.5 w-2.5 rounded-full bg-emerald-500 ring-2 ring-white z-30 hidden pointer-events-none"></span>
                        ${avatarInner}
                    </div>
                `;

                chatWindow.innerHTML = `
            <div class="px-4 py-3 border-b border-white/10 bg-gradient-to-r from-slate-900 to-slate-800 text-white" id="chat-header-${userId}">
                <div class="flex justify-between items-center gap-3">
                    <div class="flex items-center gap-3 min-w-0">
                        ${avatarHtml}
                        <div class="min-w-0">
                            <h3 class="text-sm font-semibold truncate">${userName}</h3>
                            <div id="chat-status-${userId}" data-typing="0" class="text-[11px] text-white/70 leading-tight truncate">Offline</div>
                        </div>
                    </div>
                    <div class="flex items-center space-x-2">
                        <button class="text-white hover:text-gray-200 back-to-users" data-user-id="${userId}">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                            </svg>
                        </button>
                        <button class="text-white hover:text-gray-200 collapse-chat" data-user-id="${userId}" title="Collapse">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                        <button class="text-white hover:text-gray-200 close-chat" data-user-id="${userId}">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
            <div class="h-[300px] flex flex-col">
                <div class="flex-1 overflow-y-auto p-3 bg-[radial-gradient(ellipse_at_top,_var(--tw-gradient-stops))] from-slate-50 via-white to-white" id="messages-container-${userId}">
                    <div class="text-center text-gray-500 mt-4 text-sm">
                        Loading messages...
                    </div>
                </div>
                <div class="p-3 border-t border-gray-200 bg-white">
                    <div class="relative">
                        <div class="hidden absolute bottom-12 left-0 right-0 bg-white border border-gray-200 rounded-xl shadow-lg p-2"
                             id="emoji-picker-${userId}">
                            <div class="grid grid-cols-8 gap-1 text-lg">
                                ${['😀','😁','😂','🤣','😊','😍','😘','😎','🤔','😅','😢','😭','😡','👍','🙏','👏','💪','🔥','🎉','✅','❌','📌','🧪','🧬','🐾','🌍','📎','📄','📊','🔬','📍','➡️'].map(e => `<button type="button" class="hover:bg-gray-100 rounded-lg p-1 emoji-btn" data-emoji="${e}">${e}</button>`).join('')}
                            </div>
                        </div>

                        <form class="flex items-center gap-2 w-full message-form" data-user-id="${userId}">
                            <button type="button" class="w-9 h-9 rounded-lg bg-gray-100 hover:bg-gray-200 flex items-center justify-center"
                                    title="Emoji" aria-label="Emoji" id="emoji-button-${userId}">
                                <svg class="w-5 h-5 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M12 22a10 10 0 110-20 10 10 0 010 20z"></path>
                                </svg>
                            </button>
                            <input
                                type="text"
                                class="min-w-0 flex-1 border border-gray-300 rounded-xl px-3 py-2 text-sm focus:outline-none focus:border-slate-700 focus:ring-2 focus:ring-slate-200"
                                placeholder="Message…"
                            >
                            <button
                                type="submit"
                                class="shrink-0 inline-flex items-center gap-2 bg-blue-600 text-white px-4 py-2 rounded-xl hover:bg-blue-700 focus:outline-none text-sm font-semibold shadow-sm"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M22 2L11 13"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M22 2L15 22l-4-9-9-4 20-7z"></path>
                                </svg>
                                Send
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        `;

                chatWindowsContainer.appendChild(chatWindow);
                activeChats.set(userId, chatWindow);
                ensureChatShortcut(userId, userName, photoPath);
                dragElement(chatWindow);

                // Load messages
                loadMessages(userId);

                // Start polling for new messages
                const messagePollingInterval = setInterval(() => loadMessages(userId), 3000);
                chatWindow.dataset.pollingInterval = messagePollingInterval;
                const typingPollingInterval = setInterval(() => pollTypingStatus(userId), 1500);
                chatWindow.dataset.typingPollingInterval = typingPollingInterval;
                pollTypingStatus(userId);
                refreshOnlineStatuses();

                // Handle message submission
                const messageForm = chatWindow.querySelector('.message-form');
                const messageInput = messageForm.querySelector('input');
                const emojiButton = chatWindow.querySelector(`#emoji-button-${userId}`);
                const emojiPicker = chatWindow.querySelector(`#emoji-picker-${userId}`);

                messageForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    const content = messageInput.value.trim();
                    if (!content) return;

                    stopTyping(userId);
                    const activeTypingHeartbeat = typingHeartbeatIntervals.get(String(userId));
                    if (activeTypingHeartbeat) {
                        clearInterval(activeTypingHeartbeat);
                        typingHeartbeatIntervals.delete(String(userId));
                    }
                    sendMessage(userId, content);
                    messageInput.value = '';
                });

                messageInput.addEventListener('input', function() {
                    const value = (messageInput.value || '').trim();
                    const id = String(userId);
                    if (value === '') {
                        stopTyping(id);
                        const activeTypingHeartbeat = typingHeartbeatIntervals.get(id);
                        if (activeTypingHeartbeat) {
                            clearInterval(activeTypingHeartbeat);
                            typingHeartbeatIntervals.delete(id);
                        }
                        return;
                    }

                    if (!typingHeartbeatIntervals.has(id)) {
                        startTyping(id);
                        const hb = setInterval(() => {
                            const currentValue = (messageInput.value || '').trim();
                            if (currentValue === '') {
                                clearInterval(hb);
                                typingHeartbeatIntervals.delete(id);
                                stopTyping(id);
                                return;
                            }
                            startTyping(id);
                        }, 3000);
                        typingHeartbeatIntervals.set(id, hb);
                    }
                });

                if (emojiButton && emojiPicker) {
                    emojiButton.addEventListener('click', function() {
                        emojiPicker.classList.toggle('hidden');
                    });

                    emojiPicker.querySelectorAll('.emoji-btn').forEach(btn => {
                        btn.addEventListener('click', function() {
                            const emoji = this.dataset.emoji;
                            if (!emoji) return;
                            messageInput.value = (messageInput.value || '') + emoji;
                            messageInput.focus();
                        });
                    });
                }

                // Handle close
                const closeButton = chatWindow.querySelector('.close-chat');
                closeButton.addEventListener('click', function() {
                    closeChatWindowByUserId(userId, { removeShortcut: true });
                });

                // Handle collapse (keep avatar shortcut)
                const collapseButton = chatWindow.querySelector('.collapse-chat');
                collapseButton.addEventListener('click', function() {
                    closeChatWindowByUserId(userId, { removeShortcut: false });
                });

                // Handle back to users list
                const backButton = chatWindow.querySelector('.back-to-users');
                backButton.addEventListener('click', function() {
                    closeChatWindowByUserId(userId, { removeShortcut: false });
                    showUsersList();
                });

                // Focus the input field
                messageInput.focus();
            }

            // Show users list
            function showUsersList() {
                if (usersListWindow && document.body.contains(usersListWindow)) {
                    bringToFront(usersListWindow);
                    const search = usersListWindow.querySelector('#chat-search');
                    if (search) search.focus();
                    return;
                }

                usersListWindow = document.createElement('div');
                usersListWindow.className =
                    'w-80 overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-black/5 transition-all duration-300 cursor-move';
                bringToFront(usersListWindow);
                usersListWindow.innerHTML = `
            <div class="px-4 py-3 border-b border-white/10 bg-gradient-to-r from-slate-900 to-slate-800 text-white">
                <div class="flex justify-between items-center gap-3">
                    <div>
                        <h3 class="text-sm font-semibold">Chats</h3>
                        <div class="text-[11px] text-white/70">Pick a collaborator</div>
                    </div>
                    <button class="text-white hover:text-gray-200 close-users-list">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>
            <div class="p-3 border-b border-gray-200 bg-white">
                <div class="relative">
                    <div class="absolute inset-y-0 left-3 flex items-center text-gray-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M11 19a8 8 0 100-16 8 8 0 000 16z"></path>
                        </svg>
                    </div>
                    <input
                        id="chat-search"
                        type="text"
                        class="w-full rounded-xl border border-gray-300 bg-gray-50 pl-9 pr-3 py-2 text-sm focus:outline-none focus:border-slate-700 focus:ring-2 focus:ring-slate-200"
                        placeholder="Search people…"
                    />
                </div>
            </div>
            <div class="h-[300px] overflow-y-auto p-2 bg-gray-50" id="users-list">
                Loading users...
            </div>
        `;

                chatWindowsContainer.appendChild(usersListWindow);
                dragElement(usersListWindow);

                // Load users
                loadUsers(usersListWindow);

                // Handle close
                const closeButton = usersListWindow.querySelector('.close-users-list');
                closeButton.addEventListener('click', function() {
                    usersListWindow.remove();
                    usersListWindow = null;
                });
            }

            // Load users
            function loadUsers(container) {
                const usersList = container.querySelector('#users-list');
                const search = container.querySelector('#chat-search');
                fetch('/chat')
                    .then(response => response.text())
                    .then(html => {
                        const parser = new DOMParser();
                        const doc = parser.parseFromString(html, 'text/html');
                        const users = doc.querySelectorAll('.user-chat');
                        usersList.innerHTML = '';
                        users.forEach(user => {
                            const userElement = user.cloneNode(true);
                            const userId = userElement.dataset.userId;
                            userElement.classList.add('bg-white', 'shadow-sm', 'border', 'border-gray-200');
                            const avatar = userElement.querySelector('.w-8.h-8');
                            if (avatar) {
                                avatar.classList.add('relative', 'overflow-visible');
                                avatar.classList.remove('overflow-hidden');
                                const onlineDot = document.createElement('span');
                                onlineDot.className = 'absolute top-0 left-0 -translate-x-1/2 -translate-y-1/2 h-2.5 w-2.5 rounded-full bg-emerald-500 ring-2 ring-white z-30 hidden pointer-events-none';
                                onlineDot.setAttribute('data-user-online-dot', '1');
                                avatar.appendChild(onlineDot);
                            }

                            // Add unread badge container
                            const badgeContainer = document.createElement('div');
                            badgeContainer.className = 'ml-auto';
                            userElement.querySelector('div').appendChild(badgeContainer);

                            // Add unread badge
                            const badge = document.createElement('span');
                            badge.className =
                                'bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center hidden';
                            badge.id = `unread-badge-${userId}`;
                            badgeContainer.appendChild(badge);

                            // Load unread count for this user
                            loadUserUnreadCount(userId);

                            userElement.addEventListener('click', function() {
                                const userName = this.dataset.userName || this.querySelector('p')?.textContent || 'Chat';
                                const photoPath = this.dataset.photoPath || '';
                                container.remove();
                                createChatWindow(userId, userName, photoPath);
                            });
                            usersList.appendChild(userElement);
                        });
                        refreshOnlineStatuses();

                        if (search) {
                            const runFilter = () => {
                                const q = (search.value || '').trim().toLowerCase();
                                usersList.querySelectorAll('.user-chat').forEach(el => {
                                    const text = (el.textContent || '').toLowerCase();
                                    el.classList.toggle('hidden', q !== '' && !text.includes(q));
                                });
                            };
                            search.addEventListener('input', runFilter);
                            runFilter();
                            setTimeout(() => search.focus(), 0);
                        }
                    })
                    .catch(error => console.error('Error loading users:', error));
            }

            // Load unread count for a specific user
            function loadUserUnreadCount(userId) {
                fetch(`/messages/${userId}/unread/count`)
                    .then(response => response.json())
                    .then(data => {
                        const count = data.count || 0;
                        const badge = document.getElementById(`unread-badge-${userId}`);
                        if (badge) {
                            if (count > 0) {
                                badge.textContent = count;
                                badge.classList.remove('hidden');
                            } else {
                                badge.classList.add('hidden');
                            }
                        }

                        const shortcut = chatShortcuts?.querySelector(`[data-shortcut-user-id="${userId}"]`);
                        if (shortcut) {
                            const shortcutBadge = shortcut.querySelector('[data-shortcut-unread="1"]');
                            if (shortcutBadge) {
                                if (count > 0) {
                                    shortcutBadge.textContent = count > 99 ? '99+' : String(count);
                                    shortcutBadge.classList.remove('hidden');
                                } else {
                                    shortcutBadge.classList.add('hidden');
                                }
                            }
                        } else if (count > 0) {
                            // If this user has unread messages, auto-create their shortcut button.
                            hydrateUserMapIfNeeded().then((userMap) => {
                                const u = userMap.get(String(userId));
                                if (!u) return;
                                ensureChatShortcut(String(userId), u.userName, u.photoPath);
                            });
                        }
                    })
                    .catch(error => console.error('Error loading user unread count:', error));
            }

            // Load messages
            function loadMessages(userId) {
                const messagesContainer = document.getElementById(`messages-container-${userId}`);
                if (!messagesContainer) return;

                // Store current scroll position and height
                const wasAtBottom = messagesContainer.scrollHeight - messagesContainer.scrollTop === messagesContainer.clientHeight;
                const oldScrollHeight = messagesContainer.scrollHeight;

                fetch(`/messages/${userId}`)
                    .then(response => response.json())
                    .then(messages => {
                        messagesContainer.innerHTML = '';
                        let lastDayKey = null;
                        messages.forEach(message => {
                            const dayKey = new Date(message.created_at).toDateString();
                            if (dayKey !== lastDayKey) {
                                messagesContainer.appendChild(createDateSeparator(message.created_at));
                                lastDayKey = dayKey;
                            }

                            const messageElement = createMessageElement(message, userId);
                            messagesContainer.appendChild(messageElement);
                        });
                        
                        // Only scroll to bottom if we were already at the bottom
                        if (wasAtBottom) {
                            messagesContainer.scrollTop = messagesContainer.scrollHeight;
                        } else {
                            // Maintain relative scroll position
                            const newScrollHeight = messagesContainer.scrollHeight;
                            const scrollDiff = newScrollHeight - oldScrollHeight;
                            messagesContainer.scrollTop += scrollDiff;
                        }
                        
                        loadUnreadCount();
                        loadUserUnreadCount(userId);

                    })
                    .catch(error => console.error('Error loading messages:', error));
            }

            // Send message
            function sendMessage(userId, content) {
                fetch('/messages', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({
                            receiver_id: userId,
                            content: content
                        })
                    })
                    .then(response => response.json())
                    .then(message => {
                        const messagesContainer = document.getElementById(`messages-container-${userId}`);
                        if (messagesContainer) {
                            const messageElement = createMessageElement(message, userId);
                            messagesContainer.appendChild(messageElement);
                            messagesContainer.scrollTop = messagesContainer.scrollHeight;
                        }
                    })
                    .catch(error => console.error('Error sending message:', error));
            }

            // Create message element
            function createMessageElement(message, peerUserId) {
                const isCurrentUser = message.sender_id === {{ Auth::id() }};
                const messageDiv = document.createElement('div');
                messageDiv.className = `flex ${isCurrentUser ? 'justify-end' : 'justify-start'} mb-2`;

                // Process the message content to convert @ mentions to links
                const processedContent = processMessageContent(message.content);
                const time = timeLabel(message.created_at);
                let ticksHtml = '';
                if (isCurrentUser) {
                    const visualized = !!message.is_read;
                    const delivered = visualized || isPeerOnline(peerUserId);
                    if (visualized) {
                        ticksHtml = `<span class="inline-flex items-center text-[10px] text-sky-400"><i class="fa-solid fa-check"></i><i class="fa-solid fa-check -ml-1"></i></span>`;
                    } else if (delivered) {
                        ticksHtml = `<span class="inline-flex items-center text-[10px] text-emerald-500"><i class="fa-solid fa-check"></i><i class="fa-solid fa-check -ml-1"></i></span>`;
                    } else {
                        ticksHtml = `<span class="inline-flex items-center text-[10px] text-emerald-500"><i class="fa-solid fa-check"></i></span>`;
                    }
                }

                messageDiv.innerHTML = `
            <div class="max-w-[80%]">
                <div class="${isCurrentUser ? 'bg-blue-600 text-white' : 'bg-white text-gray-900 border border-gray-200'} rounded-2xl px-3 py-2 shadow-sm">
                    <div class="break-words whitespace-pre-wrap text-sm leading-snug">${processedContent}</div>
                    <div class="mt-1 flex justify-end items-center gap-1">
                        <span class="text-[10px] ${isCurrentUser ? 'text-blue-100' : 'text-gray-500'}">${time}</span>
                        ${ticksHtml}
                    </div>
                </div>
            </div>
        `;

                return messageDiv;
            }

            // Expose a tiny API so profile pages can open a chat directly.
            window.openChatWithUser = function(userId, userName, photoPath = '') {
                if (!userId) return;
                createChatWindow(String(userId), String(userName || 'Chat'), String(photoPath || ''));
            };

            let lastTotalUnreadCount = 0;
            let unreadShortcutSyncTimer = null;

            function queueUnreadShortcutSync() {
                if (unreadShortcutSyncTimer) return;
                unreadShortcutSyncTimer = setTimeout(() => {
                    unreadShortcutSyncTimer = null;
                    if (document.hidden) return;
                    if (lastTotalUnreadCount <= 0) return;
                    hydrateUserMapIfNeeded().then((userMap) => {
                        // Ask for per-user counts; loadUserUnreadCount will auto-create shortcuts when needed.
                        userMap.forEach((_, userId) => loadUserUnreadCount(String(userId)));
                    });
                }, 750);
            }

            // While there are unread messages, periodically try to discover who sent them.
            setInterval(() => {
                if (document.hidden) return;
                if (lastTotalUnreadCount > 0) {
                    queueUnreadShortcutSync();
                }
            }, 20000);

            // Load unread message count
            function loadUnreadCount() {
                fetch('/messages/unread/count')
                    .then(response => response.json())
                    .then(data => {
                        const count = data.count || 0;
                        if (count > 0) {
                            chatBadge.textContent = count;
                            chatBadge.classList.remove('hidden');
                        } else {
                            chatBadge.classList.add('hidden');
                        }

                        // If there are unreads, ensure the sender shortcuts appear automatically.
                        const prev = lastTotalUnreadCount;
                        lastTotalUnreadCount = count;
                        if (count > 0 && count !== prev) {
                            queueUnreadShortcutSync();
                        }
                    })
                    .catch(error => console.error('Error loading unread count:', error));
            }

            // Toggle chat window
            if (chatToggle) {
                chatToggle.addEventListener('click', function(e) {
                    if (!isDragging) {
                        // Opening the picker always closes the active chat window (but keeps shortcuts).
                        closeAllChatWindows({ removeShortcuts: false });
                        if (usersListWindow && document.body.contains(usersListWindow)) {
                            usersListWindow.remove();
                            usersListWindow = null;
                        } else {
                            showUsersList();
                        }
                    }
                });
            }

            // Initial load of unread count
            loadUnreadCount();
            sendHeartbeat();
            refreshOnlineStatuses();
            // Poll for new unread messages every 30 seconds
            setInterval(loadUnreadCount, 30000);
            setInterval(sendHeartbeat, 25000);
            setInterval(refreshOnlineStatuses, 10000);

            document.addEventListener('visibilitychange', function() {
                if (!document.hidden) {
                    sendHeartbeat();
                    refreshOnlineStatuses();
                }
            });

            // Restore pinned chat shortcuts across pages/reloads.
            getPinnedChats().forEach((c) => {
                if (!c || !c.userId) return;
                ensureChatShortcut(String(c.userId), String(c.userName || 'Chat'), String(c.photoPath || ''));
            });

            // Poll per-person unread counts for pinned chats.
            setInterval(() => {
                getPinnedChats().forEach((c) => {
                    if (!c || !c.userId) return;
                    loadUserUnreadCount(String(c.userId));
                });
            }, 10000);
        });
    </script>
@endpush
