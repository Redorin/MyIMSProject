// Copied from project js/app.js

const API_URL = 'http://127.0.0.1:8000/api/spaces';

// expose logout/navigate/modifyOccupancy for inline handlers
window.logout = function() { localStorage.removeItem('user'); localStorage.removeItem('token'); sessionStorage.clear(); window.location.href = '/index.html'; };
window.navigate = function(page) { window.location.href = page; };
window.redirect = function(page) { window.location.href = page; };

window.modifyOccupancy = async function(id, currentVal, change) {
    let newVal = parseInt(currentVal) + change;
    if (newVal < 0) newVal = 0;
    try {
        const response = await fetch(`${API_URL}/${id}`, {
            method: 'PUT', headers: {'Content-Type': 'application/json','Accept': 'application/json'},
            body: JSON.stringify({ occupancy: newVal })
        });
        if (response.ok) location.reload(); else alert('Failed to update.');
    } catch(e) { console.error(e); }
};

// main logic (slimmed)
document.addEventListener('DOMContentLoaded', () => {
    async function fetchSpaces() {
        try {
            const res = await fetch(API_URL);
            const spaces = await res.json();
            console.log('Dashboard fetched spaces:', spaces.length, 'items');
            if (document.getElementById('spacesGrid')) renderSpacesGrid(spaces);
            if (document.querySelector('.spaces-list')) renderMapSpacesList(spaces);
        } catch(e) { console.error('Fetch error', e); }
    }

    function renderSpacesGrid(spaces) {
        const grid = document.getElementById('spacesGrid');
        if (!grid) return;
        console.log('Rendering spacesGrid with', spaces.length, 'spaces');
        grid.innerHTML = '';
        spaces.forEach(space => {
            const percentage = space.capacity ? Math.round((space.occupancy / space.capacity) * 100) : 0;
            let status = (space.status || '').toString().toLowerCase();
            if (!status) { if (percentage >= 80) status = 'high'; else if (percentage >= 50) status = 'medium'; else status = 'low'; }
            const image = "url('data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 400 250%22><rect fill=%22%23cbd5e1%22 width=%22400%22 height=%22250%22/></svg>')";
            const card = document.createElement('div'); card.className = 'space-card';
            card.innerHTML = `
                <div class="space-image" style="background-image: ${image}"></div>
                <div class="space-info">
                    <h3>${space.name}</h3>
                    <p class="space-type">${space.type || ''}</p>
                    <div class="space-capacity">
                        <span class="capacity-number">${space.occupancy} / ${space.capacity}</span>
                        <span class="capacity-badge ${status}">${status.charAt(0).toUpperCase() + status.slice(1)}</span>
                    </div>
                    <div class="capacity-bar"><div class="capacity-fill" style="width: ${percentage}%"></div></div>
                </div>
            `;
            grid.appendChild(card);
        });
    }

    function renderMapSpacesList(spaces) {
        const list = document.querySelector('.spaces-list'); if (!list) return; list.innerHTML = '';
        spaces.forEach(space => {
            const percentage = space.capacity ? Math.round((space.occupancy / space.capacity) * 100) : 0;
            let status = (space.status || '').toString().toLowerCase();
            if (!status) { if (percentage >= 80) status = 'high'; else if (percentage >= 50) status = 'medium'; else status = 'low'; }
            const item = document.createElement('div'); item.className = 'space-list-item'; item.setAttribute('data-occupancy', status);
            item.innerHTML = `
                <div class="list-item-header"><h4>${space.name}</h4><span class="capacity-badge ${status}">${status.charAt(0).toUpperCase() + status.slice(1)}</span></div>
                <p class="space-type">${space.type || ''}</p>
                <p class="capacity-info">${space.occupancy} / ${space.capacity} people</p>
            `;
            list.appendChild(item);
        });
    }

    fetchSpaces(); setInterval(fetchSpaces, 5000);

    // Refresh when another window notifies of changes (e.g., admin added a space)
    // Use BroadcastChannel for real-time cross-tab notification
    try {
        const spacesChannel = new BroadcastChannel('campus_spaces');
        spacesChannel.onmessage = (event) => {
            console.log('Dashboard received BroadcastChannel message:', event.data);
            if (event.data && event.data.type === 'spaces_updated') {
                console.log('Spaces updated from admin, refreshing dashboard...');
                try { fetchSpaces(); } catch(err) { console.warn('Failed to refresh on BroadcastChannel', err); }
            }
        };
    } catch(err) {
        console.warn('BroadcastChannel not supported, falling back to storage events', err);
        // Fallback: listen to storage events
        window.addEventListener('storage', (e) => {
            if (!e) return;
            if (e.key === 'spaces_updated_at') {
                try { fetchSpaces(); } catch(err) { console.warn('Failed to refresh on storage event', err); }
            }
        });
    }
});
