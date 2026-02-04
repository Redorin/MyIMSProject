// --- 1. GLOBAL CONFIGURATION ---
const API_URL = 'http://127.0.0.1:8000/api/spaces';

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

    // --- FETCH DATA (The Traffic Cop) ---
    async function fetchSpaces() {
        try {
            const response = await fetch(API_URL);
            const spaces = await response.json();

            // LOGIC: Check which page we are on
            if (document.getElementById('adminTableBody')) {
                loadAdminDashboard(spaces); // We are on Admin Page
            } 
            else if (document.querySelector('.occupancy-list')) {
                updateDashboard(spaces);    // We are on Student Page
            }
            
        } catch (error) {
            console.error("Error fetching data:", error);
        }
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
});