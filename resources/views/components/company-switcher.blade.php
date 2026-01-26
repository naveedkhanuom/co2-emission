@php
    $currentCompany = app()->bound('current_company') ? app('current_company') : null;
    $currentCompanyId = session('current_company_id') ?? (auth()->check() ? auth()->user()->company_id : null);
@endphp

@auth
<div class="company-switcher-container">
    <div class="company-switcher" id="companySwitcher">
        <div class="company-switcher-header" id="companySwitcherToggle">
            <i class="fas fa-building me-2"></i>
            <span class="company-name">
                @if($currentCompany)
                    {{ $currentCompany->name }}
                @elseif(auth()->check() && auth()->user()->company)
                    {{ auth()->user()->company->name }}
                @else
                    <span style="color: #5f6368; font-style: italic;">Select Company</span>
                @endif
            </span>
            <i class="fas fa-chevron-down ms-auto"></i>
        </div>
        <div class="company-switcher-dropdown" id="companySwitcherDropdown">
            <div class="company-switcher-list" id="companySwitcherList">
                <div class="text-center p-3">
                    <div class="spinner-border spinner-border-sm text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <div class="mt-2 small text-muted">Loading companies...</div>
                </div>
            </div>
        </div>
    </div>
</div>
@endauth

<style>
.company-switcher-container {
    padding: 15px;
    background-color: #f8f9fa;
    margin: 10px -15px 20px -15px;
    border-bottom: 1px solid #e8eaed;
    border-top: 1px solid #e8eaed;
}

/* Ensure component is always visible */
.company-switcher-container {
    min-height: 60px;
    display: block;
}

.company-switcher {
    position: relative;
}

.company-switcher-header {
    display: flex;
    align-items: center;
    padding: 12px 15px;
    background-color: white;
    border: 1px solid #e8eaed;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s;
    color: #3c4043;
    font-size: 0.9rem;
    font-weight: 500;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
    min-height: 44px;
}

.company-switcher-header:hover {
    background-color: #f1f3f4;
    border-color: #2e7d32;
    box-shadow: 0 2px 6px rgba(46, 125, 50, 0.1);
}

.company-switcher-header i {
    color: #2e7d32;
}

.company-switcher-header i.fa-chevron-down {
    transition: transform 0.3s;
    font-size: 0.8rem;
}

.company-switcher.active .company-switcher-header i.fa-chevron-down {
    transform: rotate(180deg);
}

