<template>
  <section class="brm-breadcrump w-full h-20 text-base-content flex justify-between items-center border-b mb-8">
    <div>
      <div class="text-sm breadcrumbs">
        <ul>

            <li>
                <InertiaLink :href="route('manage.dashboard')" class="flex items-center space-x-1 font-bold text-gray-400">
                  <vue-feather type="home" size="18" stroke-width="2"></vue-feather>
                  <span class="block mt-1">{{ $t('dashboard') }}</span>
                </InertiaLink>
              </li>
            <li v-if="parentItem">
                <InertiaLink :href="parentItemHref" class="flex items-center space-x-1 font-bold text-gray-400">
                  <vue-feather :type="parentItemIcon" size="18" stroke-width="2"></vue-feather>
                  <span class="block mt-1">{{ parentItem }}</span>
                </InertiaLink>
              </li>
            <li class="flex items-center space-x-1 font-bold text-gray-700" v-if="activeItem">
                <vue-feather v-if="activeItemIcon" :type="activeItemIcon" size="18" stroke-width="2"></vue-feather>
                <span class="block mt-1">{{ activeItem }}</span>
              </li>
        </ul>
      </div>
    </div>
    <div v-if="actionBtn">
        <InertiaLink :href="actionBtnHref" class="wlabel-full flex flex-col btn btn-sm btn-primary capitalize text-sm">{{ actionBtn }}</InertiaLink>
        <label
            v-if="actionBulk"
            class="w-full flex flex-col items-center px-4 py-2 my-2 bg-white rounded-md shadow-md tracking-wide uppercase border border-blue cursor-pointer hover:bg-purple-900 hover:text-white text-purple-900 ease-linear transition-all duration-150">
            <i class="fas fa-cloud-upload-alt fa-3x"></i>
            <span class="mt-2 text-base leading-normal">{{ $t('bulk_import_posts')}}</span>
            <form @submit.prevent="handleSubmit">
                <input type='file' class="hidden" @input="bulkImport($event.target.files[0])" />
                <label class="label" v-if="form.error">
                    <span class="label-text-alt font-bold text-error">{{ form.error }}</span>
                </label>
            </form>

        </label>
    </div>
  </section>
</template>

<script>
export default {
    name: 'Breadcrumb',
    props: {
        parentItem: {
            type: String,
            default: null,
            require: false,
        },
        parentItemHref: {
            type: String,
            default: null,
            require: false,
        },
        parentItemIcon: {
            type: String,
            default: null,
            require: false,
        },
        activeItem: {
            type: String,
            default: null,
            require: true,
        },
        activeItemIcon: {
            type: String,
            default: undefined,
            require: false,
        },
        actionBtn: {
            type: String,
            default: null,
            require: false,
        },
        actionBulk: {
            type: Boolean,
            default: false,
            require: false,
        },
        actionBtnHref: {
            type: String,
            default: null,
            require: false,
        },
        type: String,
    },
    data() {
        return {
            form: this.$inertia.form({
                file: '',
                type: this.type
            })
        }
    },
    methods:{
        bulkImport(file){

            this.form.file = file
            this.form.type = this.type
            this.handleSubmit()
        },
        handleSubmit() {
            this.form.post(route('manage.posts.bulk.import'), {
                errorBag: 'BulkImport',
                onSuccess: () => undefined,
                onError: (e) => undefined
            }, {
                Accept: 'application/json'
            })
        },
    }
}
</script>
