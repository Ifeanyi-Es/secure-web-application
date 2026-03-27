
        // Avatar preview and basic client-side handling
        (function(){
            const input = document.getElementById('avatarInput');
            const preview = document.getElementById('avatarPreview');
            const removeBtn = document.getElementById('removePhoto');
            const displayName = document.getElementById('displayName');

            const defaultAvatar = preview.src;

            input.addEventListener('change', function(e){
                const file = e.target.files && e.target.files[0];
                if (!file) return;
                if (!file.type.startsWith('image/')) {
                    alert('Please select an image file.');
                    input.value = '';
                    return;
                }
                const url = URL.createObjectURL(file);
                preview.src = url;
                // update display name from form if available
                const fn = document.getElementById('firstName').value || '';
                const ln = document.getElementById('lastName').value || '';
                displayName.textContent = (fn || ln) ? (fn + (ln ? ' ' + ln : '')) : 'Your Name';
                // revoke after image loads
                preview.onload = () => { URL.revokeObjectURL(url); };
            });

            removeBtn.addEventListener('click', function(){
                input.value = '';
                preview.src = defaultAvatar;
            });

            // live update display name when first/last inputs change
            ['firstName', 'lastName'].forEach(id => {
                const el = document.getElementById(id);
                el.addEventListener('input', () => {
                    const fn = document.getElementById('firstName').value.trim();
                    const ln = document.getElementById('lastName').value.trim();
                    displayName.textContent = (fn || ln) ? (fn + (ln ? ' ' + ln : '')) : 'Your Name';
                });
            });

            // optional: prevent default submission for demo
            // document.getElementById('profileForm').addEventListener('submit', function(e){
            //     // remove this preventDefault in real app to allow normal submission/upload
            //     e.preventDefault();
            //     const btn = this.querySelector('[type="submit"]');
            //     btn.classList.add('disabled');
            //     btn.innerHTML = 'Saving...';
            //     setTimeout(() => {
            //         btn.classList.remove('disabled');
            //         btn.innerHTML = 'Save profile';
            //         alert('Profile saved (demo).');
            //     }, 800);
            // });
        })();
        
            document.getElementById('year').textContent = new Date().getFullYear();

