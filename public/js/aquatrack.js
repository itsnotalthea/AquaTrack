
(function ($) {
    'use strict';

    var POLL_MS = 60000;

    function csrf() {
        var meta = $('meta[name="csrf-token"]');

        return meta.length ? meta.attr('content') : '';
    }

    function money(value) {
        return '₱' + Number(value || 0).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    }

    /* Theme ---------------------------------------------------------------- */
    function applyTheme(theme) {
        document.documentElement.dataset.theme = theme === 'dark' ? 'dark' : 'light';

        try {
            localStorage.setItem('aquatrack-theme', theme);
        } catch (e) {
            // storage may be blocked; the current page still gets the theme
        }
    }

    function initTheme() {
        $(document).on('click', '[data-theme-toggle]', function () {
            applyTheme(document.documentElement.dataset.theme === 'dark' ? 'light' : 'dark');
        });
    }

    /* Splash Screen -------------------------------------------------------- */
    function initSplash() {
        var $splash = $('#splashScreen');
        var $text = $('#splashText');
        var text = 'AquaTrack';
        var i = 0;
        var wasShown = false;
        var reducedMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

        if (!$splash.length) {
            return;
        }

        try {
            wasShown = sessionStorage.getItem('aquatrack-splash-shown') === '1';
            if (!wasShown) {
                sessionStorage.setItem('aquatrack-splash-shown', '1');
            }
        } catch (e) {
            // The splash can still run when session storage is unavailable.
        }

        if (wasShown) {
            $splash.remove();
            return;
        }

        function dismiss() {
            $splash.addClass('hidden');
            window.setTimeout(function () { $splash.remove(); }, reducedMotion ? 0 : 500);
        }

        function typeChar() {
            if (i < text.length) {
                $text.text(text.slice(0, i + 1));
                i++;
                window.setTimeout(typeChar, 110);
            } else {
                window.setTimeout(dismiss, 550);
            }
        }

        if (reducedMotion) {
            $text.text(text);
            dismiss();
            return;
        }

        window.setTimeout(typeChar, 200);
    }

    /* Sidebar -------------------------------------------------------------- */
    function closeSidebar() {
        $('body').removeClass('sidebar-open');
        syncSidebarToggle();
    }

    function syncSidebarToggle() {
        var isMobile = window.matchMedia('(max-width: 991.98px)').matches;
        var $body = $('body');
        var $sidebar = $('#appSidebar');

        if (!isMobile) {
            $body.removeClass('sidebar-open');
        }

        var expanded = isMobile ? $body.hasClass('sidebar-open') : !$body.hasClass('sidebar-collapsed');
        $('[data-sidebar-toggle]').attr('aria-expanded', expanded ? 'true' : 'false');
        $sidebar.attr('aria-hidden', expanded ? 'false' : 'true');
        $sidebar.prop('inert', !expanded);
    }

    function initSidebar() {
        if (!$('.sidebar-scrim').length) {
            $('<div class="sidebar-scrim" aria-hidden="true"></div>').appendTo('body');
        }

        $(document).on('click', '[data-sidebar-toggle]', function () {
            var $body = $('body');

            if (window.matchMedia('(max-width: 991.98px)').matches) {
                $body.toggleClass('sidebar-open');
            } else {
                $body.toggleClass('sidebar-collapsed');
            }

            syncSidebarToggle();
        });

        $(document).on('click', '.sidebar-scrim', closeSidebar);
        $(document).on('click', '.app-sidebar a', closeSidebar);
        $(window).on('resize', syncSidebarToggle);
        syncSidebarToggle();
        $(document).on('keydown', function (event) {
            if (event.key === 'Escape') {
                closeSidebar();
                closeDrawer();
            }
        });
    }

    /* Flash banners -------------------------------------------------------- */
    function flash(message, kind, detail) {
        var $stack = $('#appFlash');

        if (!$stack.length) {
            $stack = $('<div id="appFlash" class="flash-stack" aria-live="polite"></div>').appendTo('body');
        }

        var $item = $('<div class="flash" data-auto-dismiss="8000"></div>')
            .addClass(kind === 'danger' ? 'flash-error' : 'flash-success')
            .attr('role', kind === 'danger' ? 'alert' : 'status');

        $item.append('<span class="px-icon">' + (kind === 'danger' ? '!' : '✓') + '</span>');
        var $body = $('<div class="flash-body"></div>').text(message || 'Saved.');

        if (detail) {
            $body.append($('<ul></ul>').append($('<li></li>').text(detail)));
        }

        $item.append($body);
        $item.append('<button type="button" class="flash-close" data-flash-close aria-label="Dismiss">×</button>');
        $stack.append($item);
        scheduleDismiss($item);
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function dismissFlash($item) {
        $item.addClass('is-leaving');

        window.setTimeout(function () {
            $item.remove();
        }, 320);
    }

    function scheduleDismiss($item) {
        var delay = parseInt($item.attr('data-auto-dismiss'), 10) || 8000;

        window.setTimeout(function () {
            if ($item.is(':visible')) {
                dismissFlash($item);
            }
        }, delay);
    }

    function initFlash() {
        $('#appFlash .flash[data-auto-dismiss]').each(function () {
            scheduleDismiss($(this));
        });

        $(document).on('click', '[data-flash-close]', function () {
            dismissFlash($(this).closest('.flash'));
        });
    }

    /* Confirmation modal --------------------------------------------------- */
    function initConfirm() {
        if (!$('#confirmModal').length) {
            var modal = '' +
                '<div class="modal fade" id="confirmModal" tabindex="-1" aria-hidden="true">' +
                '<div class="modal-dialog modal-dialog-centered"><div class="modal-content">' +
                '<div class="modal-header"><h2 class="h5 mb-0">Please confirm</h2>' +
                '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>' +
                '<div class="modal-body" data-confirm-message>Are you sure?</div>' +
                '<div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>' +
                '<button type="button" class="btn btn-danger" data-confirm-accept>Yes, continue</button></div>' +
                '</div></div></div>';
            $('body').append(modal);
        }

        var pending = null;

        $(document).on('click', '[data-confirm]', function (event) {
            var $trigger = $(this);
            var message = $trigger.data('confirm');

            if (!message) {
                return;
            }

            event.preventDefault();
            pending = { type: 'link', element: $trigger };
            $('#confirmModal [data-confirm-message]').text(message);
            bootstrap.Modal.getOrCreateInstance(document.getElementById('confirmModal')).show();
        });

        $(document).on('submit', 'form[data-confirm]', function (event) {
            var $form = $(this);
            var message = $form.data('confirm');

            if (!message || $form.data('confirmed')) {
                return;
            }

            event.preventDefault();
            pending = { type: 'form', element: $form };
            $('#confirmModal [data-confirm-message]').text(message);
            bootstrap.Modal.getOrCreateInstance(document.getElementById('confirmModal')).show();
        });

        $(document).on('click', '[data-confirm-accept]', function () {
            bootstrap.Modal.getOrCreateInstance(document.getElementById('confirmModal')).hide();

            if (!pending) {
                return;
            }

            if (pending.type === 'link') {
                window.location.href = pending.element.attr('href');
            } else {
                pending.element.data('confirmed', true);
                pending.element.trigger('submit');
            }

            pending = null;
        });
    }

    /* Page transitions ----------------------------------------------------- */
    function initTransitions() {
        $(document).on('click', 'a[href]', function (event) {
            var href = $(this).attr('href');
            var target = $(this).attr('target');

            if (!href || href.charAt(0) === '#' || href.indexOf('mailto:') === 0 || href.indexOf('tel:') === 0 || target === '_blank') {
                return;
            }

            if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey || event.which !== 1) {
                return;
            }

            if (href.indexOf(window.location.origin) !== 0 && href.charAt(0) !== '/') {
                return;
            }

            var $main = $('#appMain');

            if (!$main.length) {
                return;
            }

            event.preventDefault();
            $main.css({ transition: 'opacity 220ms ease, transform 220ms ease', opacity: 0, transform: 'translateY(6px)' });
            window.setTimeout(function () {
                window.location.href = href;
            }, 220);
        });
    }

    /* Cart helpers (used by the order builder) ----------------------------- */
    function drawerState() {
        return window.AquaCart || null;
    }

    function openDrawer() {
        $('#cartDrawer, #cartScrim').addClass('open');
        $('body').css('overflow', 'hidden');
    }

    function closeDrawer() {
        $('#cartDrawer, #cartScrim').removeClass('open');
        $('body').css('overflow', '');
    }

    function cartLineHtml(item) {
        var mode = item.container_mode === 'bring_your_own' ? 'Bring your own container' : 'Exchange container';
        var key = encodeURIComponent(item.key);

        return '' +
            '<div class="cart-line" data-cart-line="' + key + '">' +
            '<div><h6>' + $('<div>').text(item.name).html() + '</h6>' +
            '<div class="meta">' + (item.container_mode ? mode + ' · ' : '') + money(item.unit_price) + ' each</div></div>' +
            '<div class="line-total">' + money(item.subtotal) + '</div>' +
            '<div class="qty">' +
            '<button type="button" data-cart-qty="' + key + '" data-delta="-1" aria-label="Decrease">−</button>' +
            '<span>' + item.quantity + '</span>' +
            '<button type="button" data-cart-qty="' + key + '" data-delta="1" aria-label="Increase">+</button>' +
            '<button type="button" class="ms-2" data-cart-remove="' + key + '" aria-label="Remove">×</button>' +
            '</div></div>';
    }

    function renderCart(state) {
        var $list = $('#cartItems');
        var $subtotal = $('#cartSubtotal');
        var $count = $('[data-cart-count]');

        if (!$list.length) {
            return;
        }

        if (!state.items || !state.items.length) {
            $list.html('<p class="text-muted my-3">Your cart is empty. Add a product to get started.</p>');
        } else {
            $list.html(state.items.map(cartLineHtml).join(''));
        }

        $subtotal.text(money(state.subtotal));
        $count.text(state.count).toggleClass('d-none', !state.count);
        $('#cartEmptyNote').toggle(!state.count);
        $('#cartCheckout').prop('disabled', !state.count);
    }

    function requestCart(url, method, data) {
        return $.ajax({
            url: url,
            method: method,
            dataType: 'json',
            data: data || {},
            headers: { 'X-CSRF-TOKEN': csrf(), 'X-Requested-With': 'XMLHttpRequest' }
        });
    }

    function initCart() {
        if (!$('#cartDrawer').length) {
            return;
        }

        $(document).on('click', '[data-cart-open]', function (event) {
            event.preventDefault();
            openDrawer();
        });

        $(document).on('click', '[data-cart-close], #cartScrim', closeDrawer);

        $(document).on('submit', 'form[data-cart-add]', function (event) {
            event.preventDefault();
            var $form = $(this);
            var $button = $form.find('button[type=submit]');
            $button.prop('disabled', true);

            requestCart($form.data('cart-add'), 'POST', $form.serialize())
                .done(function (state) {
                    renderCart(state);
                    closeAddModal();
                    openDrawer();
                    flash('Added to cart.', 'success');
                })
                .fail(function (xhr) {
                    var body = xhr.responseJSON || {};
                    var first = body.errors ? Object.values(body.errors)[0] : null;
                    flash(first && first[0] ? first[0] : (body.message || 'Could not add that item.'), 'danger');
                })
                .always(function () {
                    $button.prop('disabled', false);
                });
        });

        $(document).on('click', '[data-cart-qty]', function () {
            var $button = $(this);
            var key = $button.data('cart-qty');
            var $line = $button.closest('[data-cart-line]');
            var current = parseInt($line.find('.qty span').first().text(), 10) || 1;
            var next = Math.max(1, current + parseInt($button.data('delta'), 10));

            requestCart('/customer/cart/' + key, 'PUT', { quantity: next })
                .done(renderCart)
                .fail(function (xhr) {
                    var body = xhr.responseJSON || {};
                    flash(body.message || 'Could not update the cart.', 'danger');
                });
        });

        $(document).on('click', '[data-cart-remove]', function () {
            requestCart('/customer/cart/' + $(this).data('cart-remove'), 'DELETE')
                .done(renderCart)
                .fail(function () { flash('Could not remove that item.', 'danger'); });
        });

        $(document).on('click', '[data-cart-clear]', function () {
            requestCart('/customer/cart', 'DELETE').done(renderCart);
        });

        // add-item modal populated from a product card
        $(document).on('click', '[data-add-product]', function () {
            var $trigger = $(this);
            var $modal = $('#addItemModal');

            if (!$modal.length) {
                return;
            }

            $modal.find('[name=product_id]').val($trigger.data('product-id'));
            $modal.find('[data-add-name]').text($trigger.data('product-name'));
            $modal.find('[data-add-price]').text(money($trigger.data('product-price')));
            $modal.find('[data-add-stock]').text($trigger.data('product-stock') + ' available');
            $modal.find('[data-add-type]').text($trigger.data('product-type'));
            $modal.find('[name=quantity]').val(1);
            $modal.find('[data-container-choice]').toggle(!!$trigger.data('product-refill'));
            $modal.find('[name=container_mode][value=exchange]').prop('checked', true);
            bootstrap.Modal.getOrCreateInstance(document.getElementById('addItemModal')).show();
        });
    }

    function closeAddModal() {
        var el = document.getElementById('addItemModal');

        if (el && window.bootstrap) {
            bootstrap.Modal.getOrCreateInstance(el).hide();
        }
    }

    /* AJAX forms (staff/ops) ------------------------------------------------ */
    function bindAjaxForms() {
        $(document).on('submit', 'form[data-ajax-post]', function (event) {
            event.preventDefault();

            var $form = $(this);
            var payload = {};

            $form.find('input, select, textarea').each(function () {
                var $field = $(this);

                if ($field.is(':checkbox')) {
                    payload[$field.attr('name')] = $field.is(':checked') ? 1 : 0;
                } else if ($field.attr('name') && !$field.is(':submit') && !$field.is(':button')) {
                    payload[$field.attr('name')] = $field.val();
                }
            });

            var $button = $form.find('button[type=submit]');
            $button.prop('disabled', true);

            $.ajax({
                url: $form.attr('action'),
                method: 'POST',
                dataType: 'json',
                data: payload,
                headers: { 'X-CSRF-TOKEN': csrf(), 'X-Requested-With': 'XMLHttpRequest' }
            }).done(function (body) {
                flash(body.message || 'Saved.', 'success');

                if (body.reload !== false) {
                    window.setTimeout(function () { window.location.reload(); }, 450);
                }
            }).fail(function (xhr) {
                $button.prop('disabled', false);

                var body = xhr.responseJSON || {};
                var detail = null;

                if (body.errors) {
                    detail = Object.values(body.errors)[0][0];
                }

                flash(body.message || detail || 'That did not work.', 'danger');
            });
        });
    }

    // payment modal
    function bindPaymentModal() {
        var $modal = $('#paymentModal');

        if (!$modal.length) {
            return;
        }

        $(document).on('click', '[data-payment-order]', function () {
            var $trigger = $(this);

            $modal.find('[name=method]').val($trigger.data('payment-method') || 'cash');
            $modal.find('[data-payment-label]').text($trigger.data('payment-label') || '');
            $modal.data('order', $trigger.data('payment-order'));
            $modal.data('url', $trigger.data('payment-url') || ('/staff/orders/' + $trigger.data('payment-order') + '/payment'));
            bootstrap.Modal.getOrCreateInstance(document.getElementById('paymentModal')).show();
        });

        $modal.on('submit', 'form', function (event) {
            event.preventDefault();

            var orderId = $modal.data('order');
            var $form = $(this);
            var method = $form.find('[name=method]').val();
            var $button = $form.find('button[type=submit]');
            var url = $modal.data('url') || ('/staff/orders/' + orderId + '/payment');

            $button.prop('disabled', true);

            $.ajax({
                url: url,
                method: 'POST',
                dataType: 'json',
                data: { method: method, _method: 'PUT' },
                headers: { 'X-CSRF-TOKEN': csrf(), 'X-Requested-With': 'XMLHttpRequest' }
            }).done(function () {
                bootstrap.Modal.getOrCreateInstance(document.getElementById('paymentModal')).hide();
                flash('Payment recorded.', 'success');
                window.setTimeout(function () { window.location.reload(); }, 500);
            }).fail(function (xhr) {
                $button.prop('disabled', false);
                var body = xhr.responseJSON || {};
                flash(body.message || 'Could not record that payment.', 'danger');
            });
        });
    }

    /* Container visual ----------------------------------------------------- */
    function refreshContainers() {
        var $boxes = $('[data-container-visual]');

        if (!$boxes.length || document.hidden) {
            return;
        }

        $.getJSON('/api/containers')
            .done(function (body) {
                var ratio = body.fill_ratio || 0;
                var percent = Math.round(ratio * 100);

                $boxes.find('[data-fill-percent]').text(percent + '%');
                $boxes.find('[data-fill-summary]').text(
                    (ratio * 100).toFixed(1) + '% full at station'
                );
                $boxes.find('[data-fill-detail]').text(
                    body.full + ' full / ' + body.empty + ' empty of ' + body.at_station + ' on hand'
                );
                $boxes.find('[data-water-rect]').attr(
                    'transform',
                    'translate(0,' + Math.max(4, Math.min(120, 132 - Math.round(ratio * 120))) + ')'
                );
                $boxes.find('[data-count]').each(function () {
                    var $node = $(this);
                    var match = $.grep(body.data, function (row) { return row.status === $node.data('count'); });

                    if (match.length) {
                        $node.text(match[0].quantity);
                    }
                });
            });
    }

    /* Low stock polling ---------------------------------------------------- */
    function refreshLowStock() {
        var $badge = $('[data-low-stock-badge]');

        if (!$badge.length || document.hidden) {
            return;
        }

        $.getJSON('/api/notifications/low-stock')
            .done(function (body) {
                var list = $('[data-low-stock-list]');

                $badge.text(body.count);
                $badge.toggleClass('d-none', body.count === 0);

                if (list.length) {
                    if (!body.count) {
                        list.html('<p class="text-muted mb-0">Everything is above its threshold.</p>');
                        return;
                    }

                    var html = '';

                    $.each(body.data, function (index, item) {
                        html += '<li class="d-flex justify-content-between border-bottom py-1">' +
                            '<span>' + item.label + '</span>' +
                            '<span><strong>' + item.quantity + '</strong> / ' + item.threshold + '</span></li>';
                    });

                    list.html('<ul class="list-unstyled mb-0">' + html + '</ul>');
                }
            });
    }

    /* Calendar Widget ------------------------------------------------------- */
    function initCalendarWidgets() {
        $('[data-calendar-widget]').each(function () {
            var $widget = $(this);
            var data = $widget.data('deliveries') || {};
            var baseDate = new Date();
            baseDate.setDate(1);

            function renderCalendar(monthOffset) {
                var displayedMonth = new Date(baseDate.getFullYear(), baseDate.getMonth() + monthOffset, 1);
                var year = displayedMonth.getFullYear();
                var month = displayedMonth.getMonth();
                var now = new Date();

                var firstDay = new Date(year, month, 1).getDay();
                var daysInMonth = new Date(year, month + 1, 0).getDate();
                var prevMonthDays = new Date(year, month, 0).getDate();

                var monthNames = ['January', 'February', 'March', 'April', 'May', 'June',
                    'July', 'August', 'September', 'October', 'November', 'December'];

                var html = '<div class="calendar-header">' +
                    '<button type="button" data-cal-prev aria-label="Previous month"><span class="calendar-chevron calendar-chevron-prev" aria-hidden="true"></span></button>' +
                    '<span>' + monthNames[month] + ' ' + year + '</span>' +
                    '<button type="button" data-cal-next aria-label="Next month"><span class="calendar-chevron calendar-chevron-next" aria-hidden="true"></span></button>' +
                    '</div>' +
                    '<div class="calendar-grid">';

                var dow = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
                dow.forEach(function (d) {
                    html += '<div class="calendar-dow">' + d + '</div>';
                });

                // Previous month padding
                for (var i = 0; i < firstDay; i++) {
                    var day = prevMonthDays - firstDay + i + 1;
                    html += '<div class="calendar-day empty">' + day + '</div>';
                }

                // Current month days
                for (var d = 1; d <= daysInMonth; d++) {
                    var dateStr = year + '-' + String(month + 1).padStart(2, '0') + '-' + String(d).padStart(2, '0');
                    var deliveries = data[dateStr] || [];
                    var isToday = year === now.getFullYear() && month === now.getMonth() && d === now.getDate();

                    var classes = 'calendar-day';
                    if (isToday) classes += ' today';
                    if (deliveries.length > 0) classes += ' has-delivery';

                    html += '<div class="' + classes + '" title="' + deliveries.map(function (x) { return x.customer + ' - ' + x.barangay; }).join('; ') + '">' + d + '</div>';
                }

                html += '</div>';
                $widget.html(html);
            }

            var currentOffset = 0;
            renderCalendar(currentOffset);

            $widget.on('click', '[data-cal-prev]', function () {
                currentOffset--;
                renderCalendar(currentOffset);
            });
            $widget.on('click', '[data-cal-next]', function () {
                currentOffset++;
                renderCalendar(currentOffset);
            });
        });
    }
    function startPolling() {
        var timer = window.setInterval(function () {
            refreshContainers();
            refreshLowStock();
        }, POLL_MS);

        $(document).on('visibilitychange', function () {
            if (!document.hidden) {
                refreshContainers();
                refreshLowStock();
            }
        });

        $(window).on('beforeunload', function () {
            window.clearInterval(timer);
        });
    }

    $(function () {
        initSplash();
        initTheme();
        initSidebar();
        initFlash();
        initConfirm();
        initTransitions();
        initCart();
        bindAjaxForms();
        bindPaymentModal();
        initCalendarWidgets();
        startPolling();
    });
})(jQuery);
