<?php
// ================================================
// chatbot_widget.php — AI Chatbot Widget
// ================================================
?>

<!-- Chatbot Toggle Button -->
<div id="chatbot-toggle" onclick="toggleChatbot()">
    <i class="fas fa-robot"></i>
    <span class="chatbot-badge">AI</span>
    <span class="chatbot-notification"></span>
    <span class="chatbot-tooltip">Uliza mimi kitu! 💬</span>
</div>

<!-- Chatbot Window -->
<div id="chatbot-window" style="display: none;">
    <div class="chatbot-header">
        <div class="chatbot-header-left">
            <div class="chatbot-avatar">
                <i class="fas fa-robot"></i>
            </div>
            <div>
                <strong>Cafeteria Assistant</strong>
                <small><span class="online-dot"></span> Yupo mtandaoni</small>
            </div>
        </div>
        <span onclick="toggleChatbot()" style="cursor: pointer; font-size: 1.2em;">
            <i class="fas fa-times"></i>
        </span>
    </div>
    <div class="chatbot-messages" id="chatbot-messages">
        <div class="chatbot-message bot">
            👋 Habari! Mimi ni <strong>Cafeteria AI</strong>.<br><br>
            Naweza kukusaidia na:
            <br>• 🍽️ Menu na bei
            <br>• 📦 Kufuatilia oda yako
            <br>• 🥗 Vyakula vya lishe maalum
            <br>• 📅 Kuagiza mapema
            <br>• ✨ Maombi maalum
            <br><br>Uliza kitu chochote!
        </div>
    </div>
    <div class="chatbot-quick-actions">
        <button onclick="quickAsk('Mna menu gani?')">🍽️ Menu</button>
        <button onclick="quickAsk('Oda yangu iko wapi?')">📦 Oda yangu</button>
        <button onclick="quickAsk('Mna vyakula vya halal?')">🥗 Lishe</button>
        <button onclick="quickAsk('Naweza kuagiza mapema?')">📅 Pre-order</button>
    </div>
    <div class="chatbot-input">
        <input type="text" id="chatbot-input" placeholder="Andika swali lako..."
               onkeypress="if(event.key==='Enter') sendMessage()">
        <button onclick="sendMessage()">
            <i class="fas fa-paper-plane"></i>
        </button>
    </div>
</div>

<style>
/* ============================================
   CHATBOT TOGGLE BUTTON — MAIN ICON
   ============================================ */
#chatbot-toggle {
    position: fixed;
    bottom: 25px;
    right: 25px;
    width: 65px;
    height: 65px;
    background: linear-gradient(135deg, #FF6B35 0%, #F7931E 50%, #FFD23F 100%);
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.8em;
    cursor: pointer;
    box-shadow: 0 8px 25px rgba(255, 107, 53, 0.5),
                0 4px 15px rgba(247, 147, 30, 0.4);
    z-index: 9999;
    transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    animation: chatbot-pulse 2s infinite;
}

#chatbot-toggle:hover {
    transform: scale(1.15) rotate(-5deg);
    box-shadow: 0 12px 35px rgba(255, 107, 53, 0.7),
                0 6px 20px rgba(247, 147, 30, 0.6);
}

#chatbot-toggle:active {
    transform: scale(0.95);
}

/* Pulse animation — inavuma kama moyo */
@keyframes chatbot-pulse {
    0% {
        box-shadow: 0 8px 25px rgba(255, 107, 53, 0.5),
                    0 0 0 0 rgba(255, 107, 53, 0.7);
    }
    70% {
        box-shadow: 0 8px 25px rgba(255, 107, 53, 0.5),
                    0 0 0 20px rgba(255, 107, 53, 0);
    }
    100% {
        box-shadow: 0 8px 25px rgba(255, 107, 53, 0.5),
                    0 0 0 0 rgba(255, 107, 53, 0);
    }
}

/* Badge ya "AI" */
.chatbot-badge {
    position: absolute;
    top: -5px;
    right: -5px;
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    font-size: 0.55em;
    font-weight: bold;
    padding: 3px 7px;
    border-radius: 10px;
    box-shadow: 0 3px 8px rgba(102, 126, 234, 0.6);
    letter-spacing: 0.5px;
    animation: badge-glow 3s infinite;
}

