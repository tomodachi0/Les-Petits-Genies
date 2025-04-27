    </main>
    <footer>
        <div class="footer-content">
            <p>&copy; <?php echo date('Y'); ?> Kids Learning Zone. All rights reserved.</p>

            <div class="social-icons">
                <a href="#"><i class="fab fa-facebook"></i></a>
                <a href="#"><i class="fab fa-twitter"></i></a>
                <a href="#"><i class="fab fa-youtube"></i></a>
                <a href="#"><i class="fab fa-instagram"></i></a>
            </div>
        </div>
    </footer>
    <script>
        
        document.querySelector('.hamburger').addEventListener('click', function() {
            document.querySelector('.main-nav').classList.toggle('show');
        });
        
        
        document.addEventListener('DOMContentLoaded', function() {
            const animatedElements = document.querySelectorAll('.animate');
            
            if ('IntersectionObserver' in window) {
                const observer = new IntersectionObserver(entries => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            entry.target.classList.add('animated');
                            observer.unobserve(entry.target);
                        }
                    });
                });
                
                animatedElements.forEach(el => observer.observe(el));
            } else {
                
                animatedElements.forEach(el => el.classList.add('animated'));
            }
        });
    </script>
</body>
</html> 