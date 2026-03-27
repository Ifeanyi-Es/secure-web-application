// js/admin_dashboard.js
    document.addEventListener('DOMContentLoaded', function () {

      // Initialize DataTable
      const table = $('#employee_dashboard_table').DataTable({
        pageLength: 10,
        lengthChange: false,
        order: [[0, 'asc']],
        columnDefs: [{ orderable: false, targets: [2,3,4,5] }]
      });

      // Table quick filter wired to top input
      const tableSearch = document.getElementById('tableSearch');
      if (tableSearch) {
        tableSearch.addEventListener('input', function () {
          table.search(this.value).draw();
        });
      }

      // Export CSV (simple client-side)
      document.getElementById('exportBtn').addEventListener('click', function () {
        const csv = [];
        const rows = document.querySelectorAll('#employee_dashboard_table tr');
        rows.forEach(row => {
          const cols = Array.from(row.querySelectorAll('th,td')).map(td => `"${(td.innerText||'').replace(/"/g,'""')}"`);
          if (cols.length) csv.push(cols.join(','));
        });
        const blob = new Blob([csv.join('\n')], { type: 'text/csv' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url; a.download = 'appointments.csv'; document.body.appendChild(a); a.click();
        URL.revokeObjectURL(url); a.remove();
      });

      // Data for charts (Dynamic from PHP or Fallback)
      // Charts are now initialized directly in Admin_dashboard.php
      
      /* 
      // Placeholder stats update removed - PHP handles this now
      */
    });
  