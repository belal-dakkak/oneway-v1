<script setup>
import { Head, Link, useForm } from '@inertiajs/inertia-vue3';
import { useStore } from '@/stores/store';
import Header from '@/Components/Website/Header.vue';
import Footer from '@/Components/Website/Footer.vue';

const store = useStore();

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    terms: false,
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
  <div class="min-h-screen bg-background flex flex-col" :dir="store.isRTL ? 'rtl' : 'ltr'">
    <Header :title="store.t('register')" />

    <main class="flex-grow flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8 bg-muted/30">
      <div class="max-w-md w-full space-y-8 bg-card p-8 rounded-xl shadow-lg border border-border">
        <Head :title="store.t('register')" />

        <div class="text-center">
          <h2 class="text-3xl font-extrabold text-foreground">
            {{ store.t('register') }}
          </h2>
          <p class="mt-2 text-sm text-muted-foreground">
            {{ store.t('alreadyHaveAccount') }}
            <Link :href="route('login')" class="font-medium text-primary hover:text-primary/80 transition-colors">
              {{ store.t('login') }}
            </Link>
          </p>
        </div>

        <!-- Validation Errors -->
        <div v-if="Object.keys(form.errors).length > 0" class="mb-4 bg-destructive/10 border border-destructive/20 p-4 rounded-md">
          <div class="text-sm text-destructive font-medium">
            <ul class="list-disc list-inside">
              <li v-for="(error, key) in form.errors" :key="key">{{ error }}</li>
            </ul>
          </div>
        </div>

        <form class="mt-8 space-y-6" @submit.prevent="submit">
          <div class="space-y-4">
            <div>
              <label for="name" class="block text-sm font-medium text-foreground mb-1">
                {{ store.t('name') }}
              </label>
              <input
                id="name"
                v-model="form.name"
                type="text"
                required
                autofocus
                autocomplete="name"
                class="appearance-none block w-full px-3 py-3 border border-input rounded-md shadow-sm placeholder-muted-foreground focus:outline-none focus:ring-primary focus:border-primary sm:text-sm bg-background transition-all"
                :placeholder="store.t('name')"
              />
            </div>

            <div>
              <label for="email" class="block text-sm font-medium text-foreground mb-1">
                {{ store.t('email') }}
              </label>
              <input
                id="email"
                v-model="form.email"
                type="email"
                required
                class="appearance-none block w-full px-3 py-3 border border-input rounded-md shadow-sm placeholder-muted-foreground focus:outline-none focus:ring-primary focus:border-primary sm:text-sm bg-background transition-all"
                :placeholder="store.t('email')"
              />
            </div>

            <div>
              <label for="password" class="block text-sm font-medium text-foreground mb-1">
                {{ store.t('password') }}
              </label>
              <input
                id="password"
                v-model="form.password"
                type="password"
                required
                autocomplete="new-password"
                class="appearance-none block w-full px-3 py-3 border border-input rounded-md shadow-sm placeholder-muted-foreground focus:outline-none focus:ring-primary focus:border-primary sm:text-sm bg-background transition-all"
                :placeholder="store.t('password')"
              />
            </div>

            <div>
              <label for="password_confirmation" class="block text-sm font-medium text-foreground mb-1">
                {{ store.t('confirmPassword') }}
              </label>
              <input
                id="password_confirmation"
                v-model="form.password_confirmation"
                type="password"
                required
                autocomplete="new-password"
                class="appearance-none block w-full px-3 py-3 border border-input rounded-md shadow-sm placeholder-muted-foreground focus:outline-none focus:ring-primary focus:border-primary sm:text-sm bg-background transition-all"
                :placeholder="store.t('confirmPassword')"
              />
            </div>
          </div>

          <div v-if="$page.props.jetstream.hasTermsAndPrivacyPolicyFeature" class="flex items-center">
            <input
              id="terms"
              v-model="form.terms"
              type="checkbox"
              required
              class="h-4 w-4 text-primary focus:ring-primary border-input rounded bg-background"
            />
            <label for="terms" class="ltr:ml-2 rtl:mr-2 block text-sm text-muted-foreground">
              I agree to the <a target="_blank" :href="route('terms.show')" class="underline">Terms of Service</a> and <a target="_blank" :href="route('policy.show')" class="underline">Privacy Policy</a>
            </label>
          </div>

          <div>
            <button
              type="submit"
              :disabled="form.processing"
              class="group relative w-full flex justify-center py-3 px-4 border border-transparent text-sm font-bold rounded-md text-primary-foreground bg-primary hover:bg-primary/90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary disabled:opacity-50 disabled:cursor-not-allowed transition-all transform hover:scale-[1.02] active:scale-[0.98]"
            >
              <span v-if="form.processing" class="absolute left-0 inset-y-0 flex items-center pl-3">
                <svg class="animate-spin h-5 w-5 text-primary-foreground" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
              </span>
              {{ store.t('register') }}
            </button>
          </div>
        </form>
      </div>
    </main>

    <Footer />
  </div>
</template>
