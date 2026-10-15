define(['jquery', 'core/ajax', 'core/notification'], function($, Ajax, Notification) {
    function escapeHtml(s) {
        return String(s || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function normalizeAssistantText(text) {
        if (!text) { return ''; }
        var t = String(text);
        t = t.replace(/```[\s\S]*?```/g, function(block) {
            return '\n' + block.replace(/```/g, '').trim() + '\n';
        });
        t = t.replace(/`([^`]+)`/g, '$1');
        t = t.replace(/^\s{0,3}#{1,6}\s+/gm, '');
        t = t.replace(/\*\*([^*]+)\*\*/g, '$1');
        t = t.replace(/\*([^*]+)\*/g, '$1');
        t = t.replace(/__([^_]+)__/g, '$1');
        t = t.replace(/_([^_]+)_/g, '$1');
        t = t.replace(/^\s*>\s?/gm, '');
        t = t.replace(/^\s*[-*+]\s+/gm, '• ');
        t = t.replace(/^\s*(\d+)\.\s+/gm, '$1) ');
        t = t.replace(/\[([^\]]+)\]\(([^)]+)\)/g, '$1');
        t = t.replace(/^\|/gm, '');
        t = t.replace(/\|$/gm, '');
        t = t.replace(/\|/g, ' | ');
        t = t.replace(/^\s*:?-{3,}:?\s*(\|\s*:?-{3,}:?\s*)*$/gm, '');
        t = t.replace(/\n{3,}/g, '\n\n');
        return t.trim();
    }

    function copyTextToClipboard(text) {
        var value = String(text || '');
        if (!value) {
            return Promise.reject(new Error('empty'));
        }
        if (navigator.clipboard && navigator.clipboard.writeText) {
            return navigator.clipboard.writeText(value);
        }
        return new Promise(function(resolve, reject) {
            try {
                var ta = document.createElement('textarea');
                ta.value = value;
                ta.setAttribute('readonly', 'readonly');
                ta.style.position = 'fixed';
                ta.style.opacity = '0';
                ta.style.left = '-9999px';
                document.body.appendChild(ta);
                ta.focus();
                ta.select();
                var ok = document.execCommand('copy');
                document.body.removeChild(ta);
                if (ok) { resolve(); } else { reject(new Error('copy_failed')); }
            } catch (err) {
                reject(err);
            }
        });
    }

    function getSpeechText(text) {
        var t = normalizeAssistantText(text || '');
        t = t.replace(/•\s*/g, '. ');
        t = t.replace(/\s+/g, ' ').trim();
        return t;
    }


    function isLikelyWebView() {
        var ua = (navigator.userAgent || '');
        var isAndroid = /Android/i.test(ua);
        var hasWv = /\bwv\b/i.test(ua);
        var standalone = !!(window.matchMedia && window.matchMedia('(display-mode: standalone)').matches);
        var iosStandalone = !!(navigator.standalone);
        return !!(hasWv || (isAndroid && !/Chrome\//i.test(ua) && /Version\//i.test(ua)) || standalone || iosStandalone);
    }

    function isChromiumLike() {
        var ua = (navigator.userAgent || '');
        return /Chrome\//i.test(ua) || /Edg\//i.test(ua);
    }


    function isMobileOrTabletDevice() {
        var ua = (navigator.userAgent || '');
        var touch = (navigator.maxTouchPoints || 0) > 0 || ('ontouchstart' in window);
        var coarse = false;
        try { coarse = !!window.matchMedia('(pointer: coarse)').matches; } catch (e) {}
        var shortSide = Math.min(window.screen.width || 0, window.screen.height || 0);
        if (/Android|iPhone|iPad|iPod|Tablet|Mobile/i.test(ua)) { return true; }
        if (touch && (coarse || (shortSide > 0 && shortSide <= 1024))) { return true; }
        return false;
    }

    function isSecureEnough() {
        return !!(window.isSecureContext || location.protocol === 'https:' || location.hostname === 'localhost' || location.hostname === '127.0.0.1');
    }

    function detectVoiceCompatibility() {
        var support = {
            ttsApi: !!(window.speechSynthesis && typeof window.SpeechSynthesisUtterance !== 'undefined'),
            sttApi: !!(window.SpeechRecognition || window.webkitSpeechRecognition),
            secure: isSecureEnough(),
            webview: isLikelyWebView(),
            chromium: isChromiumLike(),
            tts: false,
            stt: false,
            ttsReason: '',
            sttReason: ''
        };

        if (!support.ttsApi) {
            support.ttsReason = 'El entorno actual no expone síntesis de voz.';
        } else if (support.webview) {
            support.ttsReason = 'La app/APK actual no ofrece lectura de voz confiable. Use Chrome compatible o integración nativa.';
        } else {
            support.tts = true;
        }

        if (!support.sttApi) {
            support.sttReason = 'El navegador actual no expone dictado por voz.';
        } else if (!support.chromium) {
            support.sttReason = 'El dictado por voz requiere Chrome o Edge compatibles.';
        } else if (!support.secure) {
            support.sttReason = 'El dictado por voz requiere un sitio seguro (HTTPS).';
        } else if (support.webview) {
            support.sttReason = 'La app/APK actual no ofrece dictado confiable. Use Chrome compatible o integración nativa.';
        } else {
            support.stt = true;
        }
        return support;
    }

    function dedupeRecognizedText(baseText, recognizedText) {
        var base = String(baseText || '').trim();
        var incoming = String(recognizedText || '').trim();
        if (!incoming) { return base; }

        // Remove exact prefix already present in base.
        if (base && incoming.toLowerCase().indexOf(base.toLowerCase()) === 0) {
            incoming = incoming.substring(base.length).trim();
        }

        // Remove consecutive repeated tokens/phrases: "hola hola" -> "hola"
        var words = incoming.split(/\s+/).filter(Boolean);
        var cleaned = [];
        for (var i = 0; i < words.length; i++) {
            if (i > 0 && words[i].toLowerCase() === words[i-1].toLowerCase()) {
                continue;
            }
            cleaned.push(words[i]);
        }
        incoming = cleaned.join(' ');

        // Avoid appending if base already ends with same segment.
        if (base) {
            var lowBase = base.toLowerCase();
            var lowInc = incoming.toLowerCase();
            if (lowInc && lowBase.endsWith(lowInc)) {
                return base;
            }

            // Overlap merge by words.
            var b = base.split(/\s+/);
            var r = incoming.split(/\s+/);
            var maxOverlap = Math.min(8, b.length, r.length);
            var overlap = 0;
            for (var n = maxOverlap; n >= 1; n--) {
                if (b.slice(-n).join(' ').toLowerCase() === r.slice(0, n).join(' ').toLowerCase()) {
                    overlap = n;
                    break;
                }
            }
            if (overlap > 0) {
                incoming = r.slice(overlap).join(' ');
            }
        }

        return (base ? base + (incoming ? ' ' + incoming : '') : incoming).trim();
    }

    function formatBubbleText(text, role) {
        var clean = (role === 'system') ? normalizeAssistantText(text) : String(text || '');
        return escapeHtml(clean).replace(/\n/g, '<br>');
    }

    function scrollHistoryToBottom(force) {
        if (!module.ui || !module.ui.historyDiv || !module.ui.historyDiv.length) { return; }
        var el = module.ui.historyDiv[0];
        if (!el) { return; }
        var nearBottom = (el.scrollHeight - el.scrollTop - el.clientHeight) < 140;
        if (force || nearBottom) {
            setTimeout(function() { el.scrollTop = el.scrollHeight; }, 40);
            setTimeout(function() { el.scrollTop = el.scrollHeight; }, 180);
        }
    }

    function getPopupGeometry() {
        var width = Math.min(1220, Math.max(880, Math.round(window.screen.availWidth * 0.74)));
        var height = Math.min(800, Math.max(600, Math.round(window.screen.availHeight * 0.70)));
        return {
            width: width,
            height: height,
            left: Math.max(0, Math.round((window.screen.availWidth - width) / 2)),
            top: Math.max(0, Math.round((window.screen.availHeight - height) / 2))
        };
    }

    function syncMobileViewport() {
        var vv = window.visualViewport;
        var h = vv && vv.height ? Math.round(vv.height) : window.innerHeight;
        if (!h) { return; }
        document.documentElement.style.setProperty('--vtutor-mobile-vh', h + 'px');
    }

    function autoResizeInput() {
        if (!module.ui || !module.ui.inputField || !module.ui.inputField.length) { return; }
        var el = module.ui.inputField[0];
        if (!el) { return; }
        el.style.height = 'auto';
        var next = Math.min(Math.max(el.scrollHeight, 42), 96);
        el.style.height = next + 'px';
        scrollHistoryToBottom(false);
    }

    function applyDeviceClass(module) {
        if (!module || !module.ui || !module.ui.container || !module.ui.container.length) { return; }
        var mobileorTablet = isMobileOrTabletDevice();
        module.ui.container.toggleClass('vtutor-device-mobile', !!mobileorTablet);
        module.ui.container.toggleClass('vtutor-device-desktop', !mobileorTablet);
    }
    function syncVoiceControls(module) {
        if (!module || !module.ui) { return; }
        var mobileorTablet = isMobileOrTabletDevice();
        applyDeviceClass(module);
        if (module.ui.dictateBtn && module.ui.dictateBtn.length) {
            if (mobileorTablet) {
                module.ui.dictateBtn.css('display', 'inline-flex');
                module.ui.dictateBtn.prop('disabled', false);
            } else {
                module.ui.dictateBtn.hide();
                module.ui.dictateBtn.prop('disabled', true);
            }
        }
        if (module.ui.stopDictationBtn && module.ui.stopDictationBtn.length) {
            module.ui.stopDictationBtn.hide();
            if (!mobileorTablet) {
                module.ui.stopDictationBtn.prop('disabled', true);
            } else {
                module.ui.stopDictationBtn.prop('disabled', false);
            }
        }
    }

    function decorateExistingSystemMessages() {
        $('#vtutor-chat-history .vtutor-message.vtutor-system').each(function() {
            var msg = $(this);
            if (msg.find('.vtutor-msg-actions').length) { return; }
            var content = msg.find('.vtutor-msg-content').text().trim();
            if (!content) { return; }
            var actions = $('<div class="vtutor-msg-actions">' +
                '<button type="button" class="vtutor-msg-action-btn" data-action="copy" data-text="">📋 Copiar</button>' +
                '<button type="button" class="vtutor-msg-action-btn" data-action="speak" data-text="">🔊 Leer</button>' +
                '</div>');
            actions.find('[data-action="copy"]').attr('data-text', normalizeAssistantText(content));
            actions.find('[data-action="speak"]').attr('data-text', getSpeechText(content));
            msg.append(actions);
        });
    }

var module = {
        courseid: 0,
        cmid: 0,
        ui: null,
        speechUtterance: null,
        speechEnabled: !!window.speechSynthesis,
        recognition: null,
        recognitionEnabled: !!(window.SpeechRecognition || window.webkitSpeechRecognition),
        dictationBaseText: '',
        dictationFinalText: '',
        voiceSupport: null,

        showVoiceStatus: function(msg, autoclear) {
            if (!module.ui || !module.ui.voiceStatus || !module.ui.voiceStatus.length) { return; }
            module.ui.voiceStatus.text(msg).show();
            if (autoclear) {
                setTimeout(function() { module.ui.voiceStatus.fadeOut(200); }, autoclear);
            }
        },

        stopSpeaking: function() {
            if (window.speechSynthesis) { window.speechSynthesis.cancel(); }
            $('.vtutor-msg-action-btn[data-action="speak"]').removeClass('is-active').text('🔊 Leer');
        },

        speakText: function(text, buttonEl) {
            if (!module.speechEnabled) {
                module.showVoiceStatus('Tu navegador no permite leer la respuesta en voz alta en este momento.', 2500);
                return;
            }
            var speechText = getSpeechText(text);
            if (!speechText) { return; }
            module.stopSpeaking();
            var utter = new SpeechSynthesisUtterance(speechText);
            utter.lang = 'es-419';
            utter.rate = 1.0;
            utter.pitch = 1.0;
            utter.onend = function() {
                if (buttonEl) { $(buttonEl).removeClass('is-active').text('🔊 Leer'); }
            };
            utter.onerror = function() {
                if (buttonEl) { $(buttonEl).removeClass('is-active').text('🔊 Leer'); }
                module.showVoiceStatus('No se pudo reproducir el audio de la respuesta.', 2500);
            };
            module.speechUtterance = utter;
            if (buttonEl) { $(buttonEl).addClass('is-active').text('⏹ Detener lectura'); }
            window.speechSynthesis.speak(utter);
        },

        createMessage: function(role, text, timeText) {
            var cls = role === 'user' ? 'vtutor-user' : 'vtutor-system';
            var who = role === 'user' ? 'Tú' : 'VTutor';
            var timeHtml = timeText ? '<div class="vtutor-msg-time">' + escapeHtml(timeText) + '</div>' : '';
            var actionsHtml = '';
            if (role === 'system') {
                actionsHtml = '<div class="vtutor-msg-actions">' +
                    '<button type="button" class="vtutor-msg-action-btn" data-action="copy" data-text="' + escapeHtml(normalizeAssistantText(text)) + '">📋 Copiar</button>' +
                    '<button type="button" class="vtutor-msg-action-btn" data-action="speak" data-text="' + escapeHtml(getSpeechText(text)) + '">🔊 Leer</button>' +
                    '</div>';
            }
            var html = '' +
                '<div class="vtutor-message ' + cls + '">' +
                '  <div class="vtutor-msg-header"><strong>' + who + '</strong>' + timeHtml + '</div>' +
                '  <div class="vtutor-msg-content">' + formatBubbleText(text, role) + '</div>' +
                   actionsHtml +
                '</div>';
            return $(html);
        },

        ensureBackdrop: function() {
            if (!document.getElementById('vtutor-floating-backdrop')) {
                var bd = document.createElement('div');
                bd.id = 'vtutor-floating-backdrop';
                bd.className = 'vtutor-floating-backdrop';
                document.body.appendChild(bd);
            }
            return document.getElementById('vtutor-floating-backdrop');
        },

setupFloatingUx: function() {
    var container = document.querySelector('.vtutor-container');
    if (!container) { return; }

    var standalone = String(container.getAttribute('data-standalone') || '0') === '1';
    var launcher = document.getElementById('vtutor-launcher-btn');
    var btnFloat = document.getElementById('vtutor-float-btn');
    var btnExpand = document.getElementById('vtutor-expand-btn');
    var btnClose = document.getElementById('vtutor-close-floating-btn');

    function isMobile() { return window.matchMedia('(max-width: 767px)').matches; }

    if (standalone) {
        document.body.classList.add('vtutor-standalone-body');
        document.documentElement.classList.add('vtutor-standalone-html');
        document.documentElement.style.overflow = 'hidden';
        document.body.style.overflow = 'hidden';
        if (launcher) { launcher.style.display = 'none'; }
        if (btnClose) { btnClose.style.display = ''; }

        function syncStandaloneSize(expanded) {
            container.classList.toggle('vtutor-window-expanded', !!expanded);
            var mobile = isMobile();
            document.body.style.padding = '0';
            container.style.position = 'fixed';
            container.style.width = 'auto';
            container.style.height = 'auto';
            container.style.minHeight = 'auto';
            container.style.maxHeight = 'none';
            container.style.inset = mobile ? '0' : (expanded ? '8px' : '20px');
            var input = document.getElementById('vtutor-user-input');
            if (input) { setTimeout(function() { input.focus(); }, 80); }
            scrollHistoryToBottom(true);
        }

        var expanded = true;
        syncStandaloneSize(expanded);

        if (btnFloat) {
            btnFloat.title = 'Abrir en ventana independiente';
            btnFloat.addEventListener('click', function(e) {
                e.preventDefault();
                if (window.opener || (window.name && window.name.indexOf('vtutorpro_') === 0)) {
                    var size = getPopupGeometry();
                    try { window.resizeTo(size.width, size.height); } catch (err) {}
                    try { window.focus(); } catch (err2) {}
                    return;
                }
                var size = getPopupGeometry();
                var popup = window.open(window.location.href, 'vtutorpro_' + module.courseid, 'toolbar=no,location=no,status=no,menubar=no,scrollbars=yes,resizable=yes,width=' + size.width + ',height=' + size.height + ',left=' + size.left + ',top=' + size.top);
                if (popup) { popup.focus(); }
            });
        }
        if (btnExpand) {
            btnExpand.title = 'Restaurar o ampliar ventana';
            btnExpand.addEventListener('click', function(e) {
                e.preventDefault();
                expanded = !expanded;
                syncStandaloneSize(expanded);
            });
        }
        if (btnClose) {
            btnClose.addEventListener('click', function(e) {
                e.preventDefault();
                try {
                    if (window.opener) {
                        window.close();
                        return;
                    }
                } catch (err) {}
                if (document.referrer) {
                    window.location.href = document.referrer;
                }
            });
        }
        window.addEventListener('resize', function() {
            syncStandaloneSize(container.classList.contains('vtutor-window-expanded'));
        });
        return;
    }

    var backdrop = module.ensureBackdrop();
    var originalParent = container.parentNode;
    var originalNext = container.nextSibling;
    var state = 'docked';

    if (launcher && launcher.parentNode !== document.body) {
        document.body.appendChild(launcher);
    }

    function syncLauncherState() {
        if (!launcher) { return; }
        launcher.style.display = '';
        launcher.setAttribute('aria-expanded', state === 'docked' ? 'false' : 'true');
        launcher.classList.toggle('is-open', state !== 'docked');
        launcher.title = (state === 'docked') ? 'Abrir VTutor' : 'Ocultar VTutor';
    }

    function mountToBody(mode) {
        if (!container.parentNode || container.parentNode !== document.body) { document.body.appendChild(container); }
        container.classList.remove('vtutor-floating', 'vtutor-fullscreen');
        if (mode === 'fullscreen') { container.classList.add('vtutor-fullscreen'); }
        else { container.classList.add('vtutor-floating'); }
        state = mode;
        if (btnClose) { btnClose.style.display = ''; }
        if (backdrop) { backdrop.style.display = 'block'; }
        syncLauncherState();
        var input = document.getElementById('vtutor-user-input');
        if (input) { setTimeout(function(){ input.focus(); }, 100); }
    }

    function dockBack() {
        container.classList.remove('vtutor-floating', 'vtutor-fullscreen');
        if (originalParent) {
            if (originalNext && originalNext.parentNode === originalParent) { originalParent.insertBefore(container, originalNext); }
            else { originalParent.appendChild(container); }
        }
        state = 'docked';
        if (btnClose) { btnClose.style.display = 'none'; }
        if (backdrop) { backdrop.style.display = 'none'; }
        syncLauncherState();
    }

    function openSmart() {
        if (isMobile()) { mountToBody('fullscreen'); } else { mountToBody('floating'); }
    }

    if (launcher) {
        syncLauncherState();
        launcher.style.position = 'fixed';
        launcher.style.right = isMobile() ? '12px' : '18px';
        launcher.style.bottom = isMobile() ? '12px' : '18px';
        launcher.style.zIndex = '9999';
        launcher.addEventListener('click', function() {
            if (state === 'docked') { openSmart(); } else { dockBack(); }
        });
    }
    if (btnFloat) {
        btnFloat.addEventListener('click', function(e) {
            e.preventDefault();
            if (state === 'docked') { openSmart(); }
            else if (state === 'fullscreen') { mountToBody('floating'); }
            else { dockBack(); }
        });
    }
    if (btnExpand) {
        btnExpand.addEventListener('click', function(e) {
            e.preventDefault();
            if (state === 'fullscreen') { mountToBody(isMobile() ? 'fullscreen' : 'floating'); }
            else { mountToBody('fullscreen'); }
        });
    }
    if (btnClose) { btnClose.addEventListener('click', function(e) { e.preventDefault(); dockBack(); }); }
    if (backdrop) { backdrop.addEventListener('click', function() { if (state !== 'docked') { dockBack(); } }); }
    window.addEventListener('keydown', function(e) { if (e.key === 'Escape' && state !== 'docked') { dockBack(); } });
    window.addEventListener('resize', function() { if (launcher) { launcher.style.right = isMobile() ? '12px' : '18px'; launcher.style.bottom = isMobile() ? '12px' : '18px'; } });

    document.documentElement.style.overflow = 'auto';
    document.body.style.overflow = 'auto';
    document.body.style.position = 'static';
},

setupRecognition:
 function() {
            if (!module.recognitionEnabled) { return; }
            var Ctor = window.SpeechRecognition || window.webkitSpeechRecognition;
            if (!Ctor) { return; }
            module.recognition = new Ctor();
            module.recognition.lang = 'es-419';
            module.recognition.interimResults = true;
            module.recognition.continuous = false;
            module.recognition.onstart = function() {
                module.dictationBaseText = (module.ui.inputField.val() || '').trim();
                module.dictationFinalText = '';
                module.ui.dictateBtn.addClass('is-active');
                module.ui.stopDictationBtn.css('display', 'inline-flex');
                module.showVoiceStatus('Escuchando... hable ahora.', 0);
            };
            module.recognition.onresult = function(event) {
                var finalText = '';
                var interimText = '';
                for (var i = 0; i < event.results.length; i++) {
                    var part = (event.results[i][0] && event.results[i][0].transcript) ? event.results[i][0].transcript : '';
                    if (event.results[i].isFinal) { finalText += ' ' + part; }
                    else { interimText += ' ' + part; }
                }
                finalText = finalText.trim();
                interimText = interimText.trim();

                module.dictationFinalText = finalText;
                var recognized = (finalText + ' ' + interimText).trim();
                var textToSet = dedupeRecognizedText(module.dictationBaseText, recognized);
                module.ui.inputField.val(textToSet.trim());
                autoResizeInput();
            };
            module.recognition.onerror = function(event) {
                module.ui.dictateBtn.removeClass('is-active');
                module.ui.stopDictationBtn.hide();
                var reason = 'No se pudo reconocer la voz. Intente nuevamente.';
                if (event && event.error === 'not-allowed') {
                    reason = 'El navegador bloqueó el micrófono. Permita el acceso al micrófono y vuelva a intentarlo.';
                } else if (event && event.error === 'no-speech') {
                    reason = 'No se detectó voz. Hable de nuevo y mantenga el micrófono habilitado.';
                } else if (event && event.error === 'network') {
                    reason = 'El dictado por voz no pudo comunicarse con el servicio del navegador.';
                }
                module.showVoiceStatus(reason, 3500);
            };
            module.recognition.onend = function() {
                module.ui.dictateBtn.removeClass('is-active');
                module.ui.stopDictationBtn.hide();
                if ((module.ui.inputField.val() || '').trim() !== '') {
                    module.showVoiceStatus('Dictado completado. Revise y envíe su pregunta.', 2200);
                } else {
                    module.ui.voiceStatus.hide();
                }
            };
        },

        loadHistory: function() {
            if (!module.ui || !module.ui.historyDiv.length) { return; }
            Ajax.call([{ methodname: 'block_ai_tutor_get_history', args: { courseid: module.courseid, limit: 20 } }])[0]
            .done(function(result) {
                var history = (result && result.history) ? result.history : [];
                if (history.length) {
                    module.ui.historyDiv.empty();
                    history.forEach(function(msg) {
                        module.ui.historyDiv.append(module.createMessage('user', msg.message, msg.time || ''));
                        module.ui.historyDiv.append(module.createMessage('system', msg.response, msg.time || ''));
                    });
                }
                scrollHistoryToBottom(true);
            }).fail(function(ex) { if (window.console && console.warn) { console.warn('VTutor loadHistory error', ex); } });
        },


        getStorageKey: function() {
            return 'vtutor_mode_' + module.courseid;
        },

        getSelectedMode: function() {
            if (!module.ui || !module.ui.modeSelect || !module.ui.modeSelect.length) { return 'tutorial'; }
            return String(module.ui.modeSelect.val() || 'tutorial');
        },

        restoreModeSelection: function() {
            if (!module.ui || !module.ui.modeSelect || !module.ui.modeSelect.length) { return; }
            var saved = null;
            try { saved = window.localStorage.getItem(module.getStorageKey()); } catch (e) { saved = null; }
            var allowed = ['tutorial', 'autoevaluacion', 'refuerzo', 'recursos'];
            if (allowed.indexOf(saved) === -1) { saved = 'tutorial'; }
            module.ui.modeSelect.val(saved);
        },

        persistModeSelection: function() {
            if (!module.ui || !module.ui.modeSelect || !module.ui.modeSelect.length) { return; }
            try { window.localStorage.setItem(module.getStorageKey(), module.getSelectedMode()); } catch (e) {}
        },


        decorateMessageWithMode: function(message) {
            var mode = module.getSelectedMode();
            var allowed = ['tutorial', 'autoevaluacion', 'refuerzo', 'recursos'];
            if (allowed.indexOf(mode) === -1) { mode = 'tutorial'; }
            return '[[VTMODE:' + mode + ']] ' + String(message || '');
        },


        sendMessage: function(message) {
            module.stopSpeaking();
            module.ui.historyDiv.append(module.createMessage('user', message));
            scrollHistoryToBottom(true);
            module.ui.inputField.val('');
            autoResizeInput();
            module.ui.loadingDiv.show();
            module.ui.sendBtn.prop('disabled', true);

            Ajax.call([{ methodname: 'block_ai_tutor_send_message', args: { courseid: module.courseid, message: module.decorateMessageWithMode(message), cmid: module.cmid || 0 } }])[0]
            .done(function(result) {
                module.ui.loadingDiv.hide();
                module.ui.sendBtn.prop('disabled', false);
                var response = (result && result.response) ? result.response : '⚠️ Hubo un problema al conectar con el tutor. Por favor, intenta nuevamente.';
                module.ui.historyDiv.append(module.createMessage('system', response));
                decorateExistingSystemMessages();
                scrollHistoryToBottom(true);
            }).fail(function(ex) {
                module.ui.loadingDiv.hide();
                module.ui.sendBtn.prop('disabled', false);
                module.ui.historyDiv.append(module.createMessage('system', '⚠️ Hubo un problema al conectar con el tutor. Por favor, intenta nuevamente.'));
                scrollHistoryToBottom(true);
                if (window.console && console.error) { console.error('VTutor sendMessage error', ex); }
                if (Notification && Notification.exception) { /* intentionally quiet in UI */ }
            });
        },

        clearHistory: function() {
            Ajax.call([{ methodname: 'block_ai_tutor_clear_history', args: { courseid: module.courseid } }])[0]
            .done(function() {
                module.stopSpeaking();
                module.ui.historyDiv.empty();
                module.ui.historyDiv.append(module.createMessage('system', 'Conversación reiniciada. ¿En qué te puedo ayudar ahora?'));
                decorateExistingSystemMessages();
            }).fail(function(ex) { if (window.console && console.error) { console.error('VTutor clearHistory error', ex); } });
        },

        init: function(courseid, cmid) {
            module.courseid = parseInt(courseid, 10) || 0;
            module.cmid = parseInt(cmid, 10) || 0;
            module.ui = {
                container: $('.vtutor-container'),
                historyDiv: $('#vtutor-chat-history'),
                loadingDiv: $('#vtutor-loading'),
                inputField: $('#vtutor-user-input'),
                sendBtn: $('#vtutor-send-btn'),
                clearBtns: $('[data-vtutor-clear="1"]'),
                modeSelect: $('#vtutor-mode-select'),
                dictateBtn: $('#vtutor-dictate-btn'),
                stopDictationBtn: $('#vtutor-stop-dictation-btn'),
                voiceStatus: $('#vtutor-voice-status')
            };
            if (!module.ui.historyDiv.length) { return; }

            syncVoiceControls(module);

            module.setupFloatingUx();
            module.setupRecognition();
            module.restoreModeSelection();
            module.loadHistory();
            decorateExistingSystemMessages();

            syncMobileViewport();
            if (window.visualViewport) {
                window.visualViewport.addEventListener('resize', syncMobileViewport);
                window.visualViewport.addEventListener('scroll', syncMobileViewport);
            }
            $(window).on('resize orientationchange', function() { syncMobileViewport(); syncVoiceControls(module); });
            module.ui.inputField.on('input focus', function() {
                autoResizeInput();
                syncMobileViewport();
                scrollHistoryToBottom(false);
            });
            setTimeout(function() { autoResizeInput(); syncMobileViewport(); scrollHistoryToBottom(true); }, 120);

            module.ui.inputField.on('keydown', function(e) {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    module.ui.sendBtn.trigger('click');
                }
            });

            module.ui.sendBtn.on('click', function() {
                var message = (module.ui.inputField.val() || '').trim();
                if (!message) { return; }
                module.sendMessage(message);
            });

            module.ui.clearBtns.on('click', function() { if (window.confirm('¿Desea limpiar la conversación actual?')) { module.clearHistory(); } });

            if (module.ui.modeSelect && module.ui.modeSelect.length) {
                module.ui.modeSelect.on('change', function() {
                    module.persistModeSelection();
                });
            }

            module.ui.historyDiv.on('click', '.vtutor-msg-action-btn[data-action="copy"]', function() {
                var btn = this;
                var original = $(btn).text();
                copyTextToClipboard($(btn).attr('data-text') || '').then(function() {
                    $(btn).addClass('is-active').text('✅ Copiado');
                    setTimeout(function() { $(btn).removeClass('is-active').text(original); }, 1800);
                }).catch(function() {
                    module.showVoiceStatus('No se pudo copiar la respuesta.', 2200);
                });
            });

            module.ui.historyDiv.on('click', '.vtutor-msg-action-btn[data-action="speak"]', function() {
                var btn = this;
                if ($(btn).hasClass('is-active')) { module.stopSpeaking(); return; }
                module.speakText($(btn).attr('data-text') || '', btn);
            });

            module.ui.dictateBtn.on('click', function() {
                if (!module.recognition) {
                    module.showVoiceStatus('El dictado por voz no está disponible en este navegador o en este dispositivo.', 2800);
                    return;
                }
                try { module.recognition.start(); } catch (e) {}
            });

            module.ui.stopDictationBtn.on('click', function() {
                if (module.recognition) { try { module.recognition.stop(); } catch (e) {} }
            });
        }
    };

    return module;
});