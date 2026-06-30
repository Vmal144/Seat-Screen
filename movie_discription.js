document.addEventListener('DOMContentLoaded', function() {
    // Get DOM elements
    const trailerModal = document.getElementById('trailer-modal');
    const trailerBtn = document.querySelector('.trailer-btn');
    const closeModal = document.querySelector('.close-modal');
    const trailerIframe = document.getElementById('trailer-iframe');
    
    // Add event listener to trailer button
    trailerBtn?.addEventListener('click', function() {
        // Get the trailer URL from the iframe's current src (set by PHP)
        const trailerUrl = trailerIframe.src;
        if (trailerUrl) {
            trailerIframe.src = trailerUrl; // Ensure the src is applied
            trailerModal.style.display = 'flex';
        } else {
            console.log('No trailer URL available.');
        }
    });
    
    // Add event listener to close modal
    closeModal?.addEventListener('click', function() {
        trailerIframe.src = ''; // Clear the src to stop the video
        trailerModal.style.display = 'none';
    });
    
    // Close modal when clicking outside
    window.addEventListener('click', function(event) {
        if (event.target === trailerModal) {
            trailerIframe.src = '';
            trailerModal.style.display = 'none';
        }
    });
    
    // Date selector functionality
    const dateBtns = document.querySelectorAll('.date-btn');
    dateBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            dateBtns.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            console.log('Selected date:', this.dataset.date);
        });
    });

    // Load more reviews
    document.querySelector('.load-more-btn')?.addEventListener('click', function() {
        console.log('Load more reviews clicked');
    });
});