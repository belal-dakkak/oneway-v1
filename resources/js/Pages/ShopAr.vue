<!-- This example requires Tailwind CSS v2.0+ -->
<template>

    <div class="dark h-screen relative">
        <!-- Navbar -->

        <div class="self-end fixed bottom-12 right-12 z-40">
            <a href="#navbar">
                <ArrowDown class="rotate-180 text-gray-500 border p-3 rounded-full border-gray-200"/>
            </a>
        </div>

        <section id="navbar" class="snap-start bg-black">
            <nav class="relative container mx-auto text-white">
                <!-- Flex container -->
                <div class="flex items-center justify-between">
                    <!-- Logo -->
                    <inertia-link :href="route('homepage.arabic')" class="">
                        <img :src="'/custom/logo-icon.png'" class="w-48" alt="" />
                    </inertia-link>
                    <!-- Menu Items -->
                    <div class="hidden space-x-6 md:flex">
                        <a :href="route('homepage.arabic')" class="hover:text-gray-500 hover:underline underline-offset-8">الرئيسة</a>
                        <a :href="route('homepage.arabic')+'#about'" class="hover:text-gray-500 hover:underline underline-offset-8">عن الشركة</a>
                        <a :href="route('homepage.arabic')+'#foundation'" class="hover:text-gray-500 hover:underline underline-offset-8">تأسيس الشركة</a>
                        <a :href="route('homepage.arabic')+'#mission'" class="hover:text-gray-500 hover:underline underline-offset-8">مهمتنا</a>
                        <a :href="route('homepage.arabic')+'#vision'" class="hover:text-gray-500 hover:underline underline-offset-8">رؤيتنا</a>
                        <button
                          @click="store.toggleCurrency"
                          class="text-xs font-bold border border-gray-600 rounded px-2 py-1 hover:border-white transition-colors"
                        >
                          {{ store.currency }}
                        </button>
                        <inertia-link :href="route('shop')" class="hover:text-gray-500 hover:underline underline-offset-8">English</inertia-link>
                    </div>
                    <!-- Button -->
                    <!-- Button -->
                    <a href="#products" class="hidden p-3 px-6 pt-2 text-white bg-custom rounded-full baseline hover:bg-gray-300 md:block">
                        المزيد من المنتجات
                    </a>

                    <!-- Hamburger Icon -->
                    <button
                        id="menu-btn"
                        class="block hamburger md:hidden focus:outline-none"
                        v-bind:class="navToggle?'open':''"
                        @click="toggleNavbar"
                    >
                        <span class="hamburger-top"></span>
                        <span class="hamburger-middle"></span>
                        <span class="hamburger-bottom"></span>
                    </button>
                </div>

                <div class="md:hidden flex justify-around mt-4" style="position: fixed;bottom: 0;z-index: 999;margin-left: 8%;">
                    <!-- <a href="https://www.tiktok.com/@oneway503" target="_blank"><img src="/assets/header/tiktok.png" style="padding: 18px;" alt="" srcset=""></a>
                    <a href="https://www.facebook.com/theoneway.fashion" target="_blank"><img src="/assets/header/facebook.png" style="padding: 18px;" alt="" srcset=""></a>
                    <a :href="'https://wa.me/'+wnumber" target="_blank"><img src="/assets/header/whatsapp.png" style="padding: 18px;" alt="" srcset=""></a>
                    <a href="https://www.instagram.com/theoneway.fashion" target="_blank"><img src="/assets/header/instagram.png" style="padding: 18px;" alt="" srcset=""></a> -->
                    <a href="https://www.tiktok.com/@oneway503" target="_blank"><img src="/assets/header/tiktok.png" style="padding: 18px;" alt="" srcset=""></a>
                    <a href="https://www.facebook.com/theoneway.fashion" target="_blank"><img src="/assets/header/facebook.png" style="padding: 18px;" alt="" srcset=""></a>
                    <a href="https://wa.me/+971545516995" target="_blank"><img src="/assets/header/whatsapp.png" style="padding: 18px;" alt="" srcset=""></a>
                    <a href="https://www.instagram.com/theoneway.fashion" target="_blank"><img src="/assets/header/instagram.png" style="padding: 18px;" alt="" srcset=""></a>
                </div>

                <!-- Mobile Menu -->
                <div class="md:hidden">
                    <div
                        id="menu"
                        class="absolute flex-col items-center z-20 bg-black self-end py-8 mt-10 space-y-6 font-bold sm:w-auto sm:self-center left-6 right-6 drop-shadow-md"
                        v-bind:class="navToggle?'flex open':'hidden'"
                    >
                        <a :href="route('homepage.arabic')">الرئيسة</a>
                        <a :href="route('homepage.arabic')+'#about'">عن الشركة</a>
                        <a :href="route('homepage.arabic')+'#foundation'">تأسيس الشركة</a>
                        <a :href="route('homepage.arabic')+'#mission'">مهمتنا</a>
                        <a :href="route('homepage.arabic')+'#vision'">رؤيتنا</a>
                        <button
                          @click="store.toggleCurrency"
                          class="text-white font-bold border border-gray-600 rounded px-4 py-2"
                        >
                          {{ store.currency }}
                        </button>
                        <inertia-link :href="route('homepage.arabic')">English</inertia-link>
                    </div>
                </div>
            </nav>
        </section>

        <section id="header" class="snap-start h-1/2 relative text-white flex justify-center items-center">
            <div class="z-10 absolute flex justify-center w-auto min-w-full min-h-full max-w-none bg-gradient-to-r from-black opacity-70"></div>
        </section>
        <h2 class="text-3xl bg-black text-center py-2 font-extrabold z-20 text-white" v-if="category">{{category.name}}</h2>

        <div class="absolute hidden md:block top-1/4 space-y-6 left-2 flex flex-col justify-around" style="position: fixed; z-index: 1000; color: white">
            <a :href="tiktok" target="_blank"><img src="/assets/header/tiktok.png" class="p-2" alt="" srcset=""></a>
            <a :href="facebook" target="_blank"><img src="/assets/header/facebook.png" class="p-2" alt="" srcset=""></a>
            <a :href="instagram" target="_blank"><img src="/assets/header/instagram.png" class="p-2" alt="" srcset=""></a>
            <div class="contact-icon flex-col items-center justify-center font-bold">
                <span>Contact Us</span>
                <a :href="whatsapp" target="_blank" class="flex place-content-center">
                    <img src="/assets/header/whatsapp.png" class="p-2" alt="" srcset="">
                </a>
            </div>
        </div>

        <section id="categories" class="hidden md:flex snap-start relative text-white flex justify-center">
            <div class="w-3/4 mx-auto">
                <carousel class="border border-gray-800" ref="myCarousel" :pauseAutoplayOnHover="true" :autoplay="2000" :items-to-show="8.5" :mouseDrag="true" :touchDrag="true" :itemsToScroll="1" :transition="300" :wrapAround="true">
                    <template #slides>
                        <slide class="" v-for="(category, index) in categories" :key="category.id">
                            <inertia-link :href="route('shop', {category: category.id})" class="carousel__item py-20 flex flex-col justify-between items-center">
                                <img :src="category.image_url"  class="w-24 h-24 rounded-full" alt="">
                                <h2 class="text-xl mt-2 animate__animated animate__fast animate__fadeInRight">{{ category.name }}</h2>
                            </inertia-link>
                        </slide>
                    </template>
                </carousel>
            </div>
        </section>
        <section id="categories-mobile" class="grid grid-cols-3 md:hidden">
            <div class="border border-gray-400 rounded-2xl flex justify-center align-middle" v-for="(category, index) in categories" :key="category.id">
                <inertia-link :href="route('shop', {category: category.id})" class="py-2 flex flex-col justify-between items-center">
                    <h2 class="text-xl text-center text-white mt-2 animate__animated animate__fast animate__fadeInRight">{{ category.name }}</h2>
                </inertia-link>
            </div>
        </section>

        <section id="products" class="snap-start relative text-white flex justify-center">
            <div class="absolute top-12">
                <input type="search" v-model="params.search" class="w-full bg-gray-200 bg-opacity-0 focus:outline-none focus:ring-0 outline-0 drop-shadow-md border border-t-0 border-l-0 border-r-0 border-b-2" placeholder="search...">
            </div>
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 my-32 p-5">
                <div
                    v-for="product in productColors.data"
                    class="
                        mx-2 my-3 max-w-sm shadow-md dark:bg-gray-800 dark:border-gray-700
                        rounded-md overflow-hidden grid grid-rows-2 max-h-[500px]"
                >
                    <a @click="sendToShop(product)" class="h-fit w-full">
                        <img
                            :src="product.image_url"
                            alt="product image"
                            class="cursor-pointer object-cover w-full h-[345px]"
                        >
                    </a>
                    <div class="p-5 bg-gray-400 h-full flex flex-col" dir="rtl" style="margin-top: 35%">
                        <a @click="sendToShop(product)">
                            <span class="cursor-pointer text-2xl font-bold text-gray-900 dark:text-black">{{ product.name }}</span>
                        </a>
                        <div class="flex justify-between items-center">
                            <span class="text-3xl font-bold text-gray-900 dark:text-white" v-if="true">{{ store.formatPrice(product.final_price_value) }}</span>
                        </div>
                        <div class="rounded-full bg-gray-900 px-3 flex flex-wrap items-center justify-center mt-5 mb-auto">
                            <div
                                v-for="size in product.sizes"
                                class="mr-3"
                            >
                                {{ size }}
                            </div>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-2xl font-bold text-gray-900 dark:text-white" v-if="true">{{ store.formatPrice(product.final_price_value) }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Show More Button -->
            <div v-if="productColors.next_page_url" class="absolute bottom-12 w-full flex justify-center pb-20">
                <button
                    @click="loadMore"
                    :disabled="loadingMore"
                    class="px-12 py-4 bg-primary text-white rounded-full font-bold shadow-2xl hover:scale-105 transition-all disabled:opacity-50"
                >
                    <span v-if="loadingMore">جاري التحميل...</span>
                    <span v-else>مشاهدة المزيد</span>
                </button>
            </div>
        </section>

        <section id="partner" class="snap-center text-white h-1/2 flex justify-center items-center">
            <div class="bg-custom bg-simplify w-3/4 h-full mx-auto flex flex-col md:flex-col justify-around items-center">
                <h2 class="text-3xl md:text-7xl font-extrabold">شريك يمكنك الاعتماد عليه</h2>
                <p class="text-lg md:text-2xl text-gray-300">
                    مورد ملابس مرخص موثوق من قبل العديد من الشركات والمتاجر في الإمارات العربية المتحدة ولبنان. تتميز وان واي بحضور منافس في السوق من حيث التكلفة والجودة حيث أصبحت واحدة من العلامات التجارية الموردة المعروفة في وقت قياسي لأننا عازمون على توفير المنتجات التي ترضي أصحاب العلامات التجارية والمستهلكين على حدٍ سواء!
                </p>
            </div>
        </section>

        <footer class="snap-start h-1/2 bg-black flex flex-col md:flex-row items-center md:justify-around text-white">
            <div class="flex flex-col ml-12 items-center py-4">
                <img :src="'/custom/logo-icon.png'" class="w-48" alt="one-way">
                <p>
                    © 2023 One Way.
                    جميع الحقوق محفوطة.</p>
            </div>

            <div class="flex flex-col items-center py-6">
                <h2 class="text-3xl font-extrabold">ابق على تواصل</h2>
                <p class="text-gray-300">الإمارات العربية المتحدة</p>
                <p class="text-gray-300">+971 545 516 995</p>
                <p class="text-gray-300">+971 564 533 655</p>
                <br>

                <p class="text-gray-300">لبنان، بيروت</p>
                <p class="text-gray-300">+961 76 658 734</p>

                <p class="text-gray-300">theoneway.fashion@gmail.com</p>
                <!--<p class="text-gray-300">bilalcollections1@gmail.com</p>-->
            </div>

            <div class="flex flex-col space-y-2 md:space-y-8 justify-around items-center md:mr-12">
                <h2 class="text-3xl font-extrabold">الفروع</h2>
                <div class="flex-col text-right">
                    <div>فرع 1 : عجمان الصناعية 2 شارع بيروت</div>
                    <div>فرع 2 : الشارقة سيتي سنتر</div>
                    <div>فرع 3 : بيروت, لبنان</div>
                </div>
            </div>

        </footer>

    </div>
    <transition name="modal">
          <modal v-if="showModal" @close="showModal = false"
            class="w-[85%] aspect-auto max-h-[587px] max-w-[800px] overflow-auto"
            style="position: fixed;top: 50%;left: 50%; transform: translateX(-50%) translateY(-50%);">
              <div class="modal-mask rounded-2xl">
                  <div class="modal-wrapper">
                      <div class="modal-container relative flex flex-col" style="background: white;overflow: auto;padding: 20px;">
                          <span
                              class="
                                absolute top-1 right-1 mt-1 mr-2 cursor-pointer flex justify-center
                                items-center w-[30px] h-[30px] text-xl text-white bg-red-400 p-[5px]
                                rounded-md font-extrabold"
                              @click="closeModal()"
                          >
                              x
                          </span>
                          <div class="modal-header flex justify-center">
                              <h3 class="text-teal-600 text-3xl font-bold"><!--{{ __('Show')}}--> {{this.item.name}}</h3>
                          </div>
                          <div class="modal-body border-b-4 border-state-500 pb-6">
                              <div class="main-container mt-[50px]">
                                  <ProductCarousel
                                  :slides="this.item.colors"
                                  @product-carousel-click="openCarouselModal"
                                  />
                                  <article class="product-info container self-start mt-[40px] lg:mt-[130px]" dir="rtl">
                                        <h3 class="product-info__producer-name font-bold">{{ this.item.category.name }}</h3>
                                        <h1 class="product-info__name">{{ this.item.name }}</h1>
                                        <p class="product-info__description text-sm">
                                        {{ this.item.details }}
                                        </p>
                                        <div class="rounded-full bg-gray-900 px-3">{{ this.item.available_sizes_without_stock }}</div>
                                        <div class="rounded-full bg-gray-900 px-3 flex flex-wrap items-center justify-center mt-5 mb-auto">
                                            <div
                                                v-for="size in this.item.sizes"
                                                class="mr-3 text-white"
                                            >
                                                {{ size }}
                                            </div>
                                        </div>
                                        <div class="flex flex-wrap items-center justify-center mt-5 mb-auto">
                                            <ColorTag
                                                v-for = "color in this.item.colors"
                                                :hexcolor = "color.color_code"
                                                :label = "color.color_name"
                                                className = "my-1"
                                            />
                                        </div>
                                  </article>
                            </div>
                          </div>
                          <div v-if="false" class="modal-footer mt-12 border">
                              <button class="modal-default-button px-2 py-1 hover:bg-rose-600 bg-rose-400 text-white rounded-lg" @click="closeModal()">
                                  {{ __('Cancel')}}
                              </button>
                          </div>
                      </div>
                    </div>
                </div>

          </modal>
      </transition>

