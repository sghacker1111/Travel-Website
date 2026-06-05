/* ==========================================================================
   AI local Search Agent Widget - Top Everest Treks
   ========================================================================== */

document.addEventListener('DOMContentLoaded', () => {
  createChatWidget();
});

function createChatWidget() {
  // Check if widget already exists
  if (document.getElementById('ai-chat-widget')) return;

  // Append Chat Panel markup to body
  const chatHtml = `
    <!-- Chat Trigger Bubble -->
    <button class="chat-trigger-bubble" id="ai-chat-trigger" aria-label="Open AI Assistant">
      <svg viewBox="0 0 24 24">
        <path d="M12 2c5.52 0 10 4 10 9 0 2.21-.89 4.23-2.36 5.76L21 21l-4.75-1.25C14.97 20.47 13.53 21 12 21c-5.52 0-10-4-10-9s4.48-9 10-9zm0 2c-4.42 0-8 3.13-8 7s3.58 7 8 7c1.16 0 2.27-.22 3.28-.62l.53-.21 2.8.74-.75-2.01.27-.37C19.26 14.34 20 12.75 20 11c0-3.87-3.58-7-8-7z"/>
      </svg>
      <span>AI Help</span>
    </button>

    <!-- Chat Dialog Panel -->
    <div class="chat-dialog-panel" id="ai-chat-panel">
      <div class="chat-dialog-header">
        <div style="display:flex; align-items:center; gap:0.5rem;">
          <div class="chat-avatar-bot">
            <span style="position:relative;">AI<span style="position:absolute; bottom:-2px; right:-2px; width:8px; height:8px; background:#10b981; border-radius:50%; border:2px solid var(--color-primary);"></span></span>
          </div>
          <div>
            <h4 style="margin:0; font-size:0.95rem; font-weight:700;">Himalayan Assistant</h4>
            <span style="font-size:0.7rem; color:rgba(255,255,255,0.7); display:block; display:flex; align-items:center; gap:0.25rem;">
              <span style="color:#10b981;">●</span> Online • 24/7 Support
            </span>
          </div>
        </div>
        <button class="chat-close-btn" id="ai-chat-close" aria-label="Close Chat">✕</button>
      </div>

      <div class="chat-dialog-body" id="ai-chat-messages">
        <div class="chat-msg msg-bot">
          Hello! I am your AI assistant. Ask me questions about our Everest, Annapurna, Langtang, or Peak Climbing packages, permit fees, or itineraries!
        </div>
      </div>

      <form class="chat-dialog-footer" id="ai-chat-form">
        <input type="text" id="ai-chat-input" placeholder="Ask about EBC price, permits, or ABC..." required autocomplete="off" />
        <button type="submit" aria-label="Send message">
          <svg viewBox="0 0 24 24">
            <path d="M2 21l21-9L2 3v7l15 2-15 2z"/>
          </svg>
        </button>
      </form>
    </div>
  `;

  const widgetContainer = document.createElement('div');
  widgetContainer.id = 'ai-chat-widget';
  widgetContainer.innerHTML = chatHtml;
  document.body.appendChild(widgetContainer);

  // Inject Styles for the AI Chat widget dynamically
  const styleEl = document.createElement('style');
  styleEl.innerHTML = `
    .chat-trigger-bubble {
      position: fixed;
      bottom: 2rem;
      right: 7rem;
      background-color: var(--color-primary);
      color: white;
      border: none;
      padding: 0 1.25rem;
      height: 56px;
      border-radius: var(--radius-full);
      box-shadow: var(--shadow-md);
      display: flex;
      align-items: center;
      gap: 0.5rem;
      font-family: var(--font-heading);
      font-weight: 700;
      font-size: 0.95rem;
      z-index: 998;
      cursor: pointer;
      transition: all var(--transition-fast);
    }
    .chat-trigger-bubble:hover {
      transform: scale(1.05);
      background-color: var(--color-primary-hover);
      box-shadow: var(--shadow-lg);
    }
    .chat-trigger-bubble svg {
      width: 22px;
      height: 22px;
      fill: white;
    }
    .chat-dialog-panel {
      position: fixed;
      bottom: 6rem;
      right: 2rem;
      width: 360px;
      height: 480px;
      background: var(--bg-white);
      border: 1px solid var(--border-color);
      border-radius: var(--radius-lg);
      box-shadow: var(--shadow-lg);
      display: flex;
      flex-direction: column;
      overflow: hidden;
      z-index: 1000;
      opacity: 0;
      visibility: hidden;
      transform: translateY(20px);
      transition: all var(--transition-normal);
    }
    .chat-dialog-panel.open {
      opacity: 1;
      visibility: visible;
      transform: translateY(0);
    }
    .chat-dialog-header {
      background: var(--color-primary);
      color: white;
      padding: 1rem 1.25rem;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    .chat-avatar-bot {
      width: 32px;
      height: 32px;
      background: var(--color-secondary);
      color: white;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-family: var(--font-heading);
      font-weight: 800;
      font-size: 0.85rem;
    }
    .chat-close-btn {
      background: none;
      border: none;
      color: white;
      font-size: 1.1rem;
      cursor: pointer;
      opacity: 0.8;
    }
    .chat-close-btn:hover {
      opacity: 1;
    }
    .chat-dialog-body {
      flex-grow: 1;
      padding: 1.25rem;
      overflow-y: auto;
      display: flex;
      flex-direction: column;
      gap: 1rem;
      background-color: var(--bg-light);
    }
    .chat-msg {
      max-width: 85%;
      padding: 0.75rem 1rem;
      border-radius: var(--radius-md);
      font-size: 0.88rem;
      line-height: 1.45;
    }
    .msg-bot {
      background-color: var(--bg-white);
      border: 1px solid var(--border-color);
      color: var(--text-dark);
      align-self: flex-start;
      border-top-left-radius: 2px;
    }
    .msg-user {
      background-color: var(--color-primary);
      color: white;
      align-self: flex-end;
      border-top-right-radius: 2px;
    }
    .chat-dialog-footer {
      padding: 0.75rem 1rem;
      background: var(--bg-white);
      border-top: 1px solid var(--border-color);
      display: flex;
      gap: 0.5rem;
    }
    .chat-dialog-footer input {
      flex-grow: 1;
      border: 1px solid var(--border-color);
      border-radius: var(--radius-sm);
      padding: 0.5rem 0.85rem;
      outline: none;
      font-size: 0.88rem;
      background-color: var(--bg-light);
    }
    .chat-dialog-footer input:focus {
      border-color: var(--color-primary);
      background-color: var(--bg-white);
    }
    .chat-dialog-footer button {
      background: var(--color-primary);
      color: white;
      border: none;
      width: 36px;
      height: 36px;
      border-radius: var(--radius-sm);
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      transition: all var(--transition-fast);
    }
    .chat-dialog-footer button:hover {
      background-color: var(--color-primary-hover);
    }
    .chat-dialog-footer button svg {
      width: 16px;
      height: 16px;
      fill: white;
    }
    .chat-thinking-indicator {
      display: flex;
      gap: 0.25rem;
      padding: 0.25rem 0;
    }
    .chat-thinking-dot {
      width: 6px;
      height: 6px;
      background-color: var(--text-muted);
      border-radius: 50%;
      animation: thinking 1.4s infinite ease-in-out both;
    }
    .chat-thinking-dot:nth-child(2) { animation-delay: 0.2s; }
    .chat-thinking-dot:nth-child(3) { animation-delay: 0.4s; }
    @keyframes thinking {
      0%, 80%, 100% { transform: scale(0); }
      40% { transform: scale(1.0); }
    }
    @media (max-width: 640px) {
      .chat-dialog-panel {
        width: calc(100% - 2rem);
        right: 1rem;
        left: 1rem;
        height: 400px;
        bottom: 5rem;
      }
      .chat-trigger-bubble {
        right: 1rem;
        bottom: 1rem;
        width: 50px;
        padding: 0;
        justify-content: center;
      }
      .chat-trigger-bubble span { display: none; }
    }
  `;
  document.head.appendChild(styleEl);

  // Bind Actions
  const trigger = document.getElementById('ai-chat-trigger');
  const panel = document.getElementById('ai-chat-panel');
  const closeBtn = document.getElementById('ai-chat-close');
  const form = document.getElementById('ai-chat-form');
  const input = document.getElementById('ai-chat-input');
  const msgContainer = document.getElementById('ai-chat-messages');

  trigger.addEventListener('click', () => {
    panel.classList.toggle('open');
  });

  closeBtn.addEventListener('click', () => {
    panel.classList.remove('open');
  });

  form.addEventListener('submit', (e) => {
    e.preventDefault();
    const query = input.value.trim();
    if (!query) return;

    // Append user message
    appendMessage(query, 'user');
    input.value = '';

    // Thinking indicator
    const thinkingId = appendThinkingIndicator();

    // Fetch and answer query
    fetchAndAnswer(query, thinkingId);
  });

  function appendMessage(text, sender) {
    const msg = document.createElement('div');
    msg.className = `chat-msg msg-${sender}`;
    msg.innerHTML = text.replace(/\n/g, '<br>');
    msgContainer.appendChild(msg);
    msgContainer.scrollTop = msgContainer.scrollHeight;
  }

  function appendThinkingIndicator() {
    const indicator = document.createElement('div');
    indicator.className = 'chat-msg msg-bot';
    indicator.id = `think-${Date.now()}`;
    indicator.innerHTML = `
      <div class="chat-thinking-indicator">
        <div class="chat-thinking-dot"></div>
        <div class="chat-thinking-dot"></div>
        <div class="chat-thinking-dot"></div>
      </div>
    `;
    msgContainer.appendChild(indicator);
    msgContainer.scrollTop = msgContainer.scrollHeight;
    return indicator.id;
  }

  function removeThinkingIndicator(id) {
    const indicator = document.getElementById(id);
    if (indicator) indicator.remove();
  }

  // Fetch and answer from AI chatbot backend
  function fetchAndAnswer(query, thinkingId) {
    const formData = new FormData();
    formData.append('query', query);

    fetch('/ai-chat.php', {
      method: 'POST',
      body: formData
    })
      .then(res => {
        if (!res.ok) throw new Error("HTTP error " + res.status);
        return res.json();
      })
      .then(data => {
        removeThinkingIndicator(thinkingId);
        appendMessage(data.reply || "Sorry, I received an invalid response.", 'bot');
      })
      .catch(err => {
        console.error(err);
        removeThinkingIndicator(thinkingId);
        appendMessage("Sorry, I had trouble connecting to the chat server. Please try again later.", 'bot');
      });
  }
}
