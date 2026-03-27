
  
    document.addEventListener('DOMContentLoaded', function () {
  const $table = $('#employee_dashboard_table');
  const search = document.getElementById('tableSearch');

  const showError = (message) => {
    console.error(message);
    // Replace table body with a friendly message
    $table.find('tbody').html('<tr><td colspan="5" class="text-center text-muted">' + message + '</td></tr>');
  };

  // Fetch data first to avoid DataTables 'Loading...' spinner getting stuck
  fetch('get_applications.php', { credentials: 'same-origin' })
    .then(response => {
      if (response.status === 401) {
        // Not logged in
        window.alert('You are not logged in. Please sign in to view your applications.');
        window.location.href = 'Login.html';
        throw new Error('Unauthorized');
      }
      // Read response as text to avoid JSON parse errors on empty responses
      return response.text().then(text => {
        if (!response.ok) {
          // Try to extract meaningful message from body
          let msg = 'Server error';
          if (text) {
            try {
              const obj = JSON.parse(text);
              msg = obj.error || JSON.stringify(obj);
            } catch (e) {
              msg = text;
            }
          }
          throw new Error(msg);
        }
        // response.ok: parse body if present, otherwise return empty array
        if (!text) return [];
        try {
          return JSON.parse(text);
        } catch (e) {
          throw new Error('Invalid JSON from server');
        }
      });
    })
    .then(data => {
      if (!Array.isArray(data)) {
        showError('Invalid data received from server');
        return;
      }

      const table = $table.DataTable({
        data: data,
        responsive: true,
        paging: true,
        pageLength: 10,
        lengthChange: false,
        ordering: true,
        columnDefs: [{ orderable: false, targets: 0 }],
        dom: 't<"d-flex justify-content-between align-items-center mt-2"p>',
        language: {
          paginate: { previous: '<i class="bi bi-chevron-left"></i>', next: '<i class="bi bi-chevron-right"></i>' }
        },
        columns: [
          {
            data: null,
            render: function (data, type, row, meta) {
              return meta.row + 1;
            }
          },
          { data: 'job_title' },
          { data: 'category' },
          { data: 'location' },
          { 
            data: 'status',
            render: function(data, type, row) {
                let colorClass = '';
                if (data === 'Interview') colorClass = 'text-success';
                else if (data === 'Rejected') colorClass = 'text-primary';
                return `<span class="${colorClass} fw-bold">${data}</span>`;
            }
          }
        ]
      });

      if (search) {
        search.addEventListener('input', function () {
          table.search(this.value).draw();
        });
      }
    })
    .catch(err => {
      if (err.message === 'Unauthorized') return;
      showError('Unable to load applications. ' + (err.message || ''));
    });
});
 