.company-name {
    flex: 1;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.company-switcher-dropdown {
    position: absolute;
    top: calc(100% + 5px);
    left: 0;
    right: 0;
    background-color: white;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    max-height: 300px;
    overflow-y: auto;
    z-index: 1000;
    display: none;
    margin-top: 5px;
}

.company-switcher.active .company-switcher-dropdown {
    display: block;
}

.company-switcher-list {
    padding: 5px;
}

.company-item {
    padding: 12px 15px;
    cursor: pointer;
    border-radius: 6px;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    gap: 10px;
    color: #3c4043;
}

.company-item:hover {
    background-color: #f1f3f4;
}

.company-item.active {
    background-color: #2e7d32;
    color: white;
}

.company-item.active:hover {
    background-color: #1b5e20;
}

.company-item-icon {
    width: 32px;
    height: 32px;
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: rgba(46, 125, 50, 0.1);
    color: #2e7d32;
    font-weight: 600;
    font-size: 0.85rem;
    flex-shrink: 0;
}

.company-item.active .company-item-icon {
    background-color: rgba(255, 255, 255, 0.2);
    color: white;
}

.company-item-info {
    flex: 1;
    min-width: 0;
}

.company-item-name {
    font-weight: 500;
    font-size: 0.9rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    color: inherit;
}

.company-item-type {
    font-size: 0.75rem;
    color: #5f6368;
    margin-top: 2px;
}

.company-item.active .company-item-type {
    color: rgba(255, 255, 255, 0.8);
}

.company-item-check {
    color: var(--primary-green);
    display: none;
}

.company-item.active .company-item-check {
    display: block;
    color: white;
}

.no-companies {
    padding: 20px;
    text-align: center;
    color: #5f6368;
    font-size: 0.9rem;
}

.company-switcher-error {
    padding: 15px;
    text-align: center;
    color: #d32f2f;
    font-size: 0.85rem;
}

/* Ensure text is visible */
.company-switcher-header .company-name {
    color: #3c4043;
}

.company-switcher-header i.fa-building {
    color: #2e7d32;
}

.company-switcher-header i.fa-chevron-down {
    color: #5f6368;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const switcher = document.getElementById('companySwitcher');
    const toggle = document.getElementById('companySwitcherToggle');
    const dropdown = document.getElementById('companySwitcherDropdown');
    const list = document.getElementById('companySwitcherList');
    
    let companies = [];
    let currentCompanyId = {{ $currentCompanyId ?? 'null' }};

    // Toggle dropdown
    if (toggle) {
        toggle.addEventListener('click', function(e) {
            e.stopPropagation();
            switcher.classList.toggle('active');
            
            if (switcher.classList.contains('active') && companies.length === 0) {
                loadCompanies();
            }
        });
    }

    // Close dropdown when clicking outside
    document.addEventListener('click', function(e) {
        if (!switcher.contains(e.target)) {
            switcher.classList.remove('active');
        }
    });

    // Load accessible companies
    function loadCompanies() {
        fetch('{{ route("company.accessible") }}', {
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                companies = data.companies;
                currentCompanyId = data.current_company_id || currentCompanyId;
                renderCompanies();
            } else {
                showError('Failed to load companies');
            }
        })
        .catch(error => {
            console.error('Error loading companies:', error);
            showError('Error loading companies. Please refresh the page.');
        });
    }

    // Render companies list
    function renderCompanies() {
        if (companies.length === 0) {
            list.innerHTML = '<div class="no-companies"><i class="fas fa-info-circle me-2"></i>No companies available. Please contact your administrator.</div>';
            return;
        }

        list.innerHTML = companies.map(company => {
            const isActive = company.id == currentCompanyId;
            const initials = company.name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase();
            const industryType = company.industry_type ? company.industry_type.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase()) : 'Other';
            
            return `
                <div class="company-item ${isActive ? 'active' : ''}" data-company-id="${company.id}">
                    <div class="company-item-icon">${initials}</div>
                    <div class="company-item-info">
                        <div class="company-item-name">${company.name}</div>
                        <div class="company-item-type">${industryType}</div>
                    </div>
                    ${isActive ? '<i class="fas fa-check company-item-check"></i>' : ''}
                </div>
            `;
        }).join('');

        // Add click handlers
        list.querySelectorAll('.company-item').forEach(item => {
            item.addEventListener('click', function() {
                const companyId = parseInt(this.dataset.companyId);
                if (companyId !== currentCompanyId) {
                    switchCompany(companyId);
                }
            });
        });
    }

    // Switch company
    function switchCompany(companyId) {
        // Show loading state
        list.innerHTML = '<div class="text-center p-3"><div class="spinner-border spinner-border-sm text-primary" role="status"></div><div class="mt-2 small text-muted">Switching company...</div></div>';

        fetch('{{ route("company.switch") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            },
            body: JSON.stringify({ company_id: companyId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                currentCompanyId = companyId;
                // Update company name in header
                const companyName = document.querySelector('.company-name');
                if (companyName && data.company) {
                    companyName.textContent = data.company.name;
                }
                // Reload page to apply new company context
                window.location.reload();
            } else {
                showError(data.message || 'Failed to switch company');
                loadCompanies(); // Reload companies list
            }
        })
        .catch(error => {
            console.error('Error switching company:', error);
            showError('Error switching company. Please try again.');
            loadCompanies(); // Reload companies list
        });
    }

    // Show error message
    function showError(message) {
        list.innerHTML = `<div class="company-switcher-error"><i class="fas fa-exclamation-circle me-2"></i>${message}</div>`;
        setTimeout(() => {
            if (companies.length > 0) {
                renderCompanies();
            } else {
                loadCompanies();
            }
        }, 3000);
    }
});
</script>

