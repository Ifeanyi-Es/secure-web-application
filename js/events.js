  
    document.addEventListener('DOMContentLoaded', function() {
      // Initialize calendar
      const calendarEl = document.getElementById('calendar');
      const calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        selectable: true,
        editable: true,
        headerToolbar: {
          left: 'prev,next today',
          center: 'title',
          right: 'dayGridMonth,timeGridWeek,timeGridDay'
        },
        dateClick: function(info) {
          // Open modal and prefill date
          document.getElementById('eventDate').value = info.dateStr;
          const modal = new bootstrap.Modal(document.getElementById('eventModal'));
          modal.show();
        },
        events: [
          { title: 'Sample Event', start: new Date().toISOString().slice(0,10), color: '#3788d8' }
        ]
      });

      calendar.render();

      // Handle form submission
      document.getElementById('eventForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const title = document.getElementById('eventTitle').value;
        const date = document.getElementById('eventDate').value;
        const color = document.getElementById('eventColor').value;

        if(title && date) {
          calendar.addEvent({
            title: title,
            start: date,
            color: color
          });
        }

        // Reset form and close modal
        this.reset();
        bootstrap.Modal.getInstance(document.getElementById('eventModal')).hide();
      });
    });
  