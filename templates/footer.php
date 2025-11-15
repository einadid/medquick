<?php
// FILE: templates/footer.php (Final Layout Controller Version)
// PURPOSE: Closes the main content area, displays the correct footer or bottom navigation based on user role,
// and includes necessary global JavaScript files.

        // Determine the role to load the correct closing elements and footers.
        $isSalesman = (is_logged_in() && has_role(ROLE_SALESMAN));
        $isShopAdmin = (is_logged_in() && has_role(ROLE_SHOP_ADMIN));
?>
        </main> <!-- .flex-grow (main content area) ends here -->

        <?php
        // --- Role-based Footer/Navigation Logic ---

        if ($isSalesman) {
            // For Salesman, include their specific mobile bottom navigation.
            include __DIR__ . '/_salesman_bottom_nav.php';
            // Close the divs opened in header.php for the salesman layout.
            echo '</div></div>'; 
        } elseif ($isShopAdmin) {
            // For Shop Admin, include their specific mobile bottom navigation.
            include __DIR__ . '/_shop_admin_bottom_nav.php';
            // Close the divs opened in header.php for the shop admin layout.
            echo '</div></div>';
        } else {
            // For all other roles (Customer, Admin, Public), show the default site footer.
        ?>
            <footer class="bg-slate-800 text-slate-400 mt-auto">
                <div class="container mx-auto px-4 sm:px-6 py-12">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                        <!-- About Section -->
                        <div>
                            <h3 class="text-lg font-semibold text-white mb-4">QuickMed</h3>
                            <p class="text-sm">Your trusted partner in health. Delivering medicines fast and managing pharmacy inventory with ease.</p>
                            <div class="flex space-x-4 mt-4">
                                <a href="#" class="text-slate-400 hover:text-white" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                                <a href="#" class="text-slate-400 hover:text-white" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
                                <a href="#" class="text-slate-400 hover:text-white" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                            </div>
                        </div>
                        <!-- Quick Links -->
                        <div>
                            <h3 class="text-base font-semibold text-white mb-4">Quick Links</h3>
                            <ul class="space-y-2 text-sm">
                                <li><a href="catalog.php" class="hover:text-white">Medicines</a></li>
                                <li><a href="#" class="hover:text-white">About Us</a></li>
                                <li><a href="#" class="hover:text-white">Contact</a></li>
                                <li><a href="#" class="hover:text-white">Terms of Service</a></li>
                            </ul>
                        </div>
                        <!-- User Account Links -->
                        <div>
                            <h3 class="text-base font-semibold text-white mb-4">My Account</h3>
                            <ul class="space-y-2 text-sm">
                                <li><a href="login.php" class="hover:text-white">Login</a></li>
                                <li><a href="signup.php" class="hover:text-white">Create Account</a></li>
                                <li><a href="cart.php" class="hover:text-white">My Cart</a></li>
                                <li><a href="orders.php" class="hover:text-white">Order History</a></li>
                            </ul>
                        </div>
                        <!-- Newsletter Subscription -->
                        <div>
                            <h3 class="text-base font-semibold text-white mb-4">Subscribe</h3>
                            <p class="text-sm mb-3">Get updates on new stock and special offers.</p>
                            <form action="#" method="POST">
                                <div class="flex"><input type="email" placeholder="Your email" class="w-full px-3 py-2 text-slate-800 rounded-l-md focus:outline-none focus:ring-2 focus:ring-teal-500"><button type="submit" class="bg-teal-600 hover:bg-teal-700 text-white px-4 rounded-r-md">Go</button></div>
                            </form>
                        </div>
                    </div>
                    <div class="mt-12 border-t border-slate-700 pt-6 text-center text-sm">
                        <p>&copy; <?= date('Y') ?> QuickMed. All Rights Reserved.</p>
                    </div>
                </div>
            </footer>
        <?php 
            // Close the div for the default layout
            echo '</div>'; 
        }
        ?>
    </div> <!-- #app div ends -->

    <!-- ======================================================= -->
    <!-- ============== GLOBAL JAVASCRIPT FILES ================ -->
    <!-- ======================================================= -->

    <!-- Main application JavaScript file -->
    <script src="assets/js/main.js"></script>
    
    <!-- Alpine.js for dropdowns and other small UI interactions -->
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>

</body>
</html>