@keyframes badge-glow {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.1); }
}

/* Notification dot — kuna ujumbe mpya */
.chatbot-notification {
    position: absolute;
    top: 5px;
    right: 5px;
    width: 12px;
    height: 12px;
    background: #ff3b30;
    border-radius: 50%;
    border: 2px solid white;
    animation: notification-blink 1.5s infinite;
}

@keyframes notification-blink {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.3; }
}

/* Tooltip — inaonekana juu ya icon */
.chatbot-tooltip {
    position: absolute;
    bottom: 75px;
    right: 0;
    background: #2c3e50;
    color: white;
    padding: 8px 14px;
    border-radius: 8px;
    font-size: 0.75em;
    white-space: nowrap;
    opacity: 0;
    pointer-events: none;
    transition: opacity 0.3s;
    font-weight: 500;
    box-shadow: 0 4px 12px rgba(0,0,0,0.2);
}

.chatbot-tooltip::after {
    content: '';
    position: absolute;
    top: 100%;
    right: 25px;
    border: 6px solid transparent;
    border-top-color: #2c3e50;
}

#chatbot-toggle:hover .chatbot-tooltip {
    opacity: 1;
}

/* ============================================
   CHAT WINDOW
   ============================================ */
#chatbot-window {
    position: fixed;
    bottom: 105px;
    right: 25px;
    width: 380px;
    max-width: calc(100vw - 50px);
    height: 550px;
    max-height: calc(100vh - 150px);
    background: white;
    border-radius: 20px;
    box-shadow: 0 15px 50px rgba(0,0,0,0.3);
    display: flex;
    flex-direction: column;
    overflow: hidden;
    z-index: 9999;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    animation: chat-slide-up 0.4s ease-out;
}

