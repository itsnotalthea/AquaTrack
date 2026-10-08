$(function () {
    // nav and footer are rendered by resources/views/partials/{nav,footer}.blade.php

    // rising pixel bubbles
    const $h = $('.hero');
    for (let i = 0; i < 14; i++) {
        const s = 8 + Math.random() * 16;
        $('<span class="bubble">')
            .css({
                left: Math.random() * 100 + '%',
                width: s,
                height: s,
                animationDuration: (8 + Math.random() * 10) + 's',
                animationDelay: (-Math.random() * 10) + 's'
            })
            .appendTo($h);
    }

    // delivery calendar
    if ($('#calendar').length) {
        let d = new Date(),
            y = d.getFullYear(),
            m = d.getMonth(),
            sel = null;

        const sched = {
            3: ['9:00 AM - Brgy. Concepcion Uno (12 gallons)', '2:00 PM - Brgy. Sto. Nino (5 gallons)'],
            8: ['10:00 AM - Brgy. Marikina Heights (20 gallons)'],
            14: ['1:00 PM - Brgy. Malanday (8 gallons)'],
            21: ['9:30 AM - Brgy. Parang (15 gallons)'],
            27: ['3:00 PM - Brgy. Tumana (6 gallons)']
        };

        const names = [
            'January', 'February', 'March', 'April', 'May', 'June',
            'July', 'August', 'September', 'October', 'November', 'December'
        ];

        function draw() {
            let h = '<div class="cal-head">' +
                        '<button id="pv" aria-label="Previous month">&lt;</button>' +
                        '<span>' + names[m] + ' ' + y + '</span>' +
                        '<button id="nx" aria-label="Next month">&gt;</button>' +
                    '</div>' +
                    '<div class="cal-grid">';

            ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa'].forEach(x => h += '<div class="dow">' + x + '</div>');

            const first = new Date(y, m, 1).getDay(),
                  n = new Date(y, m + 1, 0).getDate(),
                  t = new Date();

            for (let i = 0; i < first; i++) h += '<div></div>';

            for (let i = 1; i <= n; i++) {
                const c = [
                    'day',
                    sched[i] ? 'has' : '',
                    (i === t.getDate() && m === t.getMonth() && y === t.getFullYear()) ? 'today' : '',
                    i === sel ? 'sel' : ''
                ].join(' ');

                h += `<div class="${c}" data-d="${i}">${i}</div>`;
            }

            $('#calendar').html(h + '</div>');
        }

        function show() {
            const $l = $('#sched');
            if (!sel) {
                $l.html('<li class="list-group-item">Pick a highlighted date to see scheduled deliveries.</li>');
                return;
            }

            const a = sched[sel];
            $l.html(
                a ? a.map(x => '<li class="list-group-item">' + x + '</li>').join('')
                  : '<li class="list-group-item">No deliveries scheduled for ' + names[m] + ' ' + sel + '.</li>'
            );
        }

        $('#calendar')
            .on('click', '#pv', () => {
                m--;
                if (m < 0) {
                    m = 11;
                    y--;
                }
                sel = null;
                draw();
                show();
            })
            .on('click', '#nx', () => {
                m++;
                if (m > 11) {
                    m = 0;
                    y++;
                }
                sel = null;
                draw();
                show();
            })
            .on('click', '.day', function () {
                sel = +$(this).data('d');
                draw();
                show();
            });

        draw();
        show();
    }

    // order form
    if ($('#orderForm').length) {
        const price = { refill: 25, newcont: 250, dispenser: 150 };

        $('#date').attr('min', new Date().toISOString().split('T')[0]);

        function total() {
            const q = Math.max(1, +$('#qty').val() || 1);
            let t = price[$('#product').val()] * q;
            const del = $('input[name=method]:checked').val() === 'delivery';

            if (del) {
                t += 30;
                $('#addr').prop('required', true).closest('.col-12').show();
            } else {
                $('#addr').prop('required', false).closest('.col-12').hide();
            }

            $('#total').text('PHP ' + t.toFixed(2));
        }

        $('#orderForm').on('input change', 'input,select', total);
        total();

        $('#orderForm').on('submit', function (e) {
            e.preventDefault();
            this.classList.add('was-validated');
            if (!this.checkValidity()) return;

            location.href = '/customer/orders/create';
        });
    }

    // login (ui)
    if ($('#loginForm').length) {
        $('#showPw').on('change', function () {
            $('#pw').attr('type', this.checked ? 'text' : 'password');
        });

        $('#loginForm').on('submit', function (e) {
            e.preventDefault();
            this.classList.add('was-validated');
            if (!this.checkValidity()) return;

            $('#loginMsg')
                .removeClass('d-none alert-danger')
                .addClass('alert-success')
                .text('Welcome, ' + $('#role option:selected').text() + '! Redirecting... (UI demo, no database yet)');

            setTimeout(() => location.href = 'index.html', 1500);
        });
    }

    // promotional video embedded
    $(window).on('message', function (e) {
        let data = e.originalEvent.data;
        if (typeof data === 'string') {
            try {
                data = JSON.parse(data);
            } catch (err) {
                return;
            }
        }

        if (data?.type === 'onCurrentTime' && data.value?.duration > 0) {
            if (data.value.currentTime >= data.value.duration - 5) {
                $('#tiktok-player')[0]?.contentWindow?.postMessage({
                    'x-tiktok-player': true,
                    type: 'pause'
                }, '*');
            }
        }
    });
});