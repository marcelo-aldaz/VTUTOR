define([], function() {
    function isMobileOrTablet() {
        var ua = navigator.userAgent || '';
        return window.matchMedia('(max-width: 1024px)').matches || /Android|iPhone|iPad|iPod|Mobile|Tablet/i.test(ua);
    }

    function centerPosition(size, screenSize) {
        return Math.max(0, Math.round((screenSize - size) / 2));
    }

    function openPopup(url, windowname) {
        var width = Math.min(1220, Math.max(880, Math.round(window.screen.availWidth * 0.74)));
        var height = Math.min(800, Math.max(600, Math.round(window.screen.availHeight * 0.70)));
        var left = centerPosition(width, window.screen.availWidth);
        var top = centerPosition(height, window.screen.availHeight);
        var features = [
            'toolbar=no',
            'location=no',
            'status=no',
            'menubar=no',
            'scrollbars=yes',
            'resizable=yes',
            'width=' + width,
            'height=' + height,
            'left=' + left,
            'top=' + top
        ].join(',');
        var popup = window.open(url, windowname, features);
        if (popup && typeof popup.focus === 'function') {
            popup.focus();
            return true;
        }
        return false;
    }

    function ensurePageLauncher(panel, chaturl, popupname) {
        var courseid = panel.getAttribute('data-courseid') || '0';
        var existing = document.getElementById('vtutor-page-launcher-' + courseid);
        if (existing) {
            return existing;
        }
        var button = document.createElement('button');
        button.type = 'button';
        button.id = 'vtutor-page-launcher-' + courseid;
        button.className = 'vtutor-page-launcher';
        button.setAttribute('title', panel.getAttribute('data-launchertitle') || 'Abrir VTutor Pro');
        button.setAttribute('aria-label', panel.getAttribute('data-launchertitle') || 'Abrir VTutor Pro');
        var label = panel.getAttribute('data-launcherlabel') || 'VTutor Pro';
        var markimg = panel.querySelector('.vtutor-launch-entry-mark img');
        var logo = markimg ? markimg.getAttribute('src') : '';
        var mark = document.createElement('span');
        mark.className = 'vtutor-page-launcher-mark';
        if (logo) {
            var image = document.createElement('img');
            image.src = logo;
            image.alt = '';
            mark.appendChild(image);
        } else {
            mark.textContent = 'V';
        }
        var labelspan = document.createElement('span');
        labelspan.className = 'vtutor-page-launcher-label';
        labelspan.textContent = label;
        button.appendChild(mark);
        button.appendChild(labelspan);
        button.addEventListener('click', function(e) {
            e.preventDefault();
            if (isMobileOrTablet()) {
                window.location.href = chaturl;
                return;
            }
            var ok = openPopup(chaturl, popupname);
            if (!ok) {
                window.location.href = chaturl;
            }
        });
        document.body.appendChild(button);
        return button;
    }

    return {
        init: function(courseid, chaturl) {
            var selector = '.vtutor-launch-entry[data-courseid="' + String(courseid) + '"]';
            var panel = document.querySelector(selector);
            if (!panel) {
                return;
            }
            var popupname = panel.getAttribute('data-popupname') || ('vtutorpro_' + String(courseid));
            var button = panel.querySelector('.vtutor-open-popup');
            if (button) {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    if (isMobileOrTablet()) {
                        window.location.href = chaturl;
                        return;
                    }
                    var ok = openPopup(chaturl, popupname);
                    if (!ok) {
                        window.location.href = chaturl;
                    }
                });
            }
            ensurePageLauncher(panel, chaturl, popupname);
        }
    };
});