@keyframes chat-slide-up {
    from {
        opacity: 0;
        transform: translateY(30px) scale(0.9);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

/* Header */
.chatbot-header {
    background: linear-gradient(135deg, #FF6B35 0%, #F7931E 100%);
    color: white;
    padding: 15px 18px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.chatbot-header-left {
    display: flex;
    align-items: center;
    gap: 12px;
}

.chatbot-avatar {
    width: 40px;
    height: 40px;
    background: rgba(255,255,255,0.2);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.3em;
}

.chatbot-header strong {
    display: block;
    font-size: 1em;
}

.chatbot-header small {
    font-size: 0.75em;
    opacity: 0.9;
    display: flex;
    align-items: center;
    gap: 5px;
    margin-top: 2px;
}

.online-dot {
    display: inline-block;
    width: 7px;
    height: 7px;
    background: #4CAF50;
    border-radius: 50%;
    box-shadow: 0 0 6px #4CAF50;
}

/* Messages */
.chatbot-messages {
    flex: 1;
    overflow-y: auto;
    padding: 18px;
    background: #f8f9fa;
}

.chatbot-message {
    padding: 12px 16px;
    border-radius: 16px;
    margin-bottom: 10px;
    max-width: 85%;
    word-wrap: break-word;
    white-space: pre-wrap;
    line-height: 1.5;
    font-size: 0.92em;
    animation: message-in 0.3s ease-out;
}

@keyframes message-in {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.chatbot-message.bot {
    background: white;
    color: #333;
    border: 1px solid #e9ecef;
    border-bottom-left-radius: 4px;
}

.chatbot-message.user {
    background: linear-gradient(135deg, #FF6B35, #F7931E);
    color: white;
    margin-left: auto;
    border-bottom-right-radius: 4px;
}

/* Quick Actions */
.chatbot-quick-actions {
    display: flex;
    gap: 6px;
    padding: 10px 15px;
    background: white;
    border-top: 1px solid #f0f0f0;
    overflow-x: auto;
    scrollbar-width: none;
}

.chatbot-quick-actions::-webkit-scrollbar {
    display: none;
}

.chatbot-quick-actions button {
    background: #fff5ee;
    color: #FF6B35;
    border: 1.5px solid #FFD4B8;
    padding: 6px 12px;
    border-radius: 15px;
    font-size: 0.78em;
    cursor: pointer;
    white-space: nowrap;
    transition: all 0.2s;
    font-weight: 500;
}

.chatbot-quick-actions button:hover {
    background: #FF6B35;
    color: white;
    border-color: #FF6B35;
    transform: translateY(-2px);
}

/* Input */
.chatbot-input {
    display: flex;
    padding: 12px 15px;
    background: white;
    border-top: 1px solid #f0f0f0;
    gap: 8px;
}

.chatbot-input input {
    flex: 1;
    padding: 12px 16px;
    border: 1.5px solid #e0e0e0;
    border-radius: 22px;
    outline: none;
    font-size: 0.92em;
    transition: border-color 0.2s;
    font-family: inherit;
}

.chatbot-input input:focus {
    border-color: #FF6B35;
}

.chatbot-input button {
    background: linear-gradient(135deg, #FF6B35, #F7931E);
    color: white;
    border: none;
    width: 44px;
    height: 44px;
    border-radius: 50%;
    cursor: pointer;
    font-size: 1em;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: transform 0.2s;
    box-shadow: 0 4px 10px rgba(255,107,53,0.3);
}

.chatbot-input button:hover {
    transform: scale(1.1) rotate(15deg);
}

/* Typing indicator */
.typing-indicator {
    display: flex;
    gap: 4px;
    padding: 12px 16px;
}

.typing-indicator span {
    width: 8px;
    height: 8px;
    background: #ccc;
    border-radius: 50%;
    animation: typing 1.4s infinite;
}

.typing-indicator span:nth-child(2) { animation-delay: 0.2s; }
.typing-indicator span:nth-child(3) { animation-delay: 0.4s; }

@keyframes typing {
    0%, 60%, 100% { transform: translateY(0); opacity: 0.5; }
    30% { transform: translateY(-8px); opacity: 1; }
}

/* Mobile */
@media (max-width: 480px) {
    #chatbot-window {
        width: calc(100vw - 30px);
        height: calc(100vh - 130px);
        bottom: 90px;
        right: 15px;
    }
    #chatbot-toggle {
        width: 58px;
        height: 58px;
        font-size: 1.5em;
        bottom: 20px;
        right: 20px;
    }
    .chatbot-tooltip { display: none; }
}
</style>

<script>
function toggleChatbot() {
    const w = document.getElementById('chatbot-window');
    const isHidden = (w.style.display === 'none' || w.style.display === '');
    w.style.display = isHidden ? 'flex' : 'none';
    if (isHidden) {
        // Remove notification when opened
        const notif = document.querySelector('.chatbot-notification');
        if (notif) notif.style.display = 'none';
        setTimeout(() => document.getElementById('chatbot-input').focus(), 100);
    }
}

function quickAsk(text) {
    document.getElementById('chatbot-input').value = text;
    sendMessage();
}

function sendMessage() {
    const input = document.getElementById('chatbot-input');
    const msg = input.value.trim();
    if (!msg) return;

    addMessage(msg, 'user');
    input.value = '';

    // Show typing indicator
    showTyping();

    fetch('chatbot_api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ message: msg })
    })
    .then(r => r.json())
    .then(data => {
        hideTyping();
        addMessage(data.reply || 'Samahani, kuna hitilafu.', 'bot');
    })
    .catch(err => {
        hideTyping();
        addMessage('Samahani, mtandao una tatizo. Jaribu tena.', 'bot');
    });
}

function addMessage(text, who) {
    const box = document.getElementById('chatbot-messages');
    const div = document.createElement('div');
    div.className = 'chatbot-message ' + who;
    div.innerHTML = text.replace(/\n/g, '<br>');
    box.appendChild(div);
    box.scrollTop = box.scrollHeight;
}

function showTyping() {
    const box = document.getElementById('chatbot-messages');
    const div = document.createElement('div');
    div.className = 'chatbot-message bot typing-indicator';
    div.id = 'typing-indicator';
    div.innerHTML = '<span></span><span></span><span></span>';
    box.appendChild(div);
    box.scrollTop = box.scrollHeight;
}

function hideTyping() {
    const el = document.getElementById('typing-indicator');
    if (el) el.remove();
}
</script>