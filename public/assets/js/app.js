(function () {
    var confirmationDialogState = {
        modal: null,
        title: null,
        message: null,
        details: null,
        confirmButton: null,
        cancelButton: null,
        resolve: null,
    };

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
        if (confirmationDialogState.modal === modal && typeof confirmationDialogState.resolve === 'function') {
            settleConfirmationDialog(false);
            return;
        }

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
            normalizeLegacyModals(dialog);
            executeScripts(dialog);
            pruneEmptyAlerts();
            focusFirstInteractive(modal);
        } catch (error) {
            dialog.innerHTML = '<div class="modal-content"><div class="modal-body"><div class="alert alert-danger">No se pudo cargar el formulario solicitado.</div></div><div class="modal-footer"><button class="btn btn-default" type="button" data-dismiss="modal">Cerrar</button></div></div>';
            normalizeLegacyModals(dialog);
        }
    }

    function directChildren(parent, selector) {
        return Array.from(parent.children).filter(function (child) {
            return child.matches(selector);
        });
    }

    function wrapLooseFieldPairs(container) {
        if (!container || container.dataset.appLoosePairsWrapped === 'true') {
            return;
        }

        Array.from(container.children).forEach(function (child) {
            if (!child.matches('label.control-label')) {
                return;
            }

            var next = child.nextElementSibling;
            if (!next || next.matches('.modal-footer')) {
                return;
            }

            if (child.parentElement !== container || next.parentElement !== container) {
                return;
            }

            var wrapper = document.createElement('div');
            wrapper.className = 'form-group app-form-row';
            container.insertBefore(wrapper, child);
            wrapper.appendChild(child);
            wrapper.appendChild(next);
        });

        container.dataset.appLoosePairsWrapped = 'true';
    }

    function classifyFormGroups(scope) {
        scope.querySelectorAll('.form-group').forEach(function (group) {
            if (group.dataset.appFormGroupReady === 'true') {
                return;
            }

            var directLabels = directChildren(group, 'label');
            var directColumns = Array.from(group.children).filter(function (child) {
                return !child.matches('label') && /\bcol-(xs|sm|md|lg)-\d+\b/.test(child.className);
            });

            if (directLabels.length === 1 && directColumns.length === 1) {
                group.classList.add('app-form-row');
            } else if (directLabels.length > 1) {
                group.classList.add('app-form-row--multi');
            }

            group.dataset.appFormGroupReady = 'true';
        });
    }

    function normalizeModalBody(body) {
        if (!body || body.dataset.appModalBodyReady === 'true') {
            return;
        }

        body.classList.add('app-modal-body');
        wrapLooseFieldPairs(body);

        body.querySelectorAll('section.panel, .panel, .row > [class*="col-"]').forEach(function (container) {
            wrapLooseFieldPairs(container);
        });

        classifyFormGroups(body);

        var topRow = Array.from(body.children).find(function (child) {
            return child.classList && child.classList.contains('row');
        });

        if (topRow) {
            var columns = Array.from(topRow.children).filter(function (child) {
                return /\bcol-(xs|sm|md|lg)-\d+\b/.test(child.className);
            });

            if (columns.length >= 2) {
                topRow.classList.add('app-modal-layout');
                columns[0].classList.add('app-modal-aside');
                columns.slice(1).forEach(function (column) {
                    column.classList.add('app-modal-main');
                });
            }
        }

        body.querySelectorAll('section.panel, .panel').forEach(function (panel) {
            if (panel.closest('.modal-body')) {
                panel.classList.add('app-modal-panel');
            }
        });

        body.querySelectorAll('img, .thumb').forEach(function (image) {
            image.classList.add('app-modal-preview');
        });

        var fieldCount = body.querySelectorAll('.form-group, label.control-label').length;
        if (fieldCount <= 2) {
            body.classList.add('app-modal-body--compact');
        }

        if (body.querySelector('input[type="file"]')) {
            body.classList.add('app-modal-body--has-upload');
        }

        body.dataset.appModalBodyReady = 'true';
    }

    function normalizeModalContent(content) {
        if (!content || content.dataset.appModalContentReady === 'true') {
            return;
        }

        content.classList.add('app-modal-content');

        var dialog = content.parentElement && content.parentElement.classList.contains('modal-dialog')
            ? content.parentElement
            : null;
        if (dialog) {
            dialog.classList.add('app-modal-dialog');
            if (dialog.id === 'mdialTamanio') {
                dialog.classList.add('app-modal-dialog--wide');
            }
        }

        var form = content.closest('form') || content.querySelector('form');
        if (form) {
            form.classList.add('app-modal-form');
        }

        var header = content.querySelector('.modal-header');
        if (header) {
            header.classList.add('app-modal-header');
        }

        var body = content.querySelector('.modal-body');
        if (body) {
            var directFooter = directChildren(body, '.modal-footer')[0];
            if (directFooter) {
                content.appendChild(directFooter);
            }

            normalizeModalBody(body);
        }

        var footer = content.querySelector('.modal-footer');
        if (footer) {
            footer.classList.add('app-modal-footer');
        }

        if ((body && body.classList.contains('app-modal-body--compact')) || (form && form.querySelectorAll('.form-group').length <= 2)) {
            content.classList.add('app-modal-content--compact');
        }

        content.dataset.appModalContentReady = 'true';
    }

    function normalizeLegacyModals(root) {
        var scope = root || document;

        if (scope.matches && scope.matches('.modal')) {
            scope.classList.add('app-modal');
        }

        scope.querySelectorAll('.modal').forEach(function (modal) {
            modal.classList.add('app-modal');
        });

        var contents = [];
        if (scope.matches && scope.matches('.modal-content')) {
            contents.push(scope);
        }

        scope.querySelectorAll('.modal-content').forEach(function (content) {
            contents.push(content);
        });

        contents.forEach(normalizeModalContent);
    }

    function getConfirmationDialog() {
        if (confirmationDialogState.modal) {
            return confirmationDialogState;
        }

        var modal = document.createElement('div');
        modal.className = 'modal fade app-confirm-modal';
        modal.id = 'app-confirm-modal';
        modal.setAttribute('tabindex', '-1');
        modal.setAttribute('role', 'dialog');
        modal.setAttribute('aria-hidden', 'true');
        modal.innerHTML = [
            '<div class="modal-dialog app-modal-dialog app-confirm-dialog" role="document">',
            '  <div class="modal-content app-modal-content app-modal-content--compact">',
            '    <div class="modal-header app-modal-header">',
            '      <h3 id="app-confirm-title">Confirmar eliminacion</h3>',
            '      <button type="button" class="close" aria-label="Cerrar">&times;</button>',
            '    </div>',
            '    <div class="modal-body app-modal-body app-modal-body--compact app-confirm-body">',
            '      <div class="app-confirm-lead">',
            '        <span class="app-confirm-icon" aria-hidden="true"><i class="fa fa-exclamation-triangle"></i></span>',
            '        <div class="app-confirm-copy">',
            '          <p class="app-confirm-message" id="app-confirm-message"></p>',
            '          <p class="app-confirm-details" id="app-confirm-details" hidden></p>',
            '        </div>',
            '      </div>',
            '    </div>',
            '    <div class="modal-footer app-modal-footer">',
            '      <button type="button" class="btn btn-default app-confirm-cancel">Cancelar</button>',
            '      <button type="button" class="btn btn-danger app-confirm-submit">Si, eliminar</button>',
            '    </div>',
            '  </div>',
            '</div>'
        ].join('');

        document.body.appendChild(modal);
        normalizeLegacyModals(modal);

        confirmationDialogState.modal = modal;
        confirmationDialogState.title = modal.querySelector('#app-confirm-title');
        confirmationDialogState.message = modal.querySelector('#app-confirm-message');
        confirmationDialogState.details = modal.querySelector('#app-confirm-details');
        confirmationDialogState.confirmButton = modal.querySelector('.app-confirm-submit');
        confirmationDialogState.cancelButton = modal.querySelector('.app-confirm-cancel');

        confirmationDialogState.confirmButton.addEventListener('click', function () {
            settleConfirmationDialog(true);
        });

        confirmationDialogState.cancelButton.addEventListener('click', function () {
            settleConfirmationDialog(false);
        });

        return confirmationDialogState;
    }

    function settleConfirmationDialog(result) {
        var dialog = getConfirmationDialog();
        var resolver = dialog.resolve;

        dialog.resolve = null;
        setModalState(dialog.modal, false);

        if (typeof resolver === 'function') {
            resolver(result);
        }
    }

    function summarizeDeleteTarget(trigger) {
        var row = trigger.closest('tr');
        if (!row) {
            return '';
        }

        var values = Array.from(row.querySelectorAll('td')).map(function (cell) {
            return (cell.textContent || '').replace(/\s+/g, ' ').trim();
        }).filter(function (value) {
            return value !== '';
        });

        if (values.length === 0) {
            return '';
        }

        return values.slice(0, 3).join(' · ');
    }

    function requestConfirmation(options) {
        var dialog = getConfirmationDialog();
        var settings = options || {};

        if (typeof dialog.resolve === 'function') {
            settleConfirmationDialog(false);
        }

        dialog.title.textContent = settings.title || 'Confirmar eliminacion';
        dialog.message.textContent = settings.message || 'Esta accion eliminara el registro seleccionado y no se puede deshacer.';
        dialog.confirmButton.textContent = settings.confirmLabel || 'Si, eliminar';
        dialog.cancelButton.textContent = settings.cancelLabel || 'Cancelar';

        if (settings.details) {
            dialog.details.hidden = false;
            dialog.details.textContent = settings.details;
        } else {
            dialog.details.hidden = true;
            dialog.details.textContent = '';
        }

        return new Promise(function (resolve) {
            dialog.resolve = resolve;
            openModal(dialog.modal);
        });
    }

    function getDeleteNavigationUrl(trigger) {
        var href = trigger.getAttribute('href');
        if (!href || href.charAt(0) === '#' || /^javascript:/i.test(href)) {
            return null;
        }

        try {
            var url = new URL(href, window.location.href);
            return url.searchParams.has('idborrar') ? url.toString() : null;
        } catch (error) {
            return null;
        }
    }

    function requestDeleteConfirmation(trigger, customOptions) {
        var options = customOptions || {};
        var details = options.details || trigger.getAttribute('data-confirm-details') || summarizeDeleteTarget(trigger);

        return requestConfirmation({
            title: options.title || trigger.getAttribute('data-confirm-title') || 'Confirmar eliminacion',
            message: options.message || trigger.getAttribute('data-confirm-message') || 'Esta accion eliminara el registro seleccionado y no se puede deshacer.',
            confirmLabel: options.confirmLabel || trigger.getAttribute('data-confirm-label') || 'Si, eliminar',
            cancelLabel: options.cancelLabel || 'Cancelar',
            details: details,
        });
    }

    function installGlobalConfirmationHelpers() {
        window.appConfirmDanger = function (options) {
            var settings = typeof options === 'string' ? { message: options } : (options || {});

            return requestConfirmation({
                title: settings.title || 'Confirmar eliminacion',
                message: settings.message || 'Esta accion eliminara el registro seleccionado y no se puede deshacer.',
                confirmLabel: settings.confirmLabel || 'Si, eliminar',
                cancelLabel: settings.cancelLabel || 'Cancelar',
                details: settings.details || '',
            });
        };
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

        var dataTablesLanguage = {
            decimal: ',',
            emptyTable: 'No hay datos disponibles en esta tabla',
            info: 'Mostrando _START_ a _END_ de _TOTAL_ registros',
            infoEmpty: 'Mostrando 0 a 0 de 0 registros',
            infoFiltered: '(filtrado de _MAX_ registros totales)',
            infoPostFix: '',
            thousands: '.',
            lengthMenu: 'Mostrar _MENU_ registros',
            loadingRecords: 'Cargando...',
            processing: 'Procesando...',
            search: 'Buscar:',
            zeroRecords: 'No se encontraron resultados',
            paginate: {
                first: 'Primero',
                last: 'Ultimo',
                next: 'Siguiente',
                previous: 'Anterior'
            },
            aria: {
                sortAscending: ': activar para ordenar la columna de manera ascendente',
                sortDescending: ': activar para ordenar la columna de manera descendente'
            }
        };

        $('#dataTables-example').each(function () {
            if (!$.fn.dataTable.isDataTable(this)) {
                $(this).DataTable({
                    responsive: true,
                    language: dataTablesLanguage,
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

        var deleteTrigger = event.target.closest('a[href], button[href]');
        if (deleteTrigger && !deleteTrigger.hasAttribute('data-confirm-bypass')) {
            var deleteUrl = getDeleteNavigationUrl(deleteTrigger);
            if (deleteUrl) {
                event.preventDefault();
                requestDeleteConfirmation(deleteTrigger).then(function (confirmed) {
                    if (confirmed) {
                        window.location.href = deleteUrl;
                    }
                });
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
        normalizeLegacyModals(document);
        getConfirmationDialog();
        installGlobalConfirmationHelpers();

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
