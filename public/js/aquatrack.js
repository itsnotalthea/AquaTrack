
(function ($) {
    'use strict';

    var POLL_MS = 60000;

    function csrf() {
        var meta = $('meta[name="csrf-token"]');

        return meta.length ? meta.attr('content') : '';
    }

    function flash(message, kind) {
        var $box = $('#ajaxFlash');

        if (!$box.length) {
            $box = $('<div id="ajaxFlash" class="alert rounded-0 d-none"></div>').prependTo('main');
        }

        $box
            .removeClass('d-none alert-success alert-danger')
            .addClass(kind === 'danger' ? 'alert-danger' : 'alert-success')
            .text(message);

        window.scrollTo({ top: 0, behavior: 'smooth' });
    }


    function bindOrderForm() {
        var $form = $('form[data-ajax]');

        if (!$form.length) {
            return;
        }

        $form.on('submit', function (event) {
            event.preventDefault();

            $form.addClass('was-validated');

            if (!$form[0].checkValidity()) {
                return;
            }

            var payload = {};

            $form.find('input, select, textarea').each(function () {
                var $field = $(this);

                if (!$field.is(':checkbox')) {
                    return;
                }

                payload[$field.attr('name')] = $field.is(':checked') ? 1 : 0;
            });

            $form.find('input, select, textarea').not(':checkbox').each(function () {
                var $field = $(this);
                var name = $field.attr('name');

                if (name && !$(this).is(':submit')) {
                    payload[name] = $field.val();
                }
            });

            $form.find('button[type=submit]').prop('disabled', true);

            $.ajax({
                url: $form.data('ajax'),
                method: 'POST',
                dataType: 'json',
                data: payload,
                headers: { 'X-CSRF-TOKEN': csrf(), 'X-Requested-With': 'XMLHttpRequest' }
            }).done(function (payloadJson) {
                window.location.href = payloadJson.redirect;
            }).fail(function (xhr) {
                $form.find('button[type=submit]').prop('disabled', false);

                var body = xhr.responseJSON || {};
                var message = body.message || 'Please check the highlighted fields.';

                if (body.errors) {
                    $.each(body.errors, function (field, messages) {
                        $form.find('[name="' + field + '"]').each(function () {
                            var $field = $(this);
                            var $feedback = $field.siblings('.invalid-feedback.d-block');

                            if (!$feedback.length) {
                                $feedback = $('<div class="invalid-feedback d-block"></div>').appendTo($field.parent());
                            }

                            $feedback.text($.isArray(messages) ? messages[0] : messages);
                        });
                    });
                }

                flash(message, 'danger');
            });
        });
    }

    // staff: status changes and payments without a reload

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
                    window.setTimeout(function () { window.location.reload(); }, 400);
                }
            }).fail(function (xhr) {
                $button.prop('disabled', false);

                var body = xhr.responseJSON || {};

                flash(body.message || 'That did not work.', 'danger');
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
            $modal.modal('show');
        });

        $modal.on('submit', 'form', function (event) {
            event.preventDefault();

            var orderId = $modal.data('order');
            var $form = $(this);
            var method = $form.find('[name=method]').val();
            var $button = $form.find('button[type=submit]');

            $button.prop('disabled', true);

            $.ajax({
                url: '/staff/orders/' + orderId + '/payment',
                method: 'POST',
                dataType: 'json',
                data: { method: method },
                headers: { 'X-CSRF-TOKEN': csrf(), 'X-Requested-With': 'XMLHttpRequest' }
            }).done(function () {
                $modal.modal('hide');
                flash('Payment recorded.', 'success');
                window.setTimeout(function () { window.location.reload(); }, 500);
            }).fail(function (xhr) {
                $button.prop('disabled', false);
                var body = xhr.responseJSON || {};
                flash(body.message || 'Could not record that payment.', 'danger');
            });
        });
    }

    // container visual

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
                // the water group carries the wave, so move the group and keep the two in step
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

    // low stock polling

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
                    if (! body.count) {
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

    function startPolling() {
        var timer = window.setInterval(function () {
            refreshContainers();
            refreshLowStock();
        }, POLL_MS);

        // stop polling while the tab is hidden, catch up when it comes back
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
        bindOrderForm();
        bindAjaxForms();
        bindPaymentModal();
        startPolling();
    });
})(jQuery);