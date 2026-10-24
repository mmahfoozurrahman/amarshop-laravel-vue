<script setup>
import { onMounted, onUnmounted, ref, watch } from 'vue';

const props = defineProps({
    slides: { type: Array, default: () => [] },
    interval: { type: Number, default: 5000 },
});

const index = ref(0);
let timer = null;
let paused = false;

function go(next) {
    if (!props.slides.length) return;

    index.value = (next + props.slides.length) % props.slides.length;

    // Manual navigation restarts the countdown so the visitor always gets a full interval.
    if (!paused) {
        start();
    }
}

function start() {
    stop();

    if (paused || props.slides.length < 2) return;

    timer = window.setInterval(() => go(index.value + 1), props.interval);
}

function stop() {
    if (timer) {
        window.clearInterval(timer);
        timer = null;
    }
}

function pause() {
    paused = true;
    stop();
}

function resume() {
    paused = false;
    start();
}

// Slides arrive asynchronously (API call), so start the rotation when they land.
watch(() => props.slides.length, (length) => {
    index.value = 0;

    if (length > 1) {
        start();
    } else {
        stop();
    }
});

onMounted(start);
onUnmounted(stop);
</script>

<template>
    <section v-if="slides.length" class="hero-slider" @mouseenter="pause" @mouseleave="resume">
        <div
            v-for="(slide, slideIndex) in slides"
            :key="slide.id"
            class="hero-slide"
            :class="{ 'is-active': slideIndex === index }"
        >
            <img :src="slide.image" :alt="slide.title" class="hero-slide__image">

            <div class="hero-slide__content">
                <span class="hero-eyebrow">AmarShop edit</span>
                <h2>{{ slide.title }}</h2>
                <p>{{ slide.subtitle }}</p>
                <router-link class="btn btn-light fw-semibold px-4" :to="slide.link_url || '/products'">
                    {{ slide.button_text || 'Shop now' }}
                    <i class="bi bi-arrow-right ms-2"></i>
                </router-link>
            </div>
        </div>

        <button v-if="slides.length > 1" class="hero-nav hero-nav--prev" type="button" aria-label="Previous slide" @click="go(index - 1)">
            <i class="bi bi-chevron-left"></i>
        </button>
        <button v-if="slides.length > 1" class="hero-nav hero-nav--next" type="button" aria-label="Next slide" @click="go(index + 1)">
            <i class="bi bi-chevron-right"></i>
        </button>

        <div v-if="slides.length > 1" class="hero-dots">
            <button
                v-for="(slide, slideIndex) in slides"
                :key="`dot-${slide.id}`"
                type="button"
                :class="{ 'is-active': slideIndex === index }"
                :aria-label="`Go to slide ${slideIndex + 1}`"
                @click="go(slideIndex)"
            ></button>
        </div>
    </section>
</template>
