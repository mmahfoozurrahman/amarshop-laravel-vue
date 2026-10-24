<script setup>
import Footer from '@/shared/components/Footer.vue';
import Navbar from '@/shared/components/Navbar.vue';
</script>

<template>
    <div class="d-flex flex-column min-vh-100">
        <Navbar />

        <main class="flex-grow-1">
            <router-view />
        </main>

        <Footer />
    </div>
</template>