</template>


<script>

import 'vue3-carousel/dist/carousel.css';
import { Carousel, Slide, Pagination, Navigation } from 'vue3-carousel';
import {debounce} from "lodash/function";
import {throttle} from "lodash";
import Arrow from "../Shared/Icons/Arrow.vue";
import ProductCarousel from "./product/ProductCarousel.vue";
import ArrowDown from "../Shared/Icons/ArrowDown.vue";
import ColorTag from '@/Components/ColorTag.vue';
import { useStore } from '@/stores/store';

const components = { Carousel, Slide, Pagination, Navigation, Arrow, ArrowDown, ProductCarousel, ColorTag }

export default {
    props:{
        categories: Array,
        products: Object,
        filters: Object,
        category: Object
    },
    setup() {
      const store = useStore()
      return { store }
    },
    data(){
      return {
        params: {
            search: this.filters.search,
            field: this.filters.field,
            direction: this.direction,
            category: this.category?.id
        },
        navToggle: false,
        productColors: this.products,
        item: null,
        showModal: false,
        carouselModalOpen: false,
        imageClickedIndex: 0,
        loadingMore: false,
      }
    },
    components: components,
    watch: {
        params: {
            handler: throttle(function () {
                let params = this.params;
                Object.keys(params).forEach(key => {
                    if (params[key] == ''){
                        delete params[key]
                    }
                }, 150);
                axios.get(this.route('shop/ar', this.params)).then(response => {
                    this.productColors = {
                        ...response.data,
                        data: [...response.data.data]
                    }
                });
                // this.$inertia.get(this.route('productColors.index'), this.params, { replace: true, preserveState: true});
            }),
            deep: true
        }


    },
    mounted() {
        const btn = document.getElementById('menu-btn')
        const nav = document.getElementById('menu')

        btn.addEventListener('click', () => {
            btn.classList.toggle('open')
            nav.classList.toggle('flex')
            nav.classList.toggle('hidden')
        })
    },
    methods:{
        toggleNavbar(){
            this.navToggle = !this.navToggle
        },
        closeModal(){
            this.resultLoading = false;
            this.showModal = false;
            this.item = null;
        },
        async sendToShop(item) {
            this.showModal = true;
            this.item = item;
        },
        openCarouselModal(visibleIndex) {
            if (window.window.screen.width < 1024) return;

            this.imageClickedIndex = visibleIndex;
            this.carouselModalOpen = true;
        },
        async loadMore() {
            if (!this.productColors.next_page_url || this.loadingMore) return;

            this.loadingMore = true;
            try {
                let params = this.params;
                Object.keys(params).forEach(key => {
                    if (params[key] == ''){
                        delete params[key]
                    }
                });
                const response = await axios.get(this.productColors.next_page_url, { params: params });
                this.productColors = {
                    ...response.data,
                    data: [...this.productColors.data, ...response.data.data]
                };
            } catch (error) {
                console.error('Error loading more products:', error);
            } finally {
                this.loadingMore = false;
            }
        }
    }
}
</script>
<style scoped>

