<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="robots" content="noindex, nofollow">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Teleatendimento - Blink | Consulta {{ $appointment->date->format('d/m/Y') }}</title>

    {{-- SEO e Social Meta Tags --}}
    <meta name="description" content="Sala de teleatendimento seguro com criptografia ponta-a-ponta - Blink Saúde">
    <meta property="og:title" content="Teleatendimento Blink - Consulta {{ $appointment->date->format('d/m/Y') }}">
    <meta property="og:type" content="website">
    <meta property="og:description" content="Sala de teleatendimento seguro com criptografia ponta-a-ponta">
    <meta name="twitter:card" content="summary_large_image">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html, body {
            width: 100%;
            height: 100%;
            overflow: hidden;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #0f172a;
            color: #e2e8f0;
        }

        /* Loading overlay enquanto o iframe carrega */
        #loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: #0f172a;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            transition: opacity 0.4s ease;
        }

        #loading-overlay.hidden {
            opacity: 0;
            pointer-events: none;
        }

        .spinner {
            width: 48px;
            height: 48px;
            border: 4px solid rgba(56, 189, 248, 0.2);
            border-top-color: #38bdf8;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
            margin-bottom: 20px;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .loading-text {
            color: #94a3b8;
            font-size: 14px;
        }

        .loading-title {
            color: #38bdf8;
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 8px;
        }

        /* Container do iframe — ocupa a tela toda */
        #jitsi-container {
            width: 100%;
            height: 100%;
            position: relative;
        }

        #jitsi-iframe {
            width: 100%;
            height: 100%;
            border: none;
            display: block;
        }

        /* Header com informações da consulta */
        #room-header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 100;
            background: rgba(15, 23, 42, 0.95);
            backdrop-filter: blur(8px);
            padding: 8px 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid rgba(56, 189, 248, 0.2);
            transition: transform 0.3s ease;
            height: 48px;
        }

        #room-header.collapsed {
            transform: translateY(-100%);
        }

        #room-header .header-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        #room-header .room-badge {
            background: rgba(56, 189, 248, 0.15);
            color: #38bdf8;
            border: 1px solid rgba(56, 189, 248, 0.3);
            padding: 2px 10px;
            border-radius: 9999px;
            font-size: 12px;
            font-weight: 500;
        }

        #room-header .room-title {
            font-size: 14px;
            font-weight: 500;
            color: #e2e8f0;
        }

        #room-header .room-subtitle {
            font-size: 12px;
            color: #94a3b8;
        }

        #toggle-header-btn {
            position: fixed;
            top: 56px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 101;
            background: rgba(15, 23, 42, 0.9);
            border: 1px solid rgba(56, 189, 248, 0.3);
            color: #94a3b8;
            width: 28px;
            height: 14px;
            border-radius: 9999px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            transition: all 0.3s ease;
        }

        #toggle-header-btn:hover {
            border-color: #38bdf8;
            color: #38bdf8;
        }

        /* Badge de segurança E2E */
        .security-badge {
            display: flex;
            align-items: center;
            gap: 4px;
            font-size: 11px;
            color: #22c55e;
        }

        .security-badge .lock-icon {
            width: 12px;
            height: 12px;
        }

        /* Responsivo: mobile */
        @media (max-width: 640px) {
            #room-header {
                padding: 6px 12px;
                height: 44px;
            }

            #room-header .room-title {
                font-size: 13px;
            }

            #room-header .room-subtitle {
                display: none;
            }
        }
    </style>
