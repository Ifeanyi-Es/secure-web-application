
    document.addEventListener('DOMContentLoaded', function () {
      // Init DataTable
      const table = $('#Admin_patient_table').DataTable({
        responsive: true,
        pageLength: 10,
        lengthChange: false,
        ordering: true,
        order: [[0, 'asc']],
        columnDefs: [{ orderable: false, targets: [8] }], // Disable sorting on Actions column (index 8)
        dom: '<"d-flex justify-content-end mb-2"p>t',
        language: {
          paginate: {
            previous: '<i class="bi bi-chevron-left"></i>',
            next: '<i class="bi bi-chevron-right"></i>'
          }
        }
      });

      // Small search wired to DataTable
      const tableSearch = document.getElementById('tableSearch');
      if (tableSearch) {
        tableSearch.addEventListener('input', function () {
          table.search(this.value).draw();
        });
      }

      // Add Data modal handling (improved button)
      const addDataBtn = document.getElementById('addDataBtn');
      const addDataModalEl = document.getElementById('addDataModal');
      const addDataModal = new bootstrap.Modal(addDataModalEl);
      const addDataForm = document.getElementById('addDataForm');

      addDataBtn.addEventListener('click', function () {
        addDataForm.reset();
        document.querySelector('[name="id"]').value = ''; // Clear ID for new entry
        document.getElementById('addDataModalLabel').textContent = 'Add Patient';
        addDataModal.show();
      });

      // Handle Edit Button Click (Delegated event for DataTable compatibility)
      document.body.addEventListener('click', function(e) {
        const btn = e.target.closest('.edit-btn');
        if (btn) {
            const id = btn.getAttribute('data-id');
            const first = btn.getAttribute('data-first');
            const last = btn.getAttribute('data-last');
            const diagnosis = btn.getAttribute('data-diagnosis');
            const location = btn.getAttribute('data-location');
            const age = btn.getAttribute('data-age');
            const doctorId = btn.getAttribute('data-doctor-id');
            const nurseId = btn.getAttribute('data-nurse-id');

            // Populate Form
            addDataForm.querySelector('[name="id"]').value = id;
            addDataForm.querySelector('[name="first_name"]').value = first;
            addDataForm.querySelector('[name="last_name"]').value = last;
            addDataForm.querySelector('[name="diagnosis"]').value = diagnosis;
            addDataForm.querySelector('[name="location"]').value = location;
            addDataForm.querySelector('[name="age"]').value = age;
            
            // Set dropdowns
            // If the attribute is null or empty string, set to "" (Unassigned)
            addDataForm.querySelector('[name="doctor_id"]').value = doctorId ? doctorId : "";
            addDataForm.querySelector('[name="nurse_id"]').value = nurseId ? nurseId : "";

            document.getElementById('addDataModalLabel').textContent = 'Edit Patient (ID: ' + id + ')';
        }
      });

      /* 
      // Client-side handling disabled in favor of PHP POST
      addDataForm.addEventListener('submit', function (e) {
        e.preventDefault();
        const fd = new FormData(addDataForm);
        // ... existing code ...
        table.row.add([...]).draw(false);
        addDataModal.hide();
      });
      */
    });
 