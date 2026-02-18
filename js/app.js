// --- 1. GLOBAL CONFIGURATION ---
// base value for non-admin pages; allow override by other scripts
var API_URL = (typeof API_URL !== 'undefined') ? API_URL : 'http://127.0.0.1:8000/api/spaces';

// --- UTILITY FUNCTIONS ---
// Logout function
window.logout = function() {
    // Clear all possible stored auth keys
    try { localStorage.removeItem('auth_token'); } catch(e){}
    try { localStorage.removeItem('user_name'); } catch(e){}
    try { localStorage.removeItem('user_email'); } catch(e){}
    try { localStorage.removeItem('token'); } catch(e){}
    try { localStorage.removeItem('user'); } catch(e){}
    sessionStorage.clear();
    window.location.href = 'index.html';
};

// Navigation function
window.navigate = function(page) {
    window.location.href = page;
};

// Redirect function
window.redirect = function(page) {
    window.location.href = page;
};

// --- 2. MAKE THIS FUNCTION PUBLIC (The "Magic Key" Fix) ---
// We define this OUTSIDE the event listener so HTML buttons can find it.
window.modifyOccupancy = async function(id, currentVal, change) {
    let newVal = parseInt(currentVal) + change;
    if (newVal < 0) newVal = 0; // Prevent negative numbers

    try {
        const response = await fetch(`${API_URL}/${id}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ occupancy: newVal })
        });

        if (response.ok) {
            // Success! Reload to see change immediately
            location.reload(); 
        } else {
            alert("Failed to update.");
        }
    } catch (error) {
        console.error("Error updating:", error);
    }
};



// --- 3. MAIN APP LOGIC ---
document.addEventListener('DOMContentLoaded', () => {

    // --- NAVIGATION SETUP ---
    // Set active nav item based on current page
    const navItems = document.querySelectorAll('.nav-item');
    const currentPage = window.location.pathname.split('/').pop() || 'dashboard.html';
    
    navItems.forEach(item => {
        const href = item.getAttribute('href');
        if (href === currentPage || (currentPage === '' && href === 'dashboard.html')) {
            item.classList.add('active');
        } else {
            item.classList.remove('active');
        }
        
        // Add click handler to update active state
        item.addEventListener('click', function() {
            navItems.forEach(nav => nav.classList.remove('active'));
            this.classList.add('active');
        });
    });

    // --- LOGOUT SETUP ---
    const logoutLinks = document.querySelectorAll('.logout-link');
    logoutLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            logout();
        });
    });

    // --- USER MENU UPDATE ---
    // Update the top-right user name/email across pages.
    async function updateUserMenu() {
        const nameEls = document.querySelectorAll('.user-name');
        const emailEls = document.querySelectorAll('.user-email');
        let name = localStorage.getItem('user_name') || '';
        let email = localStorage.getItem('user_email') || '';
        const token = localStorage.getItem('auth_token');

        if (token) {
            try {
                const resp = await fetch('http://127.0.0.1:8000/api/profile', {
                    headers: {
                        'Accept': 'application/json',
                        'Authorization': 'Bearer ' + token
                    }
                });
                if (resp.ok) {
                    const data = await resp.json();
                    const user = data.user || data;
                    if (user) {
                        if (user.name) { name = user.name; localStorage.setItem('user_name', name); }
                        if (user.email) { email = user.email; localStorage.setItem('user_email', email); }
                    }
                }
            } catch (err) {
                console.warn('Failed to refresh profile:', err);
            }
        }

        nameEls.forEach(el => { if (name) el.textContent = name; });
        emailEls.forEach(el => { if (email) el.textContent = email; });
    }

    // Expose for other pages/scripts (e.g., settings page) to call after updates
    window.updateUserMenu = updateUserMenu;

    // Run once on load to sync header labels
    updateUserMenu();

    // Handle save buttons
    const saveBtns = document.querySelectorAll('.form-actions .btn-primary');
    saveBtns.forEach(btn => {
        btn.addEventListener('click', function(e) {
            if (this.textContent.includes('Save')) {
                e.preventDefault();
                alert('Changes saved successfully!');
            }
        });
    });

    // --- FETCH DATA (The Traffic Cop) ---
    async function fetchSpaces() {
        try {
            // API_URL may be '/api' on admin pages or '/api/spaces' on student pages.
            // ensure we request the correct endpoint for space list.
            let url = API_URL;
            if (url.endsWith('/api')) {
                url = url + '/spaces';
            }
            const response = await fetch(url);
            const spaces = await response.json();

            // LOGIC: Check which page we are on
            if (document.getElementById('adminTableBody')) {
                loadAdminDashboard(spaces); // We are on Admin Page
            } 
            else if (document.querySelector('.occupancy-list')) {
                updateDashboard(spaces);    // We are on Student Page
            }
            // Render main dashboard grid if present
            if (document.getElementById('spacesGrid')) {
                renderSpacesGrid(spaces);
            }
            // Render map spaces list if present
            if (document.querySelector('.map-spaces-list')) {
                renderMapSpacesList(spaces);
            }
            
        } catch (error) {
            console.error("Error fetching data:", error);
        }
    }

    

    // --- RENDER SPACES GRID ON DASHBOARD ---
    function renderSpacesGrid(spaces) {
        const grid = document.getElementById('spacesGrid');
        if (!grid) return;

        grid.innerHTML = '';

        spaces.forEach(space => {
            const percentage = space.capacity ? Math.round((space.occupancy / space.capacity) * 100) : 0;
            // prefer status column from DB if present
            let status = (space.status || '').toString().toLowerCase();
            if (!status) {
                if (percentage >= 80) status = 'high';
                else if (percentage >= 50) status = 'medium';
                else status = 'low';
            }

            const image = "url('data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 400 250%22><rect fill=%22%23cbd5e1%22 width=%22400%22 height=%22250%22/></svg>')";

            // tags not present in DB by default
            const tagsHTML = '';

            const card = document.createElement('div');
            card.className = 'space-card';
            card.innerHTML = `
                <div class="space-image" style="background-image: ${image}"></div>
                <div class="space-info">
                    <h3>${space.name}</h3>
                    <p class="space-type">${space.type || ''}</p>
                    <div class="space-capacity">
                        <span class="capacity-number">${space.occupancy} / ${space.capacity}</span>
                        <span class="capacity-badge ${status}">${status.charAt(0).toUpperCase() + status.slice(1)}</span>
                    </div>
                    <div class="capacity-bar">
                        <div class="capacity-fill" style="width: ${percentage}%"></div>
                    </div>
                    <div class="space-tags">
                        ${tagsHTML}
                    </div>
                </div>
            `;

            grid.appendChild(card);
        });
    }

    // --- RENDER SPACES LIST FOR MAP PAGE ---
    function renderMapSpacesList(spaces) {
        const listContainer = document.querySelector('.spaces-list');
        if (!listContainer) return;

        listContainer.innerHTML = '';

        spaces.forEach(space => {
            const percentage = space.capacity ? Math.round((space.occupancy / space.capacity) * 100) : 0;
            let status = (space.status || '').toString().toLowerCase();
            if (!status) {
                if (percentage >= 80) status = 'high';
                else if (percentage >= 50) status = 'medium';
                else status = 'low';
            }

            const item = document.createElement('div');
            item.className = 'space-list-item';
            item.setAttribute('data-occupancy', status);
            item.innerHTML = `
                <div class="list-item-header">
                    <h4>${space.name}</h4>
                    <span class="capacity-badge ${status}">${status.charAt(0).toUpperCase() + status.slice(1)}</span>
                </div>
                <p class="space-type">${space.type || ''}</p>
                <p class="capacity-info">${space.occupancy} / ${space.capacity} people</p>
            `;

            listContainer.appendChild(item);
        });
    }

    // --- RENDER STUDENT DASHBOARD ---
    function updateDashboard(spaces) {
        const listContainer = document.querySelector('.occupancy-list');
        if (!listContainer) return;

        listContainer.innerHTML = ''; 

        spaces.forEach(space => {
            let badgeClass = 'badge-green';
            let statusText = 'Low Traffic';
            const percentage = (space.occupancy / space.capacity);

            if (percentage > 0.8) {
                badgeClass = 'badge-red';
                statusText = 'High Traffic';
            } else if (percentage > 0.5) {
                badgeClass = 'badge-yellow';
                statusText = 'Moderate';
            }

            const cardHTML = `
                <div class="zone-card">
                    <div class="zone-info">
                        <h4>${space.name}</h4>
                        <p>Capacity: ${space.occupancy}/${space.capacity}</p>
                    </div>
                    <span class="badge ${badgeClass}">${statusText}</span>
                </div>
            `;
            listContainer.innerHTML += cardHTML;
        });
    }

    // --- RENDER ADMIN DASHBOARD ---
    function loadAdminDashboard(spaces) {
        const tableBody = document.getElementById('adminTableBody');
        if (!tableBody) return;

        tableBody.innerHTML = ''; 

        spaces.forEach(space => {
            const percentage = space.occupancy / space.capacity;
            let statusBadge = '<span class="badge badge-green">Safe</span>';
            
            if (percentage >= 0.8) {
                statusBadge = '<span class="badge badge-red">Crowded</span>';
            } else if (percentage >= 0.5) {
                statusBadge = '<span class="badge badge-yellow">Moderate</span>';
            }

            const rowHTML = `
                <tr style="border-bottom: 1px solid #eee;">
                    <td style="padding: 15px 10px; font-weight:bold;">${space.name}</td>
                    <td style="padding: 15px 10px;">
                        <span style="font-size: 1.2rem; font-weight: bold;">${space.occupancy}</span>
                        <span style="color: #666; font-size: 0.8rem;"> / ${space.capacity}</span>
                    </td>
                    <td style="padding: 15px 10px;">${statusBadge}</td>
                    <td style="padding: 15px 10px;">
                        <button class="btn-action btn-minus" 
                            onclick="modifyOccupancy(${space.id}, ${space.occupancy}, -1)">
                            <i class="fas fa-minus"></i> -
                        </button>
                        <button class="btn-action btn-plus" 
                            onclick="modifyOccupancy(${space.id}, ${space.occupancy}, 1)">
                            <i class="fas fa-plus"></i> +
                        </button>
                    </td>
                </tr>
            `;
            tableBody.innerHTML += rowHTML;
        });
    }

    // --- START THE ENGINE ---
    fetchSpaces(); // Run once immediately
    setInterval(fetchSpaces, 5000); // Run every 5 seconds

    // Listen for cross-tab notifications using BroadcastChannel
    try {
        const spacesChannel = new BroadcastChannel('campus_spaces');
        spacesChannel.onmessage = (event) => {
            if (event.data && event.data.type === 'spaces_updated') {
                console.log('Spaces updated from admin, refreshing...');
                try { fetchSpaces(); } catch(err) { console.warn('Failed to refresh on BroadcastChannel', err); }
            }
        };
    } catch(err) {
        console.warn('BroadcastChannel not supported, falling back to storage events', err);
        // Fallback: listen to storage events
        window.addEventListener('storage', (e) => {
            if (!e) return;
            if (e.key === 'spaces_updated_at') {
                try { fetchSpaces(); } catch (err) { console.warn('Failed to refresh on storage event', err); }
            }
        });
    }
    // FIND THIS FUNCTION IN js/app.js AND REPLACE IT ENTIRELY
async function fetchPendingUsers() {
    const tableBody = document.getElementById('pendingUsersTable');
    const badge = document.getElementById('pending-badge');
    
    // 1. If we are not on the Admin page, stop.
    if (!tableBody) return; 

    // 2. GET THE KEY (Token)
    const token = localStorage.getItem('auth_token');

    try {
        const response = await fetch(`${AUTH_URL}/pending-users`, {
            method: 'GET',
            // 3. SEND THE KEY (The Missing Part!)
            headers: {
                'Authorization': `Bearer ${token}`, 
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            }
        });

        // Check if the token is invalid (Expired or Fake)
        if (response.status === 401) {
            console.error("Token invalid. Logging out...");
            logout(); // Force logout if token is bad
            return;
        }

        const users = await response.json();

        // 4. Update the Notification Badge (Red number)
        if (badge) {
            badge.innerText = users.length;
            badge.style.display = users.length > 0 ? 'inline-block' : 'none';
        }

        // 5. Update the Table
        tableBody.innerHTML = ''; 
        
        if (users.length === 0) {
            tableBody.innerHTML = '<tr><td colspan="4" style="text-align:center;">No pending registrations.</td></tr>';
            return;
        }

        users.forEach(user => {
            const row = `
                <tr>
                    <td>${user.name}</td>
                    <td>${user.student_id}</td>
                    <td>${user.email}</td>
                    <td>
                        <button class="btn-action btn-plus" onclick="approveUser(${user.id})" title="Approve Student">
                            Verify <i class="fas fa-check"></i>
                        </button>
                    </td>
                </tr>
            `;
            tableBody.innerHTML += row;
        });

    } catch (error) {
        console.error("Error loading pending users:", error);
    }
}
});