.rounded-custom{
    border-radius: 3rem;
}

.bg-custom{
    background-color: rgba(35,32,42,var(--tw-bg-opacity));
}


#header{
    background-image: url("/assets/shop-1.jpeg");
    background-repeat: no-repeat;
    background-size: cover;
    background-position: center;
}

.bg-simplify{
    background-image: url("/assets/bg-simplify-section.svg");
    background-repeat: no-repeat;
    background-size: auto;
}

#partner, #categories, #products, #categories-mobile{
    background-color: #201824;
}

#mission-area, #vision-area, #objective-area{
    background-color: #251d2a;
}

.carousel__slide > .carousel__item {
    transform: scale(1);
    opacity: 0.5;
    transition: 0.5s;
}
.carousel__slide--visible > .carousel__item {
    opacity: 1;
    transform: rotateY(0);
}
.carousel__slide--next > .carousel__item {
    transform: scale(0.9) translate(-10px);
}
.carousel__slide--prev > .carousel__item {
    transform: scale(0.9) translate(10px);
}
.carousel__slide--active > .carousel__item {
    transform: scale(1.1);
}
.product-info {
  display: flex;
  flex-direction: column;
}
.product-info__producer-name {
  text-transform: uppercase;
  font-size: var(--fs-300);
  font-weight: 700;
  color: hsl(26, 100%, 55%);
  margin-bottom: 15px;
}
.product-info__name {
  font-size: var(--fs-lg);
  font-weight: 700;
}
.product-info__description {
  color: hsl(219, 9%, 45%);
  line-height: 1.5rem;
}
.product-info__price-container {
  display: flex;
  max-width: 100%;
  justify-content: space-between;
  align-items: center;
}
.product-info__current-price-container {
  display: flex;
  align-items: center;
  gap: 1rem;
}
.product-info__current-price {
  font-size: var(--fs-600);
  font-weight: 700;
}
.product-info__discount {
  color: var(--color-primary-orange);
  padding: 0.3rem 0.6rem;
  border-radius: var(--border-radius-size);
  font-weight: 700;
  background-color: var(--color-primary-pale-orange);
}
.product-info__old-price {
  color: var(--color-grayish-blue);
  font-weight: 700;
  text-decoration: line-through;
}
.product-info__buttons-container {
  display: flex;
  flex-direction: column;
  gap: 2rem;
}
@media (min-width: 1024px) {
  .product-info {
    max-width: 40%;
  }
  .product-info__price-container {
    flex-direction: column;
    align-items: flex-start;
  }
  .product-info__buttons-container {
    flex-direction: row;
    gap: 1.3rem;
  }
}
@media (min-width: 1024px) {
  .main-container {
    display: flex;
    margin-top: 40px;
    align-items: center;
    justify-content: center;
    gap: 6rem;
  }
}

.contact-icon
{
    position: fixed;
    bottom: 20px;
}
</style>
