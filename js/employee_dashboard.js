// employee_dashboard.js
    document.addEventListener('DOMContentLoaded', function () {
      // DataTable init
      const table = $('#employee_dashboard_table').DataTable({
        responsive: true,
        pageLength: 10,
        lengthChange: false,
        ordering: true,
        dom: 't<"d-flex justify-content-end mt-2"p>',
        columnDefs: [{ orderable: false, targets: [] }],
        language: {
          paginate: { previous: '<i class="bi bi-chevron-left"></i>', next: '<i class="bi bi-chevron-right"></i>' }
        }
      });

      // Wire search input
      const search = document.getElementById('tableSearch');
      if (search) search.addEventListener('input', () => table.search(search.value).draw());

      // KPI values derived from table (placeholder logic)
      // const I_interviewing = table.rows().count();
      // document.getElementById('interviewing').textContent = I_interviewing;
      // const I_successful = Math.max(0, Math.round(I_interviewing * 0.33));
      // document.getElementById('successful').textContent = I_successful;
      // const I_totalApplicants = 68;
      // document.getElementById('totalApplicants').textContent = I_totalApplicants ;
      
      // Get values from PHP-rendered DOM
      const elInterviewing = document.getElementById('interviewing');
      const elSuccessful = document.getElementById('successful');
      const elTotal = document.getElementById('totalApplicants');

      const I_interviewing = elInterviewing ? parseInt(elInterviewing.textContent.trim()) || 0 : 0;
      const I_successful = elSuccessful ? parseInt(elSuccessful.textContent.trim()) || 0 : 0;
      const I_totalApplicants = elTotal ? parseInt(elTotal.textContent.trim()) || 0 : 0;

      console.log('KPI Data Loaded:', { I_interviewing, I_successful, I_totalApplicants });

      // Small donut charts for KPI cards (Chart.js)
      function makeDoughnut(ctx, value, totalColor, accentColor) {
        if (typeof Chart === 'undefined') return;
        
        return new Chart(ctx, {
          type: 'doughnut',
          data: {
            datasets: [{
              data: [value, 100 - value],
              backgroundColor: [accentColor, totalColor],
              hoverBackgroundColor: [accentColor, totalColor],
              borderWidth: 0
            }]
          },
          options: {
            cutout: '75%',
            plugins: { legend: { display: false }, tooltip: { enabled: false } },
            maintainAspectRatio: false
          }
        });
      }

      // Render KPI mini-charts with placeholder percentages
      // Use a fixed denominator or max value for the charts, or just show the value relative to total applicants if available
      const totalBase = I_totalApplicants > 0 ? I_totalApplicants : 100; 

      makeDoughnut(document.getElementById('kpiTotalChart').getContext('2d'), Math.min(100, Math.round((I_interviewing/totalBase)*100)), '#E9EEF8', '#0d6efd');
      makeDoughnut(document.getElementById('kpiInChart').getContext('2d'), Math.min(100, Math.round((I_successful/totalBase)*100)), '#E9EEF8', '#f59e0b');
      // For total applicants, maybe just show 100% or relative to a target? Let's just show 75% for visual if we don't have a target.
      // Or if I_totalApplicants is a percentage (like 68%), use it. But here it is a count.
      // Let's just make it a full circle or arbitrary for now since we don't have a "target" count.
      makeDoughnut(document.getElementById('kpiOccupancy').getContext('2d'), 100, '#E9EEF8', '#10b981');
    });
    
 