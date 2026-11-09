<?php
session_start();
if (isset($_SESSION['user_id'])) {
    if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'employee') {
        header('Location: employee_dashboard.php'); exit();
    } else {
        header('Location: admin_dashboard.php'); exit();
    }
}
if (isset($_GET['admin'])) { header('Location: staff_login.php'); exit(); }
if (isset($_GET['customer'])) { header('Location: user/'); exit(); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cafeteria Management System</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, rgba(224, 123, 57, 0) 0%, rgba(200, 106, 47, 0) 100%), url('index.jpg') center/cover no-repeat;
            background-attachment: fixed;
            min-height: 100vh;
            display: flex; align-items: center; justify-content: center;
            overflow-x: hidden;
            padding: 40px;
        }

        /* ============================================
           LANGUAGE SWITCHER
           ============================================ */
        #lang-switcher {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 10000;
            display: flex;
            gap: 4px;
            background: rgba(74, 46, 31, 0.9);
            padding: 4px;
            border-radius: 12px;
            box-shadow: 0 6px 20px rgba(0,0,0,0.3);
            backdrop-filter: blur(10px);
        }
        #lang-switcher button {
            background: transparent;
            color: rgba(255,255,255,0.7);
            border: none;
            padding: 8px 14px;
            border-radius: 8px;
            font-size: 0.78em;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s;
            letter-spacing: 0.5px;
            font-family: inherit;
        }
        #lang-switcher button:hover {
            color: white;
            background: rgba(255,255,255,0.1);
        }
        #lang-switcher button.active {
            background: linear-gradient(135deg, #E07B39 0%, #C86A2F 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(224,123,57,0.5);
        }

        .landing-container {
            text-align: center;
            color: white;
            max-width: 1200px;
            width: 100%;
            padding: 60px 40px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 25px;
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
            position: relative;
            overflow: hidden;
        }

        .logo {
            font-size: 5em; margin-bottom: 30px;
            text-shadow: 0 2px 6px rgba(0,0,0,0.5), 0 4px 12px rgba(0,0,0,0.3);
            animation: pulse 2s ease-in-out infinite;
            display: inline-block; color: #FFFFFF;
        }
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }

        h1 {
            font-size: 3.5em; margin-bottom: 15px; color: #FFFFFF;
            text-shadow: 0 2px 4px rgba(0,0,0,0.5), 0 4px 8px rgba(0,0,0,0.4);
            font-weight: 700; letter-spacing: -1px;
        }

        .tagline {
            font-size: 1.4em; margin-bottom: 50px;
            color: #FFFFFF; font-weight: 300;
            max-width: 600px; margin-left: auto; margin-right: auto;
            line-height: 1.4; text-shadow: 0 2px 4px rgba(0,0,0,0.4);
        }

        .portal-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 40px;
            margin-top: 50px;
        }

        .portal-card {
            background: rgba(255, 255, 255, 0.15);
            border-radius: 20px; padding: 40px 30px;
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            cursor: pointer; text-decoration: none; color: white;
            display: block; position: relative; overflow: hidden;
        }
        .portal-card::before {
            content: ''; position: absolute;
            top: 0; left: -100%; width: 100%; height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
        }
        .portal-card:hover::before { left: 100%; }
        .portal-card:hover {
            transform: translateY(-15px) scale(1.02);
            box-shadow: 0 25px 50px rgba(0,0,0,0.3);
            background: rgba(255,255,255,0.25);
        }

        .portal-icon {
            font-size: 4em; margin-bottom: 25px; display: block;
            text-shadow: 0 2px 6px rgba(0,0,0,0.4), 0 4px 12px rgba(0,0,0,0.2);
            transition: transform 0.3s ease; color: #FFFFFF;
        }
        .portal-card:hover .portal-icon { transform: scale(1.1) rotate(5deg); }

        .portal-title {
            font-size: 1.8em; font-weight: 700; margin-bottom: 15px;
            text-shadow: 0 2px 4px rgba(0,0,0,0.5); color: #FFFFFF;
        }

        .portal-description {
            font-size: 1.1em; line-height: 1.6; font-weight: 300;
            color: #FFFFFF; text-shadow: 0 1px 3px rgba(0,0,0,0.3);
        }

        @media (max-width: 768px) {
            body { padding: 20px; }
            .landing-container { padding: 40px 25px; }
            h1 { font-size: 2.4em; }
            .tagline { font-size: 1.1em; }
            .portal-grid { grid-template-columns: 1fr; gap: 25px; }
            #lang-switcher { top: 12px; right: 12px; }
            #lang-switcher button { padding: 6px 10px; font-size: 0.7em; }
        }

        /* ============================================
           CHATBOT
           ============================================ */
        #chatbot-toggle {
            position: fixed; bottom: 25px; right: 25px;
            width: 68px; height: 68px;
            background: linear-gradient(135deg, #E07B39 0%, #C86A2F 100%);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            cursor: pointer;
            box-shadow: 0 8px 25px rgba(224, 123, 57, 0.5);
            z-index: 9999;
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            animation: chatbot-pulse 2s infinite;
        }
        #chatbot-toggle:hover {
            transform: scale(1.15) rotate(-5deg);
            box-shadow: 0 12px 35px rgba(224, 123, 57, 0.7);
        }
        @keyframes chatbot-pulse {
            0% { box-shadow: 0 8px 25px rgba(224,123,57,0.5), 0 0 0 0 rgba(224,123,57,0.7); }
            70% { box-shadow: 0 8px 25px rgba(224,123,57,0.5), 0 0 0 18px rgba(224,123,57,0); }
            100% { box-shadow: 0 8px 25px rgba(224,123,57,0.5), 0 0 0 0 rgba(224,123,57,0); }
        }

        .smile-head { width: 46px; height: 46px; filter: drop-shadow(0 2px 3px rgba(0,0,0,0.2)); }

        .chatbot-notification {
            position: absolute; top: 5px; right: 5px;
            width: 12px; height: 12px;
            background: #ff3b30; border-radius: 50%;
            border: 2px solid white;
            animation: notification-blink 1.5s infinite;
        }
        @keyframes notification-blink {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.3; }
        }

        .chatbot-tooltip {
            position: absolute; bottom: 78px; right: 0;
            background: #2c3e50; color: white;
            padding: 8px 14px; border-radius: 8px;
            font-size: 0.78em; white-space: nowrap;
            opacity: 0; pointer-events: none;
            transition: opacity 0.3s; font-weight: 500;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }
        .chatbot-tooltip::after {
            content: ''; position: absolute;
            top: 100%; right: 25px;
            border: 6px solid transparent;
            border-top-color: #2c3e50;
        }
        #chatbot-toggle:hover .chatbot-tooltip { opacity: 1; }

        #chatbot-window {
            position: fixed;
            right: 20px;
            width: min(420px, calc(100vw - 40px));
            background: #FDF6EE;
            border-radius: 18px;
            box-shadow: 0 15px 50px rgba(0,0,0,0.3);
            display: none;
            flex-direction: row;
            overflow: hidden;
            z-index: 9999;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 400px;
            max-height: 85vh;
        }

        .chatbot-sidebar {
            width: 130px;
            background: #4A2E1F;
            color: white;
            display: flex; flex-direction: column;
            padding: 12px 8px;
            border-right: 1px solid #2E1C11;
            flex-shrink: 0;
        }

        .sidebar-new-chat {
            background: linear-gradient(135deg, #E07B39 0%, #C86A2F 100%);
            color: white; border: none;
            padding: 10px 6px;
            border-radius: 8px;
            font-size: 0.78em;
            font-weight: 600;
            cursor: pointer;
            margin-bottom: 12px;
            display: flex; align-items: center; justify-content: center;
            gap: 5px; transition: transform 0.2s;
            white-space: nowrap;
        }
        .sidebar-new-chat:hover {
            transform: scale(1.03);
            box-shadow: 0 4px 12px rgba(224,123,57,0.4);
        }

        .sidebar-section-title {
            font-size: 0.62em;
            text-transform: uppercase;
            opacity: 0.6;
            letter-spacing: 0.5px;
            margin: 8px 4px 6px;
        }

        .sidebar-chat-list {
            flex: 1; overflow-y: auto;
            display: flex; flex-direction: column;
            gap: 4px;
            margin-bottom: 8px;
            min-height: 0;
        }

        .sidebar-chat-item {
            padding: 8px 22px 8px 6px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.72em;
            color: rgba(255,255,255,0.85);
            transition: background 0.2s;
            display: flex; align-items: center;
            gap: 5px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            position: relative;
            user-select: none;
        }
        .sidebar-chat-item:hover { background: rgba(255,255,255,0.1); }
        .sidebar-chat-item.active {
            background: rgba(224,123,57,0.4);
            color: white;
        }

        .chat-delete-btn {
            position: absolute;
            right: 2px;
            top: 50%;
            transform: translateY(-50%);
            background: transparent;
            border: none;
            color: rgba(255,255,255,0.5);
            font-size: 0.9em;
            cursor: pointer;
            padding: 2px 4px;
            opacity: 0;
            transition: opacity 0.2s;
            border-radius: 4px;
        }
        .sidebar-chat-item:hover .chat-delete-btn { opacity: 1; }
        .chat-delete-btn:hover {
            background: rgba(255,59,48,0.3);
            color: #ff6b6b;
        }

        .sidebar-delete-all {
            background: rgba(255, 59, 48, 0.15);
            color: #ff6b6b;
            border: 1px solid rgba(255, 59, 48, 0.3);
            padding: 7px 6px;
            border-radius: 6px;
            font-size: 0.7em;
            font-weight: 600;
            cursor: pointer;
            margin-top: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 4px;
            transition: all 0.2s;
            white-space: nowrap;
        }
        .sidebar-delete-all:hover {
            background: rgba(255, 59, 48, 0.3);
            color: white;
        }

        .chatbot-main {
            flex: 1; display: flex; flex-direction: column;
            background: #FDF6EE;
            overflow: hidden;
            min-width: 0;
        }

        .chatbot-header {
            background: linear-gradient(135deg, #E07B39 0%, #C86A2F 100%);
            color: white;
            padding: 12px 14px;
            display: flex; justify-content: space-between; align-items: center;
            flex-shrink: 0;
        }

        .chatbot-header-left { display: flex; align-items: center; gap: 10px; }

        .chatbot-avatar {
            width: 36px; height: 36px;
            background: rgba(255,255,255,0.25);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            overflow: hidden;
        }
        .chatbot-avatar svg { width: 32px; height: 32px; }

        .chatbot-header strong { display: block; font-size: 0.9em; }
        .chatbot-header small {
            font-size: 0.7em; opacity: 0.9;
            display: flex; align-items: center; gap: 5px;
            margin-top: 2px;
        }

        .online-dot {
            display: inline-block;
            width: 7px; height: 7px;
            background: #4CAF50; border-radius: 50%;
            box-shadow: 0 0 6px #4CAF50;
        }

        .chatbot-messages {
            flex: 1;
            overflow-y: auto;
            padding: 14px;
            background: #FAF0E6;
            scroll-behavior: smooth;
            min-height: 0;
        }

        .chatbot-message {
            padding: 10px 14px;
            border-radius: 14px;
            margin-bottom: 8px;
            max-width: 88%;
            word-wrap: break-word;
            line-height: 1.5;
            font-size: 0.85em;
        }

        .chatbot-message.bot {
            background: #FFF8F0;
            color: #3E2723;
            border: 1px solid #F0D9C1;
            border-bottom-left-radius: 4px;
        }

        .chatbot-message.user {
            background: linear-gradient(135deg, #E07B39, #C86A2F);
            color: white;
            margin-left: auto;
            border-bottom-right-radius: 4px;
        }

        .chatbot-message.bot .main-point {
            color: #000000;
            font-weight: 700;
            display: block;
            margin-top: 8px;
            margin-bottom: 4px;
            font-size: 0.95em;
        }

        .chatbot-message.bot .explanation {
            color: #3E2723;
            display: block;
            margin-bottom: 10px;
            font-weight: 400;
        }

        .chatbot-message.bot .blank-space {
            display: block;
            height: 8px;
        }

        .chatbot-input {
            display: flex;
            padding: 10px 12px;
            background: #FDF6EE;
            border-top: 1px solid #F0D9C1;
            gap: 8px;
            flex-shrink: 0;
            min-height: 58px;
        }

        .chatbot-input input {
            flex: 1;
            padding: 10px 14px;
            border: 1.5px solid #E8D4BE;
            border-radius: 20px;
            background: #FFFBF5;
            outline: none;
            font-size: 0.85em;
            transition: border-color 0.2s;
            font-family: inherit;
            color: #3E2723;
            min-width: 0;
        }
        .chatbot-input input:focus {
            border-color: #E07B39;
            background: #FFFFFF;
        }
        .chatbot-input input::placeholder { color: #B8A395; }

        .chatbot-input .mic-btn {
            background: #F0D9C1;
            color: #C86A2F;
            border: none;
            width: 38px; height: 38px;
            border-radius: 50%;
            cursor: pointer;
            font-size: 0.9em;
            display: flex; align-items: center; justify-content: center;
            transition: all 0.2s;
            flex-shrink: 0;
        }
        .chatbot-input .mic-btn:hover {
            background: #E07B39;
            color: white;
        }
        .chatbot-input .mic-btn.listening {
            background: #ff3b30;
            color: white;
            animation: mic-pulse 1s infinite;
        }
        @keyframes mic-pulse {
            0%, 100% { box-shadow: 0 0 0 0 rgba(255,59,48,0.7); }
            70% { box-shadow: 0 0 0 12px rgba(255,59,48,0); }
        }

        .chatbot-input .stop-btn {
            background: #ff3b30;
            color: white; border: none;
            width: 38px; height: 38px;
            border-radius: 50%;
            cursor: pointer; font-size: 0.9em;
            display: flex; align-items: center; justify-content: center;
            transition: all 0.2s;
            flex-shrink: 0;
            animation: stop-pulse 1s infinite;
        }
        .chatbot-input .stop-btn:hover { transform: scale(1.1); }
        @keyframes stop-pulse {
            0%, 100% { box-shadow: 0 0 0 0 rgba(255,59,48,0.7); }
            70% { box-shadow: 0 0 0 10px rgba(255,59,48,0); }
        }

        .chatbot-input .send-btn {
            background: linear-gradient(135deg, #E07B39, #C86A2F);
            color: white; border: none;
            width: 38px; height: 38px;
            border-radius: 50%;
            cursor: pointer; font-size: 0.9em;
            display: flex; align-items: center; justify-content: center;
            transition: transform 0.2s;
            box-shadow: 0 4px 10px rgba(224,123,57,0.3);
            flex-shrink: 0;
        }
        .chatbot-input .send-btn:hover { transform: scale(1.1) rotate(15deg); }

        .typing-indicator {
            display: flex; gap: 4px;
            padding: 10px 14px;
            background: #FFF8F0;
            border: 1px solid #F0D9C1;
            border-radius: 14px;
            max-width: 88%;
            width: fit-content;
        }
        .typing-indicator span {
            width: 7px; height: 7px;
            background: #C86A2F;
            border-radius: 50%;
            animation: typing 1.4s infinite;
        }
        .typing-indicator span:nth-child(2) { animation-delay: 0.2s; }
        .typing-indicator span:nth-child(3) { animation-delay: 0.4s; }
        @keyframes typing {
            0%, 60%, 100% { transform: translateY(0); opacity: 0.4; }
            30% { transform: translateY(-8px); opacity: 1; }
        }

        @media (max-width: 768px) {
            #chatbot-window {
                width: calc(100vw - 20px);
                max-width: calc(100vw - 20px);
                top: auto !important;
                bottom: 85px !important;
                height: calc(100vh - 110px) !important;
                max-height: calc(100vh - 110px) !important;
                right: 10px;
                left: 10px;
                flex-direction: column;
            }
            .chatbot-sidebar {
                width: 100%;
                height: auto;
                max-height: 110px;
                border-right: none;
                border-bottom: 1px solid #2E1C11;
                padding: 8px 6px;
                flex-shrink: 0;
            }
            .sidebar-new-chat {
                padding: 8px 12px;
                font-size: 0.75em;
                margin-bottom: 6px;
            }
            .sidebar-chat-list {
                flex-direction: row;
                overflow-x: auto;
                max-height: 40px;
                gap: 4px;
            }
            .sidebar-chat-item { min-width: 100px; font-size: 0.68em; }
            .sidebar-section-title { display: none; }
        }

        @media (max-width: 1024px) and (min-width: 769px) {
            #chatbot-window {
                width: min(380px, calc(100vw - 40px));
            }
        }

        @media (max-height: 600px) {
            #chatbot-window { min-height: 300px; }
        }

        @media (max-width: 480px) {
            #chatbot-toggle { width: 60px; height: 60px; bottom: 20px; right: 20px; }
            .chatbot-tooltip { display: none; }
        }
    </style>
</head>
<body>
    <!-- ✅ LANGUAGE SWITCHER -->
    <div id="lang-switcher">
        <button id="lang-en" onclick="setLanguage('en')" class="active">EN</button>
        <button id="lang-sw" onclick="setLanguage('sw')">SW</button>
    </div>

    <div class="landing-container">
        <div class="logo"><i class="fas fa-utensils"></i></div>

        <h1 id="txt-welcome">Welcome to Our Cafeteria</h1>
        <p class="tagline" id="txt-tagline">Delicious food, great service, unforgettable experiences</p>

        <div class="portal-grid">
            <a href="user/" class="portal-card">
                <i class="fas fa-store portal-icon"></i>
                <div class="portal-title" id="txt-customer-title">Customer Portal</div>
                <div class="portal-description" id="txt-customer-desc">
                    Browse our menu, place orders, and register for events.
                    Experience our cafeteria from the comfort of your device.
                </div>
            </a>
            <a href="?admin=1" class="portal-card">
                <i class="fas fa-user-shield portal-icon"></i>
                <div class="portal-title" id="txt-staff-title">Staff Portal</div>
                <div class="portal-description" id="txt-staff-desc">
                    Access the management system to handle orders, manage inventory,
                    process payments, and oversee daily operations.
                </div>
            </a>
        </div>
    </div>

    <!-- CHATBOT TOGGLE -->
    <div id="chatbot-toggle" onclick="toggleChatbot()">
        <svg class="smile-head" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
            <circle cx="50" cy="50" r="40" fill="#FFF5E6" stroke="#E07B39" stroke-width="3"/>
            <path d="M 15 40 Q 15 10 50 10 Q 85 10 85 40 Q 75 25 50 22 Q 25 25 15 40 Z" fill="#3E2723"/>
            <circle cx="35" cy="48" r="4" fill="#333"/>
            <circle cx="65" cy="48" r="4" fill="#333"/>
            <circle cx="36" cy="47" r="1.5" fill="#FFF"/>
            <circle cx="66" cy="47" r="1.5" fill="#FFF"/>
            <path d="M 30 62 Q 50 82 70 62" stroke="#333" stroke-width="3.5" fill="none" stroke-linecap="round"/>
            <circle cx="25" cy="58" r="5" fill="#FFB6B6" opacity="0.7"/>
            <circle cx="75" cy="58" r="5" fill="#FFB6B6" opacity="0.7"/>
        </svg>
        <span class="chatbot-notification"></span>
        <span class="chatbot-tooltip" id="txt-tooltip">Ask me anything!</span>
    </div>

    <!-- CHATBOT WINDOW -->
    <div id="chatbot-window">
        <div class="chatbot-sidebar">
            <button class="sidebar-new-chat" onclick="newChat()">
                <i class="fas fa-plus"></i> <span id="txt-new-chat">New Chat</span>
            </button>
            <div class="sidebar-section-title" id="txt-prev-chats">Previous Chats</div>
            <div class="sidebar-chat-list" id="chat-list"></div>
            <button class="sidebar-delete-all" onclick="deleteAllChats()">
                <i class="fas fa-trash"></i> <span id="txt-clear-all">Clear All</span>
            </button>
        </div>

        <div class="chatbot-main">
            <div class="chatbot-header">
                <div class="chatbot-header-left">
                    <div class="chatbot-avatar">
                        <svg viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
                            <circle cx="50" cy="50" r="40" fill="#FFF5E6" stroke="#FFF" stroke-width="2"/>
                            <path d="M 15 40 Q 15 10 50 10 Q 85 10 85 40 Q 75 25 50 22 Q 25 25 15 40 Z" fill="#3E2723"/>
                            <circle cx="35" cy="48" r="4" fill="#333"/>
                            <circle cx="65" cy="48" r="4" fill="#333"/>
                            <circle cx="36" cy="47" r="1.5" fill="#FFF"/>
                            <circle cx="66" cy="47" r="1.5" fill="#FFF"/>
                            <path d="M 30 62 Q 50 82 70 62" stroke="#333" stroke-width="3.5" fill="none" stroke-linecap="round"/>
                            <circle cx="25" cy="58" r="5" fill="#FFB6B6" opacity="0.7"/>
                            <circle cx="75" cy="58" r="5" fill="#FFB6B6" opacity="0.7"/>
                        </svg>
                    </div>
                    <div>
                        <strong id="txt-assistant-name">Cafeteria Assistant</strong>
                        <small><span class="online-dot"></span> <span id="txt-online">Online</span></small>
                    </div>
                </div>
                <span onclick="toggleChatbot()" style="cursor: pointer; font-size: 1.1em;">
                    <i class="fas fa-times"></i>
                </span>
            </div>
            <div class="chatbot-messages" id="chatbot-messages"></div>
            <div class="chatbot-input">
                <input type="text" id="chatbot-input" placeholder="Type or speak..."
                       onkeypress="if(event.key==='Enter') sendMessage()">
                <button class="mic-btn" id="mic-btn" onclick="toggleVoiceInput()" title="Speak">
                    <i class="fas fa-microphone"></i>
                </button>
                <button class="stop-btn" id="stop-btn" onclick="stopAll()" title="Stop" style="display:none;">
                    <i class="fas fa-stop"></i>
                </button>
                <button class="send-btn" onclick="sendMessage()" title="Send">
                    <i class="fas fa-paper-plane"></i>
                </button>
            </div>
        </div>
    </div>

    <script>
    // ============================================
    // LANGUAGE TRANSLATIONS
    // ============================================
    const TRANSLATIONS = {
        en: {
            welcome: 'Welcome to Our Cafeteria',
            tagline: 'Delicious food, great service, unforgettable experiences',
            customerTitle: 'Customer Portal',
            customerDesc: 'Browse our menu, place orders, and register for events. Experience our cafeteria from the comfort of your device.',
            staffTitle: 'Staff Portal',
            staffDesc: 'Access the management system to handle orders, manage inventory, process payments, and oversee daily operations.',
            tooltip: 'Ask me anything!',
            newChat: 'New Chat',
            prevChats: 'Previous Chats',
            clearAll: 'Clear All',
            assistantName: 'Cafeteria Assistant',
            online: 'Online',
            inputPlaceholder: 'Type or speak...',
            welcomeMsg: 'Hello! I am Cafeteria Assistant. How can I help you today?'
        },
        sw: {
            welcome: 'Karibu kwenye Cafeteria Yetu',
            tagline: 'Chakula kitamu, huduma nzuri, uzoefu usiosahaulika',
            customerTitle: 'Lango la Mteja',
            customerDesc: 'Angalia menu yetu, weka oda, na usajili kwa matukio. Pata huduma ya cafeteria yetu kwa urahisi kupitia kifaa chako.',
            staffTitle: 'Lango la Wafanyakazi',
            staffDesc: 'Ingia kwenye mfumo wa usimamizi kushughulikia oda, kusimamia bidhaa, kuchakata malipo, na kusimamia shughuli za kila siku.',
            tooltip: 'Niulize kitu chochote!',
            newChat: 'Mazungumzo Mpya',
            prevChats: 'Mazungumzo ya Nyuma',
            clearAll: 'Futa Zote',
            assistantName: 'Msaidizi wa Cafeteria',
            online: 'Mtandaoni',
            inputPlaceholder: 'Andika au ongea...',
            welcomeMsg: 'Habari! Mimi ni Msaidizi wa Cafeteria. Naweza kukusaidia vipi leo?'
        }
    };

    let currentLang = 'en';

    // ============================================
    // SET LANGUAGE
    // ============================================
    function setLanguage(lang) {
        if (!TRANSLATIONS[lang]) lang = 'en';
        currentLang = lang;
        localStorage.setItem('cafeteria_lang', lang);

        // Update document lang attribute
        document.documentElement.lang = lang;

        // Update buttons
        document.getElementById('lang-en').classList.toggle('active', lang === 'en');
        document.getElementById('lang-sw').classList.toggle('active', lang === 'sw');

        const t = TRANSLATIONS[lang];

        // Landing page
        document.getElementById('txt-welcome').textContent = t.welcome;
        document.getElementById('txt-tagline').textContent = t.tagline;
        document.getElementById('txt-customer-title').textContent = t.customerTitle;
        document.getElementById('txt-customer-desc').textContent = t.customerDesc;
        document.getElementById('txt-staff-title').textContent = t.staffTitle;
        document.getElementById('txt-staff-desc').textContent = t.staffDesc;

        // Chatbot UI
        document.getElementById('txt-tooltip').textContent = t.tooltip;
        document.getElementById('txt-new-chat').textContent = t.newChat;
        document.getElementById('txt-prev-chats').textContent = t.prevChats;
        document.getElementById('txt-clear-all').textContent = t.clearAll;
        document.getElementById('txt-assistant-name').textContent = t.assistantName;
        document.getElementById('txt-online').textContent = t.online;

        // Input placeholder
        const input = document.getElementById('chatbot-input');
        if (input) input.placeholder = t.inputPlaceholder;

        console.log('[LANG] Switched to:', lang);
    }

    function getCurrentLang() {
        return localStorage.getItem('cafeteria_lang') || 'en';
    }

    // ============================================
    // CONFIG
    // ============================================
    const STORAGE_KEY = 'cafeteria_chats_v3';
    const ACTIVE_KEY = 'cafeteria_active_chat_v3';
    // ✅ BADILISHO: Tumia proxy.php (InfinityFree inazuia fetch kwenda nje moja kwa moja)
    const API_URL = 'proxy.php';
    const MENU_API_URL = 'menu_api.php';

    let currentChatId = null;
    let currentMessages = [];

    let currentUtterance = null;
    let recognition = null;
    let isListening = false;
    let lastInputWasVoice = false;
    let isStreaming = false;
    let continuousVoiceMode = false;

    let cafeteriaData = {
        menu: [],
        events: [],
        payment_methods: [],
        categories: [],
        stats: {},
        team: []
    };

    // ============================================
    // POSITION CHATBOT
    // ============================================
    function positionChatbotWindow() {
        const container = document.querySelector('.landing-container');
        const win = document.getElementById('chatbot-window');
        if (!container || !win) return;

        const vh = window.innerHeight;
        const vw = window.innerWidth;

        if (vw <= 768) {
            win.style.top = '';
            win.style.bottom = '85px';
            win.style.height = 'calc(100vh - 110px)';
            win.style.maxHeight = 'calc(100vh - 110px)';
            return;
        }

        const rect = container.getBoundingClientRect();
        const offsetTop = 40;
        const offsetBottom = 40;

        let topPos = rect.top + offsetTop;
        let bottomPos = vh - rect.bottom + offsetBottom;

        if (topPos < 20) topPos = 20;
        if (bottomPos < 20) bottomPos = 20;

        const availableHeight = vh - topPos - bottomPos;
        let finalHeight = availableHeight;

        if (finalHeight < 400) {
            finalHeight = Math.min(400, vh - 40);
            topPos = (vh - finalHeight) / 2;
            win.style.top = topPos + 'px';
            win.style.bottom = 'auto';
        } else {
            win.style.top = topPos + 'px';
            win.style.bottom = bottomPos + 'px';
        }

        win.style.height = finalHeight + 'px';
        win.style.maxHeight = finalHeight + 'px';
    }

    // ============================================
    // LOAD DATA
    // ============================================
    async function loadCafeteriaData() {
        try {
            console.log('[MENU] Fetching:', MENU_API_URL);
            const res = await fetch(MENU_API_URL);
            if (!res.ok) throw new Error('HTTP ' + res.status);
            const data = await res.json();
            cafeteriaData = {
                menu: data.menu || [],
                events: data.events || [],
                payment_methods: data.payment_methods || [],
                categories: data.categories || [],
                stats: data.stats || {},
                team: data.team || []
            };
            console.log('[MENU] Loaded:', cafeteriaData.menu.length, 'items');
        } catch (err) {
            console.error('[MENU] Load failed:', err.message);
        }
    }

    // ============================================
    // STORAGE
    // ============================================
    function getAllChats() {
        try {
            const raw = localStorage.getItem(STORAGE_KEY);
            return raw ? JSON.parse(raw) : {};
        } catch (e) { return {}; }
    }
    function saveAllChats(chats) {
        localStorage.setItem(STORAGE_KEY, JSON.stringify(chats));
    }
    function getActiveChatId() { return localStorage.getItem(ACTIVE_KEY); }
    function setActiveChatId(id) { localStorage.setItem(ACTIVE_KEY, String(id)); }

    function generateId() {
        return 'chat_' + Date.now() + '_' + Math.floor(Math.random() * 1000);
    }

    // ============================================
    // INIT
    // ============================================
    function initChatbot() {
        let chats = getAllChats();
        const newId = generateId();
        chats[newId] = { title: 'New Chat', messages: [], createdAt: Date.now() };
        saveAllChats(chats);
        setActiveChatId(newId);

        currentChatId = newId;
        currentMessages = [];
        renderSidebar();
        renderMessages();
    }

    // ============================================
    // SIDEBAR
    // ============================================
    function renderSidebar() {
        const chats = getAllChats();
        const list = document.getElementById('chat-list');
        list.innerHTML = '';

        const sortedIds = Object.keys(chats).sort(
            (a, b) => chats[b].createdAt - chats[a].createdAt
        );

        sortedIds.forEach(id => {
            const chat = chats[id];
            if (!chat.messages || chat.messages.length === 0) return;

            const item = document.createElement('div');
            item.className = 'sidebar-chat-item' + (id === currentChatId ? ' active' : '');
            item.dataset.chatId = id;

            const icon = document.createElement('i');
            icon.className = 'fas fa-comment';

            const title = document.createElement('span');
            title.style.flex = '1';
            title.style.overflow = 'hidden';
            title.style.textOverflow = 'ellipsis';
            title.style.whiteSpace = 'nowrap';
            title.textContent = chat.title || 'Chat';

            const del = document.createElement('button');
            del.className = 'chat-delete-btn';
            del.title = 'Delete';
            del.innerHTML = '<i class="fas fa-times"></i>';
            del.onclick = function(e) {
                e.stopPropagation();
                deleteChat(id);
            };

            item.appendChild(icon);
            item.appendChild(title);
            item.appendChild(del);

            item.onclick = function(e) {
                if (e.target.closest('.chat-delete-btn')) return;
                switchChat(id);
            };

            list.appendChild(item);
        });
    }

    // ============================================
    // RENDER MESSAGES
    // ============================================
    function renderMessages() {
        const box = document.getElementById('chatbot-messages');
        box.innerHTML = '';
        currentMessages.forEach(m => {
            const div = document.createElement('div');
            div.className = 'chatbot-message ' + m.who;
            if (m.who === 'bot') {
                div.innerHTML = formatBotMessage(m.text);
            } else {
                div.textContent = m.text;
            }
            box.appendChild(div);
        });
        box.scrollTop = box.scrollHeight;
    }

    // ============================================
    // FORMAT BOT MESSAGE
    // ============================================
    function formatBotMessage(text) {
        const esc = document.createElement('div');
        esc.textContent = text;
        let html = esc.innerHTML;

        const lines = html.split('\n');
        let output = '';

        lines.forEach(line => {
            const trimmed = line.trim();

            if (trimmed === '') {
                output += '<span class="blank-space"></span>';
                return;
            }

            const letters = trimmed.replace(/[^A-Za-z]/g, '');
            const isAllCaps = letters.length >= 3 && letters === letters.toUpperCase();

            if (isAllCaps) {
                output += `<span class="main-point">${trimmed}</span>`;
            } else {
                output += `<span class="explanation">${trimmed}</span>`;
            }
        });

        return output;
    }

    // ============================================
    // STOP
    // ============================================
    function stopAll() {
        if (window.speechSynthesis.speaking) window.speechSynthesis.cancel();
        if (isListening && recognition) recognition.stop();
        if (isStreaming && window.currentAbortController) {
            window.currentAbortController.abort();
            isStreaming = false;
        }
        const stopBtn = document.getElementById('stop-btn');
        if (stopBtn) stopBtn.style.display = 'none';

        if (continuousVoiceMode && recognition) {
            setTimeout(() => {
                try { recognition.start(); } catch (e) {}
            }, 300);
        }
    }

    // ============================================
    // SPEAK
    // ============================================
    function speakText(text) {
        if (window.speechSynthesis.speaking) window.speechSynthesis.cancel();

        const utterance = new SpeechSynthesisUtterance(text);
        const lang = detectLanguage(text);

        utterance.lang = lang;
        utterance.rate = 0.9;
        utterance.pitch = 1.0;
        utterance.volume = 1.0;

        const voices = window.speechSynthesis.getVoices();
        const langCode = lang.substring(0, 2).toLowerCase();

        let best = voices.find(v =>
            v.lang.toLowerCase().startsWith(langCode) &&
            (v.name.includes('Google') ||
             v.name.includes('Microsoft') ||
             v.name.includes('Samantha') ||
             v.name.includes('Zira') ||
             v.name.includes('Female'))
        );
        if (!best) best = voices.find(v => v.lang.toLowerCase().startsWith(langCode));
        if (!best) best = voices.find(v => v.name.includes('Google'));
        if (best) utterance.voice = best;

        const stopBtn = document.getElementById('stop-btn');

        utterance.onstart = () => { if (stopBtn) stopBtn.style.display = 'flex'; };
        utterance.onend = () => {
            if (stopBtn) stopBtn.style.display = 'none';
            if (continuousVoiceMode && recognition) {
                setTimeout(() => {
                    try { recognition.start(); } catch (e) {}
                }, 400);
            }
        };
        utterance.onerror = () => {
            if (stopBtn) stopBtn.style.display = 'none';
            if (continuousVoiceMode && recognition) {
                setTimeout(() => {
                    try { recognition.start(); } catch (e) {}
                }, 400);
            }
        };

        currentUtterance = utterance;
        window.speechSynthesis.speak(utterance);
    }

    // ============================================
    // LANGUAGE DETECTION (for voice)
    // ============================================
    function detectLanguage(text) {
        const t = text.toLowerCase();
        const patterns = [
            { code: 'sw-KE', words: ['habari', 'asante', 'tafadhali', 'karibu', 'chakula', 'bei', 'nini', 'wapi', 'naweza', 'nataka', 'mimi', 'wewe', 'kwa', 'kula'] },
            { code: 'en-US', words: ['the', 'and', 'you', 'have', 'menu', 'price', 'order', 'food', 'hello', 'thank'] },
            { code: 'fr-FR', words: ['bonjour', 'merci', 'prix', 'menu', 'combien', 'vous'] },
            { code: 'es-ES', words: ['hola', 'gracias', 'precio', 'menú', 'cuánto'] },
            { code: 'ar-SA', words: ['مرحبا', 'شكرا', 'قائمة', 'سعر'] },
            { code: 'hi-IN', words: ['नमस्ते', 'धन्यवाद', 'कीमत', 'मेनू'] },
            { code: 'zh-CN', words: ['你好', '谢谢', '价格', '菜单'] },
            { code: 'de-DE', words: ['hallo', 'danke', 'preis', 'speisekarte'] },
            { code: 'pt-PT', words: ['olá', 'obrigado', 'preço', 'menu'] }
        ];
        let best = { code: 'en-US', score: 0 };
        for (const p of patterns) {
            let score = 0;
            for (const w of p.words) if (t.includes(w)) score++;
            if (score > best.score) best = { code: p.code, score };
        }
        return best.code;
    }

    // ============================================
    // SPEECH RECOGNITION
    // ============================================
    function initSpeechRecognition() {
        const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
        if (!SpeechRecognition) return null;

        const rec = new SpeechRecognition();
        rec.continuous = false;
        rec.interimResults = false;
        rec.maxAlternatives = 1;
        rec.lang = currentLang === 'sw' ? 'sw-KE' : 'en-US';

        rec.onstart = () => {
            isListening = true;
            document.getElementById('mic-btn').classList.add('listening');
        };

        rec.onresult = (event) => {
            const transcript = event.results[0][0].transcript;
            document.getElementById('chatbot-input').value = transcript;
            lastInputWasVoice = true;
            setTimeout(() => sendMessage(), 300);
        };

        rec.onerror = (event) => {
            isListening = false;
            document.getElementById('mic-btn').classList.remove('listening');
            if (event.error === 'not-allowed') alert('Please allow microphone access.');
        };

        rec.onend = () => {
            isListening = false;
            document.getElementById('mic-btn').classList.remove('listening');
        };

        return rec;
    }

    function toggleVoiceInput() {
        if (!recognition) {
            recognition = initSpeechRecognition();
            if (!recognition) {
                alert('Voice input not supported. Use Chrome, Edge, or Safari.');
                return;
            }
        }

        // Update recognition language based on current UI lang
        recognition.lang = currentLang === 'sw' ? 'sw-KE' : 'en-US';

        if (continuousVoiceMode) {
            continuousVoiceMode = false;
            if (isListening) recognition.stop();
            if (window.speechSynthesis.speaking) window.speechSynthesis.cancel();
            document.getElementById('mic-btn').classList.remove('listening');
            const stopBtn = document.getElementById('stop-btn');
            if (stopBtn) stopBtn.style.display = 'none';
        } else {
            continuousVoiceMode = true;
            try { recognition.start(); } catch (e) { console.warn(e); }
        }
    }

    // ============================================
    // SAVE
    // ============================================
    function saveCurrentState() {
        if (!currentChatId) return;
        const chats = getAllChats();
        if (!chats[currentChatId]) return;

        chats[currentChatId].messages = currentMessages;

        const firstUser = currentMessages.find(m => m.who === 'user');
        if (firstUser) {
            const t = firstUser.text.substring(0, 20);
            chats[currentChatId].title = t + (firstUser.text.length > 20 ? '...' : '');
        }
        saveAllChats(chats);
    }

    // ============================================
    // NEW / SWITCH / DELETE
    // ============================================
    function newChat() {
        saveCurrentState();
        const newId = generateId();
        const chats = getAllChats();
        chats[newId] = { title: 'New Chat', messages: [], createdAt: Date.now() };
        saveAllChats(chats);
        setActiveChatId(newId);
        currentChatId = newId;
        currentMessages = [];
        renderSidebar();
        renderMessages();
        document.getElementById('chatbot-input').focus();
    }

    function switchChat(id) {
        if (id === currentChatId) return;
        saveCurrentState();
        const chats = getAllChats();
        if (!chats[id]) return;
        currentChatId = id;
        currentMessages = chats[id].messages || [];
        setActiveChatId(id);
        renderSidebar();
        renderMessages();
    }

    function deleteChat(id) {
        if (!confirm(currentLang === 'sw' ? 'Futa mazungumzo haya?' : 'Delete this chat?')) return;
        const chats = getAllChats();
        delete chats[id];
        saveAllChats(chats);

        if (id === currentChatId) {
            const remaining = Object.keys(chats);
            const nonEmpty = remaining.filter(k => chats[k].messages && chats[k].messages.length > 0);

            if (nonEmpty.length > 0) {
                const newest = nonEmpty.sort((a, b) => chats[b].createdAt - chats[a].createdAt)[0];
                currentChatId = newest;
                currentMessages = chats[newest].messages || [];
                setActiveChatId(newest);
            } else {
                const newId = generateId();
                chats[newId] = { title: 'New Chat', messages: [], createdAt: Date.now() };
                saveAllChats(chats);
                setActiveChatId(newId);
                currentChatId = newId;
                currentMessages = [];
            }
        }
        renderSidebar();
        renderMessages();
    }

    function deleteAllChats() {
        const msg = currentLang === 'sw'
            ? 'Futa mazungumzo YOTE? Hii haiwezi kutenduliwa.'
            : 'Delete ALL chats? This cannot be undone.';
        if (!confirm(msg)) return;
        localStorage.removeItem(STORAGE_KEY);
        localStorage.removeItem(ACTIVE_KEY);
        currentChatId = null;
        currentMessages = [];
        initChatbot();
    }

    // ============================================
    // ADD MESSAGE
    // ============================================
    function addMessage(text, who) {
        currentMessages.push({ who, text, ts: Date.now() });

        const box = document.getElementById('chatbot-messages');
        const div = document.createElement('div');
        div.className = 'chatbot-message ' + who;
        if (who === 'bot') {
            div.innerHTML = formatBotMessage(text);
        } else {
            div.textContent = text;
        }
        box.appendChild(div);
        box.scrollTop = box.scrollHeight;

        saveCurrentState();
        renderSidebar();
    }

    // ============================================
    // SEND MESSAGE
    // ============================================
    async function sendMessage() {
        const input = document.getElementById('chatbot-input');
        const msg = input.value.trim();
        if (!msg) return;

        const wasVoice = lastInputWasVoice;
        lastInputWasVoice = false;

        addMessage(msg, 'user');
        input.value = '';
        showTyping();
        isStreaming = true;

        const controller = new AbortController();
        window.currentAbortController = controller;

        try {
            await loadCafeteriaData();

            const history = currentMessages
                .filter(m => m.who === 'user' || m.who === 'bot')
                .slice(-6)
                .map(m => ({
                    role: m.who === 'user' ? 'user' : 'assistant',
                    content: m.text
                }));

            const response = await fetch(API_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    message: msg,
                    history: history,
                    cafeteria_data: cafeteriaData,
                    language: currentLang
                }),
                signal: controller.signal
            });

            if (!response.ok) throw new Error('Server error: ' + response.status);

            hideTyping();
            const box = document.getElementById('chatbot-messages');
            const div = document.createElement('div');
            div.className = 'chatbot-message bot';
            box.appendChild(div);

            let streamBuffer = '';
            let displayText = '';
            let typing = false;

            const runTyping = async () => {
                if (typing) return;
                typing = true;
                while (displayText.length < streamBuffer.length) {
                    displayText += streamBuffer[displayText.length];
                    div.innerHTML = formatBotMessage(displayText);
                    requestAnimationFrame(() => {
                        box.scrollTop = box.scrollHeight;
                    });

                    const lag = streamBuffer.length - displayText.length;
                    const delay = lag > 150 ? 3 : lag > 60 ? 8 : lag > 20 ? 15 : 22;
                    await new Promise(r => setTimeout(r, delay));
                }
                typing = false;
            };

            const reader = response.body.getReader();
            const decoder = new TextDecoder();

            while (true) {
                const { done, value } = await reader.read();
                if (done) break;
                streamBuffer += decoder.decode(value, { stream: true });
                runTyping();
            }

            while (displayText.length < streamBuffer.length) {
                await new Promise(r => setTimeout(r, 60));
            }

            await new Promise(r => setTimeout(r, 80));

            isStreaming = false;

            currentMessages.push({ who: 'bot', text: streamBuffer, ts: Date.now() });
            saveCurrentState();
            renderSidebar();

            if ((wasVoice || continuousVoiceMode) && streamBuffer) {
                setTimeout(() => speakText(streamBuffer), 300);
            }

        } catch (err) {
            isStreaming = false;
            hideTyping();
            if (err.name === 'AbortError') {
                addMessage('[Stopped]', 'bot');
            } else {
                addMessage('Network error. Please try again.', 'bot');
            }
        }
    }

    // ============================================
    // TYPING INDICATOR
    // ============================================
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

    // ============================================
    // TOGGLE
    // ============================================
    function toggleChatbot() {
        const w = document.getElementById('chatbot-window');
        const isHidden = (w.style.display === 'none' || w.style.display === '');
        w.style.display = isHidden ? 'flex' : 'none';
        if (isHidden) {
            positionChatbotWindow();
            const notif = document.querySelector('.chatbot-notification');
            if (notif) notif.style.display = 'none';
            renderSidebar();
            renderMessages();
            setTimeout(() => document.getElementById('chatbot-input').focus(), 100);
        } else {
            saveCurrentState();
        }
    }

    // ============================================
    // BOOT
    // ============================================
    window.addEventListener('DOMContentLoaded', async () => {
        // ✅ Load language preference
        const savedLang = getCurrentLang();
        setLanguage(savedLang);

        initChatbot();
        if (window.speechSynthesis) {
            window.speechSynthesis.getVoices();
            window.speechSynthesis.onvoiceschanged = () => window.speechSynthesis.getVoices();
        }
        recognition = initSpeechRecognition();

        await loadCafeteriaData();

        window.addEventListener('resize', () => {
            const w = document.getElementById('chatbot-window');
            if (w && w.style.display === 'flex') {
                positionChatbotWindow();
            }
        });
    });
    </script>
</body>
</html>