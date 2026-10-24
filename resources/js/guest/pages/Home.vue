<script setup>
import { onMounted, ref } from 'vue';
import BrandCard from '@/shared/components/BrandCard.vue';
import CategoryCard from '@/shared/components/CategoryCard.vue';
import HeroSlider from '@/shared/components/HeroSlider.vue';
import ProductGrid from '@/shared/components/ProductGrid.vue';
import SectionHeading from '@/shared/components/SectionHeading.vue';
import { fetchHome } from '@/shared/services/catalog';
import { errorMessage } from '@/shared/utils/format';

const loading = ref(true);
const error = ref('');
const data = ref({
    sliders: [],
    brands: [],
    categories: [],
    latest_products: [],
    featured_products: [],
});

onMounted(async () => {
    try {
        data.value = await fetchHome();
    } catch (exception) {
        error.value = errorMessage(exception, 'Could not load the storefront right now.');
    } finally {
        loading.value = false;
    }
});

const perks = [
    { icon: 'bi-truck', title: 'Fast delivery', text: 'Inside Dhaka in 1–2 days' },
    { icon: 'bi-arrow-repeat', title: 'Easy returns', text: '7-day return window' },
    { icon: 'bi-shield-check', title: 'Secure payment', text: 'SSLCommerz demo gateway' },
    { icon: 'bi-headset', title: 'Real support', text: 'Every day, 9am – 9pm' },
];
</script>

<template>
    <div class="container py-4">
        <div v-if="error" class="alert alert-danger">{{ error }}</div>

        <div v-if="loading" class="skeleton" style="aspect-ratio: 16 / 6; border-radius: 14px;"></div>
        <HeroSlider v-else :slides="data.sliders" />

        <div class="row g-3 mt-1">
            <div v-for="perk in perks" :key="perk.title" class="col-6 col-lg-3">
                <div class="se-card se-card-pad h-100 d-flex align-items-center gap-3">
                    <i class="bi fs-4 text-brand" :class="perk.icon"></i>
                    <div>
                        <strong class="d-block small">{{ perk.title }}</strong>
                        <small class="se-muted">{{ perk.text }}</small>
                    </div>
                </div>
            </div>
        </div>

        <section class="se-section">
            <SectionHeading
                title="Shop by category"
                subtitle="Six curated departments, one simple checkout."
                :link-to="{ name: 'categories' }"
                link-text="All categories"
            />

            <div v-if="loading" class="row g-3">
                <div v-for="n in 6" :key="`cat-skeleton-${n}`" class="col-6 col-md-4 col-lg-2">
                    <div class="skeleton" style="aspect-ratio: 4 / 3; border-radius: 14px;"></div>
                </div>
            </div>
            <div v-else class="row g-3">
                <div v-for="category in data.categories.slice(0, 6)" :key="category.id" class="col-6 col-md-4 col-lg-2">
                    <CategoryCard :category="category" />
                </div>
            </div>
        </section>

        <section class="se-section pt-0">
            <SectionHeading
                title="Trusted brands"
                subtitle="Products from brands we actually stock and support."
                :link-to="{ name: 'brands' }"
                link-text="All brands"
            />

            <div v-if="loading" class="row g-3">
                <div v-for="n in 8" :key="`brand-skeleton-${n}`" class="col-6 col-md-4 col-lg-3">
                    <div class="skeleton" style="height: 62px; border-radius: 14px;"></div>
                </div>
            </div>
            <div v-else class="row g-3">
                <div v-for="brand in data.brands.slice(0, 8)" :key="brand.id" class="col-6 col-md-4 col-lg-3">
                    <BrandCard :brand="brand" />
                </div>
            </div>
        </section>

        <section class="se-section pt-0">
            <SectionHeading
                title="Latest arrivals"
                subtitle="Fresh stock added to the store this week."
                :link-to="{ name: 'products', query: { sort: 'newest' } }"
                link-text="Shop new"
            />
            <ProductGrid
                :products="data.latest_products"
                :loading="loading"
                :skeleton-count="8"
                empty-title="No products yet"
                empty-message="Seed the database to see the demo catalogue."
            />
        </section>

        <section class="se-section pt-0">
            <div class="se-card se-card-pad mb-4 d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div>
                    <span class="pill pill-warning mb-2 d-inline-block">Handpicked</span>
                    <h2 class="se-section-title mb-1">Featured this month</h2>
                    <p class="se-muted mb-0">The products our customers keep coming back for.</p>
                </div>
                <router-link :to="{ name: 'products', query: { featured: 1 } }" class="btn btn-brand">
                    See all featured <i class="bi bi-arrow-right ms-1"></i>
                </router-link>
            </div>

            <ProductGrid :products="data.featured_products" :loading="loading" :skeleton-count="8" />
        </section>
    </div>
</template>

