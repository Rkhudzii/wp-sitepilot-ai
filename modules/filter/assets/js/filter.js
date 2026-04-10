document.addEventListener('DOMContentLoaded', function () {
    const groups = {};

    document.querySelectorAll('.recrm-filter-builder-instance').forEach(function (block) {
        const instance = block.dataset.instance || '';
        if (!instance) {
            return;
        }

        if (!groups[instance]) {
            groups[instance] = {
                instance: instance,
                formBlock: null,
                form: null,
                resultsBlock: null,
                results: null,
                forms: []
            };
        }

        const form = block.querySelector('.recrm-filter');
        const results = block.querySelector('.recrm-results-wrap');

        if (form) {
            groups[instance].forms.push(form);

            if (!groups[instance].form) {
                groups[instance].formBlock = block;
                groups[instance].form = form;
            }
        }

        if (results && !groups[instance].results) {
            groups[instance].resultsBlock = block;
            groups[instance].results = results;
        }
    });

    Object.keys(groups).forEach(function (instance) {
        const group = groups[instance];

        group.forms.forEach(function (form) {
            initFilterToggle(form);
        });

        bindGroup(group);
    });

    window.addEventListener('resize', function () {
        Object.keys(groups).forEach(function (instance) {
            const group = groups[instance];

            if (group.forms && group.forms.length) {
                group.forms.forEach(function (form) {
                    syncToggleStateForViewport(form);
                });
            }

            if (group.form) {
                syncToggleStateForViewport(group.form);
            }
        });
    });

    function isMobileViewport() {
        return window.innerWidth <= 1024;
    }

    function hasActiveFilters(form) {
        if (!form) {
            return false;
        }

        const ignored = ['recrm_filter', 'nonce', 'paged', 'recrm_instance', 'filter_id'];

        const fields = form.querySelectorAll('select, input');
        for (let i = 0; i < fields.length; i++) {
            const field = fields[i];
            const name = field.name || '';

            if (!name || ignored.indexOf(name) !== -1) {
                continue;
            }

            if (field.type === 'hidden') {
                continue;
            }

            if (field.type === 'checkbox' || field.type === 'radio') {
                if (field.checked) {
                    return true;
                }
                continue;
            }

            if ((field.value || '').toString().trim() !== '') {
                return true;
            }
        }

        return false;
    }

    function openFilter(form, immediate) {
        const toggle = form.querySelector('.recrm-filter-toggle');
        const panel = form.querySelector('.recrm-filter-collapsible');

        if (!toggle || !panel) {
            return;
        }

        form.classList.add('is-open');
        toggle.setAttribute('aria-expanded', 'true');
        toggle.querySelector('.recrm-filter-toggle-text').textContent = 'Закрити фільтр пошуку';

        panel.style.visibility = 'visible';
        panel.style.opacity = '1';
        panel.style.overflow = 'hidden';

        if (immediate) {
            panel.style.transition = 'none';
            panel.style.maxHeight = 'none';
            panel.style.overflow = 'visible';

            requestAnimationFrame(function () {
                panel.style.transition = '';
            });

            return;
        }

        panel.style.maxHeight = panel.scrollHeight + 'px';

        window.setTimeout(function () {
            if (form.classList.contains('is-open') && isMobileViewport()) {
                panel.style.maxHeight = 'none';
                panel.style.overflow = 'visible';
            }
        }, 400);
    }

    function closeFilter(form, immediate) {
        const toggle = form.querySelector('.recrm-filter-toggle');
        const panel = form.querySelector('.recrm-filter-collapsible');

        if (!toggle || !panel) {
            return;
        }

        form.classList.remove('is-open');
        toggle.setAttribute('aria-expanded', 'false');
        toggle.querySelector('.recrm-filter-toggle-text').textContent = 'Фільтр пошуку';

        panel.style.overflow = 'hidden';

        if (immediate) {
            panel.style.transition = 'none';
            panel.style.maxHeight = '0px';
            panel.style.opacity = '0';
            panel.style.visibility = 'hidden';

            requestAnimationFrame(function () {
                panel.style.transition = '';
            });

            return;
        }

        panel.style.maxHeight = panel.scrollHeight + 'px';

        requestAnimationFrame(function () {
            panel.style.maxHeight = '0px';
            panel.style.opacity = '0';
            panel.style.visibility = 'hidden';
        });
    }

    function syncToggleStateForViewport(form) {
        const toggle = form.querySelector('.recrm-filter-toggle');
        const panel = form.querySelector('.recrm-filter-collapsible');

        if (!toggle || !panel) {
            return;
        }

        if (!isMobileViewport()) {
            form.classList.remove('is-open');
            toggle.setAttribute('aria-expanded', 'true');
            toggle.querySelector('.recrm-filter-toggle-text').textContent = 'Фільтр пошуку';

            panel.style.maxHeight = 'none';
            panel.style.opacity = '1';
            panel.style.visibility = 'visible';
            panel.style.overflow = 'visible';
            return;
        }

        if (form.classList.contains('is-open')) {
            toggle.setAttribute('aria-expanded', 'true');
            toggle.querySelector('.recrm-filter-toggle-text').textContent = 'Закрити фільтр пошуку';
            panel.style.maxHeight = 'none';
            panel.style.opacity = '1';
            panel.style.visibility = 'visible';
            panel.style.overflow = 'visible';
            return;
        }

        toggle.setAttribute('aria-expanded', 'false');
        toggle.querySelector('.recrm-filter-toggle-text').textContent = 'Фільтр пошуку';
        panel.style.maxHeight = '0px';
        panel.style.opacity = '0';
        panel.style.visibility = 'hidden';
        panel.style.overflow = 'hidden';
    }

    function initFilterToggle(form, preserveOpenState) {
        if (!form) {
            return;
        }

        const toggle = form.querySelector('.recrm-filter-toggle');
        const panel = form.querySelector('.recrm-filter-collapsible');

        if (!toggle || !panel) {
            return;
        }

        if (form.dataset.recrmToggleInit === '1') {
            syncToggleStateForViewport(form);
            return;
        }

        form.dataset.recrmToggleInit = '1';

        const shouldOpenInitially = !!preserveOpenState;

        if (isMobileViewport()) {
            if (shouldOpenInitially) {
                form.classList.add('is-open');
                openFilter(form, true);
            } else {
                form.classList.remove('is-open');
                closeFilter(form, true);
            }
        } else {
            syncToggleStateForViewport(form);
        }

        toggle.addEventListener('click', function () {
            if (!isMobileViewport()) {
                return;
            }

            if (form.classList.contains('is-open')) {
                closeFilter(form, false);
            } else {
                openFilter(form, false);
            }
        });
    }

    function bindGroup(group) {
        if (!group.form) {
            return;
        }

        initFilterToggle(group.form);

        if (!group.results) {
            return;
        }

        if (group.form.dataset.recrmBound === '1') {
            return;
        }

        group.form.dataset.recrmBound = '1';

        group.form.addEventListener('submit', function (e) {
            e.preventDefault();

            const pagedInput = group.form.querySelector('input[name="paged"]');
            const paged = pagedInput ? (pagedInput.value || '1') : '1';

            submitGroup(group, paged);
        });

        bindAutoSubmit(group);
        bindPagination(group);
        bindReset(group);
    }

    function bindAutoSubmit(group) {
        if (!group.form || !group.results) {
            return;
        }

        let timer = null;
        const form = group.form;

        function runSubmit() {
            resetPaged(form);
            submitGroup(group, 1);
        }

        form.querySelectorAll('select, input[type="checkbox"], input[type="radio"]').forEach(function (field) {
            field.addEventListener('change', function () {
                clearTimeout(timer);
                runSubmit();
            });
        });

        form.querySelectorAll('input[type="number"], input[type="text"], input[type="search"]').forEach(function (field) {
            field.addEventListener('input', function () {
                clearTimeout(timer);
                timer = setTimeout(function () {
                    runSubmit();
                }, 400);
            });

            field.addEventListener('change', function () {
                clearTimeout(timer);
                runSubmit();
            });
        });
    }

    function bindReset(group) {
        if (!group.form || !group.results) {
            return;
        }

        const resetLink = group.form.querySelector('.recrm-btn-secondary');
        if (!resetLink || resetLink.dataset.recrmBound === '1') {
            return;
        }

        resetLink.dataset.recrmBound = '1';

        resetLink.addEventListener('click', function (e) {
            e.preventDefault();

            group.form.querySelectorAll('select').forEach(function (field) {
                field.selectedIndex = 0;
            });

            group.form.querySelectorAll('input[type="number"], input[type="text"], input[type="search"]').forEach(function (field) {
                field.value = '';
            });

            group.form.querySelectorAll('input[type="checkbox"], input[type="radio"]').forEach(function (field) {
                field.checked = false;
            });

            resetPaged(group.form);
            submitGroup(group, 1);
        });
    }

    function resetPaged(form) {
        const pagedInput = form.querySelector('input[name="paged"]');
        if (pagedInput) {
            pagedInput.value = '1';
        }
    }

    function bindPagination(group) {
        if (!group.results) {
            return;
        }

        group.results.querySelectorAll('.recrm-pagination a').forEach(function (link) {
            if (link.dataset.recrmBound === '1') {
                return;
            }

            link.dataset.recrmBound = '1';

            link.addEventListener('click', function (e) {
                e.preventDefault();

                const url = new URL(link.href);
                const paged = url.searchParams.get('paged') || getPageFromPath(url.pathname) || 1;

                const pagedInput = group.form.querySelector('input[name="paged"]');
                if (pagedInput) {
                    pagedInput.value = String(paged);
                }

                submitGroup(group, paged);
            });
        });
    }

    function submitGroup(group, paged) {
        if (!group.form || !group.results) {
            return;
        }

        if (group.formBlock && group.formBlock.classList.contains('is-loading')) {
            return;
        }

        if (typeof recrmFilter === 'undefined' || !recrmFilter.ajaxUrl) {
            console.error('RECRM filter: ajaxUrl not found');
            return;
        }

        const wasOpen = group.form.classList.contains('is-open');
        const formData = new FormData(group.form);
        formData.append('action', 'recrm_filter_properties');

        if (!formData.get('nonce') && recrmFilter.nonce) {
            formData.append('nonce', recrmFilter.nonce);
        }

        if (paged) {
            formData.set('paged', String(paged));
        }

        formData.append('limit', group.form.dataset.limit || group.form.getAttribute('data-limit') || '12');
        formData.append('fields', group.form.dataset.fields || group.form.getAttribute('data-fields') || '');
        formData.append('action_url', group.form.getAttribute('action') || '');
        formData.append('recrm_instance', group.instance);

        let layout = group.form.dataset.layout || 'default';

        if (
            group.form.classList.contains('recrm-filter-header') ||
            group.form.classList.contains('recrm-header-filter')
        ) {
            layout = 'header';
        } else if (
            group.form.classList.contains('recrm-filter-sidebar') ||
            group.form.classList.contains('recrm-sidebar-filter')
        ) {
            layout = 'sidebar';
        }

        formData.append('layout', layout);

        if (group.formBlock) {
            group.formBlock.classList.add('is-loading');
        }

        fetch(recrmFilter.ajaxUrl, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        })
            .then(function (res) {
                return res.json();
            })
            .then(function (response) {
                if (!response.success || !response.data) {
                    console.error('RECRM filter response error:', response);
                    return;
                }

                if (response.data.form_html) {
                    const temp = document.createElement('div');
                    temp.innerHTML = response.data.form_html;

                    const newForm = temp.querySelector('.recrm-filter');

                    if (newForm && group.form) {
                        group.form.replaceWith(newForm);
                        group.form = newForm;
                        group.form.dataset.recrmBound = '';
                        group.form.dataset.recrmToggleInit = '';

                        const groupForms = [];
                        document.querySelectorAll('.recrm-filter-builder-instance[data-instance="' + group.instance + '"] .recrm-filter').forEach(function (form) {
                            groupForms.push(form);
                        });
                        group.forms = groupForms;

                        group.forms.forEach(function (form) {
                            initFilterToggle(form, wasOpen);
                        });

                        bindGroup(group);
                    }
                }

                if (response.data.html) {
                    group.results.innerHTML = response.data.html;
                    bindPagination(group);
                }
            })
            .catch(function (error) {
                console.error('RECRM filter error:', error);
            })
            .finally(function () {
                if (group.formBlock) {
                    group.formBlock.classList.remove('is-loading');
                }
            });
    }

    function getPageFromPath(pathname) {
        const match = pathname.match(/\/page\/(\d+)/);
        return match ? match[1] : 1;
    }
});