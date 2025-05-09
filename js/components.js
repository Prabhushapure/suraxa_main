document.addEventListener('DOMContentLoaded', function() {
    // Load sidebar
    const sidebarPlaceholder = document.getElementById('sidebar-placeholder');
    if (sidebarPlaceholder) {
        fetch('components/sidebar.html')
            .then(response => response.text())
            .then(data => {
                sidebarPlaceholder.innerHTML = data;
                
                // Highlight current page in the sidebar
                const currentPage = window.location.pathname.split('/').pop();
                const sidebarLinks = document.querySelectorAll('#sidebar-wrapper .list-group-item');
                sidebarLinks.forEach(link => {
                    if (link.getAttribute('href') === currentPage) {
                        link.classList.add('active');
                    }
                });
            });
    }
    
    // Load header
    const headerPlaceholder = document.getElementById('header-placeholder');
    if (headerPlaceholder) {
        fetch('components/header.html')
            .then(response => response.text())
            .then(data => {
                headerPlaceholder.innerHTML = data;
                
                // Set page title if available
                const pageTitle = document.getElementById('page-title');
                const titleAttribute = headerPlaceholder.getAttribute('data-title');
                if (pageTitle && titleAttribute) {
                    pageTitle.textContent = titleAttribute;
                }
            });
    }
    
    // Handle logout button click
    document.addEventListener('click', function(e) {
        if (e.target.closest('.logout-btn')) {
            // Handle logout logic here
            alert('Logging out...');
            // Redirect to login page or perform actual logout
        }
    });
});