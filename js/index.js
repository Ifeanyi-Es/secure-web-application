 
    (function(){
      // Elements
      const qInput = document.getElementById('q');
      const searchForm = document.getElementById('search_bar');
      const jobsGrid = document.getElementById('jobsGrid');
      const jobCols = Array.from(jobsGrid.querySelectorAll('.col'));
      const jobCards = jobCols.map(c => c.querySelector('.job-card'));
      const totalCountEl = document.getElementById('totalCount') || (() => {
        const el = document.createElement('span'); el.id='totalCount'; el.style.display='none'; document.body.appendChild(el); return el;
      })();
      const visibleCountEl = document.getElementById('visibleCount');

      totalCountEl.textContent = jobCards.length;
      visibleCountEl.textContent = jobCards.length;

      const normalize = s => (s||'').toString().toLowerCase().trim();

      function filterJobs(query){
        const terms = normalize(query).split(/\s+/).filter(Boolean);
        let visible = 0;
        jobCols.forEach(col => {
          const card = col.querySelector('.job-card');
          if(!card){ col.style.display = 'none'; return; }
          const hay = [card.dataset.title, card.dataset.location, card.dataset.type, card.dataset.tags].join(' ').toLowerCase();
          const matched = terms.length === 0 ? true : terms.every(t => hay.includes(t));
          col.style.display = matched ? '' : 'none';
          if(matched) visible++;
        });
        visibleCountEl.textContent = visible;
      }

      // debounce
      function debounce(fn, wait=200){
        let t;
        return (...args) => { clearTimeout(t); t = setTimeout(()=>fn(...args), wait); };
      }

      const debounced = debounce(() => filterJobs(qInput.value), 200);

      qInput.addEventListener('input', debounced);

      searchForm.addEventListener('submit', function(e){
        e.preventDefault();
        filterJobs(qInput.value);
        // smooth-scroll to jobs if results present
        const jobsSection = document.getElementById('jobs');
        if (jobsSection) jobsSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
      });

      // Ensure browse button shows all and scrolls
      const browseBtn = document.getElementById('browseBtn');
      if(browseBtn){
        browseBtn.addEventListener('click', function(){
          qInput.value = '';
          filterJobs('');
        });
      }

      // Job details modal loader
      document.addEventListener('click', function(e){
        const btn = e.target.closest('.viewBtn');
        if(!btn) return;
        const card = btn.closest('.job-card');
        if(!card) return;
        
        const title = card.dataset.title;
        const location = card.dataset.location;
        const type = card.dataset.type;
        const tags = card.dataset.tags;
        
        // Prefer data from button if available (full details), else fallback to card text
        let desc = btn.dataset.desc || (card.querySelector('.card-text') ? card.querySelector('.card-text').textContent : '');
        let req = btn.dataset.req || '';
        let resp = btn.dataset.resp || '';

        const modalLabel = document.getElementById('jobModalLabel');
        const modalBody = document.getElementById('jobModalBody');
        const modalApply = document.getElementById('modalApplyBtn');
        
        if(modalLabel) modalLabel.textContent = title;
        
        if(modalBody) {
            let content = '<p><strong>Location:</strong> ' + location + '</p>' +
                          '<p><strong>Type:</strong> ' + type + '</p>';
            
            if (tags) content += '<p><strong>Tags:</strong> ' + tags + '</p>';
            
            content += '<div class="mt-3"><strong>Description:</strong><p class="text-muted">' + desc + '</p></div>';
            
            if (req) content += '<div class="mt-3"><strong>Requirements:</strong><div class="text-muted">' + req + '</div></div>';
            if (resp) content += '<div class="mt-3"><strong>Responsibilities:</strong><div class="text-muted">' + resp + '</div></div>';
            
            modalBody.innerHTML = content;
        }

        if(modalApply){
          const applyLink = card.querySelector('a.btn-primary') ? card.querySelector('a.btn-primary').getAttribute('href') : '#';
          modalApply.setAttribute('href', applyLink);
        }
      });

      // initial filter to set counts
      filterJobs('');
    })();
 