/**
 * Maintenance Plus - Calendar View
 * Mini calendar with event overlays.
 */

'use strict';

const MPCalendar = (() => {

    const MONTHS = [
        'January','February','March','April','May','June',
        'July','August','September','October','November','December'
    ];
    const DAYS = ['Su','Mo','Tu','We','Th','Fr','Sa'];

    class Calendar {
        constructor(container, events = []) {
            this.container = container;
            this.events    = events;
            this.today     = new Date();
            this.current   = new Date(this.today.getFullYear(), this.today.getMonth(), 1);
            this.render();
        }

        prev() { this.current.setMonth(this.current.getMonth() - 1); this.render(); }
        next() { this.current.setMonth(this.current.getMonth() + 1); this.render(); }

        render() {
            const year  = this.current.getFullYear();
            const month = this.current.getMonth();

            const label = document.getElementById('mp-cal-month-label');
            if (label) label.textContent = `${MONTHS[month]} ${year}`;

            const firstDay  = new Date(year, month, 1).getDay();
            const daysMonth = new Date(year, month + 1, 0).getDate();

            let html = '<table class="mp-cal-table"><thead><tr>';
            DAYS.forEach(d => { html += `<th>${d}</th>`; });
            html += '</tr></thead><tbody><tr>';

            for (let i = 0; i < firstDay; i++) html += '<td></td>';

            for (let day = 1; day <= daysMonth; day++) {
                const dateTs  = new Date(year, month, day);
                const isToday = dateTs.toDateString() === this.today.toDateString();
                const evts    = this._eventsForDay(dateTs);
                const cls     = ['mp-cal-day', isToday ? 'mp-cal-today' : '', evts.length ? 'mp-cal-has-events' : ''].filter(Boolean).join(' ');

                const tipContent = evts.map(e => e.title).join('\n');
                html += `<td class="${cls}" title="${this._esc(tipContent)}">${day}`;

                if (evts.length) {
                    html += `<div class="mp-cal-dots">${evts.slice(0,3).map(e =>
                        `<span class="mp-cal-dot mp-cal-dot-type${e.type ?? 0}"></span>`
                    ).join('')}</div>`;
                }
                html += '</td>';

                if ((firstDay + day) % 7 === 0 && day < daysMonth) html += '</tr><tr>';
            }

            // Fill trailing cells
            const lastDay = (firstDay + daysMonth) % 7;
            if (lastDay !== 0) {
                for (let i = lastDay; i < 7; i++) html += '<td></td>';
            }

            html += '</tr></tbody></table>';
            this.container.innerHTML = html;
        }

        _eventsForDay(date) {
            const ts = date.getTime() / 1000;
            const end = ts + 86400;
            return this.events.filter(e => e.start < end && e.end > ts);
        }

        _esc(s) {
            return String(s).replace(/"/g, '&quot;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
        }
    }

    function init() {
        const el = document.getElementById('mp-calendar');
        if (!el) return;

        let events = [];
        try { events = JSON.parse(el.dataset.events || '[]'); } catch {}

        const cal = new Calendar(el, events);

        document.getElementById('mp-cal-prev')?.addEventListener('click', () => cal.prev());
        document.getElementById('mp-cal-next')?.addEventListener('click', () => cal.next());
    }

    document.readyState === 'loading'
        ? document.addEventListener('DOMContentLoaded', init)
        : init();
})();
