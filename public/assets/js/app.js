(function () {
    function executeScripts(container) {
        container.querySelectorAll('script').forEach(function (oldScript) {
            var newScript = document.createElement('script');

            Array.from(oldScript.attributes).forEach(function (attribute) {
                newScript.setAttribute(attribute.name, attribute.value);
            });

            newScript.textContent = oldScript.textContent;
            oldScript.parentNode.replaceChild(newScript, oldScript);
        });
    }

    function currentScriptName() {
        var path = window.location.pathname || '';
        var parts = path.split('/').filter(Boolean);

        return parts.length === 0 ? 'index.php' : parts[parts.length - 1];
    }

    function markActiveMenu() {
        var current = currentScriptName();

        document.querySelectorAll('#sidebar .app-menu-item').forEach(function (item) {
            var link = item.querySelector('a');
            if (!link) {
                return;
            }

            try {
                var href = new URL(link.getAttribute('href'), window.location.href);
                var target = href.pathname.split('/').filter(Boolean).pop() || '';
                item.classList.toggle('is-active', target === current);
            } catch (error) {
                item.classList.remove('is-active');
            }
        });
    }

    function isMobile() {
        return window.innerWidth < 992;
    }

    function applySidebarState() {
        var body = document.body;

        if (body.classList.contains('auth-page')) {
            return;
        }

        if (!body.dataset.sidebarReady) {
            body.dataset.sidebarReady = 'true';
            body.dataset.desktopState = 'open';
        }

        if (isMobile()) {
            if (!body.dataset.mobileState) {
                body.dataset.mobileState = 'closed';
            }

            body.classList.toggle('app-sidebar-open', body.dataset.mobileState === 'open');
            body.classList.toggle('app-sidebar-collapsed', body.dataset.mobileState !== 'open');
            return;
        }

        if (body.dataset.desktopState !== 'collapsed') {
            body.dataset.desktopState = 'open';
        }

        body.classList.toggle('app-sidebar-open', body.dataset.desktopState === 'open');
        body.classList.toggle('app-sidebar-collapsed', body.dataset.desktopState !== 'open');
    }

    function toggleSidebar() {
        var body = document.body;

        if (isMobile()) {
            body.dataset.mobileState = body.dataset.mobileState === 'open' ? 'closed' : 'open';
        } else {
            body.dataset.desktopState = body.dataset.desktopState === 'collapsed' ? 'open' : 'collapsed';
        }

        applySidebarState();
    }

    function closeMobileSidebarOnOutsideClick(event) {
        var body = document.body;
        var sidebar = document.getElementById('sidebar');
        var toggle = document.querySelector('.toggle-nav');

        if (!isMobile() || body.dataset.mobileState !== 'open' || !sidebar) {
            return;
        }

        if (sidebar.contains(event.target) || (toggle && toggle.contains(event.target))) {
            return;
        }

        body.dataset.mobileState = 'closed';
        applySidebarState();
    }

    function closeAllDropdowns(exceptContainer) {
        document.querySelectorAll('.app-user-menu.is-open, .dropdown.is-open').forEach(function (container) {
            if (exceptContainer && container === exceptContainer) {
                return;
            }

            container.classList.remove('is-open');

            var trigger = container.querySelector('[data-toggle="dropdown"]');
            if (trigger) {
                trigger.setAttribute('aria-expanded', 'false');
            }
        });
    }

    function toggleDropdown(trigger) {
        var container = trigger.closest('.app-user-menu, .dropdown, li');
        if (!container) {
            return;
        }

        var shouldOpen = !container.classList.contains('is-open');
        closeAllDropdowns(container);
        container.classList.toggle('is-open', shouldOpen);
        trigger.setAttribute('aria-expanded', shouldOpen ? 'true' : 'false');
    }

    function resolveModalTarget(trigger) {
        var target = trigger.getAttribute('data-target');
        var href = trigger.getAttribute('href');

        if ((!target || target === '_blank') && href && href.charAt(0) === '#') {
            target = href;
        }

        if (!target || target === '_blank') {
            return null;
        }

        return document.querySelector(target);
    }

    function resolveRemoteModalUrl(trigger) {
        var href = trigger.getAttribute('href');

        if (!href || href.charAt(0) === '#' || /^javascript:/i.test(href)) {
            return null;
        }

        return new URL(href, window.location.href).toString();
    }

    function updateBodyModalState() {
        document.body.classList.toggle('app-modal-open', document.querySelector('.modal.is-open') !== null);
    }

    function setModalState(modal, open) {
        modal.classList.toggle('is-open', open);
        modal.classList.toggle('in', open);
        modal.setAttribute('aria-hidden', open ? 'false' : 'true');
        updateBodyModalState();
    }

    function focusFirstInteractive(modal) {
        var firstInteractive = modal.querySelector('input, select, textarea, button, a[href]');
        if (firstInteractive) {
            firstInteractive.focus();
        }
    }

    function openModal(modal) {
        setModalState(modal, true);
        focusFirstInteractive(modal);
    }

    function closeModal(modal) {
        setModalState(modal, false);
    }

    async function loadRemoteModal(modal, remoteUrl) {
        var dialog = modal.querySelector('.modal-dialog') || modal;
        dialog.innerHTML = '<div class="modal-content"><div class="modal-body"><div class="modal-loading">Cargando formulario...</div></div></div>';
        openModal(modal);

        try {
            var response = await window.fetch(remoteUrl, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            if (!response.ok) {
                throw new Error('Request failed');
            }

            dialog.innerHTML = await response.text();
            executeScripts(dialog);
            focusFirstInteractive(modal);
        } catch (error) {
            dialog.innerHTML = '<div class="modal-content"><div class="modal-body"><div class="alert alert-danger">No se pudo cargar el formulario solicitado.</div></div><div class="modal-footer"><button class="btn btn-default" type="button" data-dismiss="modal">Cerrar</button></div></div>';
        }
    }

    function activateTab(trigger, preserveScrollPosition) {
        var target = trigger.getAttribute('data-target') || trigger.getAttribute('href');
        if (!target || target.charAt(0) !== '#') {
            return;
        }

        var pane = document.querySelector(target);
        if (!pane) {
            return;
        }

        var list = trigger.closest('.nav-tabs');
        if (list) {
            list.querySelectorAll('li').forEach(function (item) {
                item.classList.remove('active');
            });
        }

        var tabItem = trigger.closest('li');
        if (tabItem) {
            tabItem.classList.add('active');
        }

        var tabContent = pane.parentElement;
        if (tabContent) {
            tabContent.querySelectorAll('.tab-pane').forEach(function (currentPane) {
                currentPane.classList.remove('active');
            });
        }

        pane.classList.add('active');

        if (!preserveScrollPosition) {
            pane.scrollIntoView({ block: 'nearest' });
        }
    }

    function initTabs() {
        document.querySelectorAll('.nav-tabs').forEach(function (list) {
            var activeTrigger = list.querySelector('li.active > a[data-toggle="tab"]') || list.querySelector('a[data-toggle="tab"]');
            if (activeTrigger) {
                activateTab(activeTrigger, true);
            }
        });
    }

    function initDataTables($) {
        if (!$.fn.DataTable || !$.fn.dataTable) {
            return;
        }

        $('#dataTables-example').each(function () {
            if (!$.fn.dataTable.isDataTable(this)) {
                $(this).DataTable({
                    responsive: true,
                });
            }
        });
    }

    function initPrintButtons($) {
        if ($.fn.printPage && $('.btnPrint').length > 0) {
            $('.btnPrint').printPage();
        }
    }

    function pruneEmptyAlerts() {
        document.querySelectorAll('[role="alert"]').forEach(function (alertElement) {
            var content = (alertElement.textContent || '').replace(/\s+/g, '').trim();
            if (content === '') {
                alertElement.style.display = 'none';
            }
        });
    }

    function handleGlobalClicks(event) {
        var dropdownTrigger = event.target.closest('[data-toggle="dropdown"]');
        if (dropdownTrigger) {
            event.preventDefault();
            toggleDropdown(dropdownTrigger);
            return;
        }

        var tabTrigger = event.target.closest('[data-toggle="tab"]');
        if (tabTrigger) {
            event.preventDefault();
            activateTab(tabTrigger);
            return;
        }

        var modalDismissTrigger = event.target.closest('[data-dismiss="modal"], .close');
        if (modalDismissTrigger) {
            var dismissModal = modalDismissTrigger.closest('.modal');
            if (dismissModal) {
                event.preventDefault();
                closeModal(dismissModal);
                return;
            }
        }

        var modalTrigger = event.target.closest('[data-toggle="modal"], [data-target]');
        if (modalTrigger) {
            var modal = resolveModalTarget(modalTrigger);
            if (!modal) {
                return;
            }

            event.preventDefault();

            var remoteUrl = resolveRemoteModalUrl(modalTrigger);
            if (remoteUrl) {
                loadRemoteModal(modal, remoteUrl);
                return;
            }

            openModal(modal);
            return;
        }

        var openDropdown = document.querySelector('.app-user-menu.is-open, .dropdown.is-open');
        if (openDropdown && !openDropdown.contains(event.target)) {
            closeAllDropdowns();
        }

        var modalBackdrop = event.target.classList.contains('modal') ? event.target : null;
        if (modalBackdrop) {
            closeModal(modalBackdrop);
        }
    }

    function handleGlobalKeydown(event) {
        if (event.key === 'Escape') {
            closeAllDropdowns();

            var openModals = Array.from(document.querySelectorAll('.modal.is-open'));
            if (openModals.length > 0) {
                closeModal(openModals[openModals.length - 1]);
            }
        }
    }

    function boot() {
        markActiveMenu();
        applySidebarState();
        initTabs();
        pruneEmptyAlerts();

        var toggle = document.querySelector('.toggle-nav');
        if (toggle) {
            toggle.addEventListener('click', function (event) {
                event.preventDefault();
                toggleSidebar();
            });
        }

        document.addEventListener('click', closeMobileSidebarOnOutsideClick);
        document.addEventListener('click', handleGlobalClicks);
        document.addEventListener('keydown', handleGlobalKeydown);
        window.addEventListener('resize', applySidebarState);

        if (typeof window.jQuery !== 'undefined') {
            var $ = window.jQuery;
            initDataTables($);
            initPrintButtons($);
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})();
