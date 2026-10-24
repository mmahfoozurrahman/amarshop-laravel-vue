<script setup>
const year = new Date().getFullYear();
</script>

<template>
    <footer class="shop-footer">
        <div class="container">
            <div class="row g-4">
                <div class="col-lg-4">
                    <div class="shop-brand mb-3">
                        <span class="shop-brand__icon"><i class="bi bi-bag-heart-fill"></i></span>
                        <span>AmarShop</span>
                    </div>
                    <p class="se-muted mb-3">
                        A simple, carefully curated store for everyday essentials — built as a Laravel,
                        MySQL, Bootstrap and Vue demonstration project.
                    </p>
                    <div class="d-flex gap-2">
                        <span class="social-chip"><i class="bi bi-facebook"></i></span>
                        <span class="social-chip"><i class="bi bi-instagram"></i></span>
                        <span class="social-chip"><i class="bi bi-youtube"></i></span>
                    </div>
                </div>

                <div class="col-6 col-lg-2">
                    <h6 class="footer-title">Shop</h6>
                    <ul class="footer-links">
                        <li><router-link :to="{ name: 'products' }">All products</router-link></li>
                        <li><router-link :to="{ name: 'brands' }">Brands</router-link></li>
                        <li><router-link :to="{ name: 'categories' }">Categories</router-link></li>
                        <li><router-link :to="{ name: 'products', query: { sort: 'price_asc' } }">Best value</router-link></li>
                    </ul>
                </div>

                <div class="col-6 col-lg-2">
                    <h6 class="footer-title">Account</h6>
                    <ul class="footer-links">
                        <li><router-link :to="{ name: 'login' }">OTP login</router-link></li>
                        <li><router-link :to="{ name: 'profile' }">Profile</router-link></li>
                        <li><router-link :to="{ name: 'orders' }">Order history</router-link></li>
                        <li><router-link :to="{ name: 'wishlist' }">Wishlist</router-link></li>
                    </ul>
                </div>

                <div class="col-lg-4">
                    <h6 class="footer-title">Need help?</h6>
                    <ul class="footer-links">
                        <li><i class="bi bi-telephone me-2"></i>+880 1700-000000</li>
                        <li><i class="bi bi-envelope me-2"></i>support@amarshop.test</li>
                        <li><i class="bi bi-geo-alt me-2"></i>Dhanmondi, Dhaka 1209</li>
                    </ul>
                    <div class="payment-note mt-3">
                        <i class="bi bi-credit-card-2-front me-2"></i>
                        Payments handled by SSLCommerz (demo mode)
                    </div>
                </div>
            </div>

            <div class="footer-bottom">
                <span>© {{ year }} AmarShop. Built for Module 22.</span>
                <span class="se-muted">Laravel · MySQL · Bootstrap 5 · Vue 3</span>
            </div>
        </div>
    </footer>
</template>
