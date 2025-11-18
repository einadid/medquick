    </div> <!-- Close container -->
</div> <!-- Close page-wrapper -->

<!-- Green Theme Footer -->
<footer class="vintage-footer">
    <div class="footer-content">
        <div style="margin-bottom: 1.5rem;">
            <div style="display: flex; align-items: center; justify-content: center; gap: 0.75rem; margin-bottom: 1rem;">
                <span style="font-size: 2.5rem;">💊</span>
                <h3 style="font-size: 1.5rem; font-weight: 800; color: white; margin: 0;">QuickMed Pharmacy</h3>
            </div>
            <p class="footer-text" style="font-size: 1rem; margin-bottom: 0.5rem;">
                &copy; <?php echo date('Y'); ?> QuickMed. All rights reserved.
            </p>
        </div>
        
        <div style="margin-bottom: 1.5rem;">
            <p class="footer-text" style="color: var(--light-green); font-weight: 500;">
                🏥 Your trusted multi-shop healthcare partner since 2024
            </p>
        </div>
        
        <div style="display: flex; justify-content: center; gap: 2rem; flex-wrap: wrap; font-size: 0.875rem; margin-bottom: 1.5rem;">
            <a href="<?php echo SITE_URL; ?>/about.php" style="color: white; opacity: 0.9; transition: var(--transition);" onmouseover="this.style.opacity='1'; this.style.color='var(--accent-gold)'" onmouseout="this.style.opacity='0.9'; this.style.color='white'">
                <i class="fas fa-info-circle"></i> About Us
            </a>
            <a href="<?php echo SITE_URL; ?>/contact.php" style="color: white; opacity: 0.9; transition: var(--transition);" onmouseover="this.style.opacity='1'; this.style.color='var(--accent-gold)'" onmouseout="this.style.opacity='0.9'; this.style.color='white'">
                <i class="fas fa-envelope"></i> Contact
            </a>
            <a href="#" style="color: white; opacity: 0.9; transition: var(--transition);" onmouseover="this.style.opacity='1'; this.style.color='var(--accent-gold)'" onmouseout="this.style.opacity='0.9'; this.style.color='white'">
                <i class="fas fa-shield-alt"></i> Privacy Policy
            </a>
            <a href="#" style="color: white; opacity: 0.9; transition: var(--transition);" onmouseover="this.style.opacity='1'; this.style.color='var(--accent-gold)'" onmouseout="this.style.opacity='0.9'; this.style.color='white'">
                <i class="fas fa-file-contract"></i> Terms of Service
            </a>
        </div>
        
        <div style="padding-top: 1.5rem; border-top: 1px solid rgba(149, 213, 178, 0.3);">
            <p class="footer-text" style="font-size: 0.875rem; opacity: 0.9;">
                <i class="fas fa-phone"></i> Hotline: +880 1234-567890 | 
                <i class="fas fa-envelope"></i> Email: support@quickmed.com
            </p>
        </div>
    </div>
</footer>

<!-- JavaScript -->
<script>
// Auto-hide flash messages after 5 seconds
setTimeout(function() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        alert.style.transition = 'opacity 0.5s ease';
        alert.style.opacity = '0';
        setTimeout(() => alert.remove(), 500);
    });
}, 5000);

// Smooth scroll for anchor links
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            target.scrollIntoView({ 
                behavior: 'smooth', 
                block: 'start' 
            });
        }
    });
});

// Mobile navigation active state
document.querySelectorAll('.mobile-nav-item').forEach(item => {
    item.addEventListener('click', function() {
        document.querySelectorAll('.mobile-nav-item').forEach(i => {
            i.classList.remove('active');
        });
        this.classList.add('active');
    });
});

// Image lazy loading fallback
document.addEventListener('DOMContentLoaded', function() {
    const images = document.querySelectorAll('img[loading="lazy"]');
    images.forEach(img => {
        if ('loading' in HTMLImageElement.prototype) {
            img.src = img.src;
        }
    });
});

// Add loading animation to buttons on form submit
document.querySelectorAll('form').forEach(form => {
    form.addEventListener('submit', function(e) {
        const submitBtn = form.querySelector('button[type="submit"]');
        if (submitBtn && !submitBtn.disabled) {
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
            submitBtn.disabled = true;
            
            // Re-enable after 10 seconds as failsafe
            setTimeout(() => {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }, 10000);
        }
    });
});
</script>

</body>
</html>