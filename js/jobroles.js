// js/jobroles.js
    // Simple client-side filter for the job cards (no external libs)
    (function(){
      const searchInput = document.getElementById('jobSearch');
      const filterType = document.getElementById('filterType');
      const searchBtn = document.getElementById('searchBtn'); // Note: searchBtn might not exist in HTML, check if needed
      const jobsList = Array.from(document.querySelectorAll('#jobsList article.job-card'));

      function applyFilters(){
        const q = (searchInput.value || '').trim().toLowerCase();
        const type = filterType.value;
        jobsList.forEach(card => {
          const title = (card.dataset.title || '').toLowerCase();
          const loc = (card.dataset.location || '').toLowerCase();
          const t = card.dataset.type || '';
          const matchesQuery = q === '' || title.includes(q) || loc.includes(q);
          const matchesType = type === '' || t === type;
          card.style.display = (matchesQuery && matchesType) ? '' : 'none';
        });
      }

      // Check for URL search params on load
      const urlParams = new URLSearchParams(window.location.search);
      const searchParam = urlParams.get('search');
      if (searchParam) {
          searchInput.value = searchParam;
          applyFilters();
      }

      // run search on Enter key
      if (searchInput) {
        searchInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
            e.preventDefault();
            applyFilters();
            }
        });
        // run on input for instant feedback too
        searchInput.addEventListener('input', applyFilters);
      }
      
      if (filterType) {
        filterType.addEventListener('change', applyFilters);
      }
      
      if (searchBtn) {
        searchBtn.addEventListener('click', applyFilters);
      }

      // Ensure apply buttons include aria and open in same tab to application form with role param
      document.querySelectorAll('.apply-btn').forEach(btn => {
        // Buttons are anchors; no extra JS needed. Optional: add click handler to record analytics or open in new tab
        btn.setAttribute('role', 'button');
      });

      // Accessibility: allow "details" to behave nicely on keyboard
      document.querySelectorAll('details summary').forEach(s => {
        s.addEventListener('keydown', (e) => {
          if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            s.parentElement.open = !s.parentElement.open;
          }
        });
      });
    })();
 