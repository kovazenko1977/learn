/**
 * WP Event Booking - Frontend Cascading Logic
 * Flow: Month Selection -> Date Selection -> Time Slots -> Custom Form -> Confirmation
 */

(function() {
  'use strict';

  function initBookingWidget(container) {
    if (!container || container.dataset.wpebInitialized) return;
    container.dataset.wpebInitialized = 'true';

    const eventId = container.dataset.eventId;
    const ajaxUrl = window.wpebConfig ? window.wpebConfig.ajax_url : '/preview.php?ajax=1';

    // State
    const state = {
      eventId: eventId,
      selectedMonth: null, // { year, month }
      selectedDate: null,
      selectedSlot: null,  // { id, time, remaining }
      ticketCount: 1,
      maxAvailable: 1
    };

    // DOM Elements
    const stepTabs = container.querySelectorAll('.wpeb-step-tab');
    const stepPanels = container.querySelectorAll('.wpeb-step-panel');
    const monthContainer = container.querySelector('#wpebMonthList');
    const dateContainer = container.querySelector('#wpebDateList');
    const timeContainer = container.querySelector('#wpebTimeList');
    const dateSection = container.querySelector('#wpebDateSection');
    const timeSection = container.querySelector('#wpebTimeSection');
    const formPanel = container.querySelector('#wpebStepForm');
    const successPanel = container.querySelector('#wpebStepSuccess');
    const bookingForm = container.querySelector('#wpebBookingForm');
    const submitBtn = container.querySelector('#wpebSubmitBtn');
    const formError = container.querySelector('#wpebFormError');

    // Stepper buttons
    const toFormBtn = container.querySelector('#wpebToFormBtn');
    const backToDateTimeBtn = container.querySelector('#wpebBackToDateTimeBtn');

    // Summary fields in Step 2
    const summaryDate = container.querySelector('#wpebSummaryDate');
    const summaryTime = container.querySelector('#wpebSummaryTime');

    // Quantity buttons
    const qtyMinus = container.querySelector('#wpebQtyMinus');
    const qtyPlus = container.querySelector('#wpebQtyPlus');
    const qtyVal = container.querySelector('#wpebQtyVal');
    const ticketCountInput = container.querySelector('#wpebTicketCount');

    // Navigation Switcher
    function goToStep(stepNum) {
      stepPanels.forEach(p => p.style.display = 'none');
      stepTabs.forEach(t => {
        t.classList.remove('active');
        const s = parseInt(t.dataset.step, 10);
        if (s < stepNum) t.classList.add('completed');
        else t.classList.remove('completed');
        if (s === stepNum) t.classList.add('active');
      });

      const currentPanel = container.querySelector(`.wpeb-step-panel[data-step="${stepNum}"]`);
      if (currentPanel) currentPanel.style.display = 'block';

      // Scroll to top of widget smoothly on mobile
      if (window.innerWidth < 640) {
        container.scrollIntoView({ behavior: 'smooth', block: 'start' });
      }
    }

    // 1. Fetch available months
    function loadMonths() {
      monthContainer.innerHTML = '<div style="padding:15px; color:#64748b;">Загрузка месяцев...</div>';

      const url = `${ajaxUrl}${ajaxUrl.includes('?') ? '&' : '?'}action=wpeb_get_months&event_id=${eventId}`;
      fetch(url)
        .then(res => res.json())
        .then(res => {
          const list = (res.data && res.data.months) ? res.data.months : (res.months || []);
          renderMonths(list);
        })
        .catch(err => {
          monthContainer.innerHTML = '<div style="color:#ef4444; padding:10px;">Ошибка при загрузке месяцев. Пожалуйста, обновите страницу.</div>';
        });
    }

    function renderMonths(months) {
      if (!months || !months.length) {
        monthContainer.innerHTML = '<div style="color:#64748b; padding:15px;">На текущий момент нет доступных месяцев для записи.</div>';
        return;
      }

      monthContainer.innerHTML = '';
      months.forEach((m, idx) => {
        const btn = document.createElement('div');
        btn.className = 'wpeb-month-btn' + (idx === 0 ? ' selected' : '');
        btn.textContent = m.month_name;
        btn.dataset.year = m.year;
        btn.dataset.month = m.month;

        btn.addEventListener('click', () => {
          container.querySelectorAll('.wpeb-month-btn').forEach(b => b.classList.remove('selected'));
          btn.classList.add('selected');
          selectMonth(m.year, m.month);
        });

        monthContainer.appendChild(btn);
      });

      // Auto-select first month
      selectMonth(months[0].year, months[0].month);
    }

    function selectMonth(year, month) {
      state.selectedMonth = { year, month };
      state.selectedDate = null;
      state.selectedSlot = null;
      toFormBtn.disabled = true;
      timeSection.style.display = 'none';
      dateSection.style.display = 'block';

      loadDates(year, month);
    }

    // 2. Fetch available dates for selected month
    function loadDates(year, month) {
      dateContainer.innerHTML = '<div style="padding:15px; color:#64748b;">Загрузка дат...</div>';
      const url = `${ajaxUrl}${ajaxUrl.includes('?') ? '&' : '?'}action=wpeb_get_dates&event_id=${eventId}&year=${year}&month=${month}`;

      fetch(url)
        .then(res => res.json())
        .then(res => {
          const dates = (res.data && res.data.dates) ? res.data.dates : (res.dates || []);
          renderDates(dates);
        })
        .catch(err => {
          dateContainer.innerHTML = '<div style="color:#ef4444; padding:10px;">Ошибка при загрузке дат.</div>';
        });
    }

    function renderDates(dates) {
      if (!dates || !dates.length) {
        dateContainer.innerHTML = '<div style="color:#64748b; padding:15px;">В выбранном месяце нет доступных дат.</div>';
        return;
      }

      dateContainer.innerHTML = '';
      dates.forEach((d, idx) => {
        const btn = document.createElement('div');
        btn.className = 'wpeb-date-btn' + (idx === 0 ? ' selected' : '');
        btn.innerHTML = `
          <span class="wpeb-date-dow">${d.day_of_week}</span>
          <span class="wpeb-date-day">${d.day_num}</span>
        `;
        btn.dataset.date = d.date;

        btn.addEventListener('click', () => {
          container.querySelectorAll('.wpeb-date-btn').forEach(b => b.classList.remove('selected'));
          btn.classList.add('selected');
          selectDate(d.date);
        });

        dateContainer.appendChild(btn);
      });

      // Auto-select first date
      selectDate(dates[0].date);
    }

    function selectDate(dateStr) {
      state.selectedDate = dateStr;
      state.selectedSlot = null;
      toFormBtn.disabled = true;
      timeSection.style.display = 'block';

      loadTimeSlots(dateStr);
    }

    // 3. Fetch available time slots for selected date
    function loadTimeSlots(dateStr) {
      timeContainer.innerHTML = '<div style="padding:15px; color:#64748b;">Загрузка сеансов...</div>';
      const url = `${ajaxUrl}${ajaxUrl.includes('?') ? '&' : '?'}action=wpeb_get_slots&event_id=${eventId}&date=${encodeURIComponent(dateStr)}`;

      fetch(url)
        .then(res => res.json())
        .then(res => {
          const slots = (res.data && res.data.slots) ? res.data.slots : (res.slots || []);
          renderTimeSlots(slots);
        })
        .catch(err => {
          timeContainer.innerHTML = '<div style="color:#ef4444; padding:10px;">Ошибка при загрузке сеансов.</div>';
        });
    }

    function renderTimeSlots(slots) {
      if (!slots || !slots.length) {
        timeContainer.innerHTML = '<div style="color:#64748b; padding:15px; grid-column:1/-1;">На эту дату все места полностью заняты или запись закрыта.</div>';
        return;
      }

      timeContainer.innerHTML = '';
      slots.forEach((s, idx) => {
        const card = document.createElement('div');
        card.className = 'wpeb-time-card' + (idx === 0 ? ' selected' : '');
        const badgeClass = s.remaining <= 2 ? 'wpeb-time-badge warning' : 'wpeb-time-badge';

        card.innerHTML = `
          <div class="wpeb-time-val">${s.slot_time}</div>
          <div class="${badgeClass}">${s.badge}</div>
        `;

        card.addEventListener('click', () => {
          container.querySelectorAll('.wpeb-time-card').forEach(c => c.classList.remove('selected'));
          card.classList.add('selected');
          selectSlot(s);
        });

        timeContainer.appendChild(card);
      });

      // Auto-select first available slot
      selectSlot(slots[0]);
    }

    function selectSlot(slot) {
      state.selectedSlot = slot;
      state.maxAvailable = slot.remaining;
      state.ticketCount = 1;
      updateTicketCounterDisplay();

      toFormBtn.disabled = false;
    }

    function updateTicketCounterDisplay() {
      if (qtyVal) qtyVal.textContent = state.ticketCount;
      if (ticketCountInput) ticketCountInput.value = state.ticketCount;
    }

    // Quantity Handlers
    if (qtyMinus) {
      qtyMinus.addEventListener('click', () => {
        if (state.ticketCount > 1) {
          state.ticketCount--;
          updateTicketCounterDisplay();
        }
      });
    }

    if (qtyPlus) {
      qtyPlus.addEventListener('click', () => {
        if (state.ticketCount < state.maxAvailable) {
          state.ticketCount++;
          updateTicketCounterDisplay();
        } else {
          alert(`Максимально доступно для бронирования на это время: ${state.maxAvailable} мест.`);
        }
      });
    }

    // Navigation Step buttons
    if (toFormBtn) {
      toFormBtn.addEventListener('click', () => {
        if (!state.selectedSlot) {
          alert('Пожалуйста, выберите удобное время для записи.');
          return;
        }

        // Fill Step 2 Summary details
        if (summaryDate) summaryDate.textContent = state.selectedDate;
        if (summaryTime) summaryTime.textContent = state.selectedSlot.slot_time;

        goToStep(2);
      });
    }

    if (backToDateTimeBtn) {
      backToDateTimeBtn.addEventListener('click', () => {
        goToStep(1);
      });
    }

    // 4. Booking submission
    if (bookingForm) {
      bookingForm.addEventListener('submit', function(e) {
        e.preventDefault();

        if (formError) {
          formError.style.display = 'none';
          formError.textContent = '';
        }

        submitBtn.disabled = true;
        submitBtn.textContent = 'Обработка записи...';

        const formData = new FormData(bookingForm);
        formData.append('action', 'wpeb_submit_booking');
        formData.append('event_id', state.eventId);
        formData.append('slot_id', state.selectedSlot ? state.selectedSlot.id : 0);
        formData.append('ticket_count', state.ticketCount);

        fetch(ajaxUrl, {
          method: 'POST',
          body: formData
        })
          .then(res => res.json())
          .then(res => {
            submitBtn.disabled = false;
            submitBtn.textContent = 'Подтвердить запись';

            if (res.success) {
              const data = res.data || res;
              renderSuccessScreen(data);
              goToStep(3);
            } else {
              const msg = (res.data && res.data.error) ? res.data.error : (res.error || 'Произошла ошибка при отправке заявки.');
              if (formError) {
                formError.textContent = msg;
                formError.style.display = 'block';
              } else {
                alert(msg);
              }
            }
          })
          .catch(err => {
            submitBtn.disabled = false;
            submitBtn.textContent = 'Подтвердить запись';
            if (formError) {
              formError.textContent = 'Сетевая ошибка при отправке формы. Попробуйте снова.';
              formError.style.display = 'block';
            }
          });
      });
    }

    function renderSuccessScreen(resp) {
      const b = resp.booking || {};
      const code = resp.booking_code || b.booking_code || '---';

      const codeEl = container.querySelector('#wpebSuccessCode');
      const nameEl = container.querySelector('#wpebSuccessName');
      const dateEl = container.querySelector('#wpebSuccessDate');
      const timeEl = container.querySelector('#wpebSuccessTime');
      const ticketsEl = container.querySelector('#wpebSuccessTickets');

      if (codeEl) codeEl.textContent = code;
      if (nameEl) nameEl.textContent = b.client_name || '';
      if (dateEl) dateEl.textContent = b.slot_date || state.selectedDate;
      if (timeEl) timeEl.textContent = b.slot_time || (state.selectedSlot ? state.selectedSlot.slot_time : '');
      if (ticketsEl) ticketsEl.textContent = b.ticket_count || state.ticketCount;

      // Google Calendar link
      const gcalBtn = container.querySelector('#wpebGoogleCalBtn');
      if (gcalBtn && b.slot_date && b.slot_time) {
        const startIso = b.slot_date.replace(/-/g, '') + 'T' + b.slot_time.replace(/:/g, '') + '00';
        const title = encodeURIComponent(b.event_title || 'Запись на мероприятие');
        const details = encodeURIComponent(`Номер бронирования: ${code}\nИмя: ${b.client_name}`);
        gcalBtn.href = `https://calendar.google.com/calendar/render?action=TEMPLATE&text=${title}&dates=${startIso}/${startIso}&details=${details}`;
      }
    }

    // Start loading initial cascade
    loadMonths();
  }

  // Auto-init on page ready
  document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.wpeb-wrapper').forEach(initBookingWidget);
  });

  // Global selector export
  window.wpebSelectEvent = function(eventId) {
    const el = document.querySelector(`.wpeb-wrapper[data-event-id="${eventId}"]`);
    if (el) {
      el.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
  };
})();
