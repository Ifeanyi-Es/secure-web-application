
  (function(){
    const STATUS = {APPLIED:'Applied', INTERVIEW:'Interview', REJECTED:'Rejected'};
    let table;
    const store = {}; 
    let currentRow = null;

    // Populate store from DOM before DataTables initializes
    $('#appBody tr').each(function(){
        const tr = $(this);
        const pid = tr.data('pid');
        store[pid] = {
            pid: pid,
            id: tr.data('id'),
            first: tr.data('first'),
            last: tr.data('last'),
            email: tr.data('email'),
            nationality: tr.data('nation'),
            university: tr.data('univ'),
            course: tr.data('course'),
            role: tr.data('role'),
            status: tr.data('status'),
            cv: tr.data('cv'),
            cover: tr.data('cover'),
            row: this // Keep reference to DOM element
        };
    });

    function initTable() {
      // Check if table is already initialized
      if ($.fn.DataTable.isDataTable('#applicantsTable')) {
          $('#applicantsTable').DataTable().destroy();
      }

      table = $('#applicantsTable').DataTable({
        pageLength: 6,
        lengthChange: false,
        responsive: true,
        dom: 'rtip',
        drawCallback: function() {
           // Re-initialize tooltips after draw
           const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
           tooltipTriggerList.map(function (el) { return new bootstrap.Tooltip(el); });
        }
      });
    }

    initTable();

    // wire search
    $('#tableSearch').on('input', function(){ table.search(this.value).draw(); });
    $('#refreshBtn').on('click', function(){ location.reload(); });

    function toast(msg){
      const t = $(`<div class="toast align-items-center text-bg-dark border-0 show" role="alert" aria-live="assertive" aria-atomic="true"><div class="d-flex"><div class="toast-body small">${msg}</div><button class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button></div></div>`);
      $('#toastArea').append(t);
      setTimeout(()=> t.remove(), 3500);
    }

    function openModal(pid){
      currentRow = pid;
      const a = store[pid];
      if(!a) return;
      $('#modalFirst').text(a.first);
      $('#modalLast').text(a.last);
      $('#modalEmail').text(a.email);
      $('#modalNation').text(a.nationality);
      $('#modalUniv').text(a.university);
      $('#modalCourse').text(a.course);
      $('#modalRole').text(a.role);
      $('#modalStatus').text(a.status);
      $('#modalCV').attr('href',a.cv);
      $('#modalCover').attr('href',a.cover);
      
      // Set IDs for the forms in the modal
      $('#modalAppId').val(a.id);
      $('#modalAppIdReject').val(a.id);

      new bootstrap.Modal(document.getElementById('applicantModal')).show();
    }

    $('body').on('click','.viewBtn', function(){ openModal($(this).data('pid')); });

  })();
 