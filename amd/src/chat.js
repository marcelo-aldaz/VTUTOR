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

    function formatBubbleText(text, role) {
        var clean = (role === 'system') ? normalizeAssistantText(text) : String(text || '');
        return escapeHtml(clean).replace(/\n/g, '<br>');
    }

    var module = {
        courseid: 0,
        cmid: 0,
        ui: null,

        createMessage: function(role, text, timeText) {
            var cls = role === 'user' ? 'vtutor-user' : 'vtutor-system';
            var who = role === 'user' ? 'Tú' : 'VTutor';
            var timeHtml = timeText ? '<div class="vtutor-msg-time">' + escapeHtml(timeText) + '</div>' : '';
            var html = '' +
                '<div class="vtutor-message ' + cls + '">' +
                '  <div class="vtutor-msg-header"><strong>' + who + '</strong>' + timeHtml + '</div>' +
                '  <div class="vtutor-msg-content">' + formatBubbleText(text, role) + '</div>' +
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

            var launcher = document.getElementById('vtutor-launcher-btn');
            var btnFloat = document.getElementById('vtutor-float-btn');
            var btnExpand = document.getElementById('vtutor-expand-btn');
            var btnClose = document.getElementById('vtutor-close-floating-btn');
            var backdrop = module.ensureBackdrop();

            var originalParent = container.parentNode;
            var originalNext = container.nextSibling;
            var state = 'docked'; // docked|floating|fullscreen

            function isMobile() {
                return window.matchMedia('(max-width: 767px)').matches;
            }

            function mountToBody(mode) {
                if (!container.parentNode || container.parentNode !== document.body) {
                    document.body.appendChild(container);
                }
                container.classList.remove('vtutor-floating', 'vtutor-fullscreen');
                if (mode === 'fullscreen') {
                    container.classList.add('vtutor-fullscreen');
                } else {
                    container.classList.add('vtutor-floating');
                }
                state = mode;
                if (launcher) { launcher.style.display = 'none'; }
                if (btnClose) { btnClose.style.display = ''; }
                if (backdrop) { backdrop.style.display = 'block'; }
                // focus input for convenience
                var input = document.getElementById('vtutor-user-input');
                if (input) { setTimeout(function(){ input.focus(); }, 100); }
            }

            function dockBack() {
                container.classList.remove('vtutor-floating', 'vtutor-fullscreen');
                if (originalParent) {
                    if (originalNext && originalNext.parentNode === originalParent) {
                        originalParent.insertBefore(container, originalNext);
                    } else {
                        originalParent.appendChild(container);
                    }
                }
                state = 'docked';
                if (launcher) { launcher.style.display = ''; }
                if (btnClose) { btnClose.style.display = 'none'; }
                if (backdrop) { backdrop.style.display = 'none'; }
            }

            function openSmart() {
                if (isMobile()) {
                    mountToBody('fullscreen');
                } else {
                    mountToBody('floating');
                }
            }

            if (launcher) {
                launcher.style.display = '';
                launcher.addEventListener('click', function() {
                    if (state === 'docked') {
                        openSmart();
                    } else {
                        dockBack();
                    }
                });
            }

            if (btnFloat) {
                btnFloat.addEventListener('click', function(e) {
                    e.preventDefault();
                    if (state === 'docked') {
                        openSmart();
                    } else if (state === 'fullscreen') {
                        mountToBody('floating');
                    } else {
                        dockBack();
                    }
                });
            }

            if (btnExpand) {
                btnExpand.addEventListener('click', function(e) {
                    e.preventDefault();
                    if (state === 'fullscreen') {
                        mountToBody(isMobile() ? 'fullscreen' : 'floating');
                    } else {
                        mountToBody('fullscreen');
                    }
                });
            }

            if (btnClose) {
                btnClose.addEventListener('click', function(e) {
                    e.preventDefault();
                    dockBack();
                });
            }

            if (backdrop) {
                backdrop.addEventListener('click', function() {
                    if (state !== 'docked') {
                        dockBack();
                    }
                });
            }

            window.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && state !== 'docked') {
                    dockBack();
                }
            });

            // Avoid accidental page-scroll lock remnants from older versions.
            document.documentElement.style.overflow = 'auto';
            document.body.style.overflow = 'auto';
            document.body.style.position = 'static';
        },

        loadHistory: function() {
            if (!module.ui || !module.ui.historyDiv.length) { return; }
            Ajax.call([{
                methodname: 'block_ai_tutor_get_history',
                args: {
                    courseid: module.courseid,
                    limit: 20
                }
            }])[0].done(function(result) {
                var history = (result && result.history) ? result.history : [];
                if (history.length) {
                    module.ui.historyDiv.empty();
                    history.forEach(function(msg) {
                        module.ui.historyDiv.append(module.createMessage('user', msg.message, msg.time || ''));
                        module.ui.historyDiv.append(module.createMessage('system', msg.response, msg.time || ''));
                    });
                }
                module.ui.historyDiv.scrollTop(module.ui.historyDiv[0].scrollHeight);
            }).fail(function(ex) {
                if (window.console && console.warn) {
                    console.warn('VTutor loadHistory error', ex);
                }
            });
        },

        sendMessage: function(message) {
            module.ui.historyDiv.append(module.createMessage('user', message));
            module.ui.inputField.val('');
            module.ui.loadingDiv.show();
            module.ui.sendBtn.prop('disabled', true);

            Ajax.call([{
                methodname: 'block_ai_tutor_send_message',
                args: {
                    courseid: module.courseid,
                    message: message,
                    cmid: module.cmid || 0
                }
            }])[0].done(function(result) {
                module.ui.loadingDiv.hide();
                module.ui.sendBtn.prop('disabled', false);

                var response = (result && result.response) ? result.response :
                    '⚠️ Hubo un problema al conectar con el tutor. Por favor, intenta nuevamente.';
                module.ui.historyDiv.append(module.createMessage('system', response));
                module.ui.historyDiv.scrollTop(module.ui.historyDiv[0].scrollHeight);
            }).fail(function(ex) {
                module.ui.loadingDiv.hide();
                module.ui.sendBtn.prop('disabled', false);
                module.ui.historyDiv.append(module.createMessage(
                    'system',
                    '⚠️ Hubo un problema al conectar con el tutor. Por favor, intenta nuevamente.'
                ));
                module.ui.historyDiv.scrollTop(module.ui.historyDiv[0].scrollHeight);

                if (window.console && console.error) {
                    console.error('VTutor sendMessage error', ex);
                }
                if (Notification && Notification.exception) {
                    // Keep silent-ish in UI; console still gets details.
                }
            });
        },

        clearHistory: function() {
            Ajax.call([{
                methodname: 'block_ai_tutor_clear_history',
                args: {courseid: module.courseid}
            }])[0].done(function() {
                module.ui.historyDiv.empty();
                module.ui.historyDiv.append(module.createMessage(
                    'system',
                    'Conversación reiniciada. ¿En qué te puedo ayudar ahora?'
                ));
            }).fail(function(ex) {
                if (window.console && console.error) {
                    console.error('VTutor clearHistory error', ex);
                }
            });
        },

        init: function(courseid, cmid) {
            module.courseid = parseInt(courseid, 10) || 0;
            module.cmid = parseInt(cmid, 10) || 0;

            module.ui = {
                historyDiv: $('#vtutor-chat-history'),
                loadingDiv: $('#vtutor-loading'),
                inputField: $('#vtutor-user-input'),
                sendBtn: $('#vtutor-send-btn'),
                clearBtn: $('#vtutor-clear-btn')
            };

            if (!module.ui.historyDiv.length) { return; }

            module.setupFloatingUx();
            module.loadHistory();

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

            module.ui.clearBtn.on('click', function() {
                if (window.confirm('¿Desea limpiar la conversación actual?')) {
                    module.clearHistory();
                }
            });
        }
    };

    return module;
});