</head>
<body>
    {{-- Header com informações --}}
    <header id="room-header">
        <div class="header-info">
            <div>
                <div class="room-title">
                    Teleatendimento — Dr(a). {{ $appointment->professional->full_name }}
                </div>
                <div class="room-subtitle">
                    {{ $appointment->date->format('d/m/Y') }} às {{ substr($appointment->time, 0, 5) }}
                    &middot; {{ $isModerator ? 'Moderador' : 'Participante' }}
                </div>
            </div>
        </div>
        <div class="security-badge">
            <svg class="lock-icon" viewBox="0 0 24 24" fill="currentColor">
                <path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1s3.1 1.39 3.1 3.1v2z"/>
            </svg>
            Criptografia Ponta-a-Ponta
        </div>
    </header>

    {{-- Botão para colapsar/expandir header --}}
    <button id="toggle-header-btn" title="Mostrar/Ocultar cabeçalho">&#9650;</button>

    {{-- Loading spinner --}}
    <div id="loading-overlay">
        <div class="spinner"></div>
        <div class="loading-title">Preparando sua consulta</div>
        <div class="loading-text">Conectando ao servidor seguro...</div>
    </div>

    {{-- Container do Jitsi Meet --}}
    <div id="jitsi-container">
        <iframe
            id="jitsi-iframe"
            allow="camera; microphone; fullscreen; display-capture; clipboard-write; autoplay"
            allowfullscreen
            sandbox="allow-scripts allow-same-origin allow-popups allow-forms allow-modals"
            loading="lazy"
            title="Sala de Teleatendimento - Blink"
            src="{{ $accessUrl }}"
        ></iframe>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const iframe = document.getElementById('jitsi-iframe');
            const loadingOverlay = document.getElementById('loading-overlay');
            const roomHeader = document.getElementById('room-header');
            const toggleBtn = document.getElementById('toggle-header-btn');

            // Oculta o loading quando o iframe carregar
            iframe.addEventListener('load', function () {
                setTimeout(function () {
                    loadingOverlay.classList.add('hidden');
                }, 600); // Pequeno delay para transição suave
            });

            // Fallback: oculta loading após 15s mesmo se o iframe não disparar load
            setTimeout(function () {
                if (!loadingOverlay.classList.contains('hidden')) {
                    loadingOverlay.classList.add('hidden');
                }
            }, 15000);

            // Toggle do header
            let headerVisible = true;
            toggleBtn.addEventListener('click', function () {
                headerVisible = !headerVisible;
                if (headerVisible) {
                    roomHeader.classList.remove('collapsed');
                    toggleBtn.innerHTML = '&#9650;';
                    toggleBtn.style.top = '56px';
                } else {
                    roomHeader.classList.add('collapsed');
                    toggleBtn.innerHTML = '&#9660;';
                    toggleBtn.style.top = '8px';
                }
            });

            // Auto-hide header após 3s de inatividade do mouse (modo imersivo)
            let hideTimer;
            document.addEventListener('mousemove', function (e) {
                // Só reage se o mouse estiver no topo da tela
                if (e.clientY < 80) {
                    clearTimeout(hideTimer);
                    if (!headerVisible) {
                        roomHeader.classList.remove('collapsed');
                        headerVisible = true;
                        toggleBtn.innerHTML = '&#9650;';
                        toggleBtn.style.top = '56px';
                    }
                    // Agenda para esconder novamente
                    hideTimer = setTimeout(function () {
                        roomHeader.classList.add('collapsed');
                        headerVisible = false;
                        toggleBtn.innerHTML = '&#9660;';
                        toggleBtn.style.top = '8px';
                    }, 3000);
                }
            });

            // Previne saída acidental (confirmação)
            window.addEventListener('beforeunload', function (e) {
                // Mensagem padrão do navegador
                e.preventDefault();
                e.returnValue = 'Você está em uma consulta em andamento. Deseja realmente sair?';
                return e.returnValue;
            });

            // Log de auditoria: entrada na sala
            if (typeof fetch !== 'undefined') {
                fetch('{{ url("/api/consultation-rooms/" . $room->id . "/start") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Authorization': 'Bearer {{ request()->bearerToken() }}'
                    },
                    body: JSON.stringify({}),
                }).catch(function () {
                    // Silencioso — não afeta a experiência se falhar
                });
            }
        });
    </script>
</body>
